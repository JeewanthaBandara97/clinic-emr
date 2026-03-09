-- ============================================================================
-- BILLING & INVOICING TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `invoices` (
  `invoice_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(30) NOT NULL UNIQUE,
  `visit_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `invoice_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid','Cancelled') DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`invoice_id`),
  FOREIGN KEY (`visit_id`) REFERENCES `patient_visits`(`visit_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`user_id`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `idx_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('Consultation','Lab Test','Medicine','Procedure','Other') NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `item_reference_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Links to test_id, medicine_id, etc',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`invoice_id`) ON DELETE CASCADE,
  KEY `idx_invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `payment_date` date NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Card','Cheque','Bank Transfer','Insurance','Other') DEFAULT 'Cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`invoice_id`),
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`user_id`),
  KEY `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INVENTORY & STOCK MANAGEMENT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `medicine_stock` (
  `stock_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_id` int(10) UNSIGNED NOT NULL,
  `current_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `reorder_quantity` int(11) NOT NULL DEFAULT 50,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_restock_date` date DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`medicine_id`),
  UNIQUE KEY `unique_medicine_id` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_id` int(10) UNSIGNED NOT NULL,
  `transaction_type` enum('Purchase','Sale','Adjustment','Return','Damage','Expiry') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Links to invoice_item_id or purchase_order_id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`medicine_id`),
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`user_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_medicine_id` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medicine_expiry_batches` (
  `batch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_id` int(10) UNSIGNED NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_per_unit` decimal(12,2) DEFAULT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('Active','Recalled','Expired') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`batch_id`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`medicine_id`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_batch_number` (`batch_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- APPOINTMENTS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_code` varchar(20) NOT NULL UNIQUE,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `reason_for_visit` varchar(255) DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','In Progress','Completed','Cancelled','No Show','Rescheduled') DEFAULT 'Scheduled',
  `appointment_type` enum('New Patient','Follow Up','Routine Checkup','Consultation','Other') DEFAULT 'Routine Checkup',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`appointment_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`user_id`),
  KEY `idx_appointment_date` (`appointment_date`),
  KEY `idx_doctor_id` (`doctor_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointment_slots` (
  `slot_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `slot_date` date NOT NULL,
  `slot_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `is_available` tinyint(1) DEFAULT 1,
  `appointment_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`slot_id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`appointment_id`),
  KEY `idx_doctor_date` (`doctor_id`, `slot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- REPORTS & ANALYTICS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `report_cache` (
  `cache_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_type` varchar(50) NOT NULL,
  `report_date` date NOT NULL,
  `report_data` longtext NOT NULL COMMENT 'JSON serialized data',
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cache_id`),
  KEY `idx_report_type_date` (`report_type`, `report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ALTER EXISTING TABLES TO ADD NEW COLUMNS
-- ============================================================================

ALTER TABLE `medicines` ADD COLUMN IF NOT EXISTS `consultation_fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Fee for prescribing this medicine';
ALTER TABLE `patient_visits` ADD COLUMN IF NOT EXISTS `consultation_fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Doctor consultation fee for visit';
ALTER TABLE `patient_tests` ADD COLUMN IF NOT EXISTS `test_price` decimal(10,2) DEFAULT 0.00 COMMENT 'Actual price charged for test';
ALTER TABLE `doctor_details` ADD COLUMN IF NOT EXISTS `is_available_online` tinyint(1) DEFAULT 0 COMMENT 'Available for online consultations';

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_invoice_payment_status ON invoices(payment_status);
CREATE INDEX IF NOT EXISTS idx_stock_reorder_level ON medicine_stock(current_quantity, reorder_level);
CREATE INDEX IF NOT EXISTS idx_appointment_status ON appointments(status, appointment_date);
