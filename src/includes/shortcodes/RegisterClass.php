<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TeachersAPI;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;

/**
 * Handles the [tigr_report_card] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class RegisterClassShortcode {
    /** @var TeachersAPI Instance of the Teachers API */
    private $api;
    private $classRepository;
    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->api = $this->getAPI();
        $this->classRepository = new TigerClassRepository();
        // Define default attributes for the shortcode
        add_shortcode('tigr_register_class', function($atts) {
            // Merge user attributes with defaults
            $attributes = shortcode_atts([
                'type' => 'all', // default value
                'class_id' => 'english',
                'semester' => '1',
            ], $atts);
            
            return $this->render($attributes);
        });
    }

    private function formLabelWithSubtitle($dom, $label, $subtitle) {
        $label_container = DOMHelper::createElement($dom, 'span', 'form-label-container');
        $label_container->appendChild(DOMHelper::createElement($dom, 'label', 'form-label', null, $label));
        $label_container->appendChild(DOMHelper::createElement($dom, 'span', 'form-subtitle', null, $subtitle));
        return $label_container;
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
        $root = DOMHelper::createElement($dom, 'div', 'report-card-container');
        // Add data attributes for JS to use
        $root->setAttribute('data-user-id', $user_id);
        $root->setAttribute('data-class-id', $atts['class_id']);
        $dom->appendChild($root);

        $header = DOMHelper::createElement($dom, 'h2', 'register-class-header', null, 'Register a new class');
        $root->appendChild($header);

        if ($user_id) {
            $create_class_form = DOMHelper::createElement($dom, 'form', 'create-class-form', null, null, [
                'action' => '/wp-json/tiger-grades/v1/create-class', 
                'method' => 'POST'
            ]);
            $title_container = DOMHelper::createElement($dom, 'div', 'form-group');
            $title_container->appendChild(DOMHelper::createElement($dom, 'label', 'form-label', null, 'Title', [
                'for' => 'title'
            ]));
            $title_container->appendChild(DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
                'type' => 'text',
                'name' => 'title',  // Add name attribute
                'placeholder' => 'Enter the title of the class',
                'required' => 'required',
                'maxlength' => '45'
            ]));
            $create_class_form->appendChild($title_container);

            $description_container = DOMHelper::createElement($dom, 'div', 'form-group');
            $description_container->appendChild($this->formLabelWithSubtitle($dom, 'Description', '(This will be shown to everyone who has your enrollment code)'));
            $description_container->appendChild(DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
                'type' => 'text',
                'name' => 'description',
                'placeholder' => 'Enter a short description of the class',
                'required' => 'required',
                'maxlength' => '240'
            ]));
            $create_class_form->appendChild($description_container);

            $class_dates_container = DOMHelper::createElement($dom, 'div', 'form-group flexbox');
            
            // Start Date
            $start_date_wrapper = DOMHelper::createElement($dom, 'div', 'form-field');
            $start_date_wrapper->appendChild(DOMHelper::createElement($dom, 'label', 'form-label', null, 'Start Date', [
                'for' => 'start_date'
            ]));
            $start_date_wrapper->appendChild(DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
                'type' => 'date',
                'name' => 'start_date',
                'placeholder' => 'Enter the start date of the class',
                'required' => 'required'
            ]));
            $class_dates_container->appendChild($start_date_wrapper);
            
            // End Date
            $end_date_wrapper = DOMHelper::createElement($dom, 'div', 'form-field');
            $end_date_wrapper->appendChild(DOMHelper::createElement($dom, 'label', 'form-label', null, 'End Date', [
                'for' => 'end_date'
            ]));
            $end_date_wrapper->appendChild(DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
                'type' => 'date',
                'name' => 'end_date',
                'placeholder' => 'Enter the end date of the class',
                'required' => 'required'
            ]));
            $class_dates_container->appendChild($end_date_wrapper);
            $create_class_form->appendChild($class_dates_container);

            $class_types = $this->classRepository->getClassTypes();
            $class_type_selection_container = DOMHelper::createElement($dom, 'div', 'class-type-selection-container form-group');
            $create_class_form->appendChild(DOMHelper::createElement($dom, 'label', 'form-label', null, 'Class Type', [
                'for' => 'class_type'
            ]));
            foreach ($class_types as $class_type) {
                $class_type_container = DOMHelper::createElement($dom, 'label', 'class-type-selection-container-item', null, null, [
                    'data-class-type-id' => $class_type->id,
                    'for' => 'class-type-' . $class_type->id
                ]);
                
                // Add hidden radio button
                $radio = DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
                    'type' => 'radio',
                    'name' => 'class_type',
                    'id' => 'class-type-' . $class_type->id,
                    'value' => $class_type->id,
                    'required' => 'required'
                ]);
                $class_type_container->appendChild($radio);
                
                $class_type_image = DOMHelper::createElement($dom, 'img', 'class-type-selection-container-item-image', null, null, [
                    'src' => $class_type->image_src,
                    'alt' => $class_type->title,
                    'width' => '150',
                    'height' => '150'
                ]);
                $class_type_container->appendChild($class_type_image);
                $class_type_container->appendChild(DOMHelper::createElement($dom, 'div', 'class-type-selection-container-item-title', null, $class_type->title));
                $class_type_selection_container->appendChild($class_type_container);
            }
            $create_class_form->appendChild($class_type_selection_container);

            $class_size_container = DOMHelper::createElement($dom, 'div', 'form-group flexbox');
            $estimated_class_size_container = DOMHelper::createElement($dom, 'div', 'form-field flexbox column between');
            $estimated_class_size_container->appendChild(DOMHelper::createElement($dom, 'label', 'form-label', null, 'Estimated Class Size', [
                'for' => 'num_students'
            ]));
            $estimated_class_size_select = DOMHelper::createElement($dom, 'select', 'form-control', null, null, [
                'name' => 'num_students',
                'id' => 'num_students',
                'required' => 'required'
            ]);

            $registration_form_options = $this->classRepository->getClassRegistrationOptions();
            foreach ($registration_form_options['num_students'] as $option) {
                $estimated_class_size_select->appendChild(DOMHelper::createElement($dom, 'option', null, null, $option->label, [
                    'value' => $option->id
                ]));
            }
            $estimated_class_size_container->appendChild($estimated_class_size_select);
            $class_size_container->appendChild($estimated_class_size_container);

            $estimated_number_categories_container = DOMHelper::createElement($dom, 'div', 'form-field');
            $estimated_number_categories_container->appendChild($this->formLabelWithSubtitle($dom, 'Estimated Number of Categories', '(ie. tests, quizzes, homework, etc.)'));
            $estimated_number_categories_select = DOMHelper::createElement($dom, 'select', 'form-control', null, null, [
                'name' => 'num_categories',
                'id' => 'num_categories',
                'required' => 'required'
            ]);

            foreach ($registration_form_options['num_categories'] as $option) {
                $estimated_number_categories_select->appendChild(DOMHelper::createElement($dom, 'option', null, null, $option->label, [
                    'value' => $option->id
                ]));
            }
            $estimated_number_categories_container->appendChild($estimated_number_categories_select);
            $class_size_container->appendChild($estimated_number_categories_container);

            $create_class_form->appendChild($class_size_container);

            $additional_info_container = DOMHelper::createElement($dom, 'div', 'form-group');
            $additional_info_container->appendChild($this->formLabelWithSubtitle($dom, 'Anything else we should know?', '(We read this, but it won\'t be shown to anyone else)'));
            $additional_info_container->appendChild(DOMHelper::createElement($dom, 'textarea', 'form-control', null, null, [
                'name' => 'message',
                'placeholder' => 'Enter any additional information about your class',
                'rows' => '4',
                'maxlength' => '480'
            ]));
            $create_class_form->appendChild($additional_info_container);

            $create_class_form->appendChild(DOMHelper::createElement($dom, 'input', 'search-submit create-class-form-button', null, 'Register Class', ['type' => 'submit']));
            $root->appendChild($create_class_form);
            
            // Enqueue the JavaScript
            wp_enqueue_script(
                'tiger-grades-register-class',
                plugins_url('tiger-grades/js/register-class.js', dirname(__FILE__, 3)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Localize the script with necessary data
            wp_localize_script('tiger-grades-register-class', 'tigerGradesData', array(
                'nonce' => wp_create_nonce('wp_rest')
            ));

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
new RegisterClassShortcode(); 