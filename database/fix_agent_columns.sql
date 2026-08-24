-- ============================================================
-- Fix: Add agent_disposition and agent_remark to leads table
-- Run this via phpMyAdmin Import
-- ============================================================

ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `agent_disposition` VARCHAR(255) AFTER `pan_number`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `agent_remark` TEXT AFTER `agent_disposition`;
