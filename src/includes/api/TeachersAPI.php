<?php
namespace Spenpo\TigerGrades\API;

use WP_REST_Response;
use WP_Error;
use stdClass;
use DateTime;
use Exception;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;
use Spenpo\TigerGrades\Services\MicrosoftAuthService;
use Spenpo\TigerGrades\Services\HttpService;
use Spenpo\TigerGrades\Utilities\SecurityManager;
use Spenpo\TigerGrades\Utilities\LanguageManager;
/**
 * Handles all TigerGrades API functionality and route registration.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class TeachersAPI {
    /** @var self|null */
    private static $instance = null;
    private $auth_service;
    private $gradebook_item_id;
    private $graph_api_url;
    private $api_errors;
    private $classRepository;
    private $httpService;
    private $securityManager;
    private $plugin_domain;
    private $languageManager;
    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {
        $this->classRepository = new TigerClassRepository();
        $this->securityManager = new SecurityManager();
        $this->auth_service = new MicrosoftAuthService('tigr_functions');
        $this->httpService = HttpService::getInstance();
        $this->languageManager = LanguageManager::getInstance();
        $this->register_routes();
        $this->api_errors = array();
        $this->plugin_domain = $this->languageManager->getPluginDomain();
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
        // Register language detection for tiger-grades API namespace
        $this->languageManager->registerRestApiLanguageDetection('tiger-grades');
        
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
                    ],
                    'type' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'num_students' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'num_categories' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'description' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'message' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'start_date' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'end_date' => [
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
                    $can_access = is_user_logged_in() && in_array('teacher', (array) $user->roles);
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
            register_rest_route('tiger-grades/v1', '/reject-enrollment', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_reject_enrollment_request'],
                'permission_callback' => function() {
                    $user = wp_get_current_user();
                    $can_access = is_user_logged_in() && in_array('teacher', (array) $user->roles);
                    return $can_access;
                },
                'args' => [
                    'enrollment_id' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ]);
            register_rest_route('tiger-grades/v1', '/update-class', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_update_class_request'],
                'permission_callback' => [$this->securityManager, 'check_route_permission'],
                'args' => [
                    'gradebook_id' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'class_id' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'gradebook_url' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'folder_id' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => function($value) {
                            return $value === null ? null : sanitize_text_field($value);
                        }
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
    public function handle_update_class_request($request) {
        $class_id = $request->get_param('class_id');
        $gradebook_id = $request->get_param('gradebook_id');
        $folder_id = $request->get_param('folder_id');
        $gradebook_url = $request->get_param('gradebook_url');
        $data = $this->classRepository->updateClass($class_id, $gradebook_id, $folder_id, $gradebook_url);
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
    public function handle_reject_enrollment_request($request) {
        $user_id = get_current_user_id();
        $enrollment_id = $request->get_param('enrollment_id');
        $data = $this->classRepository->rejectEnrollment($enrollment_id);
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
        $data = $this->classRepository->createEnrollment($class->id, $user_id, $student_name, $optional_message);
        $data->message = __('Successfully enrolled in class', $this->plugin_domain) . ': ' . $class->title;
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
    private function create_class($title, $user_id, $class_type_id, $num_students, $num_categories, $description, $message, $start_date, $end_date) {
        $enrollment_code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        $teacher = get_user_by('id', $user_id);
        try {
            $teachers_folder_id = get_user_meta($user_id, 'teachers_folder_id', true);
            $teachers_folder_name = get_user_meta($user_id, 'teachers_folder_name', true);
            if (!$teachers_folder_name) {
                $teachers_folder_name = str_replace(' ', '_', $teacher->display_name);
                update_user_meta($user_id, 'teachers_folder_name', $teachers_folder_name);
            }
            $data = $this->classRepository->createClass($title, $user_id, $enrollment_code, $class_type_id, $num_students, $num_categories, $description, $message, $start_date, $end_date);
            $data->teachers_folder_name = $teachers_folder_name;
            $data->teacher_email = $teacher->user_email;
            $data->teachers_folder_id = $teachers_folder_id;
            return $data;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            if (str_contains($error_message, 'Duplicate entry') && 
                str_contains($error_message, 'tigr_classes.enrollment_code_UNIQUE')) {
                return $this->create_class($title, $user_id, $class_type_id, $num_students, $num_categories, $description, $message, $start_date, $end_date);
            } else {
                $this->api_errors[] = $error_message;
            }
            return false;
        }
    }

    /**
     * Makes a POST request to the class registration microservice.
     * 
     * @param string $teachers_folder_name The name of the teacher's folder
     * @param string $gradebook_name The name of the gradebook
     * @param string $email The teacher's email
     * @return array|WP_Error The response from the microservice or WP_Error on failure
     */
    private function call_class_registration_microservice($teachers_folder_name, $teachers_folder_id, $gradebook_name, $email, $class_id) {
        if (!$this->auth_service->getAccessToken(getenv("TIGER_GRADES_AZURE_FUNCTIONS_AUDIENCE"))) {
            return new WP_Error('api_error', 'Failed to acquire access token');
        }

        error_log('Calling class registration microservice');
        $url = getenv("TIGER_GRADES_AZURE_FUNCTIONS_BASE_URL") . "/api/client-function";
        $body = array(
            'function_name' => 'class-registration-orchestrator',
            'data' => array(
                'teacher_name' => $teachers_folder_name,
                'folder_id' => $teachers_folder_id,
                'gradebook_name' => $gradebook_name,
                'email' => $email,
                'class_id' => $class_id
            )
        );

        $access_token = $this->auth_service->getToken();

        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token
        );

        error_log('Request URL: ' . $url);
        error_log('Request body: ' . json_encode($body));
        error_log('Request headers: ' . print_r($headers, true));

        $response = $this->httpService->postJson($url, $headers, $body);
        
        if (is_wp_error($response)) {
            $this->api_errors[] = 'Failed to call class registration service: ' . $response->get_error_message();
            return false;
        }

        // if (!isset($response['status']) || $response['status'] !== 202) {
        //     $this->api_errors[] = 'Class registration service returned error code: ' . ($response['status'] ?? 'unknown');
        //     return false;
        // }

        return $response;
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
        $type = $request->get_param('type');
        $num_students = $request->get_param('num_students');
        $num_categories = $request->get_param('num_categories');
        $description = $request->get_param('description');
        $message = $request->get_param('message');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');
        $data = $this->create_class($title, $user_id, $type, $num_students, $num_categories, $description, $message, $start_date, $end_date);
        $data->message = __('The following class has been created', $this->plugin_domain) . ': ' . $data->title;
        $response['data'] = $data;

        if (empty($this->api_errors)) {
            $teachers_folder_name = $data->teachers_folder_name;
            $teachers_folder_id = $data->teachers_folder_id;
            $gradebook_name = $data->gradebook_file_name;
            $email = $data->teacher_email;
            $class_id = $data->id;
            try {
                $microservice_response = $this->call_class_registration_microservice($teachers_folder_name, $teachers_folder_id, $gradebook_name, $email, $class_id);
                if ($microservice_response) {
                    $response['microservice_response'] = $microservice_response;
                }
            } catch (Exception $e) {
                $this->api_errors[] = 'Failed to call class registration service: ' . $e->getMessage();
            }
        }
        
        $response['success'] = $this->api_errors ? false : true;
        $response['errors'] = $this->api_errors;
        return new WP_REST_Response($response, 200);
    }
}
