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
        ///////////////////////////////////
        //////// FOR TESTING ONLY ////////
        ////////////////////////////////
        remove_filter('rest_authentication_errors', 'rest_authentication_errors');
        add_filter('rest_authentication_errors', function($result) {
            if (true === $result || is_wp_error($result)) {
                return $result;
            }
            
            return true;
        });

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
            register_rest_route('tiger-grades/v1', '/update-class', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_update_class_request'],
                'permission_callback' => function() {
                    return true;
                },
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
        error_log('Calling class registration microservice');
        $url = getenv("SERVERLESS_BASE_URL") . "/api/client-function";
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

        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        error_log('Request URL: ' . $url);
        error_log('Request body: ' . json_encode($body));
        error_log('Request args: ' . print_r($args, true));

        $response = wp_remote_post($url, $args);
        error_log('Response: ' . print_r($response, true));
        error_log('Response code: ' . wp_remote_retrieve_response_code($response));
        error_log('Response body: ' . wp_remote_retrieve_body($response));
        error_log('Response headers: ' . print_r(wp_remote_retrieve_headers($response), true));

        if (is_wp_error($response)) {
            $this->api_errors[] = 'Failed to call class registration service: ' . $response->get_error_message();
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 202) {
            $this->api_errors[] = 'Class registration service returned error code: ' . $response_code;
            return false;
        }

        return $response_body;
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
