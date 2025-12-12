<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TigerGradesAPI;
use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
use Spenpo\TigerGrades\Utilities\LanguageManager;
use Spenpo\TigerGrades\Components\GeneralComponents;

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
    private $plugin_domain;
    private $languageManager;
    private $general_components;
    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->api = $this->getAPI();
        $this->classRepository = new TigerClassRepository();
        $this->languageManager = LanguageManager::getInstance();
        $this->plugin_domain = $this->languageManager->getPluginDomain();
        $this->general_components = new GeneralComponents();
        // Define default attributes for the shortcode
        add_shortcode('tigr_parent_class', function($atts) {
            return $this->render($atts);
        });
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
            return $this->general_components->createUnauthenticatedMessage($dom, $root);
        }

        if ($is_teacher) {
            $this->renderReportCardUI($dom, $root);
        }

        $enrollment = $this->classRepository->getEnrollment($enrollment_id);
        if (!$enrollment || $enrollment->user_id != $user_id) {
            $not_enrolled_message = DOMHelper::createElement($dom, 'div', 'not-enrolled-message');
            $not_enrolled_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-enrolled-message-text', null, __('You are not enrolled in this class', $this->plugin_domain) . '.'));
            $root->appendChild($not_enrolled_message);
            return $dom->saveHTML();
        }

        if ($enrollment->status == 'pending') {
            $pending_message = DOMHelper::createElement($dom, 'div', 'pending-message');
            $pending_message->appendChild(DOMHelper::createElement($dom, 'span', 'pending-message-text', null, __('Your enrollment is pending approval by the teacher', $this->plugin_domain) . '.'));
            $root->appendChild($pending_message);
            return $dom->saveHTML();
        }

        if ($enrollment->status == 'rejected') {
            $rejected_message = DOMHelper::createElement($dom, 'div', 'rejected-message');
            $rejected_message->appendChild(DOMHelper::createElement($dom, 'span', 'rejected-message-text', null, __('Your enrollment has been rejected. Please contact the teacher for more information', $this->plugin_domain) . '.'));
            $root->appendChild($rejected_message);
            return $dom->saveHTML();
        }

        $this->renderReportCardContent($dom, $root);
    }

    private function renderReportCardUI($dom, $root) {
        $loading = DOMHelper::createElement($dom, 'div', 'loading-message');
        $loading->appendChild(DOMHelper::createElement($dom, 'p', 'loading-text', null, __('Loading content', $this->plugin_domain) . '...'));
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
            'nonce' => wp_create_nonce('wp_rest'),
            'copy' => [
                'please_select_student' => __('Please select a student to view their grades', $this->plugin_domain),
                'date' => __('Date', $this->plugin_domain),
                'task' => __('Task', $this->plugin_domain),
                'type' => __('Type', $this->plugin_domain),
                'percent' => __('Percent', $this->plugin_domain),
                'grade' => __('Grade', $this->plugin_domain),
                'max' => __('Max', $this->plugin_domain),
                'earned' => __('Earned', $this->plugin_domain),
                'export_all' => __('Export all', $this->plugin_domain),
                'export_as_pdf' => __('Export as PDF', $this->plugin_domain),
                'select_student' => __('Select student', $this->plugin_domain),
                'no_grades_found' => __('No grades found for this student', $this->plugin_domain),
                'overall_grade' => __('Overall Grade', $this->plugin_domain),
                'letter_grade' => __('Letter Grade', $this->plugin_domain),
                'semester_average' => __('Semester Average', $this->plugin_domain),
                'exempt' => __('Exempt', $this->plugin_domain),
                'grades_are_worth' => __('grades are worth', $this->plugin_domain),
                'of_the_overall_grade' => __('of the overall grade', $this->plugin_domain),
                'woops' => __('Woops! This class is broken. You might be in the wrong place. Please try navigating to your class from the', $this->plugin_domain),
                'grades_page' => __('grades page', $this->plugin_domain),
                'grades_route' => $this->languageManager->getTranslatedRoute('/' . $this->languageManager->getTranslatedRouteSegment('grades')),
            ]
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