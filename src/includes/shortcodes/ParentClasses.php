<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TeachersAPI;
use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
/**
 * Handles the [tigr_report_card] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class ParentClassesShortcode {
    /** @var TeachersAPI Instance of the Teachers API */
    private $api;
    private $repository;

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->repository = new TigerClassRepository();
        // Define default attributes for the shortcode
        add_shortcode('tigr_parent_classes', function($atts) {
            return $this->render($atts);
        });
    }

    /**
     * Renders the report card content as HTML.
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the report card
     */
    public function render($atts) {
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $is_teacher = in_array('teacher', (array) $user->roles);

        $dom = new DOMDocument('1.0', 'utf-8');
        // Create a root container for all sections
        $root = DOMHelper::createElement($dom, 'div', 'parent-classes-container');
        $dom->appendChild($root);

        if ($user_id) {
            $classes = null;
            $teacher_name = null;
            if ($is_teacher) {
                $classes = $this->repository->getTeacherClasses($user_id);
                $teacher_name = $user->display_name;
            } else {
                $classes = $this->repository->getEnrolledClasses($user_id);
            }
            $class_flex_container = DOMHelper::createElement($dom, 'div', 'class-flex-container');
            if (count($classes) > 0) {
                foreach ($classes as $class) {
                    if ($teacher_name === null) {
                        $teacher_name = $class->teacher_name;
                    }
                    $class_card = DOMHelper::createElement($dom, 'div', 'class-card');
                    $class_link = DOMHelper::createElement($dom, 'a', 'class-link', null, $teacher_name . '\'s ' . $class->class_title . ' class', ['href' => $class->id]);
                    $class_card->appendChild($class_link);
                    $class_flex_container->appendChild($class_card);
                }
            } else {
                $no_classes_message = DOMHelper::createElement($dom, 'div', 'no-classes-message');
                $no_classes_message->appendChild(DOMHelper::createElement($dom, 'span', 'no-classes-message-text', null, 'You are not enrolled in any classes.'));
                $root->appendChild($no_classes_message);
            }
            $root->appendChild($class_flex_container);

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

    // New protected method for better testability
    protected function getAPI() {
        return TeachersAPI::getInstance();
    }
}

// Initialize shortcode
new ParentClassesShortcode(); 