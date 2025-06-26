<?php
namespace Spenpo\TigerGrades\API;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use stdClass;
use DateTime;
use Exception;
use DOMDocument;
use Spenpo\TigerGrades\Repositories\TigerGeneralRepository;
use Spenpo\TigerGrades\Utilities\LanguageManager;
/**
 * Handles all TigerGrades API functionality and route registration.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class GeneralAPI {
    /** @var self|null */
    private static $instance = null;
    private $api_errors;
    private $generalRepository;
    private $languageManager;
    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {
        $this->register_routes();
        $this->api_errors = array();
        $this->generalRepository = new TigerGeneralRepository();
        $this->languageManager = LanguageManager::getInstance();
        $this->languageManager->registerRestApiLanguageDetection('tiger-grades');
    }

    /**
     * Gets the singleton instance of the API class.
     * 
     * @return self The singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registers all REST API routes for TigerGrades.
     * 
     * @return void
     */
    public function register_routes() {
        add_filter('rest_authentication_errors', function($result) {
            if (true === $result || is_wp_error($result)) {
                return $result;
            }
            
            if (isset($_SERVER['REQUEST_URI']) && 
                strpos($_SERVER['REQUEST_URI'], '/wp-json/tiger-grades/v1/shortcode') !== false) {
                return true;
            }
            
            return $result;
        });

        add_action('rest_api_init', function() {
            register_rest_route('tiger-grades/v1', '/shortcode', array(
                'methods' => 'POST',
                'callback' => [$this, 'rest_do_shortcode'],
                'permission_callback' => function() {
                    return true;
                }
            ));
        });
    }
    
    /**
     * Renders a shortcode with proper UTF-8 support for multibyte characters (like Chinese).
     * This method ensures that shortcodes containing UTF-8 characters are properly handled
     * when importing into DOMDocument structures.
     *
     * @param DOMDocument $dom The target DOM document to import into
     * @param string $shortcode The shortcode string to render
     * @return array Array of DOMNode objects ready to be appended to the target container
     * 
     * @example
     * // Basic usage - get nodes to append manually
     * $api = GeneralAPI::getInstance();
     * $nodes = $api->renderShortcodeWithUTF8Support($dom, '[contact_form id="123"]');
     * foreach ($nodes as $node) {
     *     $container->appendChild($node);
     * }
     * 
     * @example
     * // For WordPress forms with UTF-8 content
     * $shortcode = '[user_registration_form id="456"]';
     * $nodes = $api->renderShortcodeWithUTF8Support($dom, $shortcode);
     * 
     * @see appendShortcodeWithUTF8Support() For automatic appending to container
     */
    public function renderShortcodeWithUTF8Support(DOMDocument $dom, string $shortcode): array {
        // Render the shortcode and get the HTML
        $shortcode_html = do_shortcode($shortcode);
        
        // Create a temporary DOM with proper UTF-8 encoding
        $temp = new DOMDocument('1.0', 'UTF-8');
        $temp->encoding = 'UTF-8';
        
        // Add meta charset and wrap in proper HTML structure for better encoding handling
        $html_wrapper = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $shortcode_html . '</body></html>';
        
        // Use mb_convert_encoding to ensure proper UTF-8 encoding
        $html_wrapper = mb_convert_encoding($html_wrapper, 'HTML-ENTITIES', 'UTF-8');
        
        // Load with proper flags to preserve UTF-8
        @$temp->loadHTML($html_wrapper, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA);
        
        // Import and return the nodes
        $nodes = [];
        if ($temp->getElementsByTagName('body')->length > 0) {
            $body = $temp->getElementsByTagName('body')->item(0);
            foreach ($body->childNodes as $child) {
                $nodes[] = $dom->importNode($child, true);
            }
        } else {
            // Fallback: import the document element directly
            $nodes[] = $dom->importNode($temp->documentElement, true);
        }
        
        return $nodes;
    }
    
    /**
     * Renders a shortcode with proper UTF-8 support and automatically appends it to a container.
     * This is a convenience method that combines renderShortcodeWithUTF8Support with automatic appending.
     *
     * @param DOMDocument $dom The target DOM document
     * @param DOMElement $container The container element to append the rendered shortcode to
     * @param string $shortcode The shortcode string to render
     * @return void
     * 
     * @example
     * // Simplest usage - render and append in one call
     * $api = GeneralAPI::getInstance();
     * $form_container = DOMHelper::createElement($dom, 'div', 'form-wrapper');
     * $api->appendShortcodeWithUTF8Support($dom, $form_container, '[contact_form id="123"]');
     * 
     * @example
     * // Perfect for WordPress registration forms with Chinese/UTF-8 content
     * $registration_container = DOMHelper::createElement($dom, 'div', 'registration-container');
     * $shortcode = '[user_registration_form id="' . $form_id . '"]';
     * $api->appendShortcodeWithUTF8Support($dom, $registration_container, $shortcode);
     * 
     * @example
     * // Works with any WordPress shortcode that might contain UTF-8
     * $content_area = DOMHelper::createElement($dom, 'div', 'content');
     * $api->appendShortcodeWithUTF8Support($dom, $content_area, '[gallery id="456"]');
     * $api->appendShortcodeWithUTF8Support($dom, $content_area, '[custom_form lang="zh-CN"]');
     * 
     * @see renderShortcodeWithUTF8Support() For manual node handling
     */
    public function appendShortcodeWithUTF8Support(DOMDocument $dom, \DOMElement $container, string $shortcode): void {
        $nodes = $this->renderShortcodeWithUTF8Support($dom, $shortcode);
        
        // Append all imported nodes to the container
        foreach ($nodes as $node) {
            $container->appendChild($node);
        }
    }
    
    public function rest_do_shortcode(WP_REST_Request $request) {
        $shortcode = $request->get_param('shortcode');
        
        // Validate shortcode format
        if (!preg_match('/^\[user_registration_form id="(\d+)"\]$/', $shortcode, $matches)) {
            return new WP_Error(
                'invalid_shortcode',
                'Invalid shortcode format. Only user_registration_form shortcode is allowed.',
                array('status' => 400)
            );
        }
        
        // Optional: Validate form IDs if you want to restrict to specific forms
        $allowed_form_ids = []; // Your known registration form IDs
        $allowed_form_ids = array_merge($allowed_form_ids, $this->generalRepository->getUserRegistrationFormIds('subscriber'));
        $allowed_form_ids = array_merge($allowed_form_ids, $this->generalRepository->getUserRegistrationFormIds('teacher'));
        error_log(print_r($allowed_form_ids, true));
        if (!in_array($matches[1], $allowed_form_ids)) {
            return new WP_Error(
                'invalid_form_id',
                'Invalid form ID.',
                array('status' => 400)
            );
        }

        // Capture the current state of enqueued scripts
        global $wp_scripts;
        $initial_scripts = $wp_scripts->queue;
        
        // Render the shortcode
        $rendered = do_shortcode($shortcode);
        
        // Get the newly enqueued scripts
        $new_scripts = array_diff($wp_scripts->queue, $initial_scripts);
        $script_data = array();
        
        foreach ($new_scripts as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $script = $wp_scripts->registered[$handle];
                $script_data[] = array(
                    'handle' => $handle,
                    'src' => $script->src,
                    'deps' => $script->deps,
                    'ver' => $script->ver
                );
            }
        }
        
        return array(
            'rendered' => $rendered,
            'scripts' => $script_data
        );
    }
}
