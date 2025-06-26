<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TigerGradesAPI;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Utilities\LanguageManager;
use Spenpo\TigerGrades\Components\GeneralComponents;

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
    private $plugin_domain;

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->api = $this->getAPI();
        $this->classRepository = new TigerClassRepository();
        $this->plugin_domain = LanguageManager::getInstance()->getPluginDomain();
        // Define default attributes for the shortcode
        add_shortcode('tigr_teacher_class', function($atts) {
            return $this->render($atts);
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
            $row = DOMHelper::createElement($dom, 'tr', 'enrollment-table-row', null, null, [
                'data-enrollment-id' => $enrollment->id,
                'data-student-id' => $enrollment->student_id,
            ]);
            $table_body->appendChild($row);
            
            // Student cell
            $student_cell = DOMHelper::createElement($dom, 'td', 'enrollment-table-cell student-cell', null);
            $student_flex_box = DOMHelper::createElement($dom, 'div', 'student-info flex-cell');
            $student_name = DOMHelper::createElement($dom, 'p', 'student-name', null, __('Enrolled as', $this->plugin_domain) . ": $enrollment->student_name");
            $student_flex_box->appendChild($student_name);
            
            $name_in_gradebook = DOMHelper::createElement($dom, 'p', 'name-in-gradebook', null, __('Name in gradebook', $this->plugin_domain) . ": ");
            $gradebook_name = DOMHelper::createElement($dom, 'span', 'gradebook-name loading-small');
            $name_in_gradebook->appendChild($gradebook_name);

            if ($enrollment->status === 'approved') {
                $student_flex_box->appendChild($name_in_gradebook);
            }

            $student_cell->appendChild($student_flex_box);
            $row->appendChild($student_cell);
            
            // Parent cell
            $parent_cell = DOMHelper::createElement($dom, 'td', 'enrollment-table-cell parent-cell', null);
            $parent_flex_box = DOMHelper::createElement($dom, 'div', 'parent-info flex-cell');
            $parent_name = DOMHelper::createElement($dom, 'p', 'parent-name', null, $enrollment->parent_name);
            $parent_flex_box->appendChild($parent_name);
            $email_link = DOMHelper::createElement($dom, 'a', 'email-link', null, $enrollment->parent_email, ['href' => 'mailto:' . $enrollment->parent_email]);
            $parent_flex_box->appendChild($email_link);

            if ($enrollment->message) {
                $view_message_btn = DOMHelper::createElement($dom, 'button', 'view-message-btn', null, __('View message', $this->plugin_domain), ['disabled' => 'disabled']);
                $parent_flex_box->appendChild($view_message_btn);

                $message_dialog = DOMHelper::createElement($dom, 'dialog', 'message-dialog', 'messageDialog', null, ['data-enrollment-id' => $enrollment->id]);
                $message_dialog->appendChild(DOMHelper::createElement($dom, 'h2', 'message-dialog-header', null, __('Message from parent', $this->plugin_domain)));
                $message_dialog->appendChild(DOMHelper::createElement($dom, 'p', 'message-dialog-message', null, $enrollment->message));
                $message_dialog->appendChild(DOMHelper::createElement($dom, 'button', 'message-dialog-close', null, __('Close', $this->plugin_domain)));
                $dom->appendChild($message_dialog);
            }

            $parent_cell->appendChild($parent_flex_box);
            $row->appendChild($parent_cell);
            
            // Status cell
            $status_cell = DOMHelper::createElement($dom, 'td', 'enrollment-table-cell enrollment-status', null, __($enrollment->status, $this->plugin_domain));
            $row->appendChild($status_cell);
            
            // Create action cell with buttons
            $actionCell = DOMHelper::createElement($dom, 'td', 'enrollment-table-cell');
            $actionsFlexBox = DOMHelper::createElement($dom, 'div', 'enrollment-actions-flex-box enrollment-actions');
            $approveBtnText = 'Approve';
            if ($enrollment->status === 'approved') {
                $approveBtnText = 'Change';
            }
            $approveButton = DOMHelper::createElement($dom, 'button', 'class-manage-action-btn approve-enrollment-btn', null, __($approveBtnText, $this->plugin_domain), [
                'data-enrollment-id' => $enrollment->id,
                'type' => 'button',
                'disabled' => 'disabled'
            ]);
            $approveButton->appendChild(DOMHelper::createElement($dom, 'span', 'loading-small'));
            $actionsFlexBox->appendChild($approveButton);

            $rejectBtnText = 'Reject';
            if ($enrollment->status === 'approved') {
                $rejectBtnText = 'Remove';
            }
            $rejectButton = DOMHelper::createElement($dom, 'button', 'class-manage-action-btn reject-enrollment-btn', null, null, [
                'data-enrollment-id' => $enrollment->id,
                'type' => 'button',
                'disabled' => 'disabled'
            ]);
            $rejectBtnLabel = DOMHelper::createElement($dom, 'span', 'reject-enrollment-btn-label', null, __($rejectBtnText, $this->plugin_domain));
            $rejectButton->appendChild($rejectBtnLabel);
            $actionsFlexBox->appendChild($rejectButton);
            $actionCell->appendChild($actionsFlexBox);
            $row->appendChild($actionCell);
        }

        $approveDialog = DOMHelper::createElement($dom, 'dialog', 'approve-dialog', 'approveDialog');
        $approveDialog->appendChild(DOMHelper::createElement($dom, 'h2', 'approve-dialog-header', null, __('Approve enrollment', $this->plugin_domain)));
        $approveDialog->appendChild(DOMHelper::createElement($dom, 'p', 'approve-dialog-description', null, __('Choose a student from your gradebook to link with this parent\'s account', $this->plugin_domain) . '.'));
        $controlsContainer = DOMHelper::createElement($dom, 'div', 'approve-dialog-controls-container');
        $studentSelect = DOMHelper::createElement($dom, 'select', 'approve-dialog-student-select', null, null, ['disabled' => 'disabled']);
        $studentSelect->appendChild(DOMHelper::createElement($dom, 'option', 'approve-dialog-student-select-option', null, __('Select a student', $this->plugin_domain), ['selected' => 'selected', 'disabled' => 'disabled', 'value' => '']));
        $controlsContainer->appendChild($studentSelect);
        $controlsContainer->appendChild(DOMHelper::createElement($dom, 'button', 'approve-dialog-cancel', null, __('Cancel', $this->plugin_domain)));
        $confirmButton = DOMHelper::createElement($dom, 'button', 'approve-dialog-confirm', null, null, ['disabled' => 'disabled']);
        $confirmButton->appendChild(DOMHelper::createElement($dom, 'span', 'approve-dialog-confirm-text', null, __('Confirm', $this->plugin_domain)));
        $controlsContainer->appendChild($confirmButton);
        $approveDialog->appendChild($controlsContainer);
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

        $enrollments_header = DOMHelper::createElement($dom, 'h2', 'enrollments-header', null, __('Enrollments', $this->plugin_domain));
        $root->appendChild($enrollments_header);

        $enrollment_table_container = DOMHelper::createElement($dom, 'div', 'enrollment-table-container responsive-table-container');
        $root->appendChild($enrollment_table_container);
        $enrollment_table = DOMHelper::createElement($dom, 'table', 'enrollment-table responsive-table');
        $table_header = DOMHelper::createElement($dom, 'thead', 'enrollment-table-header');
        $table_header->appendChild(DOMHelper::createElement($dom, 'tr', 'enrollment-table-row'));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, __('Student', $this->plugin_domain)));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, __('Parent', $this->plugin_domain)));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, __('Status', $this->plugin_domain)));
        $table_header->appendChild(DOMHelper::createElement($dom, 'th', 'enrollment-table-header-cell', null, __('Actions', $this->plugin_domain)));
        $enrollment_table->appendChild($table_header);
        $enrollment_table_container->appendChild($enrollment_table);

        if (!$user_id) {
            $generalComponents = new GeneralComponents();
            return $generalComponents->createUnauthenticatedMessage($dom, $root);
        }

        $class_id = get_query_var('class_id');
        $enrollments = $this->classRepository->getClassEnrollments($class_id, $user_id);
        if (count($enrollments) > 0) {
            $this->renderEnrollmentTable($dom, $enrollments, $enrollment_table);
            
            // Enqueue the JavaScript
            wp_enqueue_script(
                'tiger-grades-class-management',
                plugins_url('tiger-grades/js/class-management.js', dirname(__FILE__, 3)),
                array('jquery'),
                '1.0.1',
                true
            );

            // Localize the script with necessary data
            wp_localize_script('tiger-grades-class-management', 'tigerGradesData', array(
                'studentApiUrl' => rest_url('tiger-grades/v1/students'),
                'approveApiUrl' => rest_url('tiger-grades/v1/approve-enrollment'),
                'rejectApiUrl' => rest_url('tiger-grades/v1/reject-enrollment'),
                'nonce' => wp_create_nonce('wp_rest'),
                'class_id' => $class_id,
                'copy' => [
                    'approved' => __('approved', $this->plugin_domain),
                    'rejected' => __('rejected', $this->plugin_domain),
                    'change' => __('Change', $this->plugin_domain),
                    'confirm' => __('Confirm', $this->plugin_domain),
                ]
            ));
        } else {
            $empty_row = DOMHelper::createElement($dom, 'tr', 'empty-row');
            $empty_row->appendChild(DOMHelper::createElement($dom, 'td', 'empty-row-cell empty-state-message', null, 'No enrollments found for this class.', ['colspan' => '5']));
            $enrollment_table->appendChild($empty_row);
        }

        return $dom->saveHTML();
    }

    // New protected method for better testability
    protected function getAPI() {
        return TigerGradesAPI::getInstance();
    }
}

// Initialize shortcode
new ClassManagementShortcode(); 