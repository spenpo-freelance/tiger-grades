<?php
/**
 * Custom String Translation Manager
 * Works alongside Polylang for programmatic string translations
 */

namespace Spenpo\TigerGrades\Utilities;

use Spenpo\TigerGrades\Utilities\LanguageManager;

class StringTranslationsManager {
    
    private $text_domain;
    private $translations = [];
    private $languages_dir;
    private $default_language;
    
    public function __construct($text_domain = null) {
        $languageConstants = LanguageManager::getInstance();
        $this->text_domain = $text_domain ?? $languageConstants->getPluginDomain();
        $this->default_language = $languageConstants->getDefaultLanguage();
        $this->languages_dir = WP_CONTENT_DIR . '/languages/plugins/';
        
        // Ensure languages directory exists
        wp_mkdir_p($this->languages_dir);
        
        // Hook into WordPress init to load textdomain
        add_action('init', [$this, 'load_textdomain']);
        
        // Hook into language switch (Polylang)
        add_action('pll_language_defined', [$this, 'load_textdomain']);
    }
    
    /**
     * Add translations and generate .mo files
     */
    public function add_translations($translations) {
        $this->translations = array_merge($this->translations, $translations);
        $this->generate_mo_files();
        $this->load_textdomain();
        return $this;
    }
    
    /**
     * Generate .mo files for all languages
     */
    private function generate_mo_files() {
        $languages = $this->get_available_languages();
        
        foreach ($languages as $lang_code) {
            $this->generate_mo_file($lang_code);
        }
    }
    
    /**
     * Generate .mo file for a specific language
     */
    private function generate_mo_file($lang_code) {
        $translations_for_lang = [];
        
        // Collect all translations for this language
        foreach ($this->translations as $translation) {
            if (isset($translation['translations'][$lang_code])) {
                $key = $translation['key'];
                $text = $translation['translations'][$lang_code];
                $context = $translation['context'] ?? '';
                
                // Use the default language's translation as the key for __()
                $original = $translation['translations'][$this->get_default_language()] ?? $key;
                
                if ($context) {
                    // For contextual translations, use the context format
                    $translations_for_lang[$context . "\x04" . $original] = $text;
                } else {
                    $translations_for_lang[$original] = $text;
                }
            }
        }
        
        if (!empty($translations_for_lang)) {
            $mo_file = $this->languages_dir . $this->text_domain . '-' . $lang_code . '.mo';
            $this->write_mo_file($mo_file, $translations_for_lang);
        }
    }
    
    /**
     * Write .mo file (binary format)
     * This is a simplified .mo file writer
     */
    private function write_mo_file($filename, $translations) {
        try {
            $keys = array_keys($translations);
            $values = array_values($translations);
            $count = count($translations);
            
            // .mo file structure (simplified)
            $key_offsets = [];
            $value_offsets = [];
            $key_lengths = [];
            $value_lengths = [];
            
            // Calculate offsets and lengths
            $keys_start = 28 + $count * 16; // Header + offset tables
            $values_start = $keys_start;
            
            foreach ($keys as $key) {
                $values_start += strlen($key) + 1; // +1 for null terminator
            }
            
            $key_offset = $keys_start;
            $value_offset = $values_start;
            
            for ($i = 0; $i < $count; $i++) {
                $key_lengths[] = strlen($keys[$i]);
                $key_offsets[] = $key_offset;
                $key_offset += strlen($keys[$i]) + 1;
                
                $value_lengths[] = strlen($values[$i]);
                $value_offsets[] = $value_offset;
                $value_offset += strlen($values[$i]) + 1;
            }
            
            // Write .mo file
            $mo_data = '';
            
            // Magic number
            $mo_data .= pack('V', 0x950412de);
            
            // Version
            $mo_data .= pack('V', 0);
            
            // Number of strings
            $mo_data .= pack('V', $count);
            
            // Offset of key table
            $mo_data .= pack('V', 28);
            
            // Offset of value table  
            $mo_data .= pack('V', 28 + $count * 8);
            
            // Hash table size (0 = no hash table)
            $mo_data .= pack('V', 0);
            
            // Offset of hash table
            $mo_data .= pack('V', 28 + $count * 16);
            
            // Key table
            for ($i = 0; $i < $count; $i++) {
                $mo_data .= pack('V', $key_lengths[$i]);
                $mo_data .= pack('V', $key_offsets[$i]);
            }
            
            // Value table
            for ($i = 0; $i < $count; $i++) {
                $mo_data .= pack('V', $value_lengths[$i]);
                $mo_data .= pack('V', $value_offsets[$i]);
            }
            
            // Keys
            foreach ($keys as $key) {
                $mo_data .= $key . "\0";
            }
            
            // Values
            foreach ($values as $value) {
                $mo_data .= $value . "\0";
            }
            
            $result = file_put_contents($filename, $mo_data);
            if ($result === false) {
                error_log('Failed to write MO file: ' . $filename);
                error_log('Directory writable: ' . (is_writable(dirname($filename)) ? 'yes' : 'no'));
            }
        } catch (\Exception $e) {
            error_log('Error writing MO file: ' . $e->getMessage());
        }
    }
    
    /**
     * Load textdomain for current language
     */
    public function load_textdomain() {
        $current_lang = $this->get_current_language();
        $mo_file = $this->languages_dir . $this->text_domain . '-' . $current_lang . '.mo';
        
        if (file_exists($mo_file)) {
            load_textdomain($this->text_domain, $mo_file);
        }
    }
    
    /**
     * Get current language (uses Polylang if available)
     */
    private function get_current_language() {
        // Try Polylang first
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language();
            if ($lang) {
                return $lang;
            }
        }
        
        // Fallback to WordPress locale
        $locale = get_locale();
        return substr($locale, 0, 2);
    }
    
    /**
     * Get available languages
     */
    private function get_available_languages() {
        if (function_exists('pll_languages_list')) {
            return pll_languages_list();
        }
        
        return ['en', 'fr', 'es', 'de'];
    }
    
    /**
     * Translation helper functions that use WordPress core i18n
     */
    public function translate($text, $context = null) {
        if ($context) {
            return _x($text, $context, $this->text_domain);
        }
        return __($text, $this->text_domain);
    }
    
    public function translate_e($text, $context = null) {
        if ($context) {
            return _ex($text, $context, $this->text_domain);
        }
        return _e($text, $this->text_domain);
    }
    
    /**
     * Clean up - remove generated .mo files
     */
    public function cleanup() {
        $languages = $this->get_available_languages();
        foreach ($languages as $lang_code) {
            $mo_file = $this->languages_dir . $this->text_domain . '-' . $lang_code . '.mo';
            if (file_exists($mo_file)) {
                unlink($mo_file);
            }
        }
    }
    
    /**
     * Get the default language code
     * 
     * @return string Default language code
     */
    private function get_default_language() {
        return $this->default_language;
    }
}

/**
 * Usage Examples:
 * 
 * // Option 1: WordPress Native (generates .mo files)
 * $translator = new WPNativeTranslationManager('my-plugin');
 * 
 * $translations = [
 *     [
 *         'key' => 'welcome_msg',
 *         'default' => 'Welcome to our site!',
 *         'translations' => [
 *             'en' => 'Welcome to our site!',
 *             'fr' => 'Bienvenue sur notre site!',
 *             'es' => '¡Bienvenido a nuestro sitio!'
 *         ]
 *     ]
 * ];
 * 
 * $translator->add_translations($translations);
 * 
 * // Use WordPress core functions
 * echo __('Welcome to our site!', 'my-plugin');
 * echo $translator->translate('Welcome to our site!');
 */