<?php
/**
 * BestDeal CRM - Root Installer
 * Place this file in: public_html/bdfsloans.com/bestdealcrm/install.php
 * Access: https://bdfsloans.com/bestdealcrm/install.php
 */

// Database credentials
$dbHost = '68.178.237.250';
$dbName = 'bestdealcrm';
$dbUser = 'sayali';
$dbPass = 'sayali@1234';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // SQL statements
        $statements = [
            // Roles
            "CREATE TABLE IF NOT EXISTS `roles` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `display_name` VARCHAR(100) NOT NULL,
                `description` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Permissions
            "CREATE TABLE IF NOT EXISTS `permissions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Role Permissions
            "CREATE TABLE IF NOT EXISTS `role_permissions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `role_id` INT UNSIGNED NOT NULL,
                `permission_id` INT UNSIGNED NOT NULL,
                UNIQUE KEY `unique_role_perm` (`role_id`, `permission_id`),
                FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Users
            "CREATE TABLE IF NOT EXISTS `users` (
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
                FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Login Logs
            "CREATE TABLE IF NOT EXISTS `login_logs` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `ip_address` VARCHAR(45),
                `user_agent` TEXT,
                `login_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user` (`user_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Lead Uploads
            "CREATE TABLE IF NOT EXISTS `lead_uploads` (
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
                FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Leads
            "CREATE TABLE IF NOT EXISTS `leads` (
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
                INDEX `idx_workflow_stage` (`workflow_stage`),
                INDEX `idx_mobile` (`mobile_number`),
                INDEX `idx_created` (`created_at`),
                FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Lead Assignments
            "CREATE TABLE IF NOT EXISTS `lead_assignments` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `lead_id` INT UNSIGNED NOT NULL,
                `assigned_to` INT UNSIGNED NOT NULL,
                `assigned_by` INT UNSIGNED NOT NULL,
                `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('active', 'reassigned') DEFAULT 'active',
                `remark` TEXT,
                INDEX `idx_lead` (`lead_id`),
                FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`),
                FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Forms
            "CREATE TABLE IF NOT EXISTS `forms` (
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
                INDEX `idx_workflow_stage` (`workflow_stage`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Form Sections
            "CREATE TABLE IF NOT EXISTS `form_sections` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `form_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `display_order` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_form` (`form_id`),
                FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Form Fields
            "CREATE TABLE IF NOT EXISTS `form_fields` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `section_id` INT UNSIGNED NOT NULL,
                `field_name` VARCHAR(100) NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'text',
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Form Field Options
            "CREATE TABLE IF NOT EXISTS `form_field_options` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `field_id` INT UNSIGNED NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `value` VARCHAR(255) NOT NULL,
                `display_order` INT DEFAULT 0,
                INDEX `idx_field` (`field_id`),
                FOREIGN KEY (`field_id`) REFERENCES `form_fields`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Form Role Access
            "CREATE TABLE IF NOT EXISTS `form_role_access` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `form_id` INT UNSIGNED NOT NULL,
                `role_id` INT UNSIGNED NOT NULL,
                UNIQUE KEY `unique_form_role` (`form_id`, `role_id`),
                FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Form Submissions
            "CREATE TABLE IF NOT EXISTS `form_submissions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `form_id` INT UNSIGNED NOT NULL,
                `lead_id` INT UNSIGNED NOT NULL,
                `submitted_by` INT UNSIGNED NOT NULL,
                `status` ENUM('draft','submitted','updated','returned') DEFAULT 'draft',
                `submitted_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_lead` (`lead_id`),
                FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`),
                FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Form Submission Values
            "CREATE TABLE IF NOT EXISTS `form_submission_values` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `submission_id` INT UNSIGNED NOT NULL,
                `field_id` INT UNSIGNED NOT NULL,
                `value` TEXT,
                INDEX `idx_submission` (`submission_id`),
                FOREIGN KEY (`submission_id`) REFERENCES `form_submissions`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`field_id`) REFERENCES `form_fields`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Workflow Stages
            "CREATE TABLE IF NOT EXISTS `workflow_stages` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `label` VARCHAR(100) NOT NULL,
                `description` TEXT,
                `display_order` INT DEFAULT 0,
                `is_final` TINYINT(1) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Workflow Transitions
            "CREATE TABLE IF NOT EXISTS `workflow_transitions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `from_stage` VARCHAR(50) NOT NULL,
                `to_stage` VARCHAR(50) NOT NULL,
                `action` VARCHAR(100) NOT NULL,
                `allowed_roles` VARCHAR(255),
                `requires_remark` TINYINT(1) DEFAULT 0,
                `display_order` INT DEFAULT 0,
                INDEX `idx_from_stage` (`from_stage`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Workflow History
            "CREATE TABLE IF NOT EXISTS `workflow_history` (
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
                FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Remarks
            "CREATE TABLE IF NOT EXISTS `remarks` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `lead_id` INT UNSIGNED NOT NULL,
                `user_id` INT UNSIGNED NOT NULL,
                `stage` VARCHAR(50),
                `remark` TEXT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_lead` (`lead_id`),
                FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Documents
            "CREATE TABLE IF NOT EXISTS `documents` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Activity Logs
            "CREATE TABLE IF NOT EXISTS `activity_logs` (
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
                INDEX `idx_created` (`created_at`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Notifications
            "CREATE TABLE IF NOT EXISTS `notifications` (
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
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Dynamic Tables
            "CREATE TABLE IF NOT EXISTS `dynamic_tables` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `display_name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `created_by` INT UNSIGNED,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Dynamic Table Columns
            "CREATE TABLE IF NOT EXISTS `dynamic_table_columns` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        
        $successCount = 0;
        $errors = [];
        
        foreach ($statements as $sql) {
            try {
                $pdo->exec($sql);
                $successCount++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), '1050') === false) { // Table already exists
                    $errors[] = $e->getMessage();
                }
            }
        }
        
        // Seed Data - Roles
        $roles = [
            ['admin', 'Admin', 'Full system access'],
            ['team_leader', 'Team Leader', 'Team management'],
            ['agent', 'Agent', 'Lead processing'],
            ['login_agent', 'Login Agent', 'Pre/Post login'],
            ['underwriting', 'Underwriting Agent', 'Risk assessment'],
            ['dispatch', 'Dispatch Agent', 'Document dispatch'],
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name, display_name, description) VALUES (?, ?, ?)");
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
        
        // Seed Data - Permissions
        $permissions = [
            'lead.view', 'lead.create', 'lead.edit', 'lead.assign', 'lead.delete', 'lead.upload',
            'form.view', 'form.create', 'form.edit', 'form.delete', 'form.submit',
            'user.create', 'user.edit', 'user.delete', 'user.view',
            'role.manage', 'workflow.approve', 'workflow.reject', 'workflow.reassign',
            'document.view', 'document.upload', 'document.download',
            'report.view', 'notification.view', 'activity.view',
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (name) VALUES (?)");
        foreach ($permissions as $perm) {
            $stmt->execute([$perm]);
        }
        
        // Admin gets all permissions
        $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                     SELECT (SELECT id FROM roles WHERE name = 'admin'), id FROM permissions");
        
        // Agent permissions
        $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                     SELECT (SELECT id FROM roles WHERE name = 'agent'), id 
                     FROM permissions WHERE name IN ('lead.view','form.view','form.submit','document.view','document.upload','document.download','notification.view')");
        
        // Login Agent permissions
        $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                     SELECT (SELECT id FROM roles WHERE name = 'login_agent'), id 
                     FROM permissions WHERE name IN ('lead.view','form.view','form.submit','document.view','document.upload','document.download','notification.view')");
        
        // Seed Data - Workflow Stages
        $stages = [
            ['LEAD_UPLOADED', 'Lead Uploaded', 1],
            ['LEAD_ASSIGNED', 'Lead Assigned', 2],
            ['AGENT_DRAFT', 'Agent Draft', 3],
            ['AGENT_SUBMITTED', 'Agent Submitted', 4],
            ['ADMIN_REVIEW_1', 'Admin Review 1', 5],
            ['LOGIN_AGENT_ASSIGNED', 'Login Agent Assigned', 6],
            ['LOGIN_AGENT_DRAFT', 'Login Agent Draft', 7],
            ['LOGIN_AGENT_SUBMITTED', 'Login Agent Submitted', 8],
            ['RETURNED_TO_AGENT', 'Returned to Agent', 9],
            ['ADMIN_REVIEW_2', 'Admin Review 2', 10],
            ['LOGIN_APPROVED', 'Login Approved', 11],
            ['POST_LOGIN', 'Post Login', 12],
            ['UNDERWRITING', 'Underwriting', 13],
            ['UNDERWRITING_APPROVED', 'Underwriting Approved', 14],
            ['UNDERWRITING_REJECTED', 'Underwriting Rejected', 15],
            ['DISPATCH', 'Dispatch', 16],
            ['COMPLETED', 'Completed', 17],
            ['REJECTED', 'Rejected', 18],
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO workflow_stages (name, label, display_order) VALUES (?, ?, ?)");
        foreach ($stages as $stage) {
            $stmt->execute($stage);
        }
        
        // Default Admin User (password: admin123)
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, username, password_hash, role_id, status) 
                               VALUES (?, ?, ?, ?, (SELECT id FROM roles WHERE name = 'admin'), 'active')");
        $stmt->execute(['Super Admin', 'admin@bestdealcrm.com', 'admin', $passwordHash]);
        
        $message = "Installation complete! {$successCount} tables created, seed data inserted.";
        if (!empty($errors)) {
            $message .= " Some warnings: " . implode('; ', array_slice($errors, 0, 3));
        }
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BestDeal CRM - Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow" style="max-width:500px;width:100%">
        <div class="card-body p-5">
            <h3 class="text-center mb-4">BestDeal CRM</h3>
            <p class="text-muted text-center">Database Installer</p>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <div class="text-center">
                    <a href="/bestdealcrm/public/index.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <div class="text-center">
                    <a href="/bestdealcrm/install.php" class="btn btn-outline-primary">Try Again</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>Setup:</strong>
                    <ul class="mb-0 mt-1 small">
                        <li>Database: <code>bestdealcrm</code> @ <code>68.178.237.250</code></li>
                        <li>Creates all 25 tables + seed data</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <strong>Default Admin Login:</strong>
                    <br><small class="text-muted">Username: <code>admin</code> | Password: <code>admin123</code></small>
                </div>
                <form method="POST">
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Install database?')">
                        <i class="bi bi-database me-1"></i> Install Database
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
