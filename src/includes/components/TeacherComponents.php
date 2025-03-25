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
            $enrollmentCodeCell->appendChild(DOMHelper::createElement($dom, 'span', 'classes-table-enrollment-code-cell-text', null, $class->enrollment_code));
            $row->appendChild($enrollmentCodeCell);

            $actionsCell = DOMHelper::createElement($dom, 'td', 'classes-table-actions-cell');
            $actions_container = DOMHelper::createElement($dom, 'div', 'flexbox');
            $actionsCell->appendChild($actions_container);
            $manageEnrollmentsButton = DOMHelper::createElement($dom, 'a', 'classes-table-manage-enrollments-button btn approve-enrollment-btn', null, 'Manage', ['href' => "/teacher/classes/{$class->id}/"]);
            $actions_container->appendChild($manageEnrollmentsButton);
            $gradesButton = DOMHelper::createElement($dom, 'a', 'classes-table-manage-enrollments-button btn approve-enrollment-btn', null, 'Grades', ['href' => "/grades/{$class->id}/"]);
            $actions_container->appendChild($gradesButton);
            $row->appendChild($actionsCell);
        }
        
        return $root;
    }
}
