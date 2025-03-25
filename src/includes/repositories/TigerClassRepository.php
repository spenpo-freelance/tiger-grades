<?php
namespace Spenpo\TigerGrades\Repositories;

use Exception as Exception;

/**
 * Handles database operations for resume data.
 * 
 * @package Spenpo\TigerGrades
 * @since 1.0.0
 */
class TigerClassRepository {
    /** @var wpdb WordPress database instance */
    private $wpdb;

    /**
     * Constructor initializes the WordPress database connection.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Creates an enrollment in the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getEnrolledClasses($user_id) {
        try {
            $enrollmentQuery = $this->wpdb->prepare("
                SELECT e.*, c.title as class_title, u.display_name as teacher_name
                FROM {$this->wpdb->prefix}tigr_enrollments e
                LEFT JOIN {$this->wpdb->prefix}tigr_classes c ON e.class_id = c.id
                LEFT JOIN {$this->wpdb->prefix}users u ON c.teacher = u.ID
                WHERE e.user_id = %d
            ", $user_id);
            
            $results = $this->wpdb->get_results($enrollmentQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Teachers can approve enrollments for a class.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function approveEnrollment($enrollment_id, $student_id) {
        try {
            $enrollmentQuery = $this->wpdb->prepare("
                UPDATE {$this->wpdb->prefix}tigr_enrollments SET student_id = %d WHERE id = %d
            ", $student_id, $enrollment_id);
            
            $results = $this->wpdb->get_results($enrollmentQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Teachers can view all enrollments for a class.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getClassEnrollments($class_id) {
        try {
            $enrollmentQuery = $this->wpdb->prepare("
                SELECT e.*, u.display_name as parent_name, u.user_email as parent_email
                FROM {$this->wpdb->prefix}tigr_enrollments e
                LEFT JOIN {$this->wpdb->prefix}users u ON e.user_id = u.ID
                WHERE e.class_id = %d
                AND e.status = 'pending'
            ", $class_id);
            
            $results = $this->wpdb->get_results($enrollmentQuery);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates an enrollment in the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getClassFromEnrollment($enrollment_id) {
        try {
            $query = $this->wpdb->prepare("
                SELECT c.*, e.student_id FROM {$this->wpdb->prefix}tigr_enrollments e 
                LEFT JOIN {$this->wpdb->prefix}tigr_classes c ON e.class_id = c.id
                WHERE e.id = %d
            ", $enrollment_id);
            
            $results = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results[0];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates an enrollment in the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getClass($class_id) {
        try {
            $query = $this->wpdb->prepare("
                SELECT c.*, u.display_name as teacher_name
                FROM {$this->wpdb->prefix}tigr_classes c
                LEFT JOIN {$this->wpdb->prefix}users u ON c.teacher = u.ID
                WHERE c.id = %d
            ", $class_id);
            
            $results = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results[0];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates an enrollment in the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getGradebookId($class_id) {
        try {
            $query = $this->wpdb->prepare("
                SELECT gradebook_id FROM {$this->wpdb->prefix}tigr_classes WHERE id = %d
            ", $class_id);
            
            $results = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results[0]->gradebook_id;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates an enrollment in the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function createEnrollment($class_id, $user_id, $student_name, $optional_message) {
        try {
            $query = $this->wpdb->prepare("
                INSERT INTO {$this->wpdb->prefix}tigr_enrollments (class_id, user_id, student_name, message)
                VALUES (%d, %d, %s, %s)
            ", $class_id, $user_id, $student_name, $optional_message);
            
            $results = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all text sections from the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getClassByEnrollmentCode($enrollment_code) {
        try {
            $query = $this->wpdb->prepare("
                SELECT c.*, u.display_name as teacher_name
                FROM {$this->wpdb->prefix}tigr_classes c
                LEFT JOIN {$this->wpdb->prefix}users u ON c.teacher = u.ID
                WHERE c.enrollment_code = %s
            ", $enrollment_code);
            
            $results = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all text sections from the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function getTeacherClasses($teacher_id) {
        try {
            $query = $this->wpdb->prepare("
                SELECT 
                    c.*, 
                    c.title as class_title,
                    COUNT(e.id) as total_enrollments,
                    SUM(CASE WHEN e.status = 'pending' THEN 1 ELSE 0 END) as pending_enrollments
                FROM {$this->wpdb->prefix}tigr_classes c
                LEFT JOIN {$this->wpdb->prefix}tigr_enrollments e ON c.id = e.class_id
                WHERE c.teacher = %d
                GROUP BY c.id
            ", $teacher_id);
            
            $results = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
            
            return $results;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all text sections from the database.
     * 
     * @return array Array of text section objects
     * @throws Exception When database error occurs
     */
    public function createClass($title, $teacher_id, $enrollment_code) {
        try {
            $query = $this->wpdb->prepare("
                INSERT INTO {$this->wpdb->prefix}tigr_classes (title, teacher, enrollment_code)
                VALUES (%s, %d, %s)
            ", $title, $teacher_id, $enrollment_code);
            
            $this->wpdb->query($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
} 