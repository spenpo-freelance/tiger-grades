DROP TRIGGER IF EXISTS `wp_tigr_classes_BEFORE_UPDATE`;
CREATE TRIGGER `wp_tigr_classes_BEFORE_UPDATE` BEFORE UPDATE ON `wp_tigr_classes`
FOR EACH ROW
BEGIN
    IF NEW.gradebook_id IS NOT NULL THEN
        SET NEW.status = 'active';
    END IF;
    SET NEW.updated = CURRENT_TIMESTAMP;
END;

DROP TRIGGER IF EXISTS `wp_tigr_enrollments_BEFORE_UPDATE`;
CREATE TRIGGER `wp_tigr_enrollments_BEFORE_UPDATE` BEFORE UPDATE ON `wp_tigr_enrollments`
FOR EACH ROW
BEGIN
    IF NEW.student_id IS NOT NULL THEN
        SET NEW.status = 'approved';
    END IF;
    SET NEW.updated = CURRENT_TIMESTAMP;
END;