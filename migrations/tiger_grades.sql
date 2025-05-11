SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Safely drop foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables first
DROP TABLE IF EXISTS `wp_tigr_secured_routes_junction`;
DROP TABLE IF EXISTS `wp_tigr_feature_range_options_junction`;
DROP TABLE IF EXISTS `wp_tigr_range_options`;
DROP TABLE IF EXISTS `wp_tigr_feature_lookup`;
DROP TABLE IF EXISTS `wp_tigr_enrollments`;
DROP TABLE IF EXISTS `wp_tigr_classes`;
DROP TABLE IF EXISTS `wp_tigr_class_types`;

-- Create tables in correct order
-- First create tables without foreign keys
CREATE TABLE `wp_tigr_range_options` (
  `id` bigint(19) UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` varchar(45) NOT NULL,
  `min` int(11) NOT NULL DEFAULT 0,
  `max` int(11) DEFAULT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_tigr_class_types` (
  `id` bigint(19) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `image` bigint(19) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_tigr_feature_lookup` (
  `id` bigint(19) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'active',
  `parent_feature` bigint(19) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Then create tables with foreign keys
CREATE TABLE `wp_tigr_classes` (
  `id` bigint(19) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher` bigint(19) UNSIGNED NOT NULL,
  `title` varchar(45) NOT NULL,
  `gradebook_id` varchar(45) DEFAULT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'pending',
  `enrollment_code` varchar(6) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `gradebook_url` varchar(255) DEFAULT NULL,
  `num_students` bigint(19) UNSIGNED NOT NULL,
  `num_categories` bigint(19) UNSIGNED NOT NULL,
  `type` bigint(19) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `message` varchar(512) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `gradebook_file_name` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_tigr_enrollments` (
  `id` bigint(19) UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` bigint(19) UNSIGNED NOT NULL,
  `user_id` bigint(19) UNSIGNED NOT NULL,
  `student_name` varchar(45) NOT NULL,
  `message` varchar(100) DEFAULT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'pending',
  `student_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_tigr_feature_range_options_junction` (
  `range_option_id` bigint(19) UNSIGNED NOT NULL,
  `feature_lookup_id` bigint(19) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_tigr_secured_routes_junction` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `feature_lookup_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes and constraints
ALTER TABLE `wp_tigr_range_options`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `wp_tigr_class_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title_UNIQUE` (`title`),
  ADD KEY `image_post_ID_idx` (`image`);

ALTER TABLE `wp_tigr_feature_lookup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_feature_idx` (`parent_feature`);

ALTER TABLE `wp_tigr_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gradebook_id_UNIQUE` (`gradebook_id`),
  ADD UNIQUE KEY `enrollment_code_UNIQUE` (`enrollment_code`),
  ADD KEY `wp_users_ID_idx` (`teacher`),
  ADD KEY `num_students_idx` (`num_students`),
  ADD KEY `num_categories_idx` (`num_categories`),
  ADD KEY `class_type_idx` (`type`);

ALTER TABLE `wp_tigr_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`),
  ADD KEY `wp_users_ID_idx` (`user_id`),
  ADD KEY `wp_tigr_classes_id_idx` (`class_id`);

ALTER TABLE `wp_tigr_feature_range_options_junction`
  ADD KEY `range_option_id_idx` (`range_option_id`),
  ADD KEY `feature_lookup_id_idx` (`feature_lookup_id`);

ALTER TABLE `wp_tigr_secured_routes_junction`
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `feature_lookup_id_idx` (`feature_lookup_id`);

-- Add foreign key constraints
ALTER TABLE `wp_tigr_feature_lookup`
  ADD CONSTRAINT `parent_feature` FOREIGN KEY (`parent_feature`) REFERENCES `wp_tigr_feature_lookup` (`id`);

ALTER TABLE `wp_tigr_classes`
  ADD CONSTRAINT `class_type` FOREIGN KEY (`type`) REFERENCES `wp_tigr_class_types` (`id`),
  ADD CONSTRAINT `num_categories` FOREIGN KEY (`num_categories`) REFERENCES `wp_tigr_range_options` (`id`),
  ADD CONSTRAINT `num_students` FOREIGN KEY (`num_students`) REFERENCES `wp_tigr_range_options` (`id`),
  ADD CONSTRAINT `wp_users_ID_teacher` FOREIGN KEY (`teacher`) REFERENCES `wp_users` (`ID`);

ALTER TABLE `wp_tigr_enrollments`
  ADD CONSTRAINT `wp_tigr_classes_id` FOREIGN KEY (`class_id`) REFERENCES `wp_tigr_classes` (`id`),
  ADD CONSTRAINT `wp_users_ID_parent` FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`ID`);

ALTER TABLE `wp_tigr_feature_range_options_junction`
  ADD CONSTRAINT `feature_lookup_id` FOREIGN KEY (`feature_lookup_id`) REFERENCES `wp_tigr_feature_lookup` (`id`),
  ADD CONSTRAINT `range_option_id` FOREIGN KEY (`range_option_id`) REFERENCES `wp_tigr_range_options` (`id`);

ALTER TABLE `wp_tigr_secured_routes_junction`
  ADD CONSTRAINT `srj_feature_lookup_id` FOREIGN KEY (`feature_lookup_id`) REFERENCES `wp_tigr_feature_lookup` (`id`),
  ADD CONSTRAINT `srj_user_id` FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`ID`);

-- Insert data
INSERT INTO `wp_tigr_class_types` (`id`, `title`, `image`) VALUES
(1, 'English', 1604),
(2, 'History', 1605),
(3, 'Science', 1651),
(4, 'Math', 1814),
(5, 'Foreign Language', 1820),
(6, 'Art', 1818),
(7, 'Chinese', 1819),
(8, 'PE', 1821);

INSERT INTO `wp_tigr_feature_lookup` (`id`, `title`, `description`, `status`, `parent_feature`) VALUES
(1, 'class-registration-form', 'form for teachers to register a new class', 'active', NULL),
(2, 'num_students', 'estimate of the number of students in the class', 'active', 1),
(3, 'num_categories', 'estimate of the number of categories for the class', 'active', 1),
(4, 'rest-api-routes', 'wordpress rest api routes registered by tiger grades plugin', 'active', NULL),
(5, '/tiger-grades/v1/update-class', 'used by tiger grades azure functions microservice to update class row after remote class registration process has been completed', 'active', 4);

INSERT INTO `wp_tigr_range_options` (`id`, `label`, `min`, `max`, `status`) VALUES
(1, '100+', 100, NULL, 'active'),
(2, '61-100', 61, 100, 'active'),
(3, '31-60', 31, 60, 'active'),
(4, '11-30', 11, 30, 'active'),
(5, '1-10', 1, 10, 'active'),
(6, '1-25', 1, 25, 'active'),
(7, '26-50', 26, 50, 'active'),
(8, '51-100', 51, 100, 'active');

INSERT INTO `wp_tigr_feature_range_options_junction` (`range_option_id`, `feature_lookup_id`) VALUES
(1, 2),
(6, 2),
(7, 2),
(8, 2),
(1, 3),
(2, 3),
(3, 3),
(4, 3),
(5, 3);

-- Add triggers
DELIMITER $$
DROP TRIGGER IF EXISTS `wp_tigr_classes_BEFORE_UPDATE`;
CREATE TRIGGER `wp_tigr_classes_BEFORE_UPDATE` BEFORE UPDATE ON `wp_tigr_classes` FOR EACH ROW BEGIN
    IF NEW.gradebook_id IS NOT NULL THEN
        SET NEW.status = 'active';
    END IF;
    SET NEW.updated = CURRENT_TIMESTAMP;
END
$$

DROP TRIGGER IF EXISTS `wp_tigr_enrollments_BEFORE_UPDATE`;
CREATE TRIGGER `wp_tigr_enrollments_BEFORE_UPDATE` BEFORE UPDATE ON `wp_tigr_enrollments` FOR EACH ROW BEGIN
    IF NEW.student_id IS NOT NULL THEN
        SET NEW.status = 'approved';
    END IF;
    SET NEW.updated = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
