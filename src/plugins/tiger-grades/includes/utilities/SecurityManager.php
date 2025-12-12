<?php

namespace Spenpo\TigerGrades\Utilities;
use Spenpo\TigerGrades\Repositories\TigerGeneralRepository;

class SecurityManager {
    private $generalRepository;

    public function __construct() {
        $this->generalRepository = new TigerGeneralRepository();
    }
    
    /**
     * Generic permission callback that checks if a user has access to a specific route
     * based on the junction table between users and routes.
     * 
     * @param \WP_REST_Request $request The request object
     * @return bool Whether the user has permission to access the route
     */
    public function check_route_permission($request) {
        // Get the current user and route
        $user_id = get_current_user_id();
        $route = $request->get_route();
        
        // error_log('Permission check started for route: ' . $route);
        // error_log('Current user ID: ' . $user_id);
        
        // If no user is logged in, deny access
        if (!$user_id) {
            // error_log('Permission denied: No user logged in');
            return false;
        }
        
        // Query the junction table to check if the user has access to this route
        $has_access = $this->generalRepository->userIsAllowedForSecuredRoute($route, $user_id);
        
        // Log the permission check for debugging
        error_log(sprintf(
            'Permission check for user %d on route %s: %s',
            $user_id,
            $route,
            $has_access ? 'GRANTED' : 'DENIED'
        ));
        
        return $has_access;
    }
}