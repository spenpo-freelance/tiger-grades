<?php
/**
 * Plugin Name:       Tiger Grades
 * Plugin URI:        https://github.com/spope851/tiger-grades
 * Description:       store, serve, and display your report card data
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Version:           1.0.0
 * Author:            spenpo
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tiger-grades
 *
 * @package tiger-grades
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants - only if not already defined (for testing compatibility)
if (!defined('TIGER_GRADES_PATH')) {
    define('TIGER_GRADES_PATH', plugin_dir_path(__FILE__));
}
if (!defined('TIGER_GRADES_URL')) {
    define('TIGER_GRADES_URL', plugin_dir_url(__FILE__));
}

// Include the DatabaseManager class
require_once TIGER_GRADES_PATH . 'includes/repositories/DatabaseManager.php';

// Register activation hook with full namespace
register_activation_hook(__FILE__, ['Spenpo\TigerGrades\Repositories\DatabaseManager', 'createDatabase']);

// Register deactivation hook with full namespace
register_deactivation_hook(__FILE__, ['Spenpo\TigerGrades\Repositories\DatabaseManager', 'teardownDatabase']);

// Load dependencies
require_once TIGER_GRADES_PATH . 'includes/api/TigerGradesAPI.php';
require_once TIGER_GRADES_PATH . 'includes/api/JwtTokenManager.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ReportCard.php';

// Register styles
function enqueue_tiger_grades_styles() {
    wp_enqueue_style(
        'tiger-grades-styles',
        plugins_url('style.css', __FILE__),
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_tiger_grades_styles');

// // Move these constant definitions up and add checks
// if (!defined('SPENPO_RESUME_VERSION')) {
//     define('SPENPO_RESUME_VERSION', '1.0.0');
// }
// if (!defined('SPENPO_RESUME_MINIMUM_WP_VERSION')) {
//     define('SPENPO_RESUME_MINIMUM_WP_VERSION', '6.6');
// }
// if (!defined('SPENPO_RESUME_PLUGIN_DIR')) {
//     define('SPENPO_RESUME_PLUGIN_DIR', plugin_dir_path(__FILE__));
// }
// if (!defined('SPENPO_RESUME_PLUGIN_URL')) {
//     define('SPENPO_RESUME_PLUGIN_URL', plugin_dir_url(__FILE__));
// }
