-- =============================================================================
-- StudentHub Portal - MySQL Database Schema (Practical 8)
-- Subject: Web Development Frameworks (ITUE203) - Semester 3
-- Charotar University of Science and Technology (CHARUSAT)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub_db`;

-- Drop tables in reverse foreign-key order
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

-- -----------------------------------------------------------------------------
-- 1. USERS TABLE (Practicals 8, 9, 10, 14)
-- Core authentication table storing hashed passwords and roles
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin', 'faculty') NOT NULL DEFAULT 'student',
  `remember_token` VARCHAR(64) NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. STUDENTS TABLE (Practicals 8, 11)
-- Detailed academic profiles linked to users (1-to-1 or independent record)
-- -----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `student_id` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(15) NULL,
  `department` VARCHAR(80) NOT NULL,
  `course` VARCHAR(60) NOT NULL,
  `year` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL DEFAULT '3rd Year',
  `semester` VARCHAR(10) DEFAULT '5th',
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
  `gpa` DECIMAL(3, 2) DEFAULT 8.00,
  `status` ENUM('active', 'inactive', 'graduated') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_students_department` (`department`),
  INDEX `idx_students_year` (`year`),
  INDEX `idx_students_search` (`name`, `student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. EVENTS TABLE (Practicals 8, 12, 13)
-- Campus events, workshops, hackathons, and sports meets
-- -----------------------------------------------------------------------------
CREATE TABLE `events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `category` ENUM('Technical', 'Workshop', 'Sports', 'Cultural', 'Career', 'Social') NOT NULL DEFAULT 'Technical',
  `event_date` DATE NOT NULL,
  `event_time` VARCHAR(50) DEFAULT '10:00 AM - 04:00 PM',
  `venue` VARCHAR(120) NOT NULL,
  `organizer` VARCHAR(100) DEFAULT 'StudentHub Committee',
  `capacity` INT UNSIGNED NOT NULL DEFAULT 100,
  `registered_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `poster_image` VARCHAR(255) NULL,
  `status` ENUM('Open', 'Closed', 'Cancelled') NOT NULL DEFAULT 'Open',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_events_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_events_date` (`event_date`),
  INDEX `idx_events_category` (`category`),
  INDEX `idx_events_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. REGISTRATIONS TABLE (Practicals 8, 13, 14)
-- Student registrations for campus events
-- -----------------------------------------------------------------------------
CREATE TABLE `registrations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `event_id` INT UNSIGNED NOT NULL,
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('confirmed', 'waitlisted', 'cancelled') NOT NULL DEFAULT 'confirmed',
  `notes` VARCHAR(255) NULL,
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_student_event` (`student_id`, `event_id`),
  INDEX `idx_reg_date` (`registration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. AUDIT LOGS TABLE (Practicals 8, 9, 12, 14)
-- Immutable audit trail recording administrative and authentication actions
-- -----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
