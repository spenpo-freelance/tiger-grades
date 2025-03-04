<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\Utilities\VersionManager;

/**
 * Handles the [tigr_version] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
class VersionShortcode {
    /**
     * Constructor initializes and registers the shortcode.
     */
    public function __construct() {
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
        
        // Return the formatted version
        return '<div class="tiger-version">APP Version: ' . esc_html($db_version) . '</div>';
    }
}

// Initialize the shortcode
new VersionShortcode(); 