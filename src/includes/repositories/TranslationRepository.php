<?php

namespace Spenpo\TigerGrades\Repositories;

/**
 * Translation Repository
 * 
 * Handles database operations related to translations and multilingual routing.
 * This class manages the complex database queries needed for Polylang integration
 * and translated content retrieval.
 * 
 * @package Spenpo\TigerGrades\Repositories
 * @since 1.0.0
 */
class TranslationRepository {
    
    /**
     * WordPress database instance
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Find the post ID for the default language page
     * 
     * @param string $route The page slug/route
     * @return int|null The post ID or null if not found
     */
    public function findDefaultLanguagePostId($route) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT ID FROM {$this->wpdb->posts} 
            WHERE post_name = %s 
            AND post_type = 'page' 
            AND post_status = 'publish'",
            $route
        ));
    }
    
    /**
     * Find the translation group ID for a post
     * 
     * @param int $post_id The post ID
     * @return int|null The translation group ID or null if not found
     */
    public function findTranslationGroupId($post_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT tr.term_taxonomy_id 
            FROM {$this->wpdb->term_relationships} tr
            JOIN {$this->wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tr.object_id = %d 
            AND tt.taxonomy = 'post_translations'",
            $post_id
        ));
    }
    
    /**
     * Find the translated post ID for a specific language within a translation group
     * 
     * @param string $language The target language code
     * @param int $translation_group_id The translation group ID
     * @return int|null The translated post ID or null if not found
     */
    public function findTranslatedPostId($language, $translation_group_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT tr.object_id 
            FROM {$this->wpdb->term_relationships} tr
            JOIN {$this->wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$this->wpdb->terms} t ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'language' 
            AND t.slug = %s
            AND tr.object_id IN (
                SELECT object_id 
                FROM {$this->wpdb->term_relationships} 
                WHERE term_taxonomy_id = %d
            )",
            $language,
            $translation_group_id
        ));
    }
    
    /**
     * Get the post slug by post ID
     * 
     * @param int $post_id The post ID
     * @return string|null The post slug or null if not found
     */
    public function getPostSlug($post_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT post_name 
            FROM {$this->wpdb->posts} 
            WHERE ID = %d",
            $post_id
        ));
    }
} 
