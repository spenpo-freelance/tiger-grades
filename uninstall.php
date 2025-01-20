<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up all plugin data
require_once plugin_dir_path(__FILE__) . 'includes/repositories/DatabaseManager.php';
DatabaseManager::teardownDatabase();

// Remove plugin options
delete_option('tigr_db_version');
?> 