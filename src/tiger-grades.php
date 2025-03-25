<?php
/**
 * Plugin Name:       Tiger Grades
 * Plugin URI:        https://github.com/spenpo-freelance/tiger-grades
 * Description:       grades for your students
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Version:           0.0.5
 * Author:            spenpo
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tiger-grades
 *
 * @package tiger-grades
 */

use Spenpo\TigerGrades\Utilities\RewriteManager;

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

require_once TIGER_GRADES_PATH . 'includes/components/TeacherComponents.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/VersionManager.php';
require_once TIGER_GRADES_PATH . 'includes/repositories/DatabaseManager.php';
require_once TIGER_GRADES_PATH . 'includes/repositories/TigerClassRepository.php';
require_once TIGER_GRADES_PATH . 'includes/api/TigerGradesAPI.php';
require_once TIGER_GRADES_PATH . 'includes/api/TeachersAPI.php';
require_once TIGER_GRADES_PATH . 'includes/api/JwtTokenManager.php';
require_once TIGER_GRADES_PATH . 'includes/api/GeneralAPI.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/DOMHelper.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ReportCard.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/RegisterClass.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/TeacherDashboard.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/TeacherClasses.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/EnrollClass.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ClassManagement.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ParentClasses.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/Version.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/Registration.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/RewriteManager.php';

// Register activation hook with full namespace
register_activation_hook(__FILE__, ['Spenpo\TigerGrades\Repositories\DatabaseManager', 'createDatabase']);

// Register deactivation hook with full namespace
register_deactivation_hook(__FILE__, ['Spenpo\TigerGrades\Repositories\DatabaseManager', 'teardownDatabase']);

// Register styles
function enqueue_tiger_grades_styles() {
    wp_enqueue_style(
        'tiger-grades-styles',
        plugins_url('style.css', __FILE__),
        array(),
        '1.0.5'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_tiger_grades_styles');
