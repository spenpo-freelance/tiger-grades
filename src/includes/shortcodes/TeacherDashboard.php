<?php
namespace Spenpo\TigerGrades\Shortcodes;

use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
use Spenpo\TigerGrades\Components\TeacherComponents;
/**
 * Handles the [tigr_report_card] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class TeacherDashboardShortcode {
    private $repository;

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->repository = new TigerClassRepository();
        // Define default attributes for the shortcode
        add_shortcode('tigr_teacher_dashboard', function($atts) {
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
        $root = DOMHelper::createElement($dom, 'div', 'teacher-dashboard-container');
        $dom->appendChild($root);

        if ($user_id) {
            $teacherComponents = new TeacherComponents();
            $root->appendChild($teacherComponents->createClassesTable($dom, $user_id));

            return $dom->saveHTML();
        } else {
            $not_logged_in_message = DOMHelper::createElement($dom, 'div', 'not-logged-in-message');
            $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-logged-in-message-text', null, 'Please '));
            $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'a', 'not-logged-in-message-text', null, 'log in', ['href' => '/my-account']));
            $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-logged-in-message-text', null, ' to view your child\'s grades.'));
            $root->appendChild($not_logged_in_message);
            return $dom->saveHTML();
        }
    }
}

// Initialize shortcode
new TeacherDashboardShortcode(); 