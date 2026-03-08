-- Migration: Add prescription amendment tracking
-- Run this script to enable amended prescription feature

ALTER TABLE `patient_prescriptions` 
ADD COLUMN `parent_prescription_id` INT UNSIGNED NULL AFTER `prescription_id`,
ADD CONSTRAINT `fk_patient_prescriptions_parent` 
  FOREIGN KEY (`parent_prescription_id`) 
  REFERENCES `patient_prescriptions`(`prescription_id`) 
  ON DELETE SET NULL;

-- Add index for faster lookups
CREATE INDEX `idx_parent_prescription_id` ON `patient_prescriptions`(`parent_prescription_id`);

-- Verify the column was added
-- SELECT * FROM patient_prescriptions LIMIT 1;
