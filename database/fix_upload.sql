-- ============================================================
-- Fix: Lead Templates Table + Missing Columns
-- Run this via phpMyAdmin Import
-- ============================================================

-- Create lead_templates table if it doesn't exist
CREATE TABLE IF NOT EXISTS `lead_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `columns` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add remark columns to leads if missing
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `admin_approval1_remark` TEXT AFTER `remark`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `admin_approval2_remark` TEXT AFTER `admin_approval1_remark`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `admin_approval3_remark` TEXT AFTER `admin_approval2_remark`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `admin_approval4_remark` TEXT AFTER `admin_approval3_remark`;

-- Add new workflow stages if missing
INSERT IGNORE INTO `workflow_stages` (`name`, `label`, `display_order`, `is_final`) VALUES
    ('ADMIN_REVIEW_3', 'Admin Review 3', 19, 0),
    ('ADMIN_REVIEW_4', 'Admin Review 4', 20, 0);
