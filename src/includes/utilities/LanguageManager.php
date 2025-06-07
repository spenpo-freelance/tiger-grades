<?php

namespace Spenpo\TigerGrades\Utilities;

use Spenpo\TigerGrades\Repositories\TranslationRepository;

/**
 * Centralized language constants and configuration management
 * 
 * This class provides a single source of truth for language-related
 * constants and configuration throughout the Tiger Grades application.
 * 
 * @package Spenpo\TigerGrades\Utilities
 * @since 1.0.0
 */
class LanguageManager {
    /**
     * Plugin domain for translations
     * @var string
     */
    public $plugin_domain;
    
    /**
     * Default language code for the application
     * @var string
     */
    public $defaultLanguage;
    
    /**
     * All supported languages with their configurations
     * @var array
     */
    public $supportedLanguages;
    
    /**
     * All supported languages with their configurations
     * @var array
     */
    public $translation_strings;

    /**
     * Translation repository instance
     * @var TranslationRepository
     */
    public $translation_repository;
    
    /**
     * Constructor - initializes language constants
     */
    public function __construct() {
        $this->initializeConstants();
        $this->translation_repository = new TranslationRepository();
    }
    
    /**
     * Initialize all language constants and configurations
     */
    private function initializeConstants() {
        $this->plugin_domain = 'tiger-grades';
        // Set default language
        $this->defaultLanguage = 'en';
        
        // Define supported languages
        $this->supportedLanguages = [
            'en' => [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'locale' => 'en_US',
                'direction' => 'ltr'
            ],
            'zh' => [
                'code' => 'zh',
                'name' => 'Chinese',
                'native_name' => '中文',
                'locale' => 'zh_CN',
                'direction' => 'ltr'
            ]
        ];

        $this->translation_strings = [
            // shortcodes/Version.php
            [
                'key' => 'tiger_grades_version',
                'translations' => [
                    'en' => 'Version',
                    'zh' => '版本',
                ]
            ],
            // shortcodes/ParentClasses.php
            [
                'key' => 'grades_page_title',
                'translations' => [
                    'en' => 'Your child is in the following classes',
                    'zh' => '您的学生正在以下班级',
                ]
            ],
            [
                'key' => 'teacher',
                'translations' => [
                    'en' => 'Teacher',
                    'zh' => '老师',
                ]
            ],
            [
                'key' => 'show_past_classes',
                'translations' => [
                    'en' => 'Show past classes',
                    'zh' => '显示过去班级',
                ]
            ],
            [
                'key' => 'hide_past_classes',
                'translations' => [
                    'en' => 'Hide past classes',
                    'zh' => '隐藏过去班级',
                ]
            ],
            // components/TeacherComponents.php
            [
                'key' => 'manage_enrollments',
                'translations' => [
                    'en' => 'Manage',
                    'zh' => '管理',
                ]
            ],
            [
                'key' => 'grades',
                'translations' => [
                    'en' => 'Grades',
                    'zh' => '成绩',
                ]
            ],
            [
                'key' => 'view_gradebook',
                'translations' => [
                    'en' => 'View',
                    'zh' => '查看',
                ]
            ],
            [
                'key' => 'classes',
                'translations' => [
                    'en' => 'Classes',
                    'zh' => '班级',
                ]
            ],
            [
                'key' => 'class',
                'translations' => [
                    'en' => 'Class',
                    'zh' => '班级',
                ]
            ],
            [
                'key' => 'status',
                'translations' => [
                    'en' => 'Status',
                    'zh' => '状态',
                ]
            ],
            [
                'key' => 'enrolled',
                'translations' => [
                    'en' => 'Enrolled',
                    'zh' => '已注册',
                ]
            ],
            [
                'key' => 'code',
                'translations' => [
                    'en' => 'Code',
                    'zh' => '代码',
                ]
            ],
            [
                'key' => 'actions',
                'translations' => [
                    'en' => 'Actions',
                    'zh' => '操作',
                ]
            ],
            [
                'key' => 'register_class',
                'translations' => [
                    'en' => 'Register Class',
                    'zh' => '注册班级',
                ]
            ],
            [
                'key' => 'active',
                'translations' => [
                    'en' => 'active',
                    'zh' => '活跃',
                ]
            ],
            [
                'key' => 'pending',
                'translations' => [
                    'en' => 'pending',
                    'zh' => '待定',
                ]
            ],
            [
                'key' => 'copy_enrollment_code',
                'translations' => [
                    'en' => 'Copy enrollment code',
                    'zh' => '复制注册代码',
                ]
            ],
            [
                'key' => 'copy_enrollment_url',
                'translations' => [
                    'en' => 'Copy enrollment URL',
                    'zh' => '复制注册URL',
                ]
            ],
            [
                'key' => 'show_qr_code',
                'translations' => [
                    'en' => 'Show QR code',
                    'zh' => '显示二维码',
                ]
            ],
            [
                'key' => 'enrollment_qr_code',
                'translations' => [
                    'en' => 'Enrollment QR Code',
                    'zh' => '注册二维码',
                ]
            ],
            [
                'key' => 'close',
                'translations' => [
                    'en' => 'Close',
                    'zh' => '关闭',
                ]
            ],
            // shortcodes/ClassManagement.php
            [
                'key' => 'enrollments',
                'translations' => [
                    'en' => 'Enrollments',
                    'zh' => '注册',
                ]
            ],
            [
                'key' => 'student',
                'translations' => [
                    'en' => 'Student',
                    'zh' => '学生',
                ]
            ],
            [
                'key' => 'parent',
                'translations' => [
                    'en' => 'Parent',
                    'zh' => '家长',
                ]
            ],
            [
                'key' => 'enrolled_as',
                'translations' => [
                    'en' => 'Enrolled as',
                    'zh' => '已注册为',
                ]
            ],
            [
                'key' => 'name_in_gradebook',
                'translations' => [
                    'en' => 'Name in gradebook',
                    'zh' => '成绩单上的名字',
                ]
            ],
            [
                'key' => 'approved',
                'translations' => [
                    'en' => 'approved',
                    'zh' => '已批准',
                ]
            ],
            [
                'key' => 'rejected',
                'translations' => [
                    'en' => 'rejected',
                    'zh' => '已拒绝',
                ]
            ],
            [
                'key' => 'approve',
                'translations' => [
                    'en' => 'Approve',
                    'zh' => '批准',
                ]
            ],
            [
                'key' => 'change',
                'translations' => [
                    'en' => 'Change',
                    'zh' => '更改',
                ]
            ],
            [
                'key' => 'reject',
                'translations' => [
                    'en' => 'Reject',
                    'zh' => '拒绝',
                ]
            ],
            [
                'key' => 'remove',
                'translations' => [
                    'en' => 'Remove',
                    'zh' => '移除',
                ]
            ],
            [
                'key' => 'view_message',
                'translations' => [
                    'en' => 'View message',
                    'zh' => '查看消息',
                ]
            ],
            [
                'key' => 'approve_enrollment',
                'translations' => [
                    'en' => 'Approve enrollment',
                    'zh' => '批准注册',
                ]
            ],
            [
                'key' => 'choose_student',
                'translations' => [
                    'en' => 'Choose a student from your gradebook to link with this parent\'s account',
                    'zh' => '从您的成绩单中选择一个学生与该家长的账户关联',
                ]
            ],
            [
                'key' => 'select_student',
                'translations' => [
                    'en' => 'Select a student',
                    'zh' => '选择一个学生',
                ]
            ],
            [
                'key' => 'cancel',
                'translations' => [
                    'en' => 'Cancel',
                    'zh' => '取消',
                ]
            ],
            [
                'key' => 'confirm',
                'translations' => [
                    'en' => 'Confirm',
                    'zh' => '确认',
                ]
            ],
            // shortcodes/ReportCard.php
            [
                'key' => 'please_select_student',
                'translations' => [
                    'en' => 'Please select a student to view their grades',
                    'zh' => '请选择一个学生来查看他们的成绩',
                ]
            ],
            [
                'key' => 'date',
                'translations' => [
                    'en' => 'Date',
                    'zh' => '日期',
                ]
            ],
            [
                'key' => 'task',
                'translations' => [
                    'en' => 'Task',
                    'zh' => '任务',
                ]
            ],
            [
                'key' => 'type',
                'translations' => [
                    'en' => 'Type',
                    'zh' => '类型',
                ]
            ],
            [
                'key' => 'percent',
                'translations' => [
                    'en' => 'Percent',
                    'zh' => '百分比',
                ]
            ],
            [
                'key' => 'grade',
                'translations' => [
                    'en' => 'Grade',
                    'zh' => '成绩',
                ]
            ],
            [
                'key' => 'max',
                'translations' => [
                    'en' => 'Max',
                    'zh' => '最大值',
                ]
            ],
            [
                'key' => 'earned',
                'translations' => [
                    'en' => 'Earned',
                    'zh' => '已获得',
                ]
            ],
            [
                'key' => 'export_all',
                'translations' => [
                    'en' => 'Export all',
                    'zh' => '导出所有',
                ]
            ],
            [
                'key' => 'export_as_pdf',
                'translations' => [
                    'en' => 'Export as PDF',
                    'zh' => '导出为PDF',
                ]
            ],
            [
                'key' => 'select_student',
                'translations' => [
                    'en' => 'Select student',
                    'zh' => '选择学生',
                ]
            ],
            [
                'key' => 'no_grades_found',
                'translations' => [
                    'en' => 'No grades found for this student',
                    'zh' => '没有找到该学生的成绩',
                ]
            ],
            [
                'key' => 'overall_grade',
                'translations' => [
                    'en' => 'Overall Grade',
                    'zh' => '总成绩',
                ]
            ],
            [
                'key' => 'letter_grade',
                'translations' => [
                    'en' => 'Letter Grade',
                    'zh' => '字母成绩',
                ]
            ],
            [
                'key' => 'semester_average',
                'translations' => [
                    'en' => 'Semester Average',
                    'zh' => '学期平均成绩',
                ]
            ],
            [
                'key' => 'exempt',
                'translations' => [
                    'en' => 'Exempt',
                    'zh' => '免试',
                ]
            ],
            [
                'key' => 'grades_are_worth',
                'translations' => [
                    'en' => 'grades are worth',
                    'zh' => '成绩占总成绩的百分比',
                ]
            ],
            [
                'key' => 'of_the_overall_grade',
                'translations' => [
                    'en' => 'of the overall grade',
                    'zh' => '占总成绩的百分比',
                ]
            ],
            [
                'key' => 'loading_content',
                'translations' => [
                    'en' => 'Loading content',
                    'zh' => '加载内容',
                ]
            ],
            [
                'key' => 'you_are_not_enrolled_in_this_class',
                'translations' => [
                    'en' => 'You are not enrolled in this class',
                    'zh' => '您未注册此班级',
                ]
            ],
            [
                'key' => 'your_enrollment_is_pending_approval_by_the_teacher',
                'translations' => [
                    'en' => 'Your enrollment is pending approval by the teacher',
                    'zh' => '您的注册正在等待老师的批准',
                ]
            ],
            [
                'key' => 'your_enrollment_has_been_rejected_please_contact_the_teacher_for_more_information',
                'translations' => [
                    'en' => 'Your enrollment has been rejected. Please contact the teacher for more information',
                    'zh' => '您的注册已被拒绝。请与老师联系以获取更多信息',
                ]
            ],
            [
                'key' => 'woops',
                'translations' => [
                    'en' => 'Woops! This class is broken. You might be in the wrong place. Please try navigating to your class from the',
                    'zh' => '啊哦! 这个班级坏了。您可能走错了地方。请尝试从成绩单页面导航到您的班级',
                ]
            ],
            [
                'key' => 'grades_page',
                'translations' => [
                    'en' => 'grades page',
                    'zh' => '成绩单页面',
                ]
            ],
            [
                'key' => 'log_in',
                'translations' => [
                    'en' => 'log in',
                    'zh' => '登录',
                ]
            ],
            [
                'key' => 'to_view_your_childs_grades',
                'translations' => [
                    'en' => 'to view your child\'s grades',
                    'zh' => '查看您的孩子的成绩',
                ]
            ],
            [
                'key' => 'please',
                'translations' => [
                    'en' => 'Please',
                    'zh' => '请',
                ]
            ]
        ];
    }
    
    /**
     * Get the default language code
     * 
     * @return string Default language code
     */
    public function getDefaultLanguage() {
        return $this->defaultLanguage;
    }
    
    /**
     * Get the default language code
     * 
     * @return string Default language code
     */
    public function getPluginDomain() {
        return $this->plugin_domain;
    }
    
    /**
     * Get the default language code
     * 
     * @return string Default language code
     */
    public function tigr_translate($text, $domain = null) {
        return __($text, $domain ?? $this->plugin_domain);
    }
    
    /**
     * Get current language code (with Polylang fallback)
     * 
     * @return string Current language code
     */
    public function getCurrentLanguage() {
        if (function_exists('pll_current_language')) {
            $current = pll_current_language();
            return $current ?: $this->defaultLanguage;
        }
        
        return $this->defaultLanguage;
    }
    
    /**
     * Get all supported languages
     * 
     * @return array Array of supported languages
     */
    public function getSupportedLanguages() {
        return $this->supportedLanguages;
    }
    
    /**
     * Get language configuration for a specific language
     * 
     * @param string $langCode Language code
     * @return array|null Language configuration or null if not found
     */
    public function getLanguageConfig($langCode) {
        return $this->supportedLanguages[$langCode] ?? null;
    }
    
    /**
     * Check if current language is the default language
     * 
     * @return bool True if current language is default, false otherwise
     */
    public function isCurrentLanguageDefault() {
        return $this->getCurrentLanguage() === $this->defaultLanguage;
    }
    
    /**
     * Get language-specific URL prefix
     * 
     * @param string|null $langCode Language code (defaults to current language)
     * @return string URL prefix for the language (empty for default language)
     */
    public function getUrlPrefix($langCode = null) {
        if ($langCode === null) {
            $langCode = $this->getCurrentLanguage();
        }
        
        return $langCode === $this->defaultLanguage ? '' : $langCode . '/';
    }

    public function getTranslatedRoute($route) {
        // If no route provided or Polylang not active, return as is
        if (!function_exists('pll_current_language')) {
            error_log('Polylang not active');
            return $route;
        }

        $current_language = pll_current_language();

        $is_default_language = $current_language === $this->defaultLanguage;
        
        if ($is_default_language) {
            return $route;
        }
        
        $is_absolute = strpos($route, '/') === 0;

        $route_prefix = $is_absolute ? '/' . $current_language : $current_language . '/';

        return $route_prefix . $route;
    }

    public function getTranslatedRouteSegment($route) {
        // If no route provided or Polylang not active, return as is
        if (empty($route) || !function_exists('pll_current_language')) {
            error_log('No route provided or Polylang not active');
            return $route;
        }
        
        $current_language = pll_current_language();
        
        // If current language is default, return original route
        if ($current_language === $this->defaultLanguage) {
            error_log('Current language is default');
            return $route;
        }
        
        // Find the post ID for the default language page
        $default_post_id = $this->translation_repository->findDefaultLanguagePostId($route);
        
        if (!$default_post_id) {
            error_log('No default post ID found');
            return $route;
        }
        
        // Find the translation group ID
        $translation_group_id = $this->translation_repository->findTranslationGroupId($default_post_id);
        
        if (!$translation_group_id) {
            error_log('No translation group ID found');
            return $route;
        }
        
        // Get the translated post ID from the translation group
        $translated_post_id = $this->translation_repository->findTranslatedPostId($current_language, $translation_group_id);
        
        if (!$translated_post_id) {
            error_log('No translated post ID found');
            return $route;
        }
        
        // Get the translated slug
        $translated_slug = $this->translation_repository->getPostSlug($translated_post_id);
        
        return $translated_slug ?: $route;
    }
    
    /**
     * Static method to get a singleton instance
     * 
     * @return LanguageManager Singleton instance
     */
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
} 