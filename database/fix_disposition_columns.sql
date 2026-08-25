-- Add disposition columns to leads table if missing
-- Run this in phpMyAdmin if agent leads disposition is not saving

-- Check and add 'disposition' column
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'disposition');
SET @sql = IF(@exists = 0, 'ALTER TABLE `leads` ADD COLUMN `disposition` VARCHAR(100) DEFAULT NULL AFTER `pan_number`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add 'agent_disposition' column
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'agent_disposition');
SET @sql = IF(@exists = 0, 'ALTER TABLE `leads` ADD COLUMN `agent_disposition` VARCHAR(100) DEFAULT NULL AFTER `pan_number`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add 'agent_remark' column
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'agent_remark');
SET @sql = IF(@exists = 0, 'ALTER TABLE `leads` ADD COLUMN `agent_remark` TEXT DEFAULT NULL AFTER `agent_disposition`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify
SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' 
AND COLUMN_NAME IN ('disposition', 'agent_disposition', 'agent_remark');
