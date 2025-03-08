<?php
namespace Spenpo\TigerGrades\Shortcodes;

use Spenpo\TigerGrades\API\TigerGradesAPI;
use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;

/**
 * Handles the [tigr_report_card] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class ReportCardShortcode {
    /** @var TigerGradesAPI Instance of the TigerGrades API */
    private $api;

    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->api = $this->getAPI();
        
        // Define default attributes for the shortcode
        add_shortcode('tigr_report_card', function($atts) {
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
     * Creates a new DOM element with specified attributes.
     * 
     * @param DOMDocument $dom       The DOM document instance
     * @param string      $tag       HTML tag name
     * @param string      $class     CSS class name
     * @param string|null $id        Optional element ID
     * @param string|null $text      Optional text content
     * @param array       $attributes Optional additional attributes
     * 
     * @return DOMElement The created element
     */
    private function createElement(DOMDocument $dom, $tag, $class, $id = null, $text = null, $attributes = []) {
        $element = $dom->createElement($tag);
        $element->setAttribute('class', $class);
        
        if ($id) {
            $element->setAttribute('id', $class."-$id");
        }

        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, $value);
        }
        
        if ($text) {
            $element_text = $dom->createTextNode($text);
            $element->appendChild($element_text);
        }

        return $element;
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
    public function render($atts) {
        $user_id = get_current_user_id();

        $dom = new DOMDocument('1.0', 'utf-8');
        // Create a root container for all sections
        $root = $this->createElement($dom, 'div', 'report-card-container');
        // Add data attributes for JS to use
        $root->setAttribute('data-user-id', $user_id);
        $root->setAttribute('data-type', $atts['type']);
        $root->setAttribute('data-class-id', $atts['class_id']);
        $root->setAttribute('data-semester', $atts['semester']);
        $dom->appendChild($root);

        if ($user_id) {
            $loading = $this->createElement($dom, 'div', 'loading-message');
            $loading->appendChild($this->createElement($dom, 'p', 'loading-text', null, 'Loading content...'));
            $root->appendChild($loading);

            // Enqueue the JavaScript
            wp_enqueue_script(
                'tiger-grades-report-card',
                plugins_url('tiger-grades/js/report-card.js', dirname(__FILE__, 3)),
                array('jquery'),
                '1.0.6',
                true
            );

            // Localize the script with necessary data
            wp_localize_script('tiger-grades-report-card', 'tigerGradesData', array(
                'apiUrl' => rest_url('tiger-grades/v1/report-card'),
                'metadataUrl' => rest_url('tiger-grades/v1/class-metadata'),
                'nonce' => wp_create_nonce('wp_rest')
            ));

            return $dom->saveHTML();

            // Get data using the singleton instance
            // $report_card = $this->api->fetchReportCard($user_id, 'date', $atts['type'], $atts['class_id']);

            // $student_name = $report_card->name;

            // $student_name_container = $this->createElement($dom, 'div', 'student-name-container');
            // $student_name_container->appendChild($this->createElement($dom, 'div', 'student-name', null, $student_name));
            // $root->appendChild($student_name_container);

            // if ($atts['type'] == 'all') {
            //     $average_container = $this->createElement($dom, 'div', 'average-container');
            //     $average_container->appendChild($this->createElement($dom, 'h4', 'average', null, "Overall Grade: " . $report_card->avg->final));
            //     $average_container->appendChild($this->createElement($dom, 'h4', 'average', null, "Letter Grade: " . $this->getLetterGrade((float)$report_card->avg->final)));
            //     $root->appendChild($average_container);
            // } else {
            //     $average_container = $this->createElement($dom, 'div', 'average-container');
            //     $average_container->appendChild($this->createElement($dom, 'h4', 'average', null, "Average: " . $report_card->avg->{$atts['type']}));
            //     $root->appendChild($average_container);
            // }

            // $grade_table = $this->createElement($dom, 'table', 'grade-table');
            // $root->appendChild($grade_table);
            
            // $grade_table_header = $this->createElement($dom, 'thead', 'grade-table-header');
            // $grade_table_header->appendChild($this->createElement($dom, 'tr', 'grade-table-header-row'));
            // $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Date'));
            // $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Assignment'));
            // if ($atts['type'] == 'all') {
            //     $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Type'));
            //     $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Percentage'));
            //     $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Letter Grade'));
            // } else {
            //     $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Possible Points'));
            //     $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Points Earned'));
            //     $grade_table_header->appendChild($this->createElement($dom, 'th', 'grade-table-header-cell', null, 'Percentage'));
            // }
            // $grade_table->appendChild($grade_table_header);

            // foreach ($report_card->grades as $grade) {
            //     $grade_row = $this->createElement($dom, 'tr', 'grade-row');
            //     $grade_row->appendChild($this->createElement($dom, 'td', 'grade-date', null, $grade->date));
            //     $grade_row->appendChild($this->createElement($dom, 'td', 'grade-name', null, $grade->name));
            //     $grade_percentage = round((float)$grade->score / (float)$grade->total * 100);
            //     if ($atts['type'] == 'all') {
            //         $type_td = $this->createElement($dom, 'td', 'grade-type');
            //         $type_td->appendChild($this->createElement($dom, 'a', 'grade-type-text', null, $grade->type, ['href' => $grade->type]));
            //         $grade_row->appendChild($type_td);
            //         $grade_letter = $this->getLetterGrade($grade_percentage);
            //         $grade_row->appendChild($this->createElement($dom, 'td', 'grade-percentage', null, $grade_percentage . '%'));
            //         $grade_row->appendChild($this->createElement($dom, 'td', 'grade-letter', null, $grade_letter));
            //     } else {
            //         $grade_row->appendChild($this->createElement($dom, 'td', 'grade-total', null, $grade->total));
            //         $grade_row->appendChild($this->createElement($dom, 'td', 'grade-score', null, $this->processScore($grade->score)));
            //         $grade_row->appendChild($this->createElement($dom, 'td', 'grade-percentage', null, $grade_percentage . '%'));
            //     }
            //     $grade_table->appendChild($grade_row);
            // }
            
            /**
             * Filters the final HTML output of the resume.
             * 
             * @since 0.0.0
             * 
             * @param string $html     The generated HTML
             * @param array  $sections The resume sections data
             * @return string The filtered HTML
             */
            // $html = apply_filters('tigr_report_card_html_output', $dom->saveHTML(), $report_card);
            
            // return $html;
        } else {
            $not_logged_in_message = $this->createElement($dom, 'div', 'not-logged-in-message');
            $not_logged_in_message->appendChild($this->createElement($dom, 'span', 'not-logged-in-message-text', null, 'Please '));
            $not_logged_in_message->appendChild($this->createElement($dom, 'a', 'not-logged-in-message-text', null, 'log in', ['href' => '/my-account']));
            $not_logged_in_message->appendChild($this->createElement($dom, 'span', 'not-logged-in-message-text', null, ' to view your child\'s grades.'));
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
new ReportCardShortcode(); 