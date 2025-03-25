<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TeachersAPI;
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

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->api = $this->getAPI();
        
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
            $create_class_form = DOMHelper::createElement($dom, 'form', 'create-class-form search-form', null, null, [
                'action' => '/wp-json/tiger-grades/v1/create-class', 
                'method' => 'POST'
            ]);
            $create_class_form->appendChild(DOMHelper::createElement($dom, 'input', 'create-class-form-input search-field', null, null, [
                'type' => 'text',
                'name' => 'title',  // Add name attribute
                'placeholder' => 'Enter the title of the class',
                'required' => 'required'  // Make the field required
            ]));
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