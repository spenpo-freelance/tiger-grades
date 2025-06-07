<?php
namespace Spenpo\TigerGrades\API;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use stdClass;
use DateTime;
use Exception;
use Spenpo\TigerGrades\Repositories\TigerGeneralRepository;
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
    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {
        $this->register_routes();
        $this->api_errors = array();
        $this->generalRepository = new TigerGeneralRepository();
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
        $allowed_form_ids[] = $this->generalRepository->getUserRegistrationFormId('subscriber');
        $allowed_form_ids[] = $this->generalRepository->getUserRegistrationFormId('teacher');
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
