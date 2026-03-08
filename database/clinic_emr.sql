-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 02:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_emr`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_sessions`
--

CREATE TABLE `clinic_sessions` (
  `session_id` int(10) UNSIGNED NOT NULL,
  `session_code` varchar(20) NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Scheduled','Active','Completed','Cancelled') DEFAULT 'Scheduled',
  `max_patients` int(11) DEFAULT 50,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_details`
--

CREATE TABLE `doctor_details` (
  `detail_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `specialization` varchar(150) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `experience_years` int(10) UNSIGNED DEFAULT 0,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `bio` text DEFAULT NULL,
  `available_days` varchar(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `available_time_start` time DEFAULT '08:00:00',
  `available_time_end` time DEFAULT '17:00:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drug_categories`
--

CREATE TABLE `drug_categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `category_level` tinyint(4) DEFAULT 1 COMMENT '1=Main, 2=Sub1, 3=Sub2',
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generic_names`
--

CREATE TABLE `generic_names` (
  `generic_id` int(10) UNSIGNED NOT NULL,
  `generic_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issuing_units`
--

CREATE TABLE `issuing_units` (
  `unit_id` int(10) UNSIGNED NOT NULL,
  `unit_name` varchar(50) NOT NULL,
  `unit_symbol` varchar(20) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `test_id` int(10) UNSIGNED NOT NULL,
  `type_id` int(10) UNSIGNED NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `test_price` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `medicine_id` int(10) UNSIGNED NOT NULL,
  `medicine_code` varchar(20) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `main_category_id` int(10) UNSIGNED DEFAULT NULL,
  `sub_category1_id` int(10) UNSIGNED DEFAULT NULL,
  `sub_category2_id` int(10) UNSIGNED DEFAULT NULL,
  `generic_id` int(10) UNSIGNED DEFAULT NULL,
  `trade_id` int(10) UNSIGNED DEFAULT NULL,
  `strength_value` varchar(50) DEFAULT NULL,
  `strength_unit_id` int(10) UNSIGNED DEFAULT NULL,
  `issuing_unit_id` int(10) UNSIGNED DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 10,
  `is_expiry_tracked` tinyint(1) DEFAULT 1,
  `discount_enabled` tinyint(1) DEFAULT 0,
  `dosage_form` varchar(50) DEFAULT NULL,
  `route` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `contraindications` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(10) UNSIGNED NOT NULL,
  `patient_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `nic_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') DEFAULT 'Unknown',
  `allergies` text DEFAULT NULL,
  `chronic_diseases` text DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `registration_date` date NOT NULL,
  `registered_by` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_prescriptions`
--

CREATE TABLE `patient_prescriptions` (
  `prescription_id` int(10) UNSIGNED NOT NULL,
  `parent_prescription_id` int(10) UNSIGNED DEFAULT NULL,
  `prescription_code` varchar(20) NOT NULL,
  `visit_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `prescription_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_medicines`
--

CREATE TABLE `patient_prescription_medicines` (
  `medicine_id` int(10) UNSIGNED NOT NULL,
  `prescription_id` int(10) UNSIGNED NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `dose` varchar(50) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `route` enum('Oral','Topical','Injection','Inhalation','Other') DEFAULT 'Oral',
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_patients`
--

CREATE TABLE `session_patients` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `queue_number` int(11) NOT NULL,
  `status` enum('Waiting','In Progress','Completed','No Show','Cancelled') DEFAULT 'Waiting',
  `check_in_time` datetime DEFAULT current_timestamp(),
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `strength_units`
--

CREATE TABLE `strength_units` (
  `strength_unit_id` int(10) UNSIGNED NOT NULL,
  `unit_name` varchar(20) NOT NULL,
  `unit_symbol` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_tests`
--

CREATE TABLE `patient_tests` (
  `test_id` int(10) UNSIGNED NOT NULL,
  `visit_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `test_type` enum('Blood Test','Urine Test','X-Ray','ECG','Ultrasound','MRI','CT Scan','Other') NOT NULL,
  `instructions` text DEFAULT NULL,
  `urgency` enum('Routine','Urgent','STAT') DEFAULT 'Routine',
  `status` enum('Requested','Sample Collected','Processing','Completed','Cancelled') DEFAULT 'Requested',
  `result` text DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_types`
--

CREATE TABLE `test_types` (
  `type_id` int(10) UNSIGNED NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade_names`
--

CREATE TABLE `trade_names` (
  `trade_id` int(10) UNSIGNED NOT NULL,
  `trade_name` varchar(100) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_visits`
--

CREATE TABLE `patient_visits` (
  `visit_id` int(10) UNSIGNED NOT NULL,
  `visit_code` varchar(20) NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('In Progress','Completed','Follow Up Required') DEFAULT 'In Progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_vital_signs`
--

CREATE TABLE `patient_vital_signs` (
  `vital_id` int(10) UNSIGNED NOT NULL,
  `visit_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `pulse_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `oxygen_saturation` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_medicines`
-- (See below for the actual view)
--
CREATE TABLE `vw_medicines` (
`medicine_id` int(10) unsigned
,`medicine_code` varchar(20)
,`medicine_name` varchar(200)
,`main_category` varchar(100)
,`sub_category_1` varchar(100)
,`sub_category_2` varchar(100)
,`generic_name` varchar(100)
,`trade_name` varchar(100)
,`manufacturer` varchar(100)
,`strength` varchar(61)
,`strength_value` varchar(50)
,`strength_unit` varchar(10)
,`issuing_unit` varchar(50)
,`unit_symbol` varchar(20)
,`mrp` decimal(10,2)
,`dosage_form` varchar(50)
,`route` varchar(50)
,`instructions` text
,`reorder_level` int(11)
,`is_expiry_tracked` tinyint(1)
,`discount_enabled` tinyint(1)
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`main_category_id` int(10) unsigned
,`sub_category1_id` int(10) unsigned
,`sub_category2_id` int(10) unsigned
,`generic_id` int(10) unsigned
,`trade_id` int(10) unsigned
,`strength_unit_id` int(10) unsigned
,`issuing_unit_id` int(10) unsigned
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_patients`
-- (See below for the actual view)
--
CREATE TABLE `vw_patients` (
`patient_id` int(10) unsigned
,`patient_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`nic_number` varchar(20)
,`date_of_birth` date
,`gender` enum('Male','Female','Other')
,`phone` varchar(20)
,`address` text
,`blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown')
,`allergies` text
,`chronic_diseases` text
,`weight` decimal(5,2)
,`height` decimal(5,2)
,`emergency_contact_name` varchar(100)
,`emergency_contact_phone` varchar(20)
,`registration_date` date
,`registered_by` int(10) unsigned
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`age` bigint(21)
,`full_name` varchar(101)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_today_queue`
-- (See below for the actual view)
--
CREATE TABLE `vw_today_queue` (
`id` int(10) unsigned
,`session_id` int(10) unsigned
,`patient_id` int(10) unsigned
,`queue_number` int(11)
,`status` enum('Waiting','In Progress','Completed','No Show','Cancelled')
,`check_in_time` datetime
,`start_time` datetime
,`end_time` datetime
,`notes` text
,`created_at` timestamp
,`updated_at` timestamp
,`patient_code` varchar(20)
,`patient_name` varchar(101)
,`phone` varchar(20)
,`age` bigint(21)
,`gender` enum('Male','Female','Other')
,`session_code` varchar(20)
,`doctor_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_today_sessions`
-- (See below for the actual view)
--
CREATE TABLE `vw_today_sessions` (
`session_id` int(10) unsigned
,`session_code` varchar(20)
,`doctor_id` int(10) unsigned
,`session_date` date
,`start_time` time
,`end_time` time
,`status` enum('Scheduled','Active','Completed','Cancelled')
,`max_patients` int(11)
,`notes` text
,`created_by` int(10) unsigned
,`created_at` timestamp
,`updated_at` timestamp
,`doctor_name` varchar(100)
,`patient_count` bigint(21)
,`waiting_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_medicines`
--
DROP TABLE IF EXISTS `vw_medicines`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_medicines`  AS SELECT `m`.`medicine_id` AS `medicine_id`, `m`.`medicine_code` AS `medicine_code`, `m`.`medicine_name` AS `medicine_name`, `mc`.`category_name` AS `main_category`, `sc1`.`category_name` AS `sub_category_1`, `sc2`.`category_name` AS `sub_category_2`, `g`.`generic_name` AS `generic_name`, `t`.`trade_name` AS `trade_name`, `t`.`manufacturer` AS `manufacturer`, concat(`m`.`strength_value`,' ',coalesce(`su`.`unit_symbol`,'')) AS `strength`, `m`.`strength_value` AS `strength_value`, `su`.`unit_symbol` AS `strength_unit`, `iu`.`unit_name` AS `issuing_unit`, `iu`.`unit_symbol` AS `unit_symbol`, `m`.`mrp` AS `mrp`, `m`.`dosage_form` AS `dosage_form`, `m`.`route` AS `route`, `m`.`instructions` AS `instructions`, `m`.`reorder_level` AS `reorder_level`, `m`.`is_expiry_tracked` AS `is_expiry_tracked`, `m`.`discount_enabled` AS `discount_enabled`, `m`.`is_active` AS `is_active`, `m`.`created_at` AS `created_at`, `m`.`updated_at` AS `updated_at`, `m`.`main_category_id` AS `main_category_id`, `m`.`sub_category1_id` AS `sub_category1_id`, `m`.`sub_category2_id` AS `sub_category2_id`, `m`.`generic_id` AS `generic_id`, `m`.`trade_id` AS `trade_id`, `m`.`strength_unit_id` AS `strength_unit_id`, `m`.`issuing_unit_id` AS `issuing_unit_id` FROM (((((((`medicines` `m` left join `drug_categories` `mc` on(`m`.`main_category_id` = `mc`.`category_id`)) left join `drug_categories` `sc1` on(`m`.`sub_category1_id` = `sc1`.`category_id`)) left join `drug_categories` `sc2` on(`m`.`sub_category2_id` = `sc2`.`category_id`)) left join `generic_names` `g` on(`m`.`generic_id` = `g`.`generic_id`)) left join `trade_names` `t` on(`m`.`trade_id` = `t`.`trade_id`)) left join `strength_units` `su` on(`m`.`strength_unit_id` = `su`.`strength_unit_id`)) left join `issuing_units` `iu` on(`m`.`issuing_unit_id` = `iu`.`unit_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_patients`
--
DROP TABLE IF EXISTS `vw_patients`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_patients`  AS SELECT `p`.`patient_id` AS `patient_id`, `p`.`patient_code` AS `patient_code`, `p`.`first_name` AS `first_name`, `p`.`last_name` AS `last_name`, `p`.`nic_number` AS `nic_number`, `p`.`date_of_birth` AS `date_of_birth`, `p`.`gender` AS `gender`, `p`.`phone` AS `phone`, `p`.`address` AS `address`, `p`.`blood_group` AS `blood_group`, `p`.`allergies` AS `allergies`, `p`.`chronic_diseases` AS `chronic_diseases`, `p`.`weight` AS `weight`, `p`.`height` AS `height`, `p`.`emergency_contact_name` AS `emergency_contact_name`, `p`.`emergency_contact_phone` AS `emergency_contact_phone`, `p`.`registration_date` AS `registration_date`, `p`.`registered_by` AS `registered_by`, `p`.`is_active` AS `is_active`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, timestampdiff(YEAR,`p`.`date_of_birth`,curdate()) AS `age`, concat(`p`.`first_name`,' ',`p`.`last_name`) AS `full_name` FROM `patients` AS `p` WHERE `p`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_today_queue`
--
DROP TABLE IF EXISTS `vw_today_queue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_today_queue`  AS SELECT `sp`.`id` AS `id`, `sp`.`session_id` AS `session_id`, `sp`.`patient_id` AS `patient_id`, `sp`.`queue_number` AS `queue_number`, `sp`.`status` AS `status`, `sp`.`check_in_time` AS `check_in_time`, `sp`.`start_time` AS `start_time`, `sp`.`end_time` AS `end_time`, `sp`.`notes` AS `notes`, `sp`.`created_at` AS `created_at`, `sp`.`updated_at` AS `updated_at`, `p`.`patient_code` AS `patient_code`, concat(`p`.`first_name`,' ',`p`.`last_name`) AS `patient_name`, `p`.`phone` AS `phone`, timestampdiff(YEAR,`p`.`date_of_birth`,curdate()) AS `age`, `p`.`gender` AS `gender`, `cs`.`session_code` AS `session_code`, `u`.`full_name` AS `doctor_name` FROM (((`session_patients` `sp` join `patients` `p` on(`sp`.`patient_id` = `p`.`patient_id`)) join `clinic_sessions` `cs` on(`sp`.`session_id` = `cs`.`session_id`)) join `users` `u` on(`cs`.`doctor_id` = `u`.`user_id`)) WHERE `cs`.`session_date` = curdate() ORDER BY `sp`.`queue_number` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_today_sessions`
--
DROP TABLE IF EXISTS `vw_today_sessions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_today_sessions`  AS SELECT `cs`.`session_id` AS `session_id`, `cs`.`session_code` AS `session_code`, `cs`.`doctor_id` AS `doctor_id`, `cs`.`session_date` AS `session_date`, `cs`.`start_time` AS `start_time`, `cs`.`end_time` AS `end_time`, `cs`.`status` AS `status`, `cs`.`max_patients` AS `max_patients`, `cs`.`notes` AS `notes`, `cs`.`created_by` AS `created_by`, `cs`.`created_at` AS `created_at`, `cs`.`updated_at` AS `updated_at`, `u`.`full_name` AS `doctor_name`, (select count(0) from `session_patients` `sp` where `sp`.`session_id` = `cs`.`session_id`) AS `patient_count`, (select count(0) from `session_patients` `sp` where `sp`.`session_id` = `cs`.`session_id` and `sp`.`status` = 'Waiting') AS `waiting_count` FROM (`clinic_sessions` `cs` join `users` `u` on(`cs`.`doctor_id` = `u`.`user_id`)) WHERE `cs`.`session_date` = curdate() ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `clinic_sessions`
--
ALTER TABLE `clinic_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_code` (`session_code`),
  ADD KEY `idx_session_code` (`session_code`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_date` (`session_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_session_created_by` (`created_by`);

--
-- Indexes for table `doctor_details`
--
ALTER TABLE `doctor_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_specialization` (`specialization`),
  ADD KEY `idx_license` (`license_number`);

--
-- Indexes for table `drug_categories`
--
ALTER TABLE `drug_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_level` (`category_level`),
  ADD KEY `idx_name` (`category_name`);

--
-- Indexes for table `generic_names`
--
ALTER TABLE `generic_names`
  ADD PRIMARY KEY (`generic_id`),
  ADD UNIQUE KEY `generic_name` (`generic_name`),
  ADD KEY `idx_name` (`generic_name`);

--
-- Indexes for table `issuing_units`
--
ALTER TABLE `issuing_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `unit_name` (`unit_name`),
  ADD KEY `idx_name` (`unit_name`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `idx_type` (`type_id`),
  ADD KEY `idx_name` (`test_name`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`),
  ADD UNIQUE KEY `medicine_code` (`medicine_code`),
  ADD KEY `idx_code` (`medicine_code`),
  ADD KEY `idx_name` (`medicine_name`),
  ADD KEY `idx_generic` (`generic_id`),
  ADD KEY `idx_trade` (`trade_id`),
  ADD KEY `idx_main_cat` (`main_category_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `fk_medicine_sub1` (`sub_category1_id`),
  ADD KEY `fk_medicine_sub2` (`sub_category2_id`),
  ADD KEY `fk_medicine_strength_unit` (`strength_unit_id`),
  ADD KEY `fk_medicine_issuing_unit` (`issuing_unit_id`);
ALTER TABLE `medicines` ADD FULLTEXT KEY `idx_search` (`medicine_name`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD UNIQUE KEY `nic_number` (`nic_number`),
  ADD KEY `idx_patient_code` (`patient_code`),
  ADD KEY `idx_nic` (`nic_number`),
  ADD KEY `idx_name` (`first_name`,`last_name`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_registration_date` (`registration_date`),
  ADD KEY `fk_patient_registered_by` (`registered_by`);

--
-- Indexes for table `patient_prescriptions`
--
ALTER TABLE `patient_prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD UNIQUE KEY `prescription_code` (`prescription_code`),
  ADD KEY `idx_prescription_code` (`prescription_code`),
  ADD KEY `idx_visit` (`visit_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_date` (`prescription_date`),
  ADD KEY `parent_prescription_id` (`parent_prescription_id`);

--
-- Indexes for table `patient_prescription_medicines`
--
ALTER TABLE `patient_prescription_medicines`
  ADD PRIMARY KEY (`medicine_id`),
  ADD KEY `idx_prescription` (`prescription_id`),
  ADD KEY `idx_medicine_name` (`medicine_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_role_name` (`role_name`);

--
-- Indexes for table `session_patients`
--
ALTER TABLE `session_patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session_patient` (`session_id`,`patient_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_queue` (`session_id`,`queue_number`);

--
-- Indexes for table `strength_units`
--
ALTER TABLE `strength_units`
  ADD PRIMARY KEY (`strength_unit_id`),
  ADD UNIQUE KEY `unit_name` (`unit_name`),
  ADD KEY `idx_name` (`unit_name`);

--
-- Indexes for table `patient_tests`
--
ALTER TABLE `patient_tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `idx_visit` (`visit_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`test_type`);

--
-- Indexes for table `test_types`
--
ALTER TABLE `test_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`),
  ADD KEY `idx_order` (`display_order`);

--
-- Indexes for table `trade_names`
--
ALTER TABLE `trade_names`
  ADD PRIMARY KEY (`trade_id`),
  ADD KEY `idx_name` (`trade_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD UNIQUE KEY `visit_code` (`visit_code`),
  ADD KEY `idx_visit_code` (`visit_code`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_date` (`visit_date`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indexes for table `patient_vital_signs`
--
ALTER TABLE `patient_vital_signs`
  ADD PRIMARY KEY (`vital_id`),
  ADD KEY `idx_visit` (`visit_id`),
  ADD KEY `idx_patient` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic_sessions`
--
ALTER TABLE `clinic_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_details`
--
ALTER TABLE `doctor_details`
  MODIFY `detail_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drug_categories`
--
ALTER TABLE `drug_categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generic_names`
--
ALTER TABLE `generic_names`
  MODIFY `generic_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issuing_units`
--
ALTER TABLE `issuing_units`
  MODIFY `unit_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `test_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `medicine_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_prescriptions`
--
ALTER TABLE `patient_prescriptions`
  MODIFY `prescription_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_prescription_medicines`
--
ALTER TABLE `patient_prescription_medicines`
  MODIFY `medicine_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session_patients`
--
ALTER TABLE `session_patients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `strength_units`
--
ALTER TABLE `strength_units`
  MODIFY `strength_unit_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_tests`
--
ALTER TABLE `patient_tests`
  MODIFY `test_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_types`
--
ALTER TABLE `test_types`
  MODIFY `type_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trade_names`
--
ALTER TABLE `trade_names`
  MODIFY `trade_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_visits`
--
ALTER TABLE `patient_visits`
  MODIFY `visit_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_vital_signs`
--
ALTER TABLE `patient_vital_signs`
  MODIFY `vital_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `clinic_sessions`
--
ALTER TABLE `clinic_sessions`
  ADD CONSTRAINT `fk_session_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_session_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `doctor_details`
--
ALTER TABLE `doctor_details`
  ADD CONSTRAINT `fk_doctor_detail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `drug_categories`
--
ALTER TABLE `drug_categories`
  ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `drug_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `fk_test_type` FOREIGN KEY (`type_id`) REFERENCES `test_types` (`type_id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_medicine_generic` FOREIGN KEY (`generic_id`) REFERENCES `generic_names` (`generic_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicine_issuing_unit` FOREIGN KEY (`issuing_unit_id`) REFERENCES `issuing_units` (`unit_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicine_main_cat` FOREIGN KEY (`main_category_id`) REFERENCES `drug_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicine_strength_unit` FOREIGN KEY (`strength_unit_id`) REFERENCES `strength_units` (`strength_unit_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicine_sub1` FOREIGN KEY (`sub_category1_id`) REFERENCES `drug_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicine_sub2` FOREIGN KEY (`sub_category2_id`) REFERENCES `drug_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicine_trade` FOREIGN KEY (`trade_id`) REFERENCES `trade_names` (`trade_id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `patient_prescriptions`
--
ALTER TABLE `patient_prescriptions`
  ADD CONSTRAINT `fk_patient_prescription_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patient_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patient_prescription_visit` FOREIGN KEY (`visit_id`) REFERENCES `patient_visits` (`visit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `patient_prescriptions_ibfk_1` FOREIGN KEY (`parent_prescription_id`) REFERENCES `patient_prescriptions` (`prescription_id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_prescription_medicines`
--
ALTER TABLE `patient_prescription_medicines`
  ADD CONSTRAINT `fk_medicine_patient_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `patient_prescriptions` (`prescription_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `session_patients`
--
ALTER TABLE `session_patients`
  ADD CONSTRAINT `fk_sp_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sp_session` FOREIGN KEY (`session_id`) REFERENCES `clinic_sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_tests`
--
ALTER TABLE `patient_tests`
  ADD CONSTRAINT `fk_test_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_test_visit` FOREIGN KEY (`visit_id`) REFERENCES `patient_visits` (`visit_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE;

--
-- Constraints for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD CONSTRAINT `fk_visit_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visit_session` FOREIGN KEY (`session_id`) REFERENCES `clinic_sessions` (`session_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `patient_vital_signs`
--
ALTER TABLE `patient_vital_signs`
  ADD CONSTRAINT `fk_vital_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vital_visit` FOREIGN KEY (`visit_id`) REFERENCES `patient_visits` (`visit_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
