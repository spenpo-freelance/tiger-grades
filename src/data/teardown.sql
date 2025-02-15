DELETE FROM `wp_posts` 
WHERE post_type = 'page' 
AND post_title IN ('Grades', 'English Grades', 'Homework Assignments', 'Tests', 'Classwork', 'Projects', 'Quizzes', 'Writing', 'Science Grades', 'Social Studies Grades')
AND post_status = 'publish';
