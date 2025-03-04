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
    private $text_translations;
    /**
     * Constructor initializes and registers the shortcode.
     */
    public function __construct() {
        // Register the shortcode
        add_shortcode('tigr_version', [$this, 'render']);
        $this->text_translations = [
            'en' => [
                'version' => 'Version',
            ],
            'zh' => [
                'version' => '版本',
            ],
        ];
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

        $lang = pll_current_language();
        
        // Return the formatted version
        return '<div class="tiger-version">APP ' . $this->text_translations[$lang]['version'] . ': ' . esc_html($db_version) . '</div>';
    }
}

// Initialize the shortcode
new VersionShortcode(); 