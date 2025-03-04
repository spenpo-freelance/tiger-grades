<?php
/**
 * Manages database operations for the resume plugin.
 * 
 * @package Spenpo\TigerGrades
 * @since 0.0.0
 */
namespace Spenpo\TigerGrades\Repositories;

use Spenpo\TigerGrades\Utilities\VersionManager;

class DatabaseManager {
    protected static function requireUpgradeFile() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }

    /**
     * Gets the table prefix, falling back to a default if needed
     * 
     * @return string The table prefix to use
     */
    protected static function getTablePrefix(): string {
        global $wpdb;
        
        // First try $wpdb->prefix
        if (!empty($wpdb->prefix)) {
            return $wpdb->prefix;
        }
        
        // Then try the test prefix constant
        if (defined('WP_TESTS_TABLE_PREFIX')) {
            return WP_TESTS_TABLE_PREFIX;
        }
        
        // Finally, fall back to wp_ as a last resort
        return 'wp_';
    }

    /**
     * Executes an SQL script file.
     * 
     * @param string $scriptPath Path to the SQL script file
     * @param string $type Type of execution ('query' or 'init')
     * @return array Result array with 'success' boolean and 'message'/'error' string
     */
    public static function executeScript(string $scriptPath, string $type = 'query'): array {
        global $wpdb;
        
        static::requireUpgradeFile();
        
        if (!file_exists($scriptPath)) {
            // error_log("SQL file not found at: " . $scriptPath);
            return [
                'success' => false,
                'error' => "SQL file not found: {$scriptPath}"
            ];
        }

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        global $wp_filesystem;
        
        // Initialize WP_Filesystem
        if ( WP_Filesystem() ) {
            $plugin_dir = plugin_dir_path( __FILE__ );
        
            // Check if the file exists
            if ( $wp_filesystem->exists( $scriptPath ) ) {
                // Read the content
                $sql = $wp_filesystem->get_contents( $scriptPath );
        
                if ( $sql !== false ) {
                    try {
                        $prefix = static::getTablePrefix();
                        $sql = str_replace('{$wpdb->prefix}', esc_sql($prefix), $sql);
                        
                        // Split SQL into individual statements
                        $statements = array_filter(
                            array_map('trim', explode(';', $sql)),
                            'strlen'
                        );
                        
                        // Initialize variables array instead of using $GLOBALS
                        $variables = [];
                        
                        // Execute each statement separately
                        foreach ($statements as $statement) {
                            // Store results of SELECT queries for variable replacement
                            if (stripos(trim($statement), 'SELECT') === 0) {
                                // Check for INTO @variable syntax
                                if (preg_match('/\s+INTO\s+@(\w+)/i', $statement, $matches)) {
                                    $varName = $matches[1];
                                    // Remove the INTO @variable part for the actual query
                                    $select_sql = preg_replace('/\s+INTO\s+@\w+/i', '', $statement);
                                    $result = $wpdb->get_var($select_sql);
                                    $variables["@{$varName}"] = $result;
                                }
                            } else {
                                // Replace any @variables in non-SELECT statements
                                foreach ($variables as $key => $value) {
                                    if ($value !== null) {
                                        $statement = str_replace($key, $wpdb->prepare('%s', $value), $statement);
                                    }
                                }
                                $result = $wpdb->query($statement);
                            }
                            
                            if ($wpdb->last_error) {
                                throw new \Exception($wpdb->last_error);
                            }
                        }
                        
                        return [
                            'success' => true,
                            'message' => "Script executed successfully"
                        ];
                    } catch (Exception $e) {
                        // error_log("Error in executeScript: " . $e->getMessage());
                        return [
                            'success' => false,
                            'error' => "Error executing script: " . $e->getMessage()
                        ];
                    }
                } else {
                    // error_log("Failed to read SQL file");
                    return [
                        'success' => false,
                        'error' => "Failed to read SQL file"
                    ];
                }
            } else {
                echo 'File not found.';
            }
        } else {
            echo 'Failed to initialize WP_Filesystem.';
        }
    }

    /**
     * Creates or updates the database schema.
     * 
     * @return void
     */
    public static function createDatabase() {
        // Always recreate database in debug/development environment
        if (defined('WP_DEBUG') && WP_DEBUG || VersionManager::needsUpdate()) {
            // New absolute path using plugin root directory
            $script_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'data/seed.sql';
            
            $result = self::executeScript($script_path, 'init');
            
            if ($result['success']) {
                VersionManager::syncWithPluginVersion();
            }
        }
    }

    /**
     * Creates or updates the database schema.
     * 
     * @return void
     */
    public static function createTestDatabase() {
        $script_path = plugin_dir_path(dirname(__FILE__)) . '../data/test-schema.sql';
        self::executeScript($script_path, 'init');
    }

    /**
     * Removes all plugin tables from the database.
     * 
     * @return void
     */
    public static function teardownDatabase() {
        $script_path = plugin_dir_path(dirname(__FILE__)) . '../data/teardown.sql';
        $result = self::executeScript($script_path, 'query');
        if ($result['success']) {
            delete_option('tigr_db_version');
        }
    }
} 