-- ============================================================
-- Calculator Saves Table
-- ============================================================
CREATE TABLE IF NOT EXISTS `calculator_saves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `customer_name` VARCHAR(255) DEFAULT NULL,
    `save_type` ENUM('calculator', 'eligibility') NOT NULL DEFAULT 'calculator',
    `data` JSON NOT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_code` (`code`),
    INDEX `idx_customer_name` (`customer_name`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
