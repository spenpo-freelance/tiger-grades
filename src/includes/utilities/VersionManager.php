<?php
/**
 * Manages version information for the Tiger Grades plugin.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
namespace Spenpo\TigerGrades\Utilities;

use Spenpo\TigerGrades\Utilities\StringTranslationsManager;
use Spenpo\TigerGrades\Utilities\LanguageManager;

class VersionManager {
    /**
     * The option name used to store the database version
     */
    const DB_VERSION_OPTION = 'tigr_db_version';
    private $translation_strings;
    private $translator;
    
    /**
     * Constructor that automatically checks and syncs the version
     */
    public function __construct() {
        // Add an action to check and sync the version on init
        add_action('init', [$this, 'checkAndSyncVersion']);
        $languageConstants = LanguageManager::getInstance();
        $this->translation_strings = $languageConstants->translation_strings;
        $this->translator = new StringTranslationsManager();
    }
    
    /**
     * Check if the database version needs to be updated and sync if needed
     */
    public function checkAndSyncVersion() {
        if (self::needsUpdate()) {
            // Always sync the version
            self::syncWithPluginVersion();
            
            // Register string translations
            $this->translator->add_translations($this->translation_strings);
        }
    }
    
    /**
     * Get the current database version from WordPress options
     * 
     * @return string The current database version
     */
    public static function getCurrentVersion(): string {
        return get_option(self::DB_VERSION_OPTION, '0');
    }
    
    /**
     * Get the plugin version from the plugin file
     * 
     * @return string The plugin version
     */
    public static function getPluginVersion(): string {
        // This is a more reliable way to get the plugin version
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $plugin_data = get_plugin_data(plugin_dir_path(dirname(dirname(__FILE__))) . 'tiger-grades.php');
        return $plugin_data['Version'] ?? '0.0.0';
    }
    
    /**
     * Update the database version
     * 
     * @param string $version The new version to set
     * @return bool True if the update was successful, false otherwise
     */
    public static function updateVersion(string $version): bool {
        return update_option(self::DB_VERSION_OPTION, $version);
    }
    
    /**
     * Increment the database version to match the plugin version
     * 
     * @return bool True if the update was successful, false otherwise
     */
    public static function syncWithPluginVersion(): bool {
        return self::updateVersion(self::getPluginVersion());
    }
    
    /**
     * Check if the database needs to be updated
     * 
     * @return bool True if the database needs to be updated, false otherwise
     */
    public static function needsUpdate(): bool {
        $current_version = self::getCurrentVersion();
        $plugin_version = self::getPluginVersion();
        
        return version_compare($current_version, $plugin_version, '<');
    }
}

// Initialize the VersionManager to trigger the automatic version check
new VersionManager(); 
