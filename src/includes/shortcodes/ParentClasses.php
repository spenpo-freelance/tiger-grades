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

            // filter classes into current and past classes
            $current_classes = array_filter($classes, function($class) {
                return $class->end_date >= date('Y-m-d');
            });
            $past_classes = array_filter($classes, function($class) {
                return $class->end_date < date('Y-m-d');
            });

            $root->appendChild(DOMHelper::createElement($dom, 'h2', 'Classes', null, 'Your child is in the following classes'));
            $current_classes_flex_container = DOMHelper::createElement($dom, 'div', 'class-flex-container');
            if (count($classes) > 0) {
                if (count($past_classes) > 0) {
                    $past_classes_flex_container = DOMHelper::createElement($dom, 'div', 'past-classes flexbox column');
                    $expand_past_classes_input = DOMHelper::createElement($dom, 'input', 'expand-past-classes-input', null, null, [
                        'type' => 'checkbox',
                        'id' => 'expand-past-classes',
                        'class' => 'expand-past-classes-input'
                    ]);
                    $past_classes_flex_container->appendChild($expand_past_classes_input);
                    
                    $expand_past_classes_label = DOMHelper::createElement($dom, 'label', 'expand-past-classes-label', null, '', [
                        'for' => 'expand-past-classes'
                    ]);
                    $past_classes_flex_container->appendChild($expand_past_classes_label);
                    
                    $past_classes_container = DOMHelper::createElement($dom, 'div', 'class-flex-container past-classes-container');
                    $past_classes_flex_container->appendChild($past_classes_container);
                    foreach ($past_classes as $class) {
                        $past_class_card = $this->createClassCard($dom, $class, $teacher_name, $is_teacher);
                        $past_classes_container->appendChild($past_class_card);
                    }
                }

                foreach ($current_classes as $class) {
                    $class_card = $this->createClassCard($dom, $class, $teacher_name, $is_teacher);
                    $current_classes_flex_container->appendChild($class_card);
                }
            } else {
                $no_classes_message = DOMHelper::createElement($dom, 'div', 'no-classes-message');
                $no_classes_message->appendChild(DOMHelper::createElement($dom, 'span', 'no-classes-message-text', null, 'No classes found'));
                $root->appendChild($no_classes_message);
            }
            $root->appendChild($past_classes_flex_container);
            $root->appendChild($current_classes_flex_container);

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

    private function createClassCard($dom, $class, $teacher_name, $is_teacher) {
        $class_card = DOMHelper::createElement($dom, 'div', 'class-card');
        $class_image_link = DOMHelper::createElement($dom, 'a', 'class-image-link', null, null, ['href' => $class->id]);
        $class_image_link->appendChild(DOMHelper::createElement($dom, 'img', 'class-image', null, null,
            [
                'src' => $class->type_image_src, 
                'alt' => $class->type_title,
                'width' => '200',
                'height' => '200'
            ]));
        $class_card->appendChild($class_image_link);
        $class_link_wrapper = DOMHelper::createElement($dom, 'h3', 'class-link-wrapper');
        $class_link = DOMHelper::createElement($dom, 'a', 'class-link', null, $class->class_title, ['href' => $class->id]);
        $class_link_wrapper->appendChild($class_link);
        $class_card->appendChild($class_link_wrapper);
        $class_teacher_name = DOMHelper::createElement($dom, 'span', 'class-teacher-name', null, 'Teacher: ' . $teacher_name);
        $class_card->appendChild($class_teacher_name);
        if (!$is_teacher) {
            $class_student_name = DOMHelper::createElement($dom, 'span', 'class-student-name', null, 'Student: ' . $class->student_name);
            $class_card->appendChild($class_student_name);
        }
        return $class_card;
    }

    // New protected method for better testability
    protected function getAPI() {
        return TeachersAPI::getInstance();
    }
}

// Initialize shortcode
new ParentClassesShortcode(); 