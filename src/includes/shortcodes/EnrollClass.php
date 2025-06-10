<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TeachersAPI;
use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
use Spenpo\TigerGrades\Components\GeneralComponents;
use Spenpo\TigerGrades\Utilities\LanguageManager;
/**
 * Handles the [tigr_class_enroll] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class EnrollClassShortcode {
    /** @var TeachersAPI Instance of the Teachers API */
    private $api;
    private $repository;
    private $language_manager;
    private $plugin_domain;

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->repository = new TigerClassRepository();
        $this->language_manager = LanguageManager::getInstance();
        $this->plugin_domain = $this->language_manager->getPluginDomain();
        // Define default attributes for the shortcode
        add_shortcode('tigr_class_enroll', function($atts) {
            return $this->render();
        });
    }

    /**
     * Renders the report card content as HTML.
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the report card
     */
    public function render() {
        $user_id = get_current_user_id();

        $dom = new DOMDocument('1.0', 'utf-8');
        // Create a root container for all sections
        $root = DOMHelper::createElement($dom, 'div', 'report-card-container');
        // Add data attributes for JS to use
        $root->setAttribute('data-user-id', $user_id);
        $dom->appendChild($root);

        if (!$user_id) {
            $generalComponents = new GeneralComponents();
            return $generalComponents->createUnauthenticatedMessage($dom, $root);
        }

        $enrollment_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        
        $form_title_text = __('Enroll in a class', $this->plugin_domain);
        if ($enrollment_code) {
            $class = $this->repository->getClassByEnrollmentCode($enrollment_code);
            if ($class) {
                $class_title = $class->title;
                $class_teacher = $class->teacher_name;
                $form_title_text = 'Enroll in ' . $class_teacher . '\'s ' . $class_title . ' class';
            }
        }
        $form_title = DOMHelper::createElement($dom, 'h2', 'enroll-class-form-title', null, $form_title_text);
        $root->appendChild($form_title);
        
        $form_container = DOMHelper::createElement($dom, 'div', 'enroll-class-form-container');
        $form = DOMHelper::createElement($dom, 'form', 'enroll-class-form', null, null, [
            'action' => '/wp-json/tiger-grades/v1/create-enrollment?lang=' . $this->language_manager->getCurrentLanguage(),
            'method' => 'POST'
        ]);
        
        $enrollment_container = DOMHelper::createElement($dom, 'div', 'form-group flexbox');
        $enrollment_code_form_field = DOMHelper::createElement($dom, 'div', 'form-field');
        $enrollment_code_label = DOMHelper::createElement($dom, 'label', 'form-label', null, __('Enrollment Code', $this->plugin_domain));
        $enrollment_code_form_field->appendChild($enrollment_code_label);
        $enrollment_code_input = DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
            'placeholder' => __('Enrollment Code', $this->plugin_domain), 
            'required' => 'required', 
            'type' => 'text', 
            'name' => 'enrollment_code', 
            'value' => $enrollment_code,
            'maxlength' => '6'
        ]);
        $enrollment_code_form_field->appendChild($enrollment_code_input);
        $enrollment_container->appendChild($enrollment_code_form_field);

        $student_name_form_field = DOMHelper::createElement($dom, 'div', 'form-field');
        $student_name_label = DOMHelper::createElement($dom, 'label', 'form-label', null, __('Student Name', $this->plugin_domain));
        $student_name_form_field->appendChild($student_name_label);
        $student_name_input = DOMHelper::createElement($dom, 'input', 'form-control', null, null, [
            'placeholder' => __('Student Name', $this->plugin_domain), 
            'required' => 'required', 
            'type' => 'text', 
            'name' => 'student_name', 
            'maxlength' => '45'
        ]);
        $student_name_form_field->appendChild($student_name_input);
        $enrollment_container->appendChild($student_name_form_field);
        $form->appendChild($enrollment_container);

        $optional_message_container = DOMHelper::createElement($dom, 'div', 'form-group');
        $optional_message_label = DOMHelper::createElement($dom, 'label', 'form-label', null, __('Anything else the teacher should know', $this->plugin_domain) . '?');
        $optional_message_container->appendChild($optional_message_label);
        $optional_message_textarea = DOMHelper::createElement($dom, 'textarea', 'form-control', null, null, [
            'placeholder' => __('Optional message for the teacher', $this->plugin_domain), 
            'name' => 'optional_message',
            'rows' => '4',
            'maxlength' => '100'
        ]);
        $optional_message_container->appendChild($optional_message_textarea);
        $form->appendChild($optional_message_container);

        $submit_container = DOMHelper::createElement($dom, 'div', 'form-group submit-enroll');
        $form_submit = DOMHelper::createElement($dom, 'input', 'btn btn-theme-primary', null, null, ['type' => 'submit', 'value' => __('Submit', $this->plugin_domain)]);
        $loading_small = DOMHelper::createElement($dom, 'div', 'loading-element');
        $submit_container->appendChild($form_submit);
        $loading_container = DOMHelper::createElement($dom, 'div', 'loading-container');
        $loading_container->appendChild($loading_small);
        $submit_container->appendChild($loading_container);
        $form->appendChild($submit_container);

        $form_container->appendChild($form);
        $root->appendChild($form_container);
        
        // Enqueue the JavaScript
        wp_enqueue_script(
            'tiger-grades-enroll-class',
            plugins_url('tiger-grades/js/enroll-class.js', dirname(__FILE__, 3)),
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize the script with necessary data
        wp_localize_script('tiger-grades-enroll-class', 'tigerGradesData', array(
            'nonce' => wp_create_nonce('wp_rest')
        ));

        return $dom->saveHTML();
    }

    // New protected method for better testability
    protected function getAPI() {
        return TeachersAPI::getInstance();
    }
}

// Initialize shortcode
new EnrollClassShortcode(); 