<?php
/**
 * OnePress Tiger Grades Child Theme Functions
 * 
 * This file contains the functions for the OnePress Tiger Grades child theme.
 * 
 * @package OnePress_Tiger_Grades_Child_Theme
 * @subpackage Functions
 * @since 1.0.0
 * @version 1.0.0
 */

 add_action('wp_enqueue_scripts', function() {
    // Enqueue parent theme styles first
    wp_enqueue_style(
        'onepress-style',
        get_template_directory_uri() . '/style.css'
    );
    
    // Enqueue child theme's main style.css
    wp_enqueue_style(
        'onepress-tigergrades-style',
        get_stylesheet_uri(),
        ['onepress-style']
    );
});