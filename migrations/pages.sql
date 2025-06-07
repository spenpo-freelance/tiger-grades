SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_520_ci';
SET collation_connection = 'utf8mb4_unicode_520_ci';

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- TODO: add permissions postmeta for Members plugin to necessary pages
-- TODO: build english and chinese menu bars with the following pages and rules (if-menu plugin):
---- home
---- login/register: user not logged in
---- account: user logged in
---- enroll: user logged in, user is subscriber role
---- dashboard: user logged in, user is teacher role
---- grades: user logged in
---- news
---- account: user logged in

-- Create stored procedure for page management
DELIMITER //
DROP PROCEDURE IF EXISTS create_or_update_page //
CREATE PROCEDURE create_or_update_page(
    IN p_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
    IN p_content TEXT,
    IN p_slug VARCHAR(255),
    IN p_parent_id INT,
    IN p_comment_status VARCHAR(20),
    IN p_ping_status VARCHAR(20),
    OUT p_post_id INT
)
BEGIN
    -- Check if page exists
    SELECT `ID` INTO p_post_id 
    FROM `wp_posts` 
    WHERE `post_title` COLLATE utf8mb4_unicode_520_ci = p_title 
    AND `post_status` = 'publish'
    AND `post_type` = 'page';
    
    -- Get site URL
    SELECT option_value INTO @site_url 
    FROM wp_options 
    WHERE option_name = 'siteurl';
    
    -- If page exists, update it
    IF p_post_id IS NOT NULL THEN
        UPDATE `wp_posts` 
        SET `post_content` = p_content,
            `post_modified` = CURRENT_TIMESTAMP,
            `post_modified_gmt` = CURRENT_TIMESTAMP
        WHERE `ID` = p_post_id;
    -- If page doesn't exist, create it
    ELSE
        INSERT INTO `wp_posts` (
            `post_author`, 
            `post_date`, 
            `post_date_gmt`, 
            `post_content`, 
            `post_title`, 
            `post_excerpt`, 
            `post_status`, 
            `comment_status`, 
            `ping_status`, 
            `post_password`, 
            `post_name`, 
            `to_ping`, 
            `pinged`, 
            `post_modified`, 
            `post_modified_gmt`, 
            `post_content_filtered`, 
            `post_parent`, 
            `guid`, 
            `menu_order`, 
            `post_type`, 
            `post_mime_type`, 
            `comment_count`
        ) VALUES (
            1, 
            CURRENT_TIMESTAMP, 
            CURRENT_TIMESTAMP, 
            p_content, 
            p_title, 
            '', 
            'publish', 
            p_comment_status, 
            p_ping_status, 
            '', 
            p_slug, 
            '', 
            '', 
            CURRENT_TIMESTAMP, 
            CURRENT_TIMESTAMP, 
            '', 
            p_parent_id, 
            '', 
            0, 
            'page', 
            '', 
            0
        );
        
        SELECT LAST_INSERT_ID() INTO p_post_id;
        UPDATE `wp_posts` 
        SET `guid` = CONCAT(@site_url, '/?page_id=', p_post_id) 
        WHERE ID = p_post_id;
    END IF;
END //

DROP PROCEDURE IF EXISTS create_pll_link;

CREATE PROCEDURE create_pll_link(
    IN en_id INT,
    IN zh_id INT
)
BEGIN
    DECLARE group_term_id INT DEFAULT 0;
    DECLARE group_term_taxonomy_id INT DEFAULT 0;
    DECLARE en_lang_tt_id INT DEFAULT 0;
    DECLARE zh_lang_tt_id INT DEFAULT 0;
    DECLARE existing_en_group INT DEFAULT 0;
    DECLARE existing_zh_group INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Silently continue on error
    END;
    
    -- Get the term_taxonomy_ids for the language taxonomies
    SELECT tt.term_taxonomy_id INTO en_lang_tt_id 
    FROM wp_term_taxonomy tt, wp_terms t
    WHERE tt.term_id = t.term_id 
    AND tt.taxonomy = 'language' 
    AND t.slug = 'en'
    LIMIT 1;
    
    SELECT tt.term_taxonomy_id INTO zh_lang_tt_id 
    FROM wp_term_taxonomy tt, wp_terms t
    WHERE tt.term_id = t.term_id 
    AND tt.taxonomy = 'language' 
    AND t.slug = 'zh'
    LIMIT 1;
    
    -- Check if either post already has a translation group
    SELECT DISTINCT tt.term_id INTO existing_en_group
    FROM wp_term_relationships tr, wp_term_taxonomy tt
    WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
    AND tr.object_id = en_id 
    AND tt.taxonomy = 'post_translations'
    LIMIT 1;
    
    SELECT DISTINCT tt.term_id INTO existing_zh_group
    FROM wp_term_relationships tr, wp_term_taxonomy tt
    WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
    AND tr.object_id = zh_id 
    AND tt.taxonomy = 'post_translations'
    LIMIT 1;
    
    -- Determine which translation group to use
    IF existing_en_group > 0 THEN
        SET group_term_id = existing_en_group;
    ELSEIF existing_zh_group > 0 THEN
        SET group_term_id = existing_zh_group;
    ELSE
        -- Create new translation group
        INSERT INTO wp_terms (name, slug) 
        VALUES (CONCAT('pll_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)), 
                CONCAT('pll_', UNIX_TIMESTAMP(), '_', FLOOR(RAND() * 10000)));
        SET group_term_id = LAST_INSERT_ID();
        
        -- Create term taxonomy entry
        INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, count)
        VALUES (group_term_id, 'post_translations', 
                CONCAT('a:2:{s:2:\"en\";i:', en_id, ';s:2:\"zh\";i:', zh_id, ';}'), 
                2);
        SET group_term_taxonomy_id = LAST_INSERT_ID();
    END IF;
    
    -- Get the term_taxonomy_id if we're using existing group
    IF group_term_taxonomy_id = 0 THEN
        SELECT tt.term_taxonomy_id INTO group_term_taxonomy_id
        FROM wp_term_taxonomy tt
        WHERE tt.term_id = group_term_id AND tt.taxonomy = 'post_translations'
        LIMIT 1;
    END IF;
    
    -- Proceed only if we have valid language taxonomy IDs and term taxonomy ID
    IF en_lang_tt_id > 0 AND zh_lang_tt_id > 0 AND group_term_taxonomy_id > 0 THEN
        
        -- Clean up existing relationships for both posts
        DELETE tr FROM wp_term_relationships tr
        INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tr.object_id IN (en_id, zh_id) 
        AND tt.taxonomy IN ('language', 'post_translations');
        
        -- Set languages for posts
        INSERT INTO wp_term_relationships (object_id, term_taxonomy_id)
        VALUES (en_id, en_lang_tt_id),
               (zh_id, zh_lang_tt_id);
        
        -- Create translation relationships
        INSERT INTO wp_term_relationships (object_id, term_taxonomy_id)
        VALUES (en_id, group_term_taxonomy_id),
               (zh_id, group_term_taxonomy_id);
        
        -- Update term counts
        UPDATE wp_term_taxonomy 
        SET count = (
            SELECT COUNT(*) 
            FROM wp_term_relationships 
            WHERE term_taxonomy_id = en_lang_tt_id
        )
        WHERE term_taxonomy_id = en_lang_tt_id;
        
        UPDATE wp_term_taxonomy 
        SET count = (
            SELECT COUNT(*) 
            FROM wp_term_relationships 
            WHERE term_taxonomy_id = zh_lang_tt_id
        )
        WHERE term_taxonomy_id = zh_lang_tt_id;
        
        UPDATE wp_term_taxonomy 
        SET count = 2,
            description = CONCAT('a:2:{s:2:\"en\";i:', en_id, ';s:2:\"zh\";i:', zh_id, ';}')
        WHERE term_taxonomy_id = group_term_taxonomy_id;
        
    END IF;
    
END //
DELIMITER ;

-- English Login page
CALL create_or_update_page(
    'Login',
    '<!-- wp:shortcode -->\n[user_registration_login]\n<!-- /wp:shortcode -->',
    'login',
    0,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Login
CALL create_or_update_page(
    '登录',
    '<!-- wp:shortcode -->\n[user_registration_login]\n<!-- /wp:shortcode -->',
    'login-zh',
    0,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Account /account
CALL create_or_update_page(
    'Account',
    '<!-- wp:shortcode -->\n[user_registration_my_account]\n<!-- /wp:shortcode -->',
    'account',
    0,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Account
CALL create_or_update_page(
    '账户',
    '<!-- wp:shortcode -->\n[user_registration_my_account]\n<!-- /wp:shortcode -->',
    'account-zh',
    0,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Enroll in a class /enroll
CALL create_or_update_page(
    'Enroll',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Instructions\" dismissible=\"true\"]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Enter the enrollment code you received from your teacher.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Enter the name of the student whose grades you want to monitor.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>The teacher will also see your name and email.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>The teacher will then match this info with a student in their gradebook.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:shortcode -->\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_info_bar type=\"warning\" title=\"Warning\" dismissible=\"true\"]\nOnly enter enrollment codes you received **directly** from your teacher.\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_class_enroll]\n<!-- /wp:shortcode -->',
    'enroll',
    0,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Enroll
CALL create_or_update_page(
    '报名课程',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Instructions\" dismissible=\"true\"]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Enter the enrollment code you received from your teacher.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Enter the name of the student whose grades you want to monitor.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>The teacher will also see your name and email.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>The teacher will then match this info with a student in their gradebook.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:shortcode -->\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_info_bar type=\"warning\" title=\"Warning\" dismissible=\"true\"]\nOnly enter enrollment codes you received **directly** from your teacher.\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_class_enroll]\n<!-- /wp:shortcode -->',
    'enroll-zh',
    0,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Teacher dashboard /teacher
CALL create_or_update_page(
    'Teacher',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Usage\" dismissible=\"true\"]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Share the enrollment QR code, URL, or the six-digit code itself with your students or their parents.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Click \"Manage\" to respond to the people who use the code to enroll in your class.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Click \"Grades\" to view the grades page to which your enrollments will gain access. Teachers can check any student''s grades there.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Click \"View\" to go to your class''s gradebook in OneDrive, where you can input new grades regularly.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:shortcode -->\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_info_bar type=\"warning\" icon=\"fas fa-lightbulb\" title=\"Warning\" dismissible=\"true\"]\n[Contact support](mailto:spencer@tigergrades.com) if your class has been in \"pending\" status for longer than 10 minutes.\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_teacher_dashboard]\n<!-- /wp:shortcode -->',
    'teacher',
    0,
    'closed',
    'closed',
    @en_id_teacher
);

-- Mandarin Teacher dashboard
CALL create_or_update_page(
    '老师',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Usage\" dismissible=\"true\"]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Share the enrollment QR code, URL, or the six-digit code itself with your students or their parents.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Click \"Manage\" to respond to the people who use the code to enroll in your class.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Click \"Grades\" to view the grades page to which your enrollments will gain access. Teachers can check any student''s grades there.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Click \"View\" to go to your class''s gradebook in OneDrive, where you can input new grades regularly.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:shortcode -->\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_info_bar type=\"warning\" icon=\"fas fa-lightbulb\" title=\"Warning\" dismissible=\"true\"]\n[Contact support](mailto:spencer@tigergrades.com) if your class has been in \"pending\" status for longer than 10 minutes.\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_teacher_dashboard]\n<!-- /wp:shortcode -->',
    'teacher-zh',
    0,
    'closed',
    'closed',
    @zh_id_teacher
);

CALL create_pll_link(
    @en_id_teacher,
    @zh_id_teacher
);

-- Teacher classes /teacher/classes
CALL create_or_update_page(
    'Classes',
    '<!-- wp:shortcode -->\n[tigr_teacher_classes]\n<!-- /wp:shortcode -->',
    'classes',
    @en_id_teacher,
    'closed',
    'closed',
    @en_id_teacher_classes
);

-- Mandarin Teacher classes
CALL create_or_update_page(
    '班级',
    '<!-- wp:shortcode -->\n[tigr_teacher_classes]\n<!-- /wp:shortcode -->',
    'classes-zh',
    @zh_id_teacher,
    'closed',
    'closed',
    @zh_id_teacher_classes
);

CALL create_pll_link(
    @en_id_teacher_classes,
    @zh_id_teacher_classes
);

-- Class management /teacher/classes/[id]
CALL create_or_update_page(
    'Class management',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Usage\" dismissible=\"true\"]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Click \"Approve\" to assign a student to an enrollment. If you assign the wrong student to an enrollment, click \"Change\" to correct the mistake.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Only one student can be assigned to an enrollment at a time. Parents with more than one student in your class can enroll again.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Enrollments can be approved or rejected at any time.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:shortcode -->\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_teacher_class]\n<!-- /wp:shortcode -->',
    'teacher-class',
    @en_id_teacher_classes,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Class management
CALL create_or_update_page(
    '课程管理',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Usage\" dismissible=\"true\"]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Click \"Approve\" to assign a student to an enrollment. If you assign the wrong student to an enrollment, click \"Change\" to correct the mistake.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Only one student can be assigned to an enrollment at a time. Parents with more than one student in your class can enroll again.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Enrollments can be approved or rejected at any time.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:shortcode -->\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_teacher_class]\n<!-- /wp:shortcode -->',
    'teacher-class-zh',
    @zh_id_teacher_classes,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Register a new class /teacher/classes/register
CALL create_or_update_page(
    'Register a new class',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Information\" dismissible=\"true\"]\nAfter a successful registration, you will see your class in the [teacher''s dashboard](/teacher) and [grades page](/grades). Your cloud-based gradebook will then be created in the background. Once it''s ready, the class will become active. It only takes a few minutes.\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_register_class]\n<!-- /wp:shortcode -->',
    'register',
    @en_id_teacher_classes,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Register a new class
CALL create_or_update_page(
    '创建课程',
    '<!-- wp:shortcode -->\n[tigr_info_bar type=\"info\" icon=\"fas fa-lightbulb\" title=\"Information\" dismissible=\"true\"]\nAfter a successful registration, you will see your class in the [teacher''s dashboard](/teacher) and [grades page](/grades). Your cloud-based gradebook will then be created in the background. Once it''s ready, the class will become active. It only takes a few minutes.\n[/tigr_info_bar]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[tigr_register_class]\n<!-- /wp:shortcode -->',
    'register-zh',
    @zh_id_teacher_classes,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Classes /grades
CALL create_or_update_page(
    'Grades',
    '<!-- wp:shortcode -->\n[tigr_parent_classes]\n<!-- /wp:shortcode -->',
    'grades',
    0,
    'open',
    'open',
    @en_id_grades
);

-- Mandarin Classes
CALL create_or_update_page(
    '成绩',
    '<!-- wp:shortcode -->\n[tigr_parent_classes]\n<!-- /wp:shortcode -->',
    'grades-zh',
    0,
    'open',
    'open',
    @zh_id_grades
);

CALL create_pll_link(
    @en_id_grades,
    @zh_id_grades
);

-- Class grades /grades/[id]
CALL create_or_update_page(
    'Class grades',
    '<!-- wp:shortcode -->\n[tigr_parent_class]\n<!-- /wp:shortcode -->',
    'parent-class',
    @en_id_grades,
    'closed',
    'closed',
    @en_id_class_grades
);

-- Mandarin Class grades
CALL create_or_update_page(
    '课程成绩',
    '<!-- wp:shortcode -->\n[tigr_parent_class]\n<!-- /wp:shortcode -->',
    'parent-class-zh',
    @zh_id_grades,
    'closed',
    'closed',
    @zh_id_class_grades
);

CALL create_pll_link(
    @en_id_class_grades,
    @zh_id_class_grades
);

-- Class grade category /grades/[id]/[category]
CALL create_or_update_page(
    'Category',
    '<!-- wp:shortcode -->\n[tigr_parent_class]\n<!-- /wp:shortcode -->',
    'category',
    @en_id_class_grades,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Class grade category
CALL create_or_update_page(
    '成绩类别',
    '<!-- wp:shortcode -->\n[tigr_parent_class]\n<!-- /wp:shortcode -->',
    'category-zh',
    @zh_id_class_grades,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Register users /register
CALL create_or_update_page(
    'Register',
    '<!-- wp:shortcode -->\n[tigr_registration]\n<!-- /wp:shortcode -->',
    'register',
    0,
    'closed',
    'closed',
    @en_id_register
);

-- Mandarin Register users
CALL create_or_update_page(
    '注册账号',
    '<!-- wp:shortcode -->\n[tigr_registration]\n<!-- /wp:shortcode -->',
    'register-zh',
    0,
    'closed',
    'closed',
    @zh_id_register
);

CALL create_pll_link(
    @en_id_register,
    @zh_id_register
);

-- Register as a teacher /register/teacher
-- form id (User Registration plugin)
SELECT `post_id` INTO @user_register_form_id FROM `wp_postmeta` WHERE `meta_key` = 'user_registration_form_setting_default_user_role' AND `meta_value` = 'teacher';

CALL create_or_update_page(
    'Register as a Teacher',
    CONCAT('<!-- wp:shortcode -->\n[user_registration_form id=\"', @user_register_form_id, '\"]\n<!-- /wp:shortcode -->'),
    'teacher',
    @en_id_register,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Register as a teacher
CALL create_or_update_page(
    '老师注册账号',
    CONCAT('<!-- wp:shortcode -->\n[user_registration_form id=\"', @user_register_form_id, '\"]\n<!-- /wp:shortcode -->'),
    'teacher-zh',
    @zh_id_register,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

-- Register as a parent /register/parent
-- form id (User Registration plugin)
SELECT `post_id` INTO @user_register_form_id FROM `wp_postmeta` WHERE `meta_key` = 'user_registration_form_setting_default_user_role' AND `meta_value` = 'subscriber';

CALL create_or_update_page(
    'Register as a Parent',
    CONCAT('<!-- wp:shortcode -->\n[user_registration_form id=\"', @user_register_form_id, '\"]\n<!-- /wp:shortcode -->'),
    'parent',
    @en_id_register,
    'closed',
    'closed',
    @en_id
);

-- Mandarin Register as a parent
CALL create_or_update_page(
    '家长注册账号',
    CONCAT('<!-- wp:shortcode -->\n[user_registration_form id=\"', @user_register_form_id, '\"]\n<!-- /wp:shortcode -->'),
    'parent-zh',
    @zh_id_register,
    'closed',
    'closed',
    @zh_id
);

CALL create_pll_link(
    @en_id,
    @zh_id
);

COMMIT;
