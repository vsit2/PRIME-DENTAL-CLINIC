-- Database Schema for Prime Dental Clinic Management System
-- Database Name: prime_dental_db

CREATE DATABASE IF NOT EXISTS `prime_dental_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `prime_dental_db`;

-- 1. Users Table (Dentist & Clinic Staff Login)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'dentist', 'staff') NOT NULL DEFAULT 'dentist',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Clinic Settings Table
CREATE TABLE IF NOT EXISTS `clinic_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clinic_name` VARCHAR(150) NOT NULL DEFAULT 'PRIME DENTAL CLINIC',
    `tagline` VARCHAR(200) NOT NULL DEFAULT 'The Prime Destination For Smiles',
    `dentist_name` VARCHAR(150) NOT NULL DEFAULT 'Dr. Rutuja Deshmukh',
    `dentist_qualification` VARCHAR(100) NOT NULL DEFAULT 'B.D.S (Mumbai)',
    `reg_no` VARCHAR(50) NOT NULL DEFAULT 'A44351',
    `phone` VARCHAR(20) NOT NULL DEFAULT '9892429014',
    `email` VARCHAR(100) NOT NULL DEFAULT 'rutujadeshmukh0124@gmail.com',
    `address` TEXT NOT NULL,
    `currency_symbol` VARCHAR(10) NOT NULL DEFAULT '₹',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Patients Table
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_no` VARCHAR(30) NOT NULL UNIQUE,
    `reg_date` DATE NOT NULL,
    `first_name` VARCHAR(60) NOT NULL,
    `middle_name` VARCHAR(60) DEFAULT NULL,
    `last_name` VARCHAR(60) NOT NULL,
    `dob` DATE DEFAULT NULL,
    `age` INT NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `place_of_work` VARCHAR(150) DEFAULT NULL,
    `education` VARCHAR(100) DEFAULT NULL,
    `physician_name` VARCHAR(100) DEFAULT NULL,
    `physician_contact` VARCHAR(30) DEFAULT NULL,
    `emergency_contact` VARCHAR(30) DEFAULT NULL,
    `emergency_person` VARCHAR(100) DEFAULT NULL,
    `emergency_relationship` VARCHAR(50) DEFAULT NULL,
    `initial_reasons` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_reg_no` (`registration_no`),
    INDEX `idx_name` (`first_name`, `last_name`),
    INDEX `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Medical History Table (1-to-1 with patients)
CREATE TABLE IF NOT EXISTS `medical_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL UNIQUE,
    `asthma` TINYINT(1) DEFAULT 0,
    `bleeding_disorder` TINYINT(1) DEFAULT 0,
    `cardiovascular_disorders` TINYINT(1) DEFAULT 0,
    `drug_allergy` TINYINT(1) DEFAULT 0,
    `drug_allergy_details` VARCHAR(255) DEFAULT NULL,
    `endocrine_disorders` TINYINT(1) DEFAULT 0,
    `fits_fainting` TINYINT(1) DEFAULT 0,
    `gastrointestinal_disorder` TINYINT(1) DEFAULT 0,
    `hospitalization` TINYINT(1) DEFAULT 0,
    `habits` TINYINT(1) DEFAULT 0,
    `habits_details` VARCHAR(255) DEFAULT NULL,
    `hiv_aids` TINYINT(1) DEFAULT 0,
    `hepatitis` TINYINT(1) DEFAULT 0,
    `tb` TINYINT(1) DEFAULT 0,
    `kidney_disorder` TINYINT(1) DEFAULT 0,
    `pregnancy_lactation` TINYINT(1) DEFAULT 0,
    `current_medication` TINYINT(1) DEFAULT 0,
    `medication_details` VARCHAR(255) DEFAULT NULL,
    `other_conditions` TINYINT(1) DEFAULT 0,
    `other_details` TEXT DEFAULT NULL,
    `additional_notes` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Visits Table
CREATE TABLE IF NOT EXISTS `visits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `visit_date` DATE NOT NULL,
    `chief_complaint` TEXT DEFAULT NULL,
    `reason_for_visit` TEXT DEFAULT NULL,
    `diagnosis` TEXT DEFAULT NULL,
    `treatment` TEXT DEFAULT NULL,
    `tooth_number` VARCHAR(100) DEFAULT NULL,
    `dentist_notes` TEXT DEFAULT NULL,
    `prescription` TEXT DEFAULT NULL,
    `follow_up_date` DATE DEFAULT NULL,
    `treatment_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_patient_visit` (`patient_id`),
    INDEX `idx_visit_date` (`visit_date`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_no` VARCHAR(30) NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `visit_id` INT DEFAULT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Cash', 'UPI', 'Card', 'Bank Transfer', 'Other') NOT NULL DEFAULT 'Cash',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_patient_payment` (`patient_id`),
    INDEX `idx_visit_payment` (`visit_id`),
    INDEX `idx_payment_date` (`payment_date`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
