<?php
/**
 * Manages database operations for the resume plugin.
 * 
 * @package Spenpo\TigerGrades
 * @since 1.0.0
 */
namespace Spenpo\TigerGrades\Repositories;

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
                        
                        // error_log("SQL content after prefix replacement: " . $sql);
                
                        $statements = array_filter(
                            array_map(
                                'trim',
                                explode(';', $sql)
                            ),
                            'strlen'
                        );
                
                        foreach ($statements as $statement) {
                            // error_log("Executing statement: " . $statement);
                            if ($type === 'init') {
                                $result = dbDelta($statement);
                                // error_log("dbDelta result: " . print_r($result, true));
                            } else if ($type === 'query') {
                                $result = $wpdb->query($statement);
                                // error_log("wpdb->query result: " . print_r($result, true));
                            } else {
                                // error_log("Unknown type: " . $type);
                            }
                            
                            if (is_wp_error($result)) {
                                throw new Exception($result->get_error_message());
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
        $current_version = get_option('tigr_db_version', '0');
        $plugin_version = '1.0.0';
        
        // Always recreate database in debug/development environment
        if (defined('WP_DEBUG') && WP_DEBUG || version_compare($current_version, $plugin_version, '<')) {
            $script_path = plugin_dir_path(dirname(__FILE__)) . '../data/seed.sql';
            $result = self::executeScript($script_path, 'init');
            
            if ($result['success']) {
                update_option('tigr_db_version', $plugin_version);
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
        self::executeScript($script_path, 'query');
    }
} 