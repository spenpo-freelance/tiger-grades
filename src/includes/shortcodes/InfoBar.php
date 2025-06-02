<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Parsedown;

/**
 * Handles the [tigr_info_bar] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
class InfoBarShortcode {
    private $parsedown;
    /**
     * Constructor initializes and registers the shortcode.
     */
    public function __construct() {
        // Register the shortcode
        add_shortcode('tigr_info_bar', [$this, 'render']);
        $this->parsedown = new Parsedown();
    }

    /**
     * Renders Markdown content to inform the user about the feature they are using.
     *
     * @param array $atts Shortcode attributes
     * @param string $content The content between shortcode tags
     * @return string The rendered shortcode content
     */
    public function render($atts, $content = null) {
        wp_enqueue_style(
            'tigr-info-bar',
            plugins_url('tiger-grades/css/info-bar.css', dirname(__FILE__, 3)),
            [],
            '0.0.1'
        );
        add_action('wp_footer', [$this, 'output_inline_scripts']);
        // Parse shortcode attributes with defaults
        $atts = shortcode_atts([
            'type' => 'info',        // info, warning, success, error
            'icon' => 'auto',        // auto, none, or specific icon class
            'title' => '',           // Optional title for the info bar
            'dismissible' => 'false' // Whether the bar can be dismissed
        ], $atts, 'tigr_info_bar');

        // If no content, return empty
        if (empty($content)) {
            return '';
        }

        // Process the content (allow shortcodes and basic formatting)
        $content = do_shortcode($content);
        $content = $this->parsedown->text($content);

        // Determine icon based on type
        $icon_class = '';
        if ($atts['icon'] === 'auto') {
            switch ($atts['type']) {
                case 'warning':
                    $icon_class = 'fas fa-exclamation-triangle';
                    break;
                case 'success':
                    $icon_class = 'fas fa-check-circle';
                    break;
                case 'error':
                    $icon_class = 'fas fa-times-circle';
                    break;
                case 'info':
                default:
                    $icon_class = 'fas fa-info-circle';
                    break;
            }
        } elseif ($atts['icon'] !== 'none') {
            $icon_class = esc_attr($atts['icon']);
        }

        // Build CSS classes
        $css_classes = [
            'tigr-info-bar',
            'tigr-info-bar--' . esc_attr($atts['type'])
        ];

        if ($atts['dismissible'] === 'true') {
            $css_classes[] = 'tigr-info-bar--dismissible';
        }

        $class_string = implode(' ', $css_classes);

        // Build the HTML output
        $output = '<div class="' . esc_attr($class_string) . '">';
        
        // Add icon if present
        if (!empty($icon_class)) {
            $output .= '<div class="tigr-info-bar__icon">';
            $output .= '<i class="' . esc_attr($icon_class) . '" aria-hidden="true"></i>';
            $output .= '</div>';
        }

        $output .= '<div class="tigr-info-bar__content">';
        
        // Add title if present
        if (!empty($atts['title'])) {
            $output .= '<h4 class="tigr-info-bar__title">' . esc_html($atts['title']) . '</h4>';
        }
        
        $output .= '<div class="tigr-info-bar__text">' . $content . '</div>';
        $output .= '</div>';

        // Add dismiss button if dismissible
        if ($atts['dismissible'] === 'true') {
            $output .= '<button class="tigr-info-bar__dismiss" type="button" aria-label="Dismiss">';
            $output .= '<i class="fas fa-times" aria-hidden="true"></i>';
            $output .= '</button>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Output inline JavaScript for dismissible functionality
     */
    public function output_inline_scripts() {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".tigr-info-bar__dismiss").forEach(function(button) {
                button.addEventListener("click", function() {
                    const infoBar = this.closest(".tigr-info-bar");
                    infoBar.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                    infoBar.style.opacity = "0";
                    infoBar.style.transform = "translateX(100%)";
                    setTimeout(function() {
                        infoBar.remove();
                    }, 300);
                });
            });
        });
        </script>';
    }
}

// Initialize the shortcode
new InfoBarShortcode(); 