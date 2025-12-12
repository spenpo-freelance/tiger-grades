<?php
namespace Spenpo\TigerGrades\Services;

use WP_Error;

/**
 * Handles all HTTP requests using WordPress HTTP API
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
class HttpService {
    /** @var self|null */
    private static $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     * Use getInstance() instead.
     */
    private function __construct() {}

    /**
     * Gets the singleton instance of the HttpService class.
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
     * Makes an HTTP request using WordPress HTTP API
     * 
     * @param string $url The URL to request
     * @param string $method The HTTP method (GET, POST, etc.)
     * @param array $headers Request headers
     * @param array $body Request body (for POST/PUT requests)
     * @return array|WP_Error The response or error
     */
    public function makeHttpRequest($url, $method = 'GET', $headers = array(), $body = null) {
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        );

        if ($body !== null) {
            $args['body'] = $body;
        }

        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'HTTP request failed: ' . $response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            return new WP_Error('api_error', "API returned status code: $http_code");
        }

        return $response;
    }

    /**
     * Makes an HTTP request and returns the JSON-decoded response
     * 
     * @param string $url The URL to request
     * @param string $method The HTTP method (GET, POST, etc.)
     * @param array $headers Request headers
     * @param array $body Request body (for POST/PUT requests)
     * @return mixed|WP_Error The JSON-decoded response or error
     */
    public function makeJsonRequest($url, $method = 'GET', $headers = array(), $body = null) {
        $headers['Content-Type'] = 'application/json';
        if ($body !== null) {
            $body = json_encode($body);
        }

        $response = $this->makeHttpRequest($url, $method, $headers, $body);
        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if ($data === null) {
            return new WP_Error('json_error', 'Failed to parse API response');
        }

        return $data;
    }

    /**
     * Makes a GET request and returns the JSON-decoded response
     * 
     * @param string $url The URL to request
     * @param array $headers Request headers
     * @return mixed|WP_Error The JSON-decoded response or error
     */
    public function getJson($url, $headers = array()) {
        return $this->makeJsonRequest($url, 'GET', $headers);
    }

    /**
     * Makes a POST request and returns the JSON-decoded response
     * 
     * @param string $url The URL to request
     * @param array $headers Request headers
     * @param array $body Request body
     * @return mixed|WP_Error The JSON-decoded response or error
     */
    public function postJson($url, $headers = array(), $body = null) {
        return $this->makeJsonRequest($url, 'POST', $headers, $body);
    }
} 