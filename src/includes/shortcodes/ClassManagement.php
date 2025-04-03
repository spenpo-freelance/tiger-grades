<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TigerGradesAPI;
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
class ClassManagementShortcode {
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
        add_shortcode('tigr_teacher_class', function($atts) {
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
    public function renderEnrollmentTable($dom, $enrollments, $enrollment_table) {
        $table_body = DOMHelper::createElement($dom, 'tbody', 'enrollment-table-body');

        foreach ($enrollments as $enrollment) {
            $enrollment_table->appendChild(DOMHelper::createElement($dom, 'tr', 'enrollment-table-row'));
            $enrollment_table->appendChild(DOMHelper::createElement($dom, 'td', 'enrollment-table-cell', null, $enrollment->student_name));
            $enrollment_table->appendChild(DOMHelper::createElement($dom, 'td', 'enrollment-table-cell', null, $enrollment->parent_name));

            $email_link = DOMHelper::createElement($dom, 'a', 'email-link', null, $enrollment->parent_email, ['href' => 'mailto:' . $enrollment->parent_email]);
            $email_cell = DOMHelper::createElement($dom, 'td', 'enrollment-table-cell', null);
            $email_cell->appendChild($email_link);
            $enrollment_table->appendChild($email_cell);
            $enrollment_table->appendChild(DOMHelper::createElement($dom, 'td', 'enrollment-table-cell', null, $enrollment->status));
            
            // Create action cell with button
            $actionCell = DOMHelper::createElement($dom, 'td', 'enrollment-table-cell');
            $approveButton = DOMHelper::createElement($dom, 'button', 'approve-enrollment-btn', null, 'Approve', [
                'data-enrollment-id' => $enrollment->id,
                'type' => 'button'
            ]);
            $actionCell->appendChild($approveButton);
            $enrollment_table->appendChild($actionCell);
        }

        $approveDialog = DOMHelper::createElement($dom, 'dialog', 'approve-dialog', 'approveDialog');
        $approveDialog->appendChild(DOMHelper::createElement($dom, 'h2', 'approve-dialog-header', null, 'Approve Enrollment'));
        $approveDialog->appendChild(DOMHelper::createElement($dom, 'p', 'approve-dialog-description', null,'Choose a student from your gradebook to link with this parent\'s account.'));
        $studentSelect = DOMHelper::createElement($dom, 'select', 'approve-dialog-student-select', null, null, ['disabled' => 'disabled']);
        $studentSelect->appendChild(DOMHelper::createElement($dom, 'option', 'approve-dialog-student-select-option', null, 'Select a student', ['selected' => 'selected', 'disabled' => 'disabled', 'value' => '']));
        $approveDialog->appendChild($studentSelect);
        $approveDialog->appendChild(DOMHelper::createElement($dom, 'button', 'approve-dialog-cancel', null, 'Cancel'));
        $approveDialog->appendChild(DOMHelper::createElement($dom, 'button', 'approve-dialog-confirm', null, 'Confirm', ['disabled' => 'disabled']));
        $dom->appendChild($approveDialog);

        $enrollment_table->appendChild($table_body);
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

        $enrollments_header = DOMHelper::createElement($dom, 'h2', 'enrollments-header', null, 'Enrollments');
        $root->appendChild($enrollments_header);
        $enrollment_table = DOMHelper::createElement($dom, 'table', 'enrollment-table');
        $table_header = DOMHelper::createElement($dom, 'thead', 'enrollment-table-header');
        $table_header->appendChild(DOMHelper::createElement($dom, 'tr', 'enrollment-table-row'));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, 'Student Name'));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, 'Parent Name'));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, 'Parent Email'));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, 'Status'));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, 'Actions'));
        $enrollment_table->appendChild($table_header);
        $root->appendChild($enrollment_table);

        if ($user_id) {
            $class_id = get_query_var('class_id');
            $enrollments = $this->classRepository->getClassEnrollments($class_id);
            if (count($enrollments) > 0) {
                $this->renderEnrollmentTable($dom, $enrollments, $enrollment_table);
                
                // Enqueue the JavaScript
                wp_enqueue_script(
                    'tiger-grades-class-management',
                    plugins_url('tiger-grades/js/class-management.js', dirname(__FILE__, 3)),
                    array('jquery'),
                    '1.0.0',
                    true
                );
    
                // Localize the script with necessary data
                wp_localize_script('tiger-grades-class-management', 'tigerGradesData', array(
                    'studentApiUrl' => rest_url('tiger-grades/v1/students'),
                    'approveApiUrl' => rest_url('tiger-grades/v1/approve-enrollment'),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'class_id' => $class_id
                ));
            } else {
                $empty_row = DOMHelper::createElement($dom, 'tr', 'empty-row');
                $empty_row->appendChild(DOMHelper::createElement($dom, 'td', 'empty-row-cell empty-state-message', null, 'No enrollments found for this class.', ['colspan' => '5']));
                $enrollment_table->appendChild($empty_row);
            }

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
        return TigerGradesAPI::getInstance();
    }
}

// Initialize shortcode
new ClassManagementShortcode(); 