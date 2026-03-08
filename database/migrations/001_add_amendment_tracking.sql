-- Migration: Add prescription amendment tracking
-- Run this script to enable amended prescription feature

ALTER TABLE `prescriptions` 
ADD COLUMN `parent_prescription_id` INT UNSIGNED NULL AFTER `prescription_id`,
ADD CONSTRAINT `fk_prescriptions_parent` 
  FOREIGN KEY (`parent_prescription_id`) 
  REFERENCES `prescriptions`(`prescription_id`) 
  ON DELETE SET NULL;

-- Add index for faster lookups
CREATE INDEX `idx_parent_prescription_id` ON `prescriptions`(`parent_prescription_id`);

-- Verify the column was added
-- SELECT * FROM prescriptions LIMIT 1;
