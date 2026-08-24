-- ============================================================
-- Fix: Add disposition column + default agent_disposition
-- Run via phpMyAdmin Import
-- ============================================================

ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `disposition` VARCHAR(100) AFTER `agent_remark`;
ALTER TABLE `leads` ADD INDEX IF NOT EXISTS `idx_disposition` (`disposition`);
