<?php
namespace Spenpo\TigerGrades\API;

use WP_REST_Response;
use WP_Error;
use stdClass;
use DateTime;
use Spenpo\TigerGrades\API\JwtTokenManager;
use Exception;
/**
 * Handles all TigerGrades API functionality and route registration.
 * 
 * @package Spenpo\TigerGrades
 * @since 1.0.0
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

    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {
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

    private function getCredentials() {
        // Load credentials when needed
        if (empty($this->msft_tenant_id) || empty($this->msft_client_id) || empty($this->msft_client_secret)) {
            error_log("TigerGrades API Debug: Loading environment variables");
            $this->msft_tenant_id = getenv('MSFT_TENANT_ID');
            $this->msft_client_id = getenv('MSFT_CLIENT_ID');
            $this->msft_client_secret = getenv('MSFT_CLIENT_SECRET');

            error_log("TigerGrades API Debug: MSFT_TENANT_ID = " . ($this->msft_tenant_id ? 'set' : 'not set'));
            error_log("TigerGrades API Debug: MSFT_CLIENT_ID = " . ($this->msft_client_id ? 'set' : 'not set'));
            error_log("TigerGrades API Debug: MSFT_CLIENT_SECRET = " . ($this->msft_client_secret ? 'set' : 'not set'));

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
            error_log("TigerGrades API Debug: Attempting to access token URL: " . $this->client_credentials_url);

            $ch = curl_init();

            // Form data for the POST request
            $postData = http_build_query([
                'client_id' => $credentials['client_id'],
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => $credentials['client_secret'],
                'grant_type' => 'client_credentials'
            ]);

            error_log("TigerGrades API Debug: Post data (excluding secret): " . 
                http_build_query([
                    'client_id' => $credentials['client_id'],
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials'
                ])
            );

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
                error_log("TigerGrades API Error: Token acquisition failed - " . curl_error($ch));
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                error_log("TigerGrades API Debug: Curl verbose output - " . $verboseLog);
                curl_close($ch);
                return false;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code !== 200) {
                error_log("TigerGrades API Error: Token endpoint returned status code: $http_code");
                error_log("TigerGrades API Debug: Raw response: " . print_r($response, true));
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                error_log("TigerGrades API Debug: Curl verbose output - " . $verboseLog);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            $data = json_decode($response);
            
            // Check if we got a valid token response
            if (!isset($data->access_token) || !isset($data->expires_in)) {
                error_log("TigerGrades API Error: Invalid token response - " . json_encode($data));
                return false;
            }

            $this->jwt_token_manager = new JwtTokenManager('tigr_graph_api');
            $this->jwt_token_manager->store_token($data->access_token, $data->expires_in);

            $this->msft_user_id = getenv('MSFT_USER_ID');
            $this->gradebook_item_id = getenv('GRADEBOOK_ITEM_ID');
            $this->graph_api_url = "https://graph.microsoft.com/v1.0/users/{$this->msft_user_id}/drive/items/{$this->gradebook_item_id}/workbook/worksheets";

            return true;
        } catch (Exception $e) {
            error_log("TigerGrades API Error: " . $e->getMessage());
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
                    'class_id' => [
                        'required' => false,
                        'default' => 'english',
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
    public function handle_report_card_request($request) {
        $user_id = get_current_user_id();
        $sort_by = $request->get_param('sort_by');
        $type = $request->get_param('type');
        $class_id = $request->get_param('class_id');

        $data = $this->fetchReportCard($user_id, $sort_by, $type, $class_id);
        
        return new WP_REST_Response($data, 200);
    }

    /**
     * Fetches and formats all report card data from the repository.
     * 
     * @param int $user_id The ID of the user to fetch report card data for
     * @param string $sort_by The field to sort the grades by
     * @param string $type The type of grades to fetch
     * @param string $class_id The ID of the class to fetch report card data for
     * @return array Formatted report card sections
     */
    public function fetchReportCard($user_id, $sort_by = 'date', $type = 'all', $class_id = 'english') {
        if (!$this->getAccessToken()) {
            return new WP_Error('api_error', 'Failed to acquire access token');
        }

        $access_token = $this->jwt_token_manager->get_token();

        $url = "{$this->graph_api_url}/{$class_id}/usedRange";

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
            error_log("TigerGrades API Error: CURL failed - " . curl_error($ch));
            return new WP_Error('api_error', 'Failed to fetch data from Microsoft Graph API');
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200) {
            error_log("TigerGrades API Error: Unexpected HTTP code $http_code - Response: " . $response);
            return new WP_Error('api_error', "Microsoft Graph API returned status code: $http_code");
        }

        curl_close($ch);

        $data = json_decode($response);
        
        // Check for JSON decode errors
        if ($data === null) {
            error_log("TigerGrades API Error: Failed to decode JSON response - " . json_last_error_msg());
            return new WP_Error('json_error', 'Failed to parse API response');
        }

        // Validate expected data structure
        if (!isset($data->text) || !is_array($data->text)) {
            error_log("TigerGrades API Error: Unexpected data structure - Missing or invalid 'text' property");
            return new WP_Error('data_error', 'Invalid data structure in API response');
        }

        $student_id = get_user_meta($user_id, 'tigr_std_id', true);
        if (empty($student_id)) {
            error_log("TigerGrades API Error: No student ID found for user $user_id");
            return new WP_Error('user_error', 'Student ID not found');
        }

        $dates = $data->text[0];
        $types = $data->text[1];
        $totals = $data->text[2];
        $names = $data->text[3];

        // Validate required data arrays
        if (empty($dates) || empty($types) || empty($totals) || empty($names)) {
            error_log("TigerGrades API Error: Missing required data arrays - " . 
                     "Dates: " . json_encode($dates) . 
                     ", Types: " . json_encode($types) . 
                     ", Totals: " . json_encode($totals) . 
                     ", Names: " . json_encode($names));
            return new WP_Error('data_error', 'Missing required grade data');
        }

        // Initialize result object
        $result = new stdClass();
        $result->grades = [];

        // Find student's data array
        $student_data = null;
        foreach ($data->text as $row) {
            if (isset($row[0]) && $row[0] == (string)$student_id) {
                $student_data = $row;
                break;
            }
        }

        if (!$student_data) {
            error_log("TigerGrades API Error: No data found for student ID $student_id");
            return new WP_Error('data_error', 'Student data not found');
        }

        $result->name = $student_data[1];
        $result->avg = new stdClass();
        $result->avg->final = $student_data[2];
        $result->avg->{$names[3]} = $student_data[3];
        $result->avg->{$names[4]} = $student_data[4];
        $result->avg->{$names[5]} = $student_data[5];
        $result->avg->{$names[6]} = $student_data[6];
        
        // Start from index 7 to skip header columns
        for ($i = 7; $i < count($dates); $i++) {
            // Skip if date is empty
            if (empty($dates[$i])) continue;

            if ($type !== 'all' && $types[$i] !== $type) continue;

            $grade = new stdClass();
            $grade->date = $dates[$i];
            $grade->type = $types[$i];
            $grade->total = $totals[$i];
            $grade->name = $names[$i];
            $grade->score = $student_data[$i];

            $result->grades[] = $grade;
        }

        // Sort the grades array based on the specified field
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
}