-- ============================================================
-- Update v2: Templates, Review 3/4, Workflow Stages
-- ============================================================

SET NAMES utf8mb4;

-- Lead Templates table
CREATE TABLE IF NOT EXISTS `lead_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `columns` JSON,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add review3/4 columns to leads
ALTER TABLE `leads`
    ADD COLUMN IF NOT EXISTS `admin_approval3_remark` TEXT AFTER `admin_approval2_remark`,
    ADD COLUMN IF NOT EXISTS `admin_approval4_remark` TEXT AFTER `admin_approval3_remark`;

-- Add new workflow stages
INSERT IGNORE INTO `workflow_stages` (`name`, `label`, `display_order`, `is_final`)
VALUES
    ('ADMIN_REVIEW_3', 'Admin Review 3', 19, 0),
    ('ADMIN_REVIEW_4', 'Admin Review 4', 20, 0);

-- Add ADMIN_REVIEW_3 and ADMIN_REVIEW_4 to workflow_transitions if missing
INSERT IGNORE INTO `workflow_transitions` (`from_stage`, `to_stage`, `action`, `required_role`)
VALUES
    ('POST_LOGIN', 'ADMIN_REVIEW_3', 'send_to_review3', 'login_agent'),
    ('ADMIN_REVIEW_3', 'UNDERWRITING', 'approve_to_underwriting', 'admin'),
    ('ADMIN_REVIEW_3', 'REJECTED', 'reject', 'admin'),
    ('UNDERWRITING_APPROVED', 'ADMIN_REVIEW_4', 'send_to_review4', 'underwriting'),
    ('ADMIN_REVIEW_4', 'DISPATCH', 'approve_to_dispatch', 'admin'),
    ('ADMIN_REVIEW_4', 'REJECTED', 'reject', 'admin');
