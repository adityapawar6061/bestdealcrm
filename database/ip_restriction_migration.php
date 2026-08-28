<?php
/**
 * Migration: Add IP Restriction tables
 * Run once to set up IP restriction feature
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();

    // 1. Create ip_whitelist table
    $db->query("CREATE TABLE IF NOT EXISTS `ip_whitelist` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `description` VARCHAR(255) DEFAULT '',
        `is_active` TINYINT(1) DEFAULT 1,
        `added_by` INT UNSIGNED,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_ip` (`ip_address`),
        FOREIGN KEY (`added_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. Add ip_restricted column to users table
    // Check if column already exists
    $colCheck = $db->fetchOne("SHOW COLUMNS FROM users LIKE 'ip_restricted'");
    if (!$colCheck) {
        $db->query("ALTER TABLE `users` ADD COLUMN `ip_restricted` TINYINT(1) DEFAULT 0 AFTER `status`");
    }

    echo "✅ Migration complete: ip_whitelist table created, ip_restricted column added to users.\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
