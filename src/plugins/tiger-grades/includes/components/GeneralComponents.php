<?php
namespace Spenpo\TigerGrades\Components;

use DOMDocument;
use DOMElement;
use WP_Error;
use WP_REST_Response;
use Spenpo\TigerGrades\Utilities\DOMHelper;
use Spenpo\TigerGrades\Utilities\LanguageManager;
/**
 * Handles the [tigr_report_card] shortcode functionality.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class GeneralComponents {
    private $languageManager;
    private $plugin_domain;
    /**
     * Constructor initializes the API connection and registers the shortcode.
     */

    public function __construct() {
        $this->languageManager = LanguageManager::getInstance();
        $this->plugin_domain = $this->languageManager->getPluginDomain();
    }

    /**
     * Renders the report card content as HTML.
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the report card
     */
    public function createUnauthenticatedMessage($dom, $root) {
        $not_logged_in_message = DOMHelper::createElement($dom, 'div', 'not-logged-in-message');
        $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-logged-in-message-text', null, __('Please', $this->plugin_domain) . ' '));
        $account_url = $this->languageManager->getTranslatedRoute('/' . $this->languageManager->getTranslatedRouteSegment('account'));
        $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'a', 'not-logged-in-message-text', null, __('log in', $this->plugin_domain), ['href' => $account_url]));
        $not_logged_in_message->appendChild(DOMHelper::createElement($dom, 'span', 'not-logged-in-message-text', null, ' ' . __('to view your child\'s grades', $this->plugin_domain) . '.'));
        $root->appendChild($not_logged_in_message);
        return $dom->saveHTML();
    }
}
