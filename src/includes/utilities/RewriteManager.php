<?php

namespace Spenpo\TigerGrades\Utilities;
use bcn_breadcrumb;
use Spenpo\TigerGrades\Repositories\TigerClassRepository;

class RewriteManager {
    private $classRepository;

    public function __construct() {
        $this->classRepository = new TigerClassRepository();
        // Register hooks with high priority (1)
        add_filter('query_vars', [$this, 'registerQueryVars'], 1);
        add_action('init', [$this, 'registerRewriteRules'], 1);
        add_action('init', [$this, 'rewriteBreadcrumbs'], 1);
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
            '^grades/([^/]+)/?$',
            'index.php?pagename=grades/parent-class&enrollment_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^grades/([^/]+)/([^/]+)/?$',
            'index.php?pagename=grades/parent-class/category&enrollment_id=$matches[1]&class_category=$matches[2]',
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
        // Current item
        $trail->add(new bcn_breadcrumb(
            $classTitle,
            $this->getBreadcrumbTemplate(!$has_category),
            array('parent-class'),
            site_url('/grades/' . $enrollment_id . '/'),
            $has_category ? 2 : 1
        ));
    }

    public function customCatalogBreadcrumbs($trail) {
        $current_path = trim($_SERVER['REQUEST_URI'], '/');
        $path_parts = explode('/', $current_path);
        
        $trail->breadcrumbs = array();
        
        // Handle teacher/classes/{id} path
        if ($path_parts[0] === 'teacher' && !empty($path_parts[2]) && $path_parts[2] !== 'register') {
            $class_id = $path_parts[2];
            if (!empty($path_parts[1]) && $path_parts[1] === 'classes') {
                if (!empty($path_parts[2])) {
                    $this->handleTeacherClassBreadcrumb($trail, $class_id);
                
                    $trail->add(new bcn_breadcrumb(
                        'Classes',
                        $this->getBreadcrumbTemplate(),
                        array('teacher-classes'),
                        site_url('/teacher/classes/'),
                        2
                    ));
                    
                    $trail->add(new bcn_breadcrumb(
                        'Teacher',
                        $this->getBreadcrumbTemplate(),
                        array('teacher-classes'),
                        site_url('/teacher/'),
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
        if ($path_parts[0] === 'grades') {
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
                array('grades'),
                site_url('/grades/'),
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
}

new RewriteManager();
