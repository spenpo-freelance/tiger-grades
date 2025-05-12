<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\Utilities\DOMHelper;
use DOMDocument;
use Spenpo\TigerGrades\API\GeneralAPI;
/**
 * Handles the [tigr_version] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
class RegistrationShortcode {
    private $text_translations;
    private $api;
    /**
     * Constructor initializes and registers the shortcode.
     */
    public function __construct() {
        $this->api = $this->getAPI();
        // Register the shortcode
        add_shortcode('tigr_registration', [$this, 'render']);
    }

    /**
     * Renders the version shortcode output.
     *
     * @param array $atts Shortcode attributes
     * @return string The rendered shortcode content
     */
    public function render($atts) {
        $lang = pll_current_language();
        
        $dom = new DOMDocument('1.0', 'utf-8');
        $root = DOMHelper::createElement($dom, 'div', 'registration-container');
        $header = DOMHelper::createElement($dom, 'h2', 'Welcome to Tiger Grades');
        $subHeader = DOMHelper::createElement($dom, 'h3', 'Which kind of account are you looking for?');
        $root->appendChild($header);
        $root->appendChild($subHeader);
        $dom->appendChild($root);
        $form_control = DOMHelper::createElement($dom, 'div', 'registration-buttons', null, null, [
            'data-active' => 'user'
        ]);
        $form_control->appendChild(DOMHelper::createElement($dom, 'button', 'registration-button', null, 'Parent/Student', [
            'type' => 'button',
            'name' => 'email',
            'data-form-id' => 'user',
        ]));
        $form_control->appendChild(DOMHelper::createElement($dom, 'button', 'registration-button', null, 'Teacher', [
            'type' => 'button',
            'name' => 'email',
            'data-form-id' => 'teacher'
        ]));
        $root->appendChild($form_control);
        
        $form_container = DOMHelper::createElement($dom, 'div', 'registration-form-container');
        $temp = new DOMDocument();
        $user_registration_id = get_option('tigr_ur_user_form_id');
        @$temp->loadHTML(do_shortcode('[user_registration_form id="'.$user_registration_id.'"]'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $node = $dom->importNode($temp->documentElement, true);
        $form_container->appendChild($node);
        $root->appendChild($form_container);

        // Enqueue the JavaScript
        wp_enqueue_script(
            'tiger-grades-user-registration',
            plugins_url('tiger-grades/js/user-registration.js', dirname(__FILE__, 3)),
            array('jquery'),
            '1.0.2',
            true
        );
        wp_localize_script(
            'tiger-grades-user-registration',
            'tigr_ajax_object',
            array(
                'teacher_form_id' => get_option('tigr_ur_teacher_form_id'),
                'user_form_id' => get_option('tigr_ur_user_form_id'),
                'ajax_url' => rest_url('tiger-grades/v1/shortcode')
            )
        );
        
        return $dom->saveHTML();
    }

    // New protected method for better testability
    protected function getAPI() {
        return GeneralAPI::getInstance();
    }
}

// Initialize the shortcode
new RegistrationShortcode(); 