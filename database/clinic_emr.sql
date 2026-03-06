-- =====================================================
-- CLINIC EMR DATABASE SCHEMA
-- Version: 1.0
-- Database: MySQL 5.7+
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS clinic_emr 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE clinic_emr;

-- =====================================================
-- TABLE: roles
-- Stores user roles (Assistant, Doctor)
-- =====================================================
CREATE TABLE roles (
    role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: users
-- Stores all system users with authentication data
-- =====================================================
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_role (role_id),
    INDEX idx_active (is_active),
    
    CONSTRAINT fk_user_role 
        FOREIGN KEY (role_id) 
        REFERENCES roles(role_id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: patients
-- Stores complete patient demographic and medical info
-- =====================================================
CREATE TABLE patients (
    patient_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    nic_number VARCHAR(20) UNIQUE,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown') DEFAULT 'Unknown',
    allergies TEXT,
    chronic_diseases TEXT,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    registration_date DATE NOT NULL,
    registered_by INT UNSIGNED,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_patient_code (patient_code),
    INDEX idx_nic (nic_number),
    INDEX idx_name (first_name, last_name),
    INDEX idx_phone (phone),
    INDEX idx_registration_date (registration_date),
    
    CONSTRAINT fk_patient_registered_by 
        FOREIGN KEY (registered_by) 
        REFERENCES users(user_id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: clinic_sessions
-- Stores daily clinic sessions created by assistants
-- =====================================================
CREATE TABLE clinic_sessions (
    session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_code VARCHAR(20) NOT NULL UNIQUE,
    doctor_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    status ENUM('Scheduled', 'Active', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    max_patients INT DEFAULT 50,
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session_code (session_code),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (session_date),
    INDEX idx_status (status),
    
    CONSTRAINT fk_session_doctor 
        FOREIGN KEY (doctor_id) 
        REFERENCES users(user_id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT fk_session_created_by 
        FOREIGN KEY (created_by) 
        REFERENCES users(user_id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: session_patients
-- Links patients to clinic sessions (queue management)
-- =====================================================
CREATE TABLE session_patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    queue_number INT NOT NULL,
    status ENUM('Waiting', 'In Progress', 'Completed', 'No Show', 'Cancelled') DEFAULT 'Waiting',
    check_in_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    start_time DATETIME,
    end_time DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session (session_id),
    INDEX idx_patient (patient_id),
    INDEX idx_status (status),
    INDEX idx_queue (session_id, queue_number),
    
    UNIQUE KEY unique_session_patient (session_id, patient_id),
    
    CONSTRAINT fk_sp_session 
        FOREIGN KEY (session_id) 
        REFERENCES clinic_sessions(session_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_sp_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES patients(patient_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: visits
-- Stores patient visit records with diagnosis
-- =====================================================
CREATE TABLE visits (
    visit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    notes TEXT,
    follow_up_date DATE,
    status ENUM('In Progress', 'Completed', 'Follow Up Required') DEFAULT 'In Progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_visit_code (visit_code),
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (visit_date),
    INDEX idx_session (session_id),
    
    CONSTRAINT fk_visit_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES patients(patient_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_visit_doctor 
        FOREIGN KEY (doctor_id) 
        REFERENCES users(user_id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT fk_visit_session 
        FOREIGN KEY (session_id) 
        REFERENCES clinic_sessions(session_id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: vital_signs
-- Stores vital signs recorded during visits
-- =====================================================
CREATE TABLE vital_signs (
    vital_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    temperature DECIMAL(4,1),
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    pulse_rate INT,
    respiratory_rate INT,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    bmi DECIMAL(4,1),
    oxygen_saturation INT,
    notes TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_visit (visit_id),
    INDEX idx_patient (patient_id),
    
    CONSTRAINT fk_vital_visit 
        FOREIGN KEY (visit_id) 
        REFERENCES visits(visit_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_vital_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES patients(patient_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: tests
-- Stores medical test requests
-- =====================================================
CREATE TABLE tests (
    test_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_type ENUM('Blood Test', 'Urine Test', 'X-Ray', 'ECG', 'Ultrasound', 'MRI', 'CT Scan', 'Other') NOT NULL,
    instructions TEXT,
    urgency ENUM('Routine', 'Urgent', 'STAT') DEFAULT 'Routine',
    status ENUM('Requested', 'Sample Collected', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Requested',
    result TEXT,
    result_date DATE,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_visit (visit_id),
    INDEX idx_patient (patient_id),
    INDEX idx_status (status),
    INDEX idx_type (test_type),
    
    CONSTRAINT fk_test_visit 
        FOREIGN KEY (visit_id) 
        REFERENCES visits(visit_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_test_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES patients(patient_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: prescriptions
-- Stores prescription headers
-- =====================================================
CREATE TABLE prescriptions (
    prescription_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_code VARCHAR(20) NOT NULL UNIQUE,
    visit_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    prescription_date DATE NOT NULL,
    notes TEXT,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_prescription_code (prescription_code),
    INDEX idx_visit (visit_id),
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (prescription_date),
    
    CONSTRAINT fk_prescription_visit 
        FOREIGN KEY (visit_id) 
        REFERENCES visits(visit_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_prescription_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES patients(patient_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT fk_prescription_doctor 
        FOREIGN KEY (doctor_id) 
        REFERENCES users(user_id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: prescription_medicines
-- Stores individual medicines in prescriptions
-- =====================================================
CREATE TABLE prescription_medicines (
    medicine_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT UNSIGNED NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    dose VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    duration_days INT NOT NULL,
    quantity INT,
    route ENUM('Oral', 'Topical', 'Injection', 'Inhalation', 'Other') DEFAULT 'Oral',
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_prescription (prescription_id),
    INDEX idx_medicine_name (medicine_name),
    
    CONSTRAINT fk_medicine_prescription 
        FOREIGN KEY (prescription_id) 
        REFERENCES prescriptions(prescription_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: activity_log
-- Stores system activity for auditing
-- =====================================================
CREATE TABLE activity_log (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT UNSIGNED,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_date (created_at),
    
    CONSTRAINT fk_log_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(user_id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default roles
INSERT INTO roles (role_name, role_description) VALUES 
('Assistant', 'Clinic assistant - manages patient registration and appointments'),
('Doctor', 'Medical doctor - manages patient consultations and prescriptions');

-- Insert default users (password: password123)
-- Password hash generated using PHP password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (username, password_hash, email, full_name, role_id, phone, is_active) VALUES 
('assistant', ' ', 'assistant@clinic.com', 'John Smith', 1, '1234567890', 1),
('doctor', ' ', 'doctor@clinic.com', 'Dr. Sarah Johnson', 2, '0987654321', 1),
('admin', ' ', 'admin@clinic.com', 'System Admin', 2, '1122334455', 1);



-- =====================================================
-- TABLE: doctor_details
-- Stores additional details specific to doctors
-- Linked to users table via user_id
-- =====================================================
CREATE TABLE doctor_details (
    detail_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    specialization VARCHAR(150) NOT NULL,
    qualification VARCHAR(255) NOT NULL,
    license_number VARCHAR(50),
    experience_years INT UNSIGNED DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT,
    available_days VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
    available_time_start TIME DEFAULT '08:00:00',
    available_time_end TIME DEFAULT '17:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_specialization (specialization),
    INDEX idx_license (license_number),
    
    CONSTRAINT fk_doctor_detail_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(user_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert doctor details for existing doctor user
INSERT INTO doctor_details (user_id, specialization, qualification, license_number, experience_years, consultation_fee)
SELECT user_id, 'General Medicine', 'MBBS', 'LIC-001', 5, 1500.00
FROM users WHERE username = 'doctor' AND role_id = 2
ON DUPLICATE KEY UPDATE specialization = 'General Medicine';



-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Generate unique patient code
CREATE PROCEDURE GeneratePatientCode(OUT new_code VARCHAR(20))
BEGIN
    DECLARE last_id INT;
    SELECT COALESCE(MAX(patient_id), 0) + 1 INTO last_id FROM patients;
    SET new_code = CONCAT('PAT', LPAD(last_id, 6, '0'));
END //

-- Generate unique session code
CREATE PROCEDURE GenerateSessionCode(OUT new_code VARCHAR(20))
BEGIN
    DECLARE last_id INT;
    SELECT COALESCE(MAX(session_id), 0) + 1 INTO last_id FROM clinic_sessions;
    SET new_code = CONCAT('SES', DATE_FORMAT(CURDATE(), '%Y%m%d'), LPAD(last_id, 3, '0'));
END //

-- Generate unique visit code
CREATE PROCEDURE GenerateVisitCode(OUT new_code VARCHAR(20))
BEGIN
    DECLARE last_id INT;
    SELECT COALESCE(MAX(visit_id), 0) + 1 INTO last_id FROM visits;
    SET new_code = CONCAT('VIS', DATE_FORMAT(CURDATE(), '%Y%m%d'), LPAD(last_id, 4, '0'));
END //

-- Generate unique prescription code
CREATE PROCEDURE GeneratePrescriptionCode(OUT new_code VARCHAR(20))
BEGIN
    DECLARE last_id INT;
    SELECT COALESCE(MAX(prescription_id), 0) + 1 INTO last_id FROM prescriptions;
    SET new_code = CONCAT('RX', DATE_FORMAT(CURDATE(), '%Y%m%d'), LPAD(last_id, 4, '0'));
END //

-- Get next queue number for a session
CREATE PROCEDURE GetNextQueueNumber(IN p_session_id INT, OUT queue_num INT)
BEGIN
    SELECT COALESCE(MAX(queue_number), 0) + 1 INTO queue_num 
    FROM session_patients 
    WHERE session_id = p_session_id;
END //

DELIMITER ;

-- =====================================================
-- VIEWS
-- =====================================================

-- View: Today's sessions with doctor info
CREATE VIEW vw_today_sessions AS
SELECT 
    cs.*,
    u.full_name AS doctor_name,
    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id) AS patient_count,
    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Waiting') AS waiting_count
FROM clinic_sessions cs
JOIN users u ON cs.doctor_id = u.user_id
WHERE cs.session_date = CURDATE();

-- View: Patient full info with age
CREATE VIEW vw_patients AS
SELECT 
    p.*,
    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
    CONCAT(p.first_name, ' ', p.last_name) AS full_name
FROM patients p
WHERE p.is_active = 1;

-- View: Today's queue
CREATE VIEW vw_today_queue AS
SELECT 
    sp.*,
    p.patient_code,
    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
    p.phone,
    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
    p.gender,
    cs.session_code,
    u.full_name AS doctor_name
FROM session_patients sp
JOIN patients p ON sp.patient_id = p.patient_id
JOIN clinic_sessions cs ON sp.session_id = cs.session_id
JOIN users u ON cs.doctor_id = u.user_id
WHERE cs.session_date = CURDATE()
ORDER BY sp.queue_number;