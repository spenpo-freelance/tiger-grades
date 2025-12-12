<?php
namespace Spenpo\TigerGrades\Services;

use Exception;
use Spenpo\TigerGrades\Services\JwtTokenManager;
use Spenpo\TigerGrades\Services\HttpService;

/**
 * Handles Microsoft Graph API authentication and token management.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class MicrosoftAuthService {
    private $jwt_token_manager;
    private $msft_user_id;
    private $msft_tenant_id;
    private $msft_client_id;
    private $msft_client_secret;
    private $client_credentials_url;
    private $api_errors;
    private $microsoft_api_name;
    private $http_service;
    /**
     * Constructor
     */
    public function __construct($microsoft_api_name) {
        $this->api_errors = array();
        $this->microsoft_api_name = $microsoft_api_name;
        $this->http_service = HttpService::getInstance();
    }

    /**
     * Gets the Microsoft Graph API credentials
     * 
     * @return array The credentials array
     * @throws Exception If required credentials are not set
     */
    private function getCredentials() {
        // Load credentials when needed
        if (empty($this->msft_tenant_id) || empty($this->msft_client_id) || empty($this->msft_client_secret)) {
            $this->msft_tenant_id = getenv('MSFT_TENANT_ID');
            $this->msft_client_id = getenv('MSFT_CLIENT_ID');
            $this->msft_client_secret = getenv('MSFT_CLIENT_SECRET');

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

    /**
     * Fetches an access token for the Microsoft Graph API.
     * 
     * @param string $scope The scope of the access token
     * @return bool True if the access token was successfully fetched, false otherwise
     */
    public function getAccessToken($scope = 'https://graph.microsoft.com/.default') {
        try {
            $credentials = $this->getCredentials();
            
            $this->client_credentials_url = "https://login.microsoftonline.com/{$credentials['tenant_id']}/oauth2/v2.0/token";

            // Form data for the POST request
            $postData = http_build_query([
                'client_id' => $credentials['client_id'],
                'scope' => $scope,
                'client_secret' => $credentials['client_secret'],
                'grant_type' => 'client_credentials'
            ]);

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $response = $this->http_service->makeHttpRequest(
                $this->client_credentials_url,
                'POST',
                $headers,
                $postData
            );

            if (is_wp_error($response)) {
                $this->api_errors[] = $response->get_error_message();
                return false;
            }

            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                $this->api_errors[] = "HTTP request failed with status code: $http_code";
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            
            // Check if we got a valid token response
            if (!isset($data->access_token) || !isset($data->expires_in)) {
                $this->api_errors[] = "Invalid token response from Microsoft Graph API";
                return false;
            }

            $this->jwt_token_manager = new JwtTokenManager($this->microsoft_api_name);
            $this->jwt_token_manager->store_token($data->access_token, $data->expires_in);

            $this->msft_user_id = getenv('MSFT_USER_ID');

            return true;
        } catch (Exception $e) {
            $this->api_errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Gets the stored access token
     * 
     * @return string|null The access token or null if not available
     */
    public function getToken() {
        if (!$this->jwt_token_manager) {
            $this->jwt_token_manager = new JwtTokenManager($this->microsoft_api_name);
        }
        return $this->jwt_token_manager->get_token();
    }

    /**
     * Gets the Microsoft User ID
     * 
     * @return string The Microsoft User ID
     */
    public function getMsftUserId() {
        return $this->msft_user_id;
    }

    /**
     * Gets any API errors that occurred
     * 
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->api_errors;
    }
} 