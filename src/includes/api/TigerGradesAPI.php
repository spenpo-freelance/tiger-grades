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
class TigerGradesAPI {
    /** @var self|null */
    private static $instance = null;
    private $jwt_token_manager;
    private $graph_api_token;
    private $msft_user_id;
    private $gradebook_item_id;
    private $graph_api_url;
    private $msft_tenant_id;
    private $msft_client_id;
    private $msft_client_secret;
    private $client_credentials_url;
    private $api_errors;
    private $classRepository;

    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {
        $this->register_routes();
        $this->api_errors = array();
        $this->classRepository = new TigerClassRepository();
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

    private function getCredentials() {
        // Load credentials when needed
        if (empty($this->msft_tenant_id) || empty($this->msft_client_id) || empty($this->msft_client_secret)) {
            # error_log("TigerGrades API Debug: Loading environment variables");
            $this->msft_tenant_id = getenv('MSFT_TENANT_ID');
            $this->msft_client_id = getenv('MSFT_CLIENT_ID');
            $this->msft_client_secret = getenv('MSFT_CLIENT_SECRET');

            # error_log("TigerGrades API Debug: MSFT_TENANT_ID = " . ($this->msft_tenant_id ? 'set' : 'not set'));
            # error_log("TigerGrades API Debug: MSFT_CLIENT_ID = " . ($this->msft_client_id ? 'set' : 'not set'));
            # error_log("TigerGrades API Debug: MSFT_CLIENT_SECRET = " . ($this->msft_client_secret ? 'set' : 'not set'));

            if (empty($this->msft_tenant_id) || empty($this->msft_client_id) || empty($this->msft_client_secret)) {
                throw new Exception("Required Microsoft Graph API credentials are not set");
            }
        }

        return [
            'tenant_id' => $this->msft_tenant_id,
            'client_id' => $this->msft_client_id,
            'client_secret' => $this->msft_client_secret
        ];
    }

    private function getAccessToken() {
        try {
            $credentials = $this->getCredentials();
            
            $this->client_credentials_url = "https://login.microsoftonline.com/{$credentials['tenant_id']}/oauth2/v2.0/token";
            # error_log("TigerGrades API Debug: Attempting to access token URL: " . $this->client_credentials_url);

            $ch = curl_init();

            // Form data for the POST request
            $postData = http_build_query([
                'client_id' => $credentials['client_id'],
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => $credentials['client_secret'],
                'grant_type' => 'client_credentials'
            ]);

            /*error_log("TigerGrades API Debug: Post data (excluding secret): " . 
                http_build_query([
                    'client_id' => $credentials['client_id'],
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials'
                ])
            );*/

            curl_setopt($ch, CURLOPT_URL, $this->client_credentials_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            
            // Add verbose debugging
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);

            $response = curl_exec($ch);
            
            if ($response === false) {
                # error_log("TigerGrades API Error: Token acquisition failed - " . curl_error($ch));
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                # error_log("TigerGrades API Debug: Curl verbose output - " . $verboseLog);
                curl_close($ch);
                return false;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code !== 200) {
                # error_log("TigerGrades API Error: Token endpoint returned status code: $http_code");
                # error_log("TigerGrades API Debug: Raw response: " . print_r($response, true));
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                # error_log("TigerGrades API Debug: Curl verbose output - " . $verboseLog);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            $data = json_decode($response);
            
            // Check if we got a valid token response
            if (!isset($data->access_token) || !isset($data->expires_in)) {
                # error_log("TigerGrades API Error: Invalid token response - " . json_encode($data));
                return false;
            }

            $this->jwt_token_manager = new JwtTokenManager('tigr_graph_api');
            $this->jwt_token_manager->store_token($data->access_token, $data->expires_in);

            $this->msft_user_id = getenv('MSFT_USER_ID');

            return true;
        } catch (Exception $e) {
            # error_log("TigerGrades API Error: " . $e->getMessage());
            $this->api_errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Registers all REST API routes for TigerGrades.
     * 
     * @return void
     */
    public function register_routes() {
        add_action('rest_api_init', function() {
            register_rest_route('tiger-grades/v1', '/report-card', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_report_card_request'],
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
                'args' => [
                    'sort_by' => [
                        'required' => false,
                        'default' => 'date',
                        'type' => 'string',
                        'enum' => ['date', 'type', 'name']
                    ],
                    'type' => [
                        'required' => false,
                        'default' => 'all',
                        'type' => 'string'
                    ],
                    'enrollment_id' => [
                        'required' => false,
                        'default' => 'english',
                        'type' => 'string'
                    ],
                    'is_teacher' => [
                        'required' => false,
                        'default' => false,
                        'type' => 'boolean'
                    ]
                ]
            ]);
            
            register_rest_route('tiger-grades/v1', '/class-metadata', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_class_metadata_request'],
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
                'args' => [
                    'type' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'enrollment_id' => [
                        'required' => true,
                        'default' => 'english',
                        'type' => 'string'
                    ],
                    'is_teacher' => [
                        'required' => false,
                        'default' => false,
                        'type' => 'boolean'
                    ]
                ]
            ]);
            
            register_rest_route('tiger-grades/v1', '/students', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_students_request'],
                'permission_callback' => function() {
                    $user = wp_get_current_user();
                    $can_access = is_user_logged_in() && in_array('teacher', (array) $user->roles);
                    return $can_access;
                },
            ]);
        });
    }

    /**
     * Handles the report card REST API request.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function handle_students_request($request) {
        $class_id = $request->get_param('class_id');
        
        $status = 200;

        $data = $this->fetchStudents($class_id);
        
        if (is_wp_error($data)) {
            $status = $data->get_error_data()['status'] ?? 500;
        }
        
        return new WP_REST_Response($data, $status);
    }

    /**
     * Handles the report card REST API request.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function handle_class_metadata_request($request) {
        $type = $request->get_param('type');
        $enrollment_id = $request->get_param('enrollment_id');
        $is_teacher = $request->get_param('is_teacher');

        $data = $this->fetchClassMetadata($type, $enrollment_id, $is_teacher);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Handles the report card REST API request.
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function handle_report_card_request($request) {
        $user_id = get_current_user_id();
        $sort_by = $request->get_param('sort_by');
        $type = $request->get_param('type');
        $enrollment_id = $request->get_param('enrollment_id');
        $is_teacher = $request->get_param('is_teacher');

        $data = $this->fetchReportCard($user_id, $enrollment_id, $sort_by, $type, $is_teacher);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Fetches and formats all report card data from the repository.
     * 
     * @param string $type The type of grades to fetch
     * @param string $class_id The ID of the class to fetch report card data for
     * @return array JSON object with the class metadata
     */
    public function fetchStudents($class_id) {
        if (!$this->getAccessToken()) {
            return new WP_Error('api_error', 'Failed to acquire access token');
        }

        $gradebook_id = $this->classRepository->getGradebookId($class_id);
        error_log("TigerGrades API Debug: Gradebook item ID: " . $gradebook_id);
        $this->graph_api_url = "https://graph.microsoft.com/v1.0/users/{$this->msft_user_id}/drive/items/{$gradebook_id}/workbook/worksheets";
        error_log("TigerGrades API Debug: Graph API URL: " . $this->graph_api_url);

        $access_token = $this->jwt_token_manager->get_token();

        $url = "{$this->graph_api_url}/grades/usedRange";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$access_token}"
            // "Range: bytes=0-1048576"  // 1MB chunk size
        ]);

        $response = curl_exec($ch);
        
        // Add error handling for curl execution
        if ($response === false) {
            # error_log("TigerGrades API Error: CURL failed - " . curl_error($ch));
            return new WP_Error('api_error', 'Failed to fetch data from Microsoft Graph API', ['status' => 500]);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            # error_log("TigerGrades API Error: Unexpected HTTP code $http_code - Response: " . $response);
            return new WP_Error('api_error', $response, ['status' => $http_code]);
        }

        curl_close($ch);

        $data = json_decode($response);
        
        // Check for JSON decode errors
        if ($data === null) {
            # error_log("TigerGrades API Error: Failed to decode JSON response - " . json_last_error_msg());
            return new WP_Error('json_error', 'Failed to parse API response');
        }

        // Validate expected data structure
        if (!isset($data->text) || !is_array($data->text)) {
            # error_log("TigerGrades API Error: Unexpected data structure - Missing or invalid 'text' property");
            return new WP_Error('data_error', 'Invalid data structure in API response');
        }

        $result = $data->text;
        
        // Initialize array to store student data
        $students = [];
        
        // Skip the header rows (first 4 rows based on fetchReportCard structure)
        for ($i = 4; $i < count($result); $i++) {
            $row = $result[$i];
            // Check if we have valid student data (ID in column 0 and name in column 1)
            if (!empty($row[0]) && !empty($row[1])) {
                $students[] = [
                    'id' => $row[0],
                    'name' => $row[1]
                ];
            }
        }

        return $students;
    }

    /**
     * Fetches and formats all report card data from the repository.
     * 
     * @param string $type The type of grades to fetch
     * @param string $enrollment_id The ID of the enrollment to fetch report card data for
     * @param bool $is_teacher Whether the user is a teacher
     * @return array JSON object with the class metadata
     * //////////////////////////////////////////////
     * //////// CAVEAT: when $is_teacher is true, $enrollment_id is actually the class_id
     * //////////////////////////////////////////////
     */
    public function fetchClassMetadata($type, $enrollment_id, $is_teacher = false) {
        if (!$this->getAccessToken()) {
            return new WP_Error('api_error', 'Failed to acquire access token');
        }

        $class = null;
        if ($is_teacher) {
            $class = $this->classRepository->getClass($enrollment_id);
        } else {
            $class = $this->classRepository->getClassFromEnrollment($enrollment_id);
        }
        $this->gradebook_item_id = $class->gradebook_id;
        # error_log("TigerGrades API Debug: Gradebook item ID: " . $this->gradebook_item_id);
        $this->graph_api_url = "https://graph.microsoft.com/v1.0/users/{$this->msft_user_id}/drive/items/{$this->gradebook_item_id}/workbook/worksheets";

        $access_token = $this->jwt_token_manager->get_token();

        $url = "{$this->graph_api_url}/categories/usedRange";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$access_token}"
        ]);

        $response = curl_exec($ch);
        
        // Add error handling for curl execution
        if ($response === false) {
            # error_log("TigerGrades API Error: CURL failed - " . curl_error($ch));
            return new WP_Error('api_error', 'Failed to fetch data from Microsoft Graph API');
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            # error_log("TigerGrades API Error: Unexpected HTTP code $http_code - Response: " . $response);
            return new WP_Error('api_error', "Microsoft Graph API returned status code: $http_code");
        }

        curl_close($ch);

        $data = json_decode($response);
        
        // Check for JSON decode errors
        if ($data === null) {
            # error_log("TigerGrades API Error: Failed to decode JSON response - " . json_last_error_msg());
            return new WP_Error('json_error', 'Failed to parse API response');
        }

        // Validate expected data structure
        if (!isset($data->text) || !is_array($data->text)) {
            # error_log("TigerGrades API Error: Unexpected data structure - Missing or invalid 'text' property");
            return new WP_Error('data_error', 'Invalid data structure in API response');
        }

        $result = $this->findWeightByType($data->text, $type);

        return $result;
    }

    /**
     * Fetches and formats all report card data from the repository.
     * 
     * @param int $user_id The ID of the user to fetch report card data for
     * @param string $enrollment_id The ID of the enrollment to fetch report card data for
     * @param string $sort_by The field to sort the grades by
     * @param string $type The type of grades to fetch
     * @param bool $is_teacher Whether the user is a teacher
     * @return array Formatted report card sections
     * //////////////////////////////////////////////
     * //////// CAVEAT: when $is_teacher is true, $enrollment_id is actually the class_id
     * //////////////////////////////////////////////
     */
    public function fetchReportCard($user_id, $enrollment_id, $sort_by = 'date', $type = 'all', $is_teacher = false) {
        if (!$this->getAccessToken()) {
            return new WP_Error('api_error', 'Failed to acquire access token');
        }

        $class = null;
        if ($is_teacher) {
            $class = $this->classRepository->getClass($enrollment_id);
        } else {
            $class = $this->classRepository->getClassFromEnrollment($enrollment_id);
        }
        $this->gradebook_item_id = $class->gradebook_id;
        error_log("TigerGrades API Debug: Gradebook item ID: " . $this->gradebook_item_id);
        $this->graph_api_url = "https://graph.microsoft.com/v1.0/users/{$this->msft_user_id}/drive/items/{$this->gradebook_item_id}/workbook/worksheets";

        $access_token = $this->jwt_token_manager->get_token();

        $url = "{$this->graph_api_url}/grades/usedRange";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$access_token}"
        ]);

        $response = curl_exec($ch);
        
        // Add error handling for curl execution
        if ($response === false) {
            # error_log("TigerGrades API Error: CURL failed - " . curl_error($ch));
            return new WP_Error('api_error', 'Failed to fetch data from Microsoft Graph API');
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            # error_log("TigerGrades API Error: Unexpected HTTP code $http_code - Response: " . $response);
            return new WP_Error('api_error', "Microsoft Graph API returned status code: $http_code");
        }

        curl_close($ch);

        $data = json_decode($response);
        
        // Check for JSON decode errors
        if ($data === null) {
            # error_log("TigerGrades API Error: Failed to decode JSON response - " . json_last_error_msg());
            return new WP_Error('json_error', 'Failed to parse API response');
        }

        // Validate expected data structure
        if (!isset($data->text) || !is_array($data->text)) {
            # error_log("TigerGrades API Error: Unexpected data structure - Missing or invalid 'text' property");
            return new WP_Error('data_error', 'Invalid data structure in API response');
        }

        if ($is_teacher) {
            $dates = $data->text[0];
            $types = array_map('strtolower', $data->text[1]);
            $type_labels = $data->text[1];
            $totals = $data->text[2];
            $names = $data->text[3];

            // Validate required data arrays
            if (empty($dates) || empty($types) || empty($totals) || empty($names)) {
                return new WP_Error('data_error', 'Missing required grade data');
            }

            $results = [];

            // Start from row 4 (index after headers) and process each student
            for ($row = 4; $row < count($data->text); $row++) {
                $student_data = $data->text[$row];
                if (empty($student_data[0])) continue;
                
                $result = $this->createReportCardObject($student_data, $dates, $types, $type_labels, $totals, $names, $type, $sort_by);
                if ($result) {
                    $results[] = $result;
                }
            }

            $report_card_object = new stdClass();
            $report_card_object->reports = $results;

            return $this->appendClassMetadata($report_card_object, $class);
        } else {
            $student_id = $class->student_id;
            if (empty($student_id)) {
                return new WP_Error('user_error', 'Student ID not found');
            }

            $dates = $data->text[0];
            $types = array_map('strtolower', $data->text[1]);
            $type_labels = $data->text[1];
            $totals = $data->text[2];
            $names = $data->text[3];

            // Validate required data arrays
            if (empty($dates) || empty($types) || empty($totals) || empty($names)) {
                return new WP_Error('data_error', 'Missing required grade data');
            }

            // Find student's data array
            $student_data = null;
            foreach ($data->text as $row) {
                if (isset($row[0]) && $row[0] == (string)$student_id) {
                    $student_data = $row;
                    break;
                }
            }

            if (!$student_data) {
                return new WP_Error('data_error', 'Student data not found');
            }

            return $this->appendClassMetadata(
                $this->createReportCardObject($student_data, $dates, $types, $type_labels, $totals, $names, $type, $sort_by),
                $class
            );
        }
    }

    /**
     * Creates a report card object for a single student
     * 
     * @param array $student_data The row of student data
     * @param array $dates Array of dates
     * @param array $types Array of grade types
     * @param array $type_labels Array of grade type labels
     * @param array $totals Array of total possible points
     * @param array $names Array of assignment names
     * @param string $type Type filter
     * @param string $sort_by Sort field
     * @return stdClass|null Report card object or null if invalid data
     */
    private function createReportCardObject($student_data, $dates, $types, $type_labels, $totals, $names, $type, $sort_by) {
        if (empty($student_data[0]) || empty($student_data[1])) {
            return null;
        }

        $result = new stdClass();
        $result->grades = [];
        $result->student_id = $student_data[0];
        $result->name = $student_data[1];
        $result->avg = new stdClass();
        $result->avg->final = $student_data[2];
        $result->avg->{$names[3]} = $student_data[3];
        $result->avg->{$names[4]} = $student_data[4];
        $result->avg->{$names[5]} = $student_data[5];
        $result->avg->{$names[6]} = $student_data[6];

        // Process individual grades
        for ($i = 7; $i < count($dates); $i++) {
            if (empty($dates[$i])) continue;
            if ($type !== 'all' && $types[$i] !== $type) continue;

            $grade = new stdClass();
            $grade->date = $dates[$i];
            $grade->type = $types[$i];
            $grade->type_label = $type_labels[$i];
            $grade->total = $totals[$i];
            $grade->name = $names[$i];
            $grade->score = $student_data[$i];

            $result->grades[] = $grade;
        }

        // Sort grades
        usort($result->grades, function($a, $b) use ($sort_by) {
            if ($sort_by === 'date') {
                $date_a = DateTime::createFromFormat('n/j/Y', $a->date);
                $date_b = DateTime::createFromFormat('n/j/Y', $b->date);
                
                if ($date_a > $date_b) return -1;
                if ($date_a < $date_b) return 1;
                return 0;
            }
            return strcmp($a->$sort_by, $b->$sort_by);
        });

        return $result;
    }

    private function appendClassMetadata($result, $class) {
        $result->teacher = $class->teacher_name;
        $result->class = $class->title;
        return $result;
    }

    // Helper function to find weight by type
    private function findWeightByType($data, $type) {
        if (!is_array($data) || count($data) < 2) {
            return null;
        }

        $return = new stdClass();
        $return->type = $type;
        $return->type_label = null;
        $return->weight = null;
        
        // Skip the header row (index 0)
        for ($i = 1; $i < count($data); $i++) {
            if (isset($data[$i][1]) && strtolower($data[$i][1]) === strtolower($type)) {
                $return->type_label = $data[$i][1];
                $return->weight = $data[$i][2];
                break;
            }
        }
        
        return $return;
    }
}
