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
     * Get the value of a feature flag.
     * 
     * @param string $name The name of the feature flag.
        * @return bool The value of the feature flag.
     * @throws Exception When database error occurs
     */
    public function getFeatureFlag($title) {
        try {
            $allowedQuery = $this->wpdb->prepare(
                "SELECT *
                 FROM `wp_tigr_feature_lookup`
                 WHERE title = %s",
                $title
            );
            
            $results = $this->wpdb->get_results($allowedQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }

            return $results[0]->status === 'active';
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get the users who are allowed to access a given secured route.
     * 
     * @param string $route The route to check access for.
     * @return array Array of user registration form IDs.
     * @throws Exception When database error occurs
     */
    public function getUserRegistrationFormIds($user_role) {
        try {
            $allowedQuery = $this->wpdb->prepare(
                "SELECT p.ID
                 FROM `wp_posts` p
                 LEFT JOIN `wp_postmeta` pmdf ON p.ID = pmdf.post_id AND pmdf.meta_key = 'user_registration_form_setting_default_user_role' 
                 WHERE p.post_type = 'user_registration'
                 AND p.post_status = 'publish'
                 AND pmdf.meta_value = %s",
                $user_role
            );
            
            $results = $this->wpdb->get_results($allowedQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return array_column($results, 'ID');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get the users who are allowed to access a given secured route.
     * 
     * @param string $route The route to check access for.
     * @return int The ID of the user registration form.
     * @throws Exception When database error occurs
     */
    public function getUserRegistrationFormId($user_role, $class_id) {
        try {
            $allowedQuery = $this->wpdb->prepare(
                "SELECT p.ID
                 FROM `wp_posts` p
                 LEFT JOIN `wp_postmeta` pmdf ON p.ID = pmdf.post_id AND pmdf.meta_key = 'user_registration_form_setting_default_user_role' 
                 LEFT JOIN `wp_postmeta` pmcc ON p.ID = pmcc.post_id AND pmcc.meta_key = 'user_registration_form_custom_class' 
                 WHERE p.post_type = 'user_registration'
                 AND p.post_status = 'publish'
                 AND pmdf.meta_value = %s
                 AND pmcc.meta_value = %s",
                $user_role,
                $class_id
            );
            
            $results = $this->wpdb->get_results($allowedQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results[0]->ID;
        } catch (Exception $e) {
            throw $e;
        }
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