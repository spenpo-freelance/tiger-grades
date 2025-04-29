<?php
namespace Spenpo\TigerGrades\Repositories;

use Exception as Exception;

/**
 * Handles database operations for resume data.
 * 
 * @package Spenpo\TigerGrades
 * @since 1.0.0
 */
class TigerGeneralRepository {
    /** @var wpdb WordPress database instance */
    private $wpdb;

    /**
     * Constructor initializes the WordPress database connection.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get the users who are allowed to access a given secured route.
     * 
     * @param string $route The route to check access for.
     * @return array Array of user IDs who are allowed to access the route.
     * @throws Exception When database error occurs
     */
    public function userIsAllowedForSecuredRoute($route, $user_id) {
        try {
            $allowedQuery = $this->wpdb->prepare(
                "SELECT COUNT(*) as count
                FROM {$this->wpdb->prefix}tigr_secured_routes_junction srj
                JOIN {$this->wpdb->prefix}tigr_feature_lookup fl ON srj.feature_lookup_id = fl.id
                WHERE srj.user_id = %d 
                AND fl.title = %s",
                $user_id,
                $route
            );
            
            $results = $this->wpdb->get_results($allowedQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results[0]->count > 0;
        } catch (Exception $e) {
            throw $e;
        }
    }
} 