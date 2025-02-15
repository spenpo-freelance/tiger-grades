SELECT option_value into @site_url FROM wp_options WHERE option_name = 'siteurl';

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`) 
VALUES (1, 
    CONCAT(
        '<!-- wp:heading -->',
        '<h2 class="wp-block-heading">Your child is in the following classes</h2>',
        '<!-- /wp:heading -->',
        '<!-- wp:group {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->',
        '<div class="wp-block-group">',
        '<!-- wp:image {"lightbox":{"enabled":false},"id":1569,"sizeSlug":"full","linkDestination":"custom"} -->',
        '<figure class="wp-block-image size-full"><a href="/grades/english/"><img src="', @site_url, '/wp-content/uploads/2025/01/english_class.png" alt="enlgish book" class="wp-image-1569"/></a></figure>',
        '<!-- /wp:image -->',
        '<!-- wp:image {"lightbox":{"enabled":false},"id":1569,"sizeSlug":"full","linkDestination":"custom"} -->',
        '<figure class="wp-block-image size-full"><a href="/grades/science/"><img src="', @site_url, '/wp-content/uploads/2025/01/science_class.png" alt="science book" class="wp-image-1569"/></a></figure>',
        '<!-- /wp:image -->',
        '<!-- wp:image {"lightbox":{"enabled":false},"id":1571,"sizeSlug":"full","linkDestination":"custom"} -->',
        '<figure class="wp-block-image size-full"><a href="/grades/social-studies"><img src="', @site_url, '/wp-content/uploads/2025/01/history_class.png" alt="history book" class="wp-image-1571"/></a></figure>',
        '<!-- /wp:image -->',
        '</div>',
        '<!-- /wp:group -->',
        '<!-- wp:paragraph -->',
        '<p></p>',
        '<!-- /wp:paragraph -->',
        '<!-- wp:group {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->',
        '<div class="wp-block-group">',
        '<!-- wp:heading {"textAlign":"center","level":3} -->',
        '<h3 class="wp-block-heading has-text-align-center"><a href="/grades/english/" data-type="page" data-id="132">English</a></h3>',
        '<!-- /wp:heading -->',
        '<!-- wp:heading {"textAlign":"center","level":3} -->',
        '<h3 class="wp-block-heading has-text-align-center"><a href="/grades/science/" data-type="page" data-id="132">Science</a></h3>',
        '<!-- /wp:heading -->',
        '<!-- wp:heading {"textAlign":"center","level":3} -->',
        '<h3 class="wp-block-heading has-text-align-center"><a href="/grades/social-studies">Social Studies</a></h3>',
        '<!-- /wp:heading --></div>',
        '<!-- /wp:group -->',
        '<!-- wp:paragraph -->',
        '<p></p>',
        '<!-- /wp:paragraph -->'
    ), 
    'Grades', 'publish', 'grades', 0, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @grades_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @grades_page_id) WHERE ID = @grades_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="english" semester="2, 1"]\n<!-- /wp:shortcode -->', 'English Grades', 'publish', 'english', @grades_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @english_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @english_page_id) WHERE ID = @english_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="english" semester="2, 1" type="homework"]\n<!-- /wp:shortcode -->', 'Homework Assignments', 'publish', 'homework', @english_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @e_homework_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @homework_page_id) WHERE ID = @homework_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="english" semester="2, 1" type="test"]\n<!-- /wp:shortcode -->', 'Tests', 'publish', 'test', @english_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @e_test_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @test_page_id) WHERE ID = @test_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="english" semester="2, 1" type="classwork"]\n<!-- /wp:shortcode -->', 'Classwork', 'publish', 'classwork', @english_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @e_classwork_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @classwork_page_id) WHERE ID = @classwork_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="english" semester="2, 1" type="writing"]\n<!-- /wp:shortcode -->', 'Writing', 'publish', 'writing', @english_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @e_writing_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @writing_page_id) WHERE ID = @writing_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="social_studies" semester="1"]\n<!-- /wp:shortcode -->', 'Social Studies Grades', 'publish', 'social-studies', @grades_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @social_studies_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @social_studies_page_id) WHERE ID = @social_studies_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="quiz" class_id="social_studies" semester="1"]\n<!-- /wp:shortcode -->', 'Quizzes', 'publish', 'quiz', @social_studies_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @ss_quiz_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @ss_quiz_page_id) WHERE ID = @ss_quiz_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="test" class_id="social_studies" semester="1"]\n<!-- /wp:shortcode -->', 'Tests', 'publish', 'test', @social_studies_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @ss_test_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @test_page_id) WHERE ID = @test_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="classwork" class_id="social_studies" semester="1"]\n<!-- /wp:shortcode -->', 'Classwork', 'publish', 'classwork', @social_studies_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @ss_classwork_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @classwork_page_id) WHERE ID = @classwork_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="project" class_id="social_studies" semester="1"]\n<!-- /wp:shortcode -->', 'Projects', 'publish', 'project', @social_studies_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @ss_project_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @project_page_id) WHERE ID = @project_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card class_id="science" semester="2"]\n<!-- /wp:shortcode -->', 'Science Grades', 'publish', 'science', @grades_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @science_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @science_page_id) WHERE ID = @science_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="quiz" class_id="science" semester="2"]\n<!-- /wp:shortcode -->', 'Quizzes', 'publish', 'quiz', @science_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @s_quiz_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @quiz_page_id) WHERE ID = @quiz_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="test" class_id="science" semester="2"]\n<!-- /wp:shortcode -->', 'Tests', 'publish', 'test', @science_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @s_test_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @test_page_id) WHERE ID = @test_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="classwork" class_id="science" semester="2"]\n<!-- /wp:shortcode -->', 'Classwork', 'publish', 'classwork', @science_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @s_classwork_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @classwork_page_id) WHERE ID = @classwork_page_id;

INSERT INTO `wp_posts` (`post_author`, `post_content`, `post_title`, `post_status`, `post_name`, `post_parent`, `guid`, `post_type`, `post_date`, `post_date_gmt`)
VALUES (1, '<!-- wp:shortcode -->\n[tigr_report_card type="project" class_id="science" semester="2"]\n<!-- /wp:shortcode -->', 'Projects', 'publish', 'project', @science_page_id, '', 'page', NOW(), UTC_TIMESTAMP());

SELECT LAST_INSERT_ID() INTO @s_project_page_id;
UPDATE `wp_posts` SET `guid` = CONCAT(@site_url, '/?page_id=', @project_page_id) WHERE ID = @project_page_id;