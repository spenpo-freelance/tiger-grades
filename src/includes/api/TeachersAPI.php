<?php
namespace Spenpo\TigerGrades\API;

use WP_REST_Response;
use WP_Error;
use stdClass;
use DateTime;
use Spenpo\TigerGrades\API\JwtTokenManager;
use Exception;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
/**
 * Handles all TigerGrades API functionality and route registration.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class TeachersAPI {
    /** @var self|null */
    private static $instance = null;
    private $api_errors;
    private $classRepository;
    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {
        $this->classRepository = new TigerClassRepository();
        $this->register_routes();
        $this->api_errors = array();
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
        add_action('rest_api_init', function() {
            register_rest_route('tiger-grades/v1', '/create-class', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_create_class_request'],
                'permission_callback' => function() {
                    $user = wp_get_current_user();
                    $can_access = is_user_logged_in() && in_array('teacher', (array) $user->roles);
                    return $can_access;
                },
                'args' => [
                    'title' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ]);
            register_rest_route('tiger-grades/v1', '/create-enrollment', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_create_enrollment_request'],
                'permission_callback' => function() {
                    $user = wp_get_current_user();
                    $can_access = is_user_logged_in() && in_array('subscriber', (array) $user->roles);
                    return $can_access;
                },
                'args' => [
                    'enrollment_code' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'student_name' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'optional_message' => [
                        'required' => false,
                        'type' => 'string'
                    ]
                ]
            ]);
            register_rest_route('tiger-grades/v1', '/approve-enrollment', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_approve_enrollment_request'],
                'permission_callback' => function() {
                    $user = wp_get_current_user();
                    $can_access = is_user_logged_in() && in_array('subscriber', (array) $user->roles);
                    return $can_access;
                },
                'args' => [
                    'enrollment_id' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'student_id' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ]);
        });
    }

    /**
     * Handles the report card REST API request.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function handle_approve_enrollment_request($request) {
        $user_id = get_current_user_id();
        $enrollment_id = $request->get_param('enrollment_id');
        $student_id = $request->get_param('student_id');
        $data = $this->classRepository->approveEnrollment($enrollment_id, $student_id);
        $response = [
            'success' => $this->api_errors ? false : true,
            'data' => $data,
            'errors' => $this->api_errors
        ];
        
        return new WP_REST_Response($response, $this->api_errors ? 500 : 200);
    }

    /**
     * Handles the report card REST API request.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function handle_create_enrollment_request($request) {
        $user_id = get_current_user_id();
        $enrollment_code = $request->get_param('enrollment_code');
        $student_name = $request->get_param('student_name');
        $optional_message = $request->get_param('optional_message');
        $class = $this->classRepository->getClassByEnrollmentCode($enrollment_code);
        $data = $this->classRepository->createEnrollment($class[0]->id, $user_id, $student_name, $optional_message);
        $response = [
            'success' => $this->api_errors ? false : true,
            'data' => $data,
            'errors' => $this->api_errors
        ];
        
        return new WP_REST_Response($response, $this->api_errors ? 500 : 200);
    }

    /**
     * Recursively creates a class until a unique enrollment code is found.
     * 
     * @param string $title The title of the class
     * @param int $user_id The ID of the user creating the class
     * @return array|false The class data or false if an error occurs
     */
    private function create_class($title, $user_id) {
        $enrollment_code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        try {
            $data = $this->classRepository->createClass($title, $user_id, $enrollment_code);
            return $data;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            if (str_contains($error_message, 'Duplicate entry') && 
                str_contains($error_message, 'tigr_classes.enrollment_code_UNIQUE')) {
                return $this->create_class($title, $user_id);
            } else {
                $this->api_errors[] = $error_message;
            }
            return false;
        }
    }

    /**
     * Handles the report card REST API request.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function handle_create_class_request($request) {
        $user_id = get_current_user_id();
        $title = $request->get_param('title');
        $data = $this->create_class($title, $user_id);
        $response = [
            'success' => $this->api_errors ? false : true,
            'data' => $data,
            'errors' => $this->api_errors
        ];
        
        return new WP_REST_Response($response, $this->api_errors ? 500 : 200);
    }
}
