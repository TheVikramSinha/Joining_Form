-- ============================================================
-- JOINING FORM SYSTEM v2 — Full Database Setup
-- Run this once in phpMyAdmin → Import
-- ============================================================

CREATE DATABASE IF NOT EXISTS joining_form CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE joining_form;

CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100), middle_name VARCHAR(100), last_name VARCHAR(100),
    date_of_birth DATE, parent_name VARCHAR(200),
    gender ENUM('Male','Female','Other'), marital_status VARCHAR(50), photo_path VARCHAR(255),
    present_address TEXT, present_state VARCHAR(100), present_district VARCHAR(100),
    present_phone VARCHAR(20), email VARCHAR(150),
    permanent_address TEXT, permanent_state VARCHAR(100), permanent_district VARCHAR(100), permanent_phone VARCHAR(20),
    date_of_appointment DATE, office_at_initial_joining VARCHAR(200),
    date_of_joining DATE, initial_designation VARCHAR(150), mode_of_recruitment VARCHAR(100),
    basic_pay DECIMAL(12,2), bank_name VARCHAR(150), ifsc_code VARCHAR(20),
    account_no VARCHAR(50), pan_card VARCHAR(20),
    commitment_person VARCHAR(200), commitment_text TEXT,
    nominee_name VARCHAR(200), nominee_relation VARCHAR(100), nominee_age INT,
    nominee_address TEXT, nominee_state VARCHAR(100), nominee_block VARCHAR(100), nominee_district VARCHAR(100),
    reference_1 TEXT, reference_2 TEXT,
    emergency_contact_name VARCHAR(200), emergency_address TEXT, emergency_phone VARCHAR(20),
    docs_certificates ENUM('Submitted','Will Submit') DEFAULT NULL,
    docs_dob ENUM('Submitted','Will Submit') DEFAULT NULL,
    docs_experience ENUM('Submitted','Will Submit') DEFAULT NULL,
    docs_relieving ENUM('Submitted','Will Submit') DEFAULT NULL,
    status ENUM('Complete','Incomplete') DEFAULT 'Incomplete',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    education_type ENUM('Basic','Grade','Professional') NOT NULL,
    board_university VARCHAR(200), marks_percent DECIMAL(5,2),
    passing_year YEAR, stream VARCHAR(150), grade VARCHAR(20),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    training_location ENUM('India','Abroad') NOT NULL,
    training_type VARCHAR(100), topic_name VARCHAR(200),
    institute_name VARCHAR(200), sponsored_by VARCHAR(150),
    date_from DATE, date_to DATE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    member_name VARCHAR(200), relation VARCHAR(100),
    date_of_birth DATE, dependent ENUM('Yes','No'),
    employment_status ENUM('Employed','Unemployed'),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    org_name VARCHAR(200), designation VARCHAR(150),
    salary_drawn DECIMAL(12,2), duration_from DATE, duration_to DATE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS candidate_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    doc_label VARCHAR(200) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    admin_username VARCHAR(100),
    note TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin password is set by reset_password.php after setup
INSERT INTO admin_users (username, password_hash)
VALUES ('admin', 'NOT_SET_RUN_RESET_PASSWORD')
ON DUPLICATE KEY UPDATE id=id;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('company_name',    'Your Company Name'),
('company_address', '123 Business Street, City, State — 000000'),
('company_phone',   '+91 00000 00000'),
('company_email',   'hr@yourcompany.com'),
('company_website', 'www.yourcompany.com'),
('brand_color',     '#1a56db'),
('logo_path',       ''),
('form_title',      'Employee Joining Form');
