<?php
/**
 * Manages version information for the Tiger Grades plugin.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.3
 */
namespace Spenpo\TigerGrades\Utilities;

class VersionManager {
    /**
     * The option name used to store the database version
     */
    const DB_VERSION_OPTION = 'tigr_db_version';
    
    /**
     * Constructor that automatically checks and syncs the version
     */
    public function __construct() {
        // Add an action to check and sync the version on init
        add_action('init', [$this, 'checkAndSyncVersion']);
    }
    
    /**
     * Check if the database version needs to be updated and sync if needed
     */
    public function checkAndSyncVersion() {
        if (self::needsUpdate()) {
            // Only update the database if we're in the admin area
            // This prevents unnecessary database operations on frontend requests
            if (is_admin()) {
                // Run the database update
                $this->updateDatabase();
            }
            
            // Always sync the version
            self::syncWithPluginVersion();
        }
    }
    
    /**
     * Update the database schema if needed
     */
    private function updateDatabase() {
        // Get the path to the seed.sql file
        $script_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'data/seed.sql';
        
        // Use the DatabaseManager to execute the script
        // We need to include the DatabaseManager if it's not already included
        if (!class_exists('\\Spenpo\\TigerGrades\\Repositories\\DatabaseManager')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'repositories/DatabaseManager.php';
        }
        
        // Execute the database script
        \Spenpo\TigerGrades\Repositories\DatabaseManager::executeScript($script_path, 'init');
    }
    
    /**
     * Get the current database version from WordPress options
     * 
     * @return string The current database version
     */
    public static function getCurrentVersion(): string {
        return get_option(self::DB_VERSION_OPTION, '0');
    }
    
    /**
     * Get the plugin version from the plugin file
     * 
     * @return string The plugin version
     */
    public static function getPluginVersion(): string {
        // This is a more reliable way to get the plugin version
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $plugin_data = get_plugin_data(plugin_dir_path(dirname(dirname(__FILE__))) . 'tiger-grades.php');
        return $plugin_data['Version'] ?? '0.0.0';
    }
    
    /**
     * Update the database version
     * 
     * @param string $version The new version to set
     * @return bool True if the update was successful, false otherwise
     */
    public static function updateVersion(string $version): bool {
        return update_option(self::DB_VERSION_OPTION, $version);
    }
    
    /**
     * Increment the database version to match the plugin version
     * 
     * @return bool True if the update was successful, false otherwise
     */
    public static function syncWithPluginVersion(): bool {
        return self::updateVersion(self::getPluginVersion());
    }
    
    /**
     * Check if the database needs to be updated
     * 
     * @return bool True if the database needs to be updated, false otherwise
     */
    public static function needsUpdate(): bool {
        $current_version = self::getCurrentVersion();
        $plugin_version = self::getPluginVersion();
        
        return version_compare($current_version, $plugin_version, '<');
    }
}

// Initialize the VersionManager to trigger the automatic version check
new VersionManager(); 