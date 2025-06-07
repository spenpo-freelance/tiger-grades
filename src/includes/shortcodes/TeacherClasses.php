<?php
namespace Spenpo\TigerGrades\Shortcodes;

use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Components\TeacherComponents;
use Spenpo\TigerGrades\Components\GeneralComponents;
/**
 * Handles the [tigr_report_card] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class TeacherClassesShortcode {
    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        // Define default attributes for the shortcode
        add_shortcode('tigr_teacher_classes', function($atts) {
            // Merge user attributes with defaults
            $attributes = shortcode_atts([
                'type' => 'all', // default value
                'class_id' => 'english',
                'semester' => '1',
            ], $atts);
            
            return $this->render($attributes);
        });
    }

    /**
     * Renders the report card content as HTML.
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the report card
     */
    public function render($atts) {
        $user_id = get_current_user_id();

        $dom = new DOMDocument('1.0', 'utf-8');
        // Create a root container for all sections
        $root = DOMHelper::createElement($dom, 'div', 'teacher-classes-container');
        $dom->appendChild($root);

        if (!$user_id) {
            $generalComponents = new GeneralComponents();
            return $generalComponents->createUnauthenticatedMessage($dom, $root);
        }

        $teacherComponents = new TeacherComponents();
        $root->appendChild($teacherComponents->createClassesTable($dom, $user_id));

        return $dom->saveHTML();
    }
}

// Initialize shortcode
new TeacherClassesShortcode(); 