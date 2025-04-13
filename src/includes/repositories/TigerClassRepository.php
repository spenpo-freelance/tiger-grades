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
                SELECT e.*, c.end_date, c.title as class_title, u.display_name as teacher_name, t.title as type_title, CONCAT('/wp-content/uploads/', pm.meta_value) as type_image_src
                FROM {$this->wpdb->prefix}tigr_enrollments e
                LEFT JOIN {$this->wpdb->prefix}tigr_classes c ON e.class_id = c.id
                LEFT JOIN {$this->wpdb->prefix}users u ON c.teacher = u.ID
                LEFT JOIN {$this->wpdb->prefix}tigr_class_types t ON c.type = t.id
                LEFT JOIN {$this->wpdb->prefix}postmeta pm ON t.image = pm.post_id AND pm.meta_key = '_wp_attached_file'
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
     * Retrieves all classes for a teacher.
     * 
     * @param int $teacher_id The ID of the teacher
     * @return array Array of class objects
     * @throws Exception When database error occurs
     */
    public function getTeacherClasses($teacher_id) {
        try {
            $query = $this->wpdb->prepare("
                SELECT 
                    c.*, 
                    c.title as class_title,
                    t.title as type_title,
                    COUNT(e.id) as total_enrollments,
                    SUM(CASE WHEN e.status = 'pending' THEN 1 ELSE 0 END) as pending_enrollments,
                    CONCAT('/wp-content/uploads/', pm.meta_value) as type_image_src
                FROM {$this->wpdb->prefix}tigr_classes c
                LEFT JOIN {$this->wpdb->prefix}tigr_enrollments e ON c.id = e.class_id
                LEFT JOIN {$this->wpdb->prefix}tigr_class_types t ON c.type = t.id
                LEFT JOIN {$this->wpdb->prefix}postmeta pm ON t.image = pm.post_id AND pm.meta_key = '_wp_attached_file'
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
    public function updateClass($class_id, $gradebook_id, $folder_id, $gradebook_url) {
        try {
            $query = $this->wpdb->prepare("
                UPDATE {$this->wpdb->prefix}tigr_classes
                SET gradebook_id = %s, gradebook_url = %s
                WHERE id = %d
            ", $gradebook_id, $gradebook_url, $class_id);
            
            $this->wpdb->query($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }

            $class = $this->getClass($class_id);

            if ($folder_id) {
                update_user_meta($class->teacher, 'teachers_folder_id', $folder_id);
            }

            return $class;
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
    private function produce_unique_gradebook_name($title, $teacher_id) {
        try {
            $query = $this->wpdb->prepare("
                SELECT gradebook_file_name FROM {$this->wpdb->prefix}tigr_classes WHERE teacher = %s
            ", $teacher_id);
            
            $current_gradebooks = $this->wpdb->get_results($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }

            $gradebook_base = str_replace(' ', '_', $title);
            $gradebook_extension = '_Gradebook.xlsx';
            $gradebook_name = $gradebook_base . $gradebook_extension;
            $i = 1;
            while (array_filter($current_gradebooks, function($gradebook) use ($gradebook_name) {
                return $gradebook->gradebook_file_name === $gradebook_name;
            })) {
                $gradebook_name = $gradebook_base . '_' . $i . $gradebook_extension;
                $i++;
            }

            return $gradebook_name;
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
    public function createClass($title, $teacher_id, $enrollment_code, $class_type_id, $num_students, $num_categories, $description, $message, $start_date, $end_date) {
        try {
            $gradebook_name = $this->produce_unique_gradebook_name($title, $teacher_id);
            $query = $this->wpdb->prepare("
                INSERT INTO {$this->wpdb->prefix}tigr_classes (title, teacher, enrollment_code, gradebook_file_name, type, num_students, num_categories, description, message, start_date, end_date)
                VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s, %s, %s)
            ", $title, $teacher_id, $enrollment_code, $gradebook_name, $class_type_id, $num_students, $num_categories, $description, $message, $start_date, $end_date);
            
            $this->wpdb->query($query);
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }

            $class_id = $this->wpdb->insert_id;
            $new_class = $this->getClass($class_id);

            return $new_class;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all class types for a select element.
     * 
     * @return array Array of class types
     * @throws Exception When database error occurs
     */
    public function getClassTypes() {
        try {
            $query = $this->wpdb->prepare("
                SELECT t.*, CONCAT('/wp-content/uploads/', pm.meta_value) as image_src
                FROM {$this->wpdb->prefix}tigr_class_types t
                LEFT JOIN {$this->wpdb->prefix}postmeta pm ON t.image = pm.post_id AND pm.meta_key = '_wp_attached_file'
                ORDER BY t.title ASC
            ");
            
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
     * Retrieves all class options for a select element.
     * 
     * @return array Array of class options
     * @throws Exception When database error occurs
     */
    public function getClassRegistrationOptions() {
        try {
            $query = $this->wpdb->prepare("
                SELECT o.*, fl.title as feature_title
                FROM {$this->wpdb->prefix}tigr_range_options o
                LEFT JOIN {$this->wpdb->prefix}tigr_feature_range_options_junction fro ON o.id = fro.range_option_id
                LEFT JOIN {$this->wpdb->prefix}tigr_feature_lookup fl ON fro.feature_lookup_id = fl.id
                WHERE fl.parent_feature = 1 -- id of registration form parent feature
                AND o.status = 'active'
                ORDER BY o.min ASC
            ");
            
            $results = $this->wpdb->get_results($query);
            // return an object with the feature_title as the key and an array of options as the value
            $options = [];
            foreach ($results as $result) {
                $options[$result->feature_title][] = $result;
            }
            
            if ($this->wpdb->last_error) {
                throw new Exception($this->wpdb->last_error);
            }

            return $options;
        } catch (Exception $e) {
            throw $e;
        }
    }
} 