-- ============================================================
-- BestDeal CRM - Complete Database Schema
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- ROLES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- PERMISSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- ROLE PERMISSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_role_perm` (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `mobile` VARCHAR(20),
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `team_leader_id` INT UNSIGNED DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `last_login_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role` (`role_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_team_leader` (`team_leader_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
    FOREIGN KEY (`team_leader_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- LOGIN LOGS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `login_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_login_at` (`login_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- LEAD UPLOADS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_uploads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255),
    `uploaded_by` INT UNSIGNED,
    `status` ENUM('processing', 'completed', 'failed', 'empty') DEFAULT 'processing',
    `total_rows` INT DEFAULT 0,
    `imported` INT DEFAULT 0,
    `skipped` INT DEFAULT 0,
    `error_log` TEXT,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_uploaded_by` (`uploaded_by`),
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- LEADS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_name` VARCHAR(255),
    `mobile_number` VARCHAR(20),
    `location` VARCHAR(255),
    `state` VARCHAR(100),
    `existing_la` VARCHAR(255),
    `salary` DECIMAL(12,2),
    `actual_salary` DECIMAL(12,2),
    `dtmf_input` VARCHAR(255),
    `response_date` DATE,
    `data_type` VARCHAR(100),
    `bank_name` VARCHAR(255),
    `current_status` VARCHAR(255),
    `update_status` VARCHAR(255),
    `remark` TEXT,
    `pan_number` VARCHAR(20),
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `assigned_by` INT UNSIGNED DEFAULT NULL,
    `workflow_stage` VARCHAR(50) DEFAULT 'LEAD_UPLOADED',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `upload_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_assigned_to` (`assigned_to`),
    INDEX `idx_assigned_by` (`assigned_by`),
    INDEX `idx_workflow_stage` (`workflow_stage`),
    INDEX `idx_mobile` (`mobile_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_upload` (`upload_id`),
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`upload_id`) REFERENCES `lead_uploads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- LEAD ASSIGNMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NOT NULL,
    `assigned_to` INT UNSIGNED NOT NULL,
    `assigned_by` INT UNSIGNED NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'reassigned') DEFAULT 'active',
    `remark` TEXT,
    INDEX `idx_lead` (`lead_id`),
    INDEX `idx_assigned_to` (`assigned_to`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`),
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORMS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `forms` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `assigned_role` VARCHAR(50),
    `related_table` VARCHAR(100),
    `workflow_stage` VARCHAR(50),
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_workflow_stage` (`workflow_stage`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORM SECTIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_sections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `form_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_form` (`form_id`),
    FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORM FIELDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_fields` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `section_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `type` ENUM('text','textarea','number','decimal','date','datetime','email','mobile','dropdown','multi-select','radio','checkbox','file','image','boolean','url','heading','section','hidden','readonly') NOT NULL DEFAULT 'text',
    `required` TINYINT(1) DEFAULT 0,
    `placeholder` VARCHAR(255),
    `default_value` TEXT,
    `display_order` INT DEFAULT 0,
    `visible_roles` VARCHAR(255),
    `editable_roles` VARCHAR(255),
    `validation_rules` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_section` (`section_id`),
    FOREIGN KEY (`section_id`) REFERENCES `form_sections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORM FIELD OPTIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_field_options` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `field_id` INT UNSIGNED NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `display_order` INT DEFAULT 0,
    INDEX `idx_field` (`field_id`),
    FOREIGN KEY (`field_id`) REFERENCES `form_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORM ROLE ACCESS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_role_access` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `form_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    UNIQUE KEY `unique_form_role` (`form_id`, `role_id`),
    FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORM SUBMISSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `form_id` INT UNSIGNED NOT NULL,
    `lead_id` INT UNSIGNED NOT NULL,
    `submitted_by` INT UNSIGNED NOT NULL,
    `status` ENUM('draft','submitted','updated','returned') DEFAULT 'draft',
    `submitted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_form` (`form_id`),
    INDEX `idx_lead` (`lead_id`),
    INDEX `idx_submitted_by` (`submitted_by`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- FORM SUBMISSION VALUES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_submission_values` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `submission_id` INT UNSIGNED NOT NULL,
    `field_id` INT UNSIGNED NOT NULL,
    `value` TEXT,
    INDEX `idx_submission` (`submission_id`),
    INDEX `idx_field` (`field_id`),
    FOREIGN KEY (`submission_id`) REFERENCES `form_submissions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`field_id`) REFERENCES `form_fields`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- WORKFLOW STAGES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workflow_stages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `label` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `display_order` INT DEFAULT 0,
    `is_final` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- WORKFLOW TRANSITIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workflow_transitions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `from_stage` VARCHAR(50) NOT NULL,
    `to_stage` VARCHAR(50) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `allowed_roles` VARCHAR(255),
    `requires_remark` TINYINT(1) DEFAULT 0,
    `display_order` INT DEFAULT 0,
    INDEX `idx_from_stage` (`from_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- WORKFLOW HISTORY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workflow_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NOT NULL,
    `previous_stage` VARCHAR(50),
    `new_stage` VARCHAR(50) NOT NULL,
    `action` VARCHAR(100),
    `performed_by` INT UNSIGNED NOT NULL,
    `user_role` VARCHAR(50),
    `remark` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_lead` (`lead_id`),
    INDEX `idx_performed_by` (`performed_by`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- REMARKS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remarks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `stage` VARCHAR(50),
    `remark` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_lead` (`lead_id`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DOCUMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NOT NULL,
    `uploaded_by` INT UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255),
    `mime_type` VARCHAR(100),
    `file_size` INT UNSIGNED,
    `document_type` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_lead` (`lead_id`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- ACTIVITY LOGS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50),
    `entity_id` INT UNSIGNED,
    `old_value` TEXT,
    `new_value` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info','warning','success','error') DEFAULT 'info',
    `related_lead_id` INT UNSIGNED,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_read` (`is_read`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DYNAMIC TABLES (Table Builder)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dynamic_tables` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DYNAMIC TABLE COLUMNS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dynamic_table_columns` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `table_id` INT UNSIGNED NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `data_type` VARCHAR(50) NOT NULL DEFAULT 'text',
    `required` TINYINT(1) DEFAULT 0,
    `unique` TINYINT(1) DEFAULT 0,
    `default_value` TEXT,
    `display_order` INT DEFAULT 0,
    `visible_roles` VARCHAR(255),
    `editable_roles` VARCHAR(255),
    FOREIGN KEY (`table_id`) REFERENCES `dynamic_tables`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Roles
INSERT INTO `roles` (`name`, `display_name`, `description`) VALUES
('admin', 'Admin', 'Full system access'),
('team_leader', 'Team Leader', 'Team management and oversight'),
('agent', 'Agent', 'Lead processing and form filling'),
('login_agent', 'Login Agent', 'Pre-login and post-login processing'),
('underwriting', 'Underwriting Agent', 'Loan underwriting and risk assessment'),
('dispatch', 'Dispatch Agent', 'Loan document dispatch');

-- Permissions
INSERT INTO `permissions` (`name`, `description`) VALUES
('lead.view', 'View leads'),
('lead.create', 'Create leads'),
('lead.edit', 'Edit leads'),
('lead.assign', 'Assign leads'),
('lead.delete', 'Delete leads'),
('lead.upload', 'Upload leads'),
('form.view', 'View forms'),
('form.create', 'Create forms'),
('form.edit', 'Edit forms'),
('form.delete', 'Delete forms'),
('form.submit', 'Submit forms'),
('user.create', 'Create users'),
('user.edit', 'Edit users'),
('user.delete', 'Delete users'),
('user.view', 'View users'),
('role.manage', 'Manage roles and permissions'),
('workflow.approve', 'Approve workflow'),
('workflow.reject', 'Reject workflow'),
('workflow.reassign', 'Reassign leads'),
('document.view', 'View documents'),
('document.upload', 'Upload documents'),
('document.download', 'Download documents'),
('report.view', 'View reports'),
('notification.view', 'View notifications'),
('activity.view', 'View activity logs');

-- Admin gets all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id
FROM permissions;

-- Agent permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE name = 'agent'),
    id
FROM permissions WHERE name IN (
    'lead.view', 'form.view', 'form.submit',
    'document.view', 'document.upload', 'document.download',
    'notification.view'
);

-- Login Agent permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE name = 'login_agent'),
    id
FROM permissions WHERE name IN (
    'lead.view', 'form.view', 'form.submit',
    'document.view', 'document.upload', 'document.download',
    'notification.view'
);

-- Workflow Stages
INSERT INTO `workflow_stages` (`name`, `label`, `display_order`) VALUES
('LEAD_UPLOADED', 'Lead Uploaded', 1),
('LEAD_ASSIGNED', 'Lead Assigned', 2),
('AGENT_DRAFT', 'Agent Draft', 3),
('AGENT_SUBMITTED', 'Agent Submitted', 4),
('ADMIN_REVIEW_1', 'Admin Review 1', 5),
('LOGIN_AGENT_ASSIGNED', 'Login Agent Assigned', 6),
('LOGIN_AGENT_DRAFT', 'Login Agent Draft', 7),
('LOGIN_AGENT_SUBMITTED', 'Login Agent Submitted', 8),
('RETURNED_TO_AGENT', 'Returned to Agent', 9),
('ADMIN_REVIEW_2', 'Admin Review 2', 10),
('LOGIN_APPROVED', 'Login Approved', 11),
('POST_LOGIN', 'Post Login', 12),
('UNDERWRITING', 'Underwriting', 13),
('UNDERWRITING_APPROVED', 'Underwriting Approved', 14),
('UNDERWRITING_REJECTED', 'Underwriting Rejected', 15),
('DISPATCH', 'Dispatch', 16),
('COMPLETED', 'Completed', 17),
('REJECTED', 'Rejected', 18);

-- Default Admin User (password: admin123)
INSERT INTO `users` (`name`, `email`, `username`, `password_hash`, `role_id`, `status`) VALUES
('Super Admin', 'admin@bestdealcrm.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', (SELECT id FROM roles WHERE name = 'admin'), 'active');

SET FOREIGN_KEY_CHECKS = 1;
