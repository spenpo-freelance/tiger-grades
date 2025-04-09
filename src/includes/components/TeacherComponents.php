<?php
namespace Spenpo\TigerGrades\Components;

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
class TeacherComponents {
    private $repository;

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->repository = new TigerClassRepository();
    }

    /**
     * Renders the report card content as HTML.
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the report card
     */
    public function createClassesTable($dom, $user_id) {
        $root = DOMHelper::createElement($dom, 'div', 'classes-table-container');
        $dom->appendChild($root);

        // Enqueue the JavaScript
        wp_enqueue_script(
            'qrcode-js',
            'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js',
            array(),
            '1.0.0',
            true
        );

        // Enqueue Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );

        wp_enqueue_script(
            'tiger-grades-enrollment-code',
            plugins_url('tiger-grades/js/enrollment-code.js', dirname(__FILE__, 3)),
            array('jquery', 'qrcode-js'),
            '1.0.0',
            true
        );

        // Enqueue the CSS
        wp_enqueue_style(
            'tiger-grades-enrollment-code',
            plugins_url('tiger-grades/css/enrollment-code.css', dirname(__FILE__, 3)),
            array(),
            '1.0.0'
        );

        $classes = $this->repository->getTeacherClasses($user_id);
        
        $classes_header_container = DOMHelper::createElement($dom, 'div', 'classes-table-header-container flexbox between');
        $root->appendChild($classes_header_container);
        $classes_header = DOMHelper::createElement($dom, 'h2', 'classes-table-header', null, 'Classes');
        $classes_header_container->appendChild($classes_header);

        $add_class_button = DOMHelper::createElement($dom, 'a', 'btn btn-theme-primary register-class-btn', null, '+ Register Class', ['href' => "/teacher/classes/register/"]);
        $classes_header_container->appendChild($add_class_button);

        $table = DOMHelper::createElement($dom, 'table', 'classes-table');
        $root->appendChild($table);

        $headerRow = DOMHelper::createElement($dom, 'tr', 'classes-table-header');
        $titleHeader = DOMHelper::createElement($dom, 'th', 'classes-table-title-header');
        $titleHeader->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-title-header-text', null, 'Class'));
        $headerRow->appendChild($titleHeader);
        $statusHeader = DOMHelper::createElement($dom, 'th', 'classes-table-status-header');
        $statusHeader->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-status-header-text', null, 'Status'));
        $headerRow->appendChild($statusHeader);
        $enrollmentsHeader = DOMHelper::createElement($dom, 'th', 'classes-table-enrollments-header');
        $enrollmentsHeader->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-enrollments-header-text', null, 'Enrolled'));
        $headerRow->appendChild($enrollmentsHeader);
        $enrollmentCodeHeader = DOMHelper::createElement($dom, 'th', 'classes-table-enrollment-code-header');
        $enrollmentCodeHeader->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-enrollment-code-header-text', null, 'Code'));
        $headerRow->appendChild($enrollmentCodeHeader);
        $actionsHeader = DOMHelper::createElement($dom, 'th', 'classes-table-actions-header');
        $actionsHeader->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-actions-header-text', null, 'Actions'));
        $headerRow->appendChild($actionsHeader);
        $table->appendChild($headerRow);

        if (empty($classes)) {
            $row = DOMHelper::createElement($dom, 'tr', 'classes-table-row');
            $table->appendChild($row);
            $titleCell = DOMHelper::createElement($dom, 'td', 'classes-table-title-cell empty-state-message', null, 'No classes found.', ['colspan' => '5']);
            $row->appendChild($titleCell);
        } else {
            foreach ($classes as $class) {
                $enrollments = $class->total_enrollments;
                if ($class->pending_enrollments > 0) {
                    $enrollments .= ' (' . $class->pending_enrollments . ' pending)';
                }
                
                $row = DOMHelper::createElement($dom, 'tr', 'classes-table-row');
                $table->appendChild($row);

                $titleCell = DOMHelper::createElement($dom, 'td', 'classes-table-title-cell');
                $titleCell->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-title-cell-text', null, $class->title));
                $row->appendChild($titleCell);

                $statusCell = DOMHelper::createElement($dom, 'td', 'classes-table-status-cell');
                $statusCell->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-status-cell-text', null, $class->status));
                $row->appendChild($statusCell);

                $enrollmentsCell = DOMHelper::createElement($dom, 'td', 'classes-table-enrollments-cell');
                $enrollmentsCell->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-enrollments-cell-text', null, $enrollments));
                $row->appendChild($enrollmentsCell);

                $enrollmentCodeCell = DOMHelper::createElement($dom, 'td', 'classes-table-enrollment-code-cell');
                $enrollmentCodeContainer = DOMHelper::createElement($dom, 'div', 'enrollment-code-container flexbox between');
                $enrollmentCodeCell->appendChild($enrollmentCodeContainer);

                // Enrollment code display
                $codeDisplay = DOMHelper::createElement($dom, 'span', 'classes-table-enrollment-code-cell-text', null, $class->enrollment_code);
                $enrollmentCodeContainer->appendChild($codeDisplay);

                // Action buttons container
                $actionButtons = DOMHelper::createElement($dom, 'div', 'enrollment-code-actions flexbox');
                $enrollmentCodeContainer->appendChild($actionButtons);

                // Copy code button
                $copyCodeButton = DOMHelper::createElement($dom, 'button', 'copy-code-btn btn btn-icon', null, null, [
                    'title' => 'Copy enrollment code',
                    'data-code' => $class->enrollment_code
                ]);
                $copyCodeButton->appendChild(DOMHelper::createElement($dom, 'span', 'dashicons dashicons-clipboard'));
                $actionButtons->appendChild($copyCodeButton);

                // Copy URL button
                $enrollmentUrl = site_url('/enroll?code=' . $class->enrollment_code);
                $copyUrlButton = DOMHelper::createElement($dom, 'button', 'copy-url-btn btn btn-icon', null, null, [
                    'title' => 'Copy enrollment URL',
                    'data-url' => $enrollmentUrl
                ]);
                $copyUrlButton->appendChild(DOMHelper::createElement($dom, 'span', 'dashicons dashicons-admin-links'));
                $actionButtons->appendChild($copyUrlButton);

                // QR code button
                $qrCodeButton = DOMHelper::createElement($dom, 'button', 'qr-code-btn btn btn-icon', null, null, [
                    'title' => 'Show QR code',
                    'data-url' => $enrollmentUrl
                ]);
                $qrCodeButton->appendChild(DOMHelper::createElement($dom, 'i', 'fas fa-qrcode'));
                $actionButtons->appendChild($qrCodeButton);

                // QR code modal
                $qrCodeModal = DOMHelper::createElement($dom, 'dialog', 'qr-code-modal');
                $qrCodeModal->appendChild(DOMHelper::createElement($dom, 'h2', 'qr-code-modal-header', null, 'Enrollment QR Code'));
                $qrCodeModal->appendChild(DOMHelper::createElement($dom, 'div', 'qr-code-container'));
                $qrCodeModal->appendChild(DOMHelper::createElement($dom, 'button', 'qr-code-modal-close', null, 'Close'));
                $enrollmentCodeContainer->appendChild($qrCodeModal);

                $row->appendChild($enrollmentCodeCell);

                $actionsCell = DOMHelper::createElement($dom, 'td', 'classes-table-actions-cell');
                $actions_container = DOMHelper::createElement($dom, 'div', 'flexbox');
                $actionsCell->appendChild($actions_container);
                $isActive = $class->status === 'active';
                if ($isActive) {
                    $manageEnrollmentsButton = DOMHelper::createElement($dom, 'a', 'classes-table-manage-enrollments-button btn approve-enrollment-btn', null, 'Manage', ['href' => "/teacher/classes/{$class->id}/"]);
                    $actions_container->appendChild($manageEnrollmentsButton);
                }
                if ($isActive) {
                    $gradesButton = DOMHelper::createElement($dom, 'a', 'classes-table-manage-enrollments-button btn approve-enrollment-btn', null, 'Grades', ['href' => "/grades/{$class->id}/"]);
                    $actions_container->appendChild($gradesButton);
                }
                if ($isActive) {
                    $viewGradebookButton = DOMHelper::createElement($dom, 'a', 'classes-table-manage-enrollments-button btn approve-enrollment-btn', null, 'View', ['href' => $class->gradebook_url, 'target' => '_blank', 'rel' => 'noopener noreferrer']);
                    $actions_container->appendChild($viewGradebookButton);
                }
                $row->appendChild($actionsCell);
            }
        }
        return $root;
    }
}
