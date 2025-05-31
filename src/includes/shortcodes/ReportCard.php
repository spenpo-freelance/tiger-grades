<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TigerGradesAPI;
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
class ReportCardShortcode {
    /** @var TigerGradesAPI Instance of the TigerGrades API */
    private $api;
    private $classRepository;
    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->api = $this->getAPI();
        $this->classRepository = new TigerClassRepository();
        // Define default attributes for the shortcode
        add_shortcode('tigr_parent_class', function($atts) {
            return $this->render($atts);
        });
    }

    /**
     * Gets the letter grade based on the percentage.
     * 
     * @param string $percentage The percentage to convert to a letter grade
     * @return string The letter grade
     */
    private function getLetterGrade($percentage) {
        if ($percentage >= 90) {
            return 'A';
        } elseif ($percentage >= 80) {
            return 'B';
        } elseif ($percentage >= 70) {
            return 'C';
        } else {
            return 'D';
        }
    }

    private function processScore($score) {
        if ($score == '0') {
            return '00';
        } elseif ($score == 'e') {
            return 'EXEMPT';
        } else {
            return $score;
        }
    }

    /**
     * Renders the report card content as HTML.
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the report card
     */
    public function render() {
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $enrollment_id = get_query_var('enrollment_id');
        $class_category = get_query_var('class_category') == '' ? 'all' : get_query_var('class_category');
        $is_teacher = in_array('teacher', (array) $user->roles);

        $dom = new DOMDocument('1.0', 'utf-8');
        // Create a root container for all sections
        $root = DOMHelper::createElement($dom, 'div', 'report-card-container');
        // Add data attributes for JS to use
        $root->setAttribute('data-user-id', $user_id);
        $root->setAttribute('data-type', $class_category);
        $root->setAttribute('data-enrollment-id', $enrollment_id);
        $root->setAttribute('data-is-teacher', $is_teacher);
        $dom->appendChild($root);

        if (!$user_id) {
            $not_logged_in_message = DOMHelper::createElement($dom, 'div', 'not-logged-in-message');
            $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-logged-in-message-text', null, 'Please '));
            $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'a', 'not-logged-in-message-text', null, 'log in', ['href' => '/my-account']));
            $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-logged-in-message-text', null, ' to view your child\'s grades.'));
            $root->appendChild($not_logged_in_message);
            return $dom->saveHTML();
        }

        if ($is_teacher) {
            $this->renderReportCardUI($dom, $root);
        }

        $enrollment = $this->classRepository->getEnrollment($enrollment_id);
        if (!$enrollment || $enrollment->user_id != $user_id) {
            error_log('Enrollment not found or user not enrolled ' . 'enrolled user: ' . $enrollment->user_id . ' ' . 'current user: ' . $user_id . ' ' . 'full enrollment: ' . print_r($enrollment, true));
            $not_enrolled_message = DOMHelper::createElement($dom, 'div', 'not-enrolled-message');
            $not_enrolled_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-enrolled-message-text', null, 'You are not enrolled in this class.'));
            $root->appendChild($not_enrolled_message);
            return $dom->saveHTML();
        }

        if ($enrollment->status == 'pending') {
            $pending_message = DOMHelper::createElement($dom, 'div', 'pending-message');
            $pending_message->appendChild(DOMHelper::createElement($dom, 'span', 'pending-message-text', null, 'Your enrollment is pending approval by the teacher.'));
            $root->appendChild($pending_message);
            return $dom->saveHTML();
        }

        if ($enrollment->status == 'rejected') {
            $rejected_message = DOMHelper::createElement($dom, 'div', 'rejected-message');
            $rejected_message->appendChild(DOMHelper::createElement($dom, 'span', 'rejected-message-text', null, 'Your enrollment has been rejected. Please contact the teacher for more information.'));
            $root->appendChild($rejected_message);
            return $dom->saveHTML();
        }

        $this->renderReportCardContent($dom, $root);
    }

    private function renderReportCardUI($dom, $root) {
        $loading = DOMHelper::createElement($dom, 'div', 'loading-message');
        $loading->appendChild(DOMHelper::createElement($dom, 'p', 'loading-text', null, 'Loading content...'));
        $root->appendChild($loading);

        // Enqueue the JavaScript
        wp_enqueue_script(
            'tiger-grades-report-card',
            plugins_url('tiger-grades/js/report-card.js', dirname(__FILE__, 3)),
            array('jquery'),
            '1.0.8',
            true
        );

        // Localize the script with necessary data
        wp_localize_script('tiger-grades-report-card', 'tigerGradesData', array(
            'apiUrl' => rest_url('tiger-grades/v1/report-card'),
            'metadataUrl' => rest_url('tiger-grades/v1/class-metadata'),
            'nonce' => wp_create_nonce('wp_rest')
        ));

        return $dom->saveHTML();
    }
    // New protected method for better testability
    protected function getAPI() {
        return TigerGradesAPI::getInstance();
    }
}

// Initialize shortcode
new ReportCardShortcode(); 