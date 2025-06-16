<?php
/**
 * Plugin Name:       Tiger Grades
 * Plugin URI:        https://github.com/spenpo-freelance/tiger-grades
 * Description:       Education intelligence for teachers, parents and teaching organizations
 * Requires at least: 6.8
 * Requires PHP:      7.2
 * Version:           0.1.1
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

// Include autoloader
require_once TIGER_GRADES_PATH . 'vendor/Parsedown.php';

require_once TIGER_GRADES_PATH . 'includes/components/TeacherComponents.php';
require_once TIGER_GRADES_PATH . 'includes/components/GeneralComponents.php';
require_once TIGER_GRADES_PATH . 'includes/repositories/TranslationRepository.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/LanguageManager.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/StringTranslationsManager.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/VersionManager.php';
require_once TIGER_GRADES_PATH . 'includes/repositories/DatabaseManager.php';
require_once TIGER_GRADES_PATH . 'includes/repositories/TigerClassRepository.php';
require_once TIGER_GRADES_PATH . 'includes/repositories/GeneralRepository.php';
require_once TIGER_GRADES_PATH . 'includes/api/TigerGradesAPI.php';
require_once TIGER_GRADES_PATH . 'includes/api/TeachersAPI.php';
require_once TIGER_GRADES_PATH . 'includes/services/JwtTokenManager.php';
require_once TIGER_GRADES_PATH . 'includes/services/MicrosoftAuthService.php';
require_once TIGER_GRADES_PATH . 'includes/services/HttpService.php';
require_once TIGER_GRADES_PATH . 'includes/api/GeneralAPI.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/DOMHelper.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/SecurityManager.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ReportCard.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/RegisterClass.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/TeacherDashboard.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/TeacherClasses.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/EnrollClass.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ClassManagement.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/ParentClasses.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/Version.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/Registration.php';
require_once TIGER_GRADES_PATH . 'includes/shortcodes/InfoBar.php';
require_once TIGER_GRADES_PATH . 'includes/utilities/RewriteManager.php';

// Enqueue scripts
function enqueue_tiger_grades_scripts() {
    // Only enqueue the hCaptcha script if we're on a relevant User Registration page
    if (is_ur_login_page() || 
        is_ur_account_page() || 
        is_ur_edit_account_page() || 
        is_ur_lost_password_page()) {
        wp_enqueue_script(
            'tiger-grades-hcaptcha',
            TIGER_GRADES_URL . 'js/hcaptcha-size.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_tiger_grades_scripts');

// Register styles
function enqueue_tiger_grades_styles() {
    wp_enqueue_style(
        'tiger-grades-styles',
        plugins_url('css/styles.css', __FILE__),
        array(),
        '1.0.8'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_tiger_grades_styles');

// Register string translations on plugin activation
register_activation_hook(__FILE__, function() {
    // Register string translations
    Spenpo\TigerGrades\Utilities\StringTranslationsManager::registerTranslations();
});
