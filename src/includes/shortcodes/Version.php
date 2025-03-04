<?php
namespace Spenpo\TigerGrades\Shortcodes;

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
        // Get the database version from WordPress options
        $db_version = get_option('tigr_db_version', 'Not available');
        
        // Return the formatted version
        return '<div class="tiger-version">APP Version: ' . esc_html($db_version) . '</div>';
    }
}

// Initialize the shortcode
new VersionShortcode(); 