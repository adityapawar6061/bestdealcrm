-- ============================================================
-- PF REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `pf_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agent_id` INT NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `monthly_salary` VARCHAR(50) NOT NULL,
    `loan_requirement` VARCHAR(50) NOT NULL,
    `loan_type` VARCHAR(50) NOT NULL,
    `processing_bank` VARCHAR(100) NOT NULL,
    `cibil_score` INT DEFAULT NULL,
    `admin_approved` ENUM('pending','yes','no') NOT NULL DEFAULT 'pending',
    `admin_remarks` TEXT DEFAULT NULL,
    `admin_files` JSON DEFAULT NULL,
    `status` ENUM('pending','replied') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_agent_id` (`agent_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CIBIL REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `cibil_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agent_id` INT DEFAULT NULL,
    `name_as_pan` VARCHAR(255) NOT NULL,
    `pan_no` VARCHAR(20) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `cibil_score` INT DEFAULT NULL,
    `monthly_salary` VARCHAR(50) NOT NULL,
    `loan_requirement` VARCHAR(50) NOT NULL,
    `loan_type` VARCHAR(50) NOT NULL,
    `loan_eligible_calc` VARCHAR(100) DEFAULT NULL,
    `calculator_id` VARCHAR(100) DEFAULT NULL,
    `requirement` VARCHAR(100) DEFAULT NULL,
    `main_status` VARCHAR(100) DEFAULT 'N/A',
    `sub_status` VARCHAR(100) DEFAULT 'N/A',
    `agent_cibil_remarks` TEXT DEFAULT NULL,
    `cibil_checked` ENUM('yes','no') NOT NULL DEFAULT 'no',
    `cibil_company` VARCHAR(100) DEFAULT NULL,
    `cibil_score_actual` INT DEFAULT NULL,
    `cibil_pdf1` VARCHAR(255) DEFAULT NULL,
    `cibil_pdf2` VARCHAR(255) DEFAULT NULL,
    `admin_remarks` TEXT DEFAULT NULL,
    `status` ENUM('pending','replied') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_agent_id` (`agent_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_pan_no` (`pan_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CRM DATA ENTRIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `crm_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `mobile_no` VARCHAR(20) NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `salary` VARCHAR(50) NOT NULL,
    `loan_amount` VARCHAR(50) NOT NULL,
    `disposition` VARCHAR(100) NOT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_disposition` (`disposition`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_mobile_no` (`mobile_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
