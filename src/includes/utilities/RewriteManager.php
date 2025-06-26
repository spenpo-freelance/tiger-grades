<?php

namespace Spenpo\TigerGrades\Utilities;
use bcn_breadcrumb;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;

class RewriteManager {
    private $classRepository;
    private $defaultLanguage;

    public function __construct() {
        $this->classRepository = new TigerClassRepository();
        $languageConstants = LanguageManager::getInstance();
        $this->defaultLanguage = $languageConstants->getDefaultLanguage();
        // Register hooks with high priority (1)
        add_filter('query_vars', [$this, 'registerQueryVars'], 1);
        add_action('init', [$this, 'registerRewriteRules'], 1);
        add_action('init', [$this, 'rewriteBreadcrumbs'], 1);
        // Hook into Polylang's URL generation - try multiple approaches
        add_filter('pll_translation_url', [$this, 'modifyLanguageSwitcherUrl'], 10, 2);
        add_filter('pll_the_languages', [$this, 'modifyLanguagesArray'], 10, 2);
    }

    public function registerQueryVars($vars) {
        // access with get_query_var('class_id')
        $vars[] = 'class_id';
        $vars[] = 'enrollment_id';
        $vars[] = 'class_category';
        return $vars;
    }

    public function registerRewriteRules() {
        add_rewrite_rule(
            '^teacher/classes/(\d+)/?$',
            'index.php?pagename=teacher/classes/teacher-class&class_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^zh/teacher-zh/classes-zh/(\d+)/?$',
            'index.php?pagename=teacher-zh/classes-zh/teacher-class-zh&class_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^grades/([^/]+)/?$',
            'index.php?pagename=grades/parent-class&enrollment_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^zh/grades-zh/([^/]+)/?$',
            'index.php?pagename=grades-zh/parent-class-zh&enrollment_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^grades/([^/]+)/([^/]+)/?$',
            'index.php?pagename=grades/parent-class/category&enrollment_id=$matches[1]&class_category=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^zh/grades-zh/([^/]+)/([^/]+)/?$',
            'index.php?pagename=grades-zh/parent-class-zh/category-zh&enrollment_id=$matches[1]&class_category=$matches[2]',
            'top'
        );
    }

    public function rewriteBreadcrumbs() {
        add_filter('bcn_breadcrumb_trail_object', [$this, 'customCatalogBreadcrumbs'], 10, 1);
    }

    private function getBreadcrumbTemplate($isCurrentItem = false) {
        if ($isCurrentItem) {
            return '<span property="itemListItem" typeof="ListItem"><span class="breadcrumb-current" property="name">%htitle%</span></span>';
        }
        return '<span property="itemListItem" typeof="ListItem"><a href="%link%" class="post post-page" property="item"><span property="name">%htitle%</span></a></span>';
    }

    private function handleTeacherClassBreadcrumb($trail, $class_id) {
        $classTitle = $this->classRepository->getClass($class_id)->title;
        // Current item
        $trail->add(new bcn_breadcrumb(
            $classTitle,
            $this->getBreadcrumbTemplate(true),
            array('current-item'),
            null,
            1
        ));
    }

    private function handleParentClassBreadcrumb($trail, $enrollment_id, $has_category = false) {
        $user = wp_get_current_user();
        $is_teacher = in_array('teacher', (array) $user->roles);
        $classTitle = null;
        if ($is_teacher) {
            // for teachers, the enrollment_id is actually the class_id
            $classTitle = $this->classRepository->getClass($enrollment_id)->title;
        } else {
            $classTitle = $this->classRepository->getClassFromEnrollment($enrollment_id)->title;
        }

        $parent_class_route = 'parent-class';
        $grades_route = 'grades';

        // if polylang is active, use the current language to determine the route
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language();
            if ($lang !== $this->defaultLanguage) {
                $parent_class_route .= '-zh';
                $grades_route .= '-zh';
            }
        }
        // Current item
        $trail->add(new bcn_breadcrumb(
            $classTitle,
            $this->getBreadcrumbTemplate(!$has_category),
            array($parent_class_route),
            site_url('/' . $grades_route . '/' . $enrollment_id . '/'),
            $has_category ? 2 : 1
        ));
    }

    public function customCatalogBreadcrumbs($trail) {
        $current_path = trim($_SERVER['REQUEST_URI'], '/');
        $path_parts = explode('/', $current_path);
        
        $trail->breadcrumbs = array();

        $teacher_classes_route = 'teacher-classes';
        $teacher_route = 'teacher';
        $classes_route = 'classes';
        $grades_route = 'grades';
        $register_route = 'register';

        // if polylang is active, use the current language to determine the route
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language();
            if ($lang !== $this->defaultLanguage) {
                $teacher_classes_route .= '-zh';
                $teacher_route .= '-zh';
                $classes_route .= '-zh';
                $grades_route .= '-zh';
                $register_route .= '-zh';
            }
        }
        
        // Handle teacher/classes/{id} path
        if ($path_parts[0] === $teacher_route && !empty($path_parts[2]) && $path_parts[2] !== $register_route) {
            $class_id = $path_parts[2];
            if (!empty($path_parts[1]) && $path_parts[1] === $classes_route) {
                if (!empty($path_parts[2])) {
                    $this->handleTeacherClassBreadcrumb($trail, $class_id);
                
                    $trail->add(new bcn_breadcrumb(
                        'Classes',
                        $this->getBreadcrumbTemplate(),
                        array($teacher_classes_route),
                        site_url('/' . $teacher_route . '/' . $classes_route . '/'),
                        2
                    ));
                    
                    $trail->add(new bcn_breadcrumb(
                        'Teacher',
                        $this->getBreadcrumbTemplate(),
                        array($teacher_classes_route),
                        site_url('/' . $teacher_route . '/'),
                        3
                    ));
                    
                    $trail->add(new bcn_breadcrumb(
                        'Tiger Grades',
                        $this->getBreadcrumbTemplate(),
                        array('home'),
                        home_url(),
                        4
                    ));
                }
            }
        }
        
        // Handle grades/{id} and grades/{id}/{category} paths
        if ($path_parts[0] === $grades_route) {
            $has_category = false;
            if (!empty($path_parts[2])) {
                $category_name = ucwords(str_replace('-', ' ', $path_parts[2]));
                $trail->add(new bcn_breadcrumb(
                    $category_name,
                    $this->getBreadcrumbTemplate(true),
                    array('current-item'),
                    null,
                    1
                ));
                $has_category = true;
            }
            
            if (!empty($path_parts[1])) {
                $enrollment_id = $path_parts[1];
                $this->handleParentClassBreadcrumb($trail, $enrollment_id, $has_category);
            }
            
            $trail->add(new bcn_breadcrumb(
                'Grades',
                $this->getBreadcrumbTemplate(),
                array($grades_route),
                site_url('/' . $grades_route . '/'),
                $has_category ? 3 : 2
            ));
            
            $trail->add(new bcn_breadcrumb(
                'Tiger Grades',
                $this->getBreadcrumbTemplate(),
                array('home'),
                home_url(),
                $has_category ? 4 : 3
            ));
        }
        
        return $trail;
    }

    public function modifyLanguageSwitcherUrl($url, $lang) {
        // Get the current URL path
        $current_path = trim($_SERVER['REQUEST_URI'], '/');
        $path_parts = explode('/', $current_path);

        // Handle teacher class URLs
        if (strpos($current_path, 'teacher-zh/classes-zh/') !== false || strpos($current_path, 'teacher/classes/') !== false) {
            $class_id = end($path_parts);
            if (is_numeric($class_id)) {
                if ($lang === $this->defaultLanguage) {
                    $new_url = home_url("teacher/classes/{$class_id}");
                    return $new_url;
                } else {
                    $new_url = home_url("zh/teacher-zh/classes-zh/{$class_id}");
                    return $new_url;
                }
            }
        }

        // Handle grades URLs
        if (strpos($current_path, 'grades-zh/') !== false || strpos($current_path, 'grades/') !== false) {
            $enrollment_id = null;
            $category = null;
            
            // Parse URL structure based on current language
            if (strpos($current_path, 'zh/grades-zh/') !== false) {
                // Chinese URL: zh/grades-zh/123 or zh/grades-zh/123/category
                $enrollment_id = $path_parts[2] ?? null;
                $category = $path_parts[3] ?? null;
            } else {
                // English URL: grades/123 or grades/123/category
                $enrollment_id = $path_parts[1] ?? null;
                $category = $path_parts[2] ?? null;
            }
            
            if ($enrollment_id) {
                if ($lang === $this->defaultLanguage) {
                    $new_url = home_url("grades/{$enrollment_id}");
                    if ($category) {
                        $new_url .= "/{$category}";
                    }
                    return $new_url;
                } else {
                    $new_url = home_url("zh/grades-zh/{$enrollment_id}");
                    if ($category) {
                        $new_url .= "/{$category}";
                    }
                    return $new_url;
                }
            }
        }

        return $url;
    }

    public function modifyLanguagesArray($languages, $args) {
        $current_path = trim($_SERVER['REQUEST_URI'], '/');
        
        foreach ($languages as $lang_code => $language) {
            // Modify the URL based on current path
            $modified_url = $this->generateCorrectLanguageUrl($current_path, $lang_code);
            if ($modified_url) {
                $languages[$lang_code]['url'] = $modified_url;
            }
        }
        
        return $languages;
    }

    private function generateCorrectLanguageUrl($current_path, $target_lang) {
        $path_parts = explode('/', $current_path);
        
        // Handle teacher class URLs
        if (strpos($current_path, 'teacher-zh/classes-zh/') !== false || strpos($current_path, 'teacher/classes/') !== false) {
            $class_id = end($path_parts);
            if (is_numeric($class_id)) {
                // Check if this is English (could be 'en', 'english', or similar)
                if ($target_lang === $this->defaultLanguage || $target_lang === 'english') {
                    return home_url("teacher/classes/{$class_id}");
                } else {
                    return home_url("zh/teacher-zh/classes-zh/{$class_id}");
                }
            }
        }

        // Handle grades URLs
        if (strpos($current_path, 'grades-zh/') !== false || strpos($current_path, 'grades/') !== false) {
            $enrollment_id = null;
            $category = null;
            
            // Parse URL structure based on current language
            if (strpos($current_path, 'zh/grades-zh/') !== false) {
                // Chinese URL: zh/grades-zh/123 or zh/grades-zh/123/category
                $enrollment_id = $path_parts[2] ?? null;
                $category = $path_parts[3] ?? null;
            } else {
                // English URL: grades/123 or grades/123/category
                $enrollment_id = $path_parts[1] ?? null;
                $category = $path_parts[2] ?? null;
            }
            
            error_log('Parsed enrollment_id: ' . $enrollment_id . ', category: ' . $category);

            if ($enrollment_id) {
                if ($target_lang === $this->defaultLanguage || $target_lang === 'english') {
                    $url = home_url("grades/{$enrollment_id}");
                    if ($category) {
                        $url .= "/{$category}";
                    }
                    return $url;
                } else {
                    $url = home_url("zh/grades-zh/{$enrollment_id}");
                    if ($category) {
                        $url .= "/{$category}";
                    }
                    return $url;
                }
            }
        }
        
        return null; // No modification needed
    }
}

new RewriteManager();
