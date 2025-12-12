<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\Utilities\VersionManager;
use Spenpo\TigerGrades\Utilities\LanguageManager;

/**
 * Handles the [tigr_version] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
class VersionShortcode {
    private $plugin_domain;
    
    /**
     * Constructor initializes and registers the shortcode.
     */
    public function __construct() {
        $languageConstants = LanguageManager::getInstance();
        $this->plugin_domain = $languageConstants->getPluginDomain();
        // Register the shortcode
        add_shortcode('tigr_version', [$this, 'render']);
    }

    /**
     * Renders the version shortcode output.
     *
     * @param array $atts Shortcode attributes
     * @return string The rendered shortcode content
     */
    public function render($atts) {
        // Get the database version using the VersionManager
        $db_version = VersionManager::getCurrentVersion();

        // Get the translated string from Polylang
        $version_text = function_exists('__') ? __('Version', $this->plugin_domain) : 'Version';
        
        // Return the formatted version
        return '<div class="tiger-version">APP ' . esc_html($version_text) . ': ' . esc_html($db_version) . '</div>';
    }
}

// Initialize the shortcode
new VersionShortcode(); 