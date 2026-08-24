-- ============================================================
-- Add disposition column to leads table
-- Run via phpMyAdmin Import
-- ============================================================

-- Check and add disposition column
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'disposition');

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `leads` ADD COLUMN `disposition` VARCHAR(100) DEFAULT NULL AFTER `agent_remark`',
    'SELECT "Column disposition already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND INDEX_NAME = 'idx_disposition');

SET @sql2 = IF(@idx_exists = 0,
    'ALTER TABLE `leads` ADD INDEX `idx_disposition` (`disposition`)',
    'SELECT "Index idx_disposition already exists" AS info');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
