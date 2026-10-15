-- MySQL/MariaDB dump converted from vaccination_db.sqlite
-- Target: phpMyAdmin / MySQL in XAMPP

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `vaccination_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vaccination_db`;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `vaccination_records`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `children`;
DROP TABLE IF EXISTS `vaccines`;
DROP TABLE IF EXISTS `hospitals`;
DROP TABLE IF EXISTS `users`;

-- Table: users
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `created_at`) VALUES
(1, 'System Administrator', 'admin@evaccine.org', '$2y$12$L4y0r3rQC6DWzWTAjM0FeuAm4nboF5Wo4ShSyU5el5ZqtmzPvU8pG', 'admin', '+1-800-555-0199', 'Central Health Department HQ', '2026-10-01 14:45:51'),
(2, 'Sarah Jenkins', 'parent@evaccine.org', '$2y$12$ltbOIHrJyDOngaVLSqTS4ukEZZErwLYHCxfHYa5a1STWsfIshmR9i', 'parent', '+1-555-014-8821', '42 Elm Street, Springfield', '2026-10-01 14:45:51'),
(3, 'David Miller', 'david@evaccine.org', '$2y$12$ltbOIHrJyDOngaVLSqTS4ukEZZErwLYHCxfHYa5a1STWsfIshmR9i', 'parent', '+1-555-019-3342', '789 Oakridge Ave, Springfield', '2026-10-01 14:45:51'),
(4, 'City General Hospital Staff', 'cityhospital@evaccine.org', '$2y$12$0U6dpWd2zBu5XoHTkhAIR.0P4Lp1UrZg9ZyWiWM5xw68mvPKNWn4e', 'hospital', '+1-555-011-2000', '100 Medical Center Blvd, Springfield', '2026-10-01 14:45:51'),
(5, 'Metro Pediatric Clinic Staff', 'metrocare@evaccine.org', '$2y$12$0U6dpWd2zBu5XoHTkhAIR.0P4Lp1UrZg9ZyWiWM5xw68mvPKNWn4e', 'hospital', '+1-555-012-3400', '45 Healthcare Plaza, Springfield', '2026-10-01 14:45:51'),
(6, 'St. Mary''s Health Center Staff', 'stmarys@evaccine.org', '$2y$12$0U6dpWd2zBu5XoHTkhAIR.0P4Lp1UrZg9ZyWiWM5xw68mvPKNWn4e', 'hospital', '+1-555-013-7800', '12 Hope Parkway, Springfield', '2026-10-01 14:45:51'),
(7, 'haider', 'inspireyoubyhaideralikhan@gmail.com', '$2y$12$B8xZT5/ZMpXJt6so5bzOgufCmR9VYZnHPnWgU7TB6v69vK994LEKK', 'parent', '+92234567', 'wsdfgjuytr', '2026-10-01 15:02:58'),
(8, 'Inspireyoubyhaideralikhan', 'inspireyoubyhaide@gmail.com', '$2y$12$qjlpyEdypWsYtlcS0P2Oj.AXYooKtccjkjH7gXJaB21nS7NF7TO8q', 'parent', '+92234567', 'wsdfgjuytr', '2026-10-01 15:03:13'),
(11, 'GEO hospital', 'hospital@1', '$2y$12$f10lhHRexq9VZNCONfedUeLvmVYnSkFiX6HznRSNF8u7lN8nvE1wa', 'hospital', '+92234567', 'wsdfgjuytr', '2026-10-01 15:49:15'),
(13, 'SEO hospital', 'hospital1@gmail.com', '$2y$12$A6YN3k1JajUPuBFgYmBIT.eGN5sptMlbwkJ99h42lCkRvpKL2zSxG', 'hospital', '+92 3172825778', 'A77 Gulshan-e-gamal blockc', '2026-10-01 16:20:12'),
(14, 'Abeera', 'fatimaabira1992@gmail.com', '$2y$12$fGgcbBqLN43NsZQnlJmTCe3J9RPqzd5KOujuXBNTL7LuDg6SQ1WSu', 'parent', '+92 3172825778', 'A77 Gulshan-e-iqbal black 7', '2026-10-01 16:46:30'),
(15, 'GEO Hospital', 'hospital7@gmail.com', '$2y$12$tpekeuHoqAGsAt1odT7mB.Fi5UjYfPx0gGmcizn.45ReiHQrWngqm', 'hospital', '+92 3172825778', 'A88 block 9', '2026-10-01 16:52:18'),
(16, 'Haider', 'parent@gmail.com', '$2y$12$LfzMPqGVXqW5cHp8tk.EFe2r6Dwqjt9uDN3OrF/6ydGhs9X6jLsV6', 'parent', '03132825778', 'wsdfgjuytr', '2026-10-05 14:17:50');

-- Table: hospitals
CREATE TABLE `hospitals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `reg_number` VARCHAR(50) DEFAULT NULL,
  `city` VARCHAR(80) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'active',
  `operating_hours` VARCHAR(100) DEFAULT '08:00 AM - 05:00 PM',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hospitals_user_id` (`user_id`),
  CONSTRAINT `fk_hospitals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hospitals` (`id`, `user_id`, `name`, `reg_number`, `city`, `location`, `phone`, `email`, `status`, `operating_hours`, `created_at`) VALUES
(6, 13, 'SEO hospital', 'RESP-OP-1987', 'Karachi', 'A77 Gulshan-e-gamal blockc', '+92 3172825778', 'hospital1@gmail.com', 'active', '08:00 AM - 05:00 PM', '2026-10-01 16:20:12'),
(7, 15, 'GEO Hospital', 'HOTP-OI-9876', 'Karachi', 'A88 block 9', '+92 3172825778', 'hospital7@gmail.com', 'active', '08:00 AM - 05:00 PM', '2026-10-01 16:52:18');

-- Table: vaccines
CREATE TABLE `vaccines` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `disease_prevented` VARCHAR(255) NOT NULL,
  `recommended_age` VARCHAR(100) NOT NULL,
  `recommended_days_from_birth` INT DEFAULT 0,
  `doses_required` INT DEFAULT 1,
  `status` VARCHAR(20) DEFAULT 'available',
  `stock_quantity` INT DEFAULT 100,
  `description` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vaccines` (`id`, `name`, `disease_prevented`, `recommended_age`, `recommended_days_from_birth`, `doses_required`, `status`, `stock_quantity`, `description`, `created_at`) VALUES
(16, 'Polio101', 'Polio', 'At Birth', 0, 1, 'available', 10, 'polio vaccine', '2026-10-01 16:42:59'),
(17, 'Polio107', 'Polio', '10 year', 0, 1, 'available', 10, 'polio', '2026-10-01 16:54:38');

-- Table: children
CREATE TABLE `children` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `parent_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` VARCHAR(10) NOT NULL,
  `blood_group` VARCHAR(10) DEFAULT NULL,
  `birth_hospital` VARCHAR(150) DEFAULT NULL,
  `allergies` TEXT,
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_children_parent_id` (`parent_id`),
  CONSTRAINT `fk_children_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `children` (`id`, `parent_id`, `name`, `dob`, `gender`, `blood_group`, `birth_hospital`, `allergies`, `notes`, `created_at`) VALUES
(5, 14, 'Haider', '2016-05-11', 'Male', 'O+', 'General hospital', 'Poilio', '', '2026-10-01 16:55:30');

-- Table: appointments
CREATE TABLE `appointments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `booking_reference` VARCHAR(30) NOT NULL,
  `child_id` INT NOT NULL,
  `vaccine_id` INT NOT NULL,
  `hospital_id` INT NOT NULL,
  `parent_id` INT NOT NULL,
  `requested_date` DATE NOT NULL,
  `requested_time_slot` VARCHAR(30) NOT NULL,
  `status` VARCHAR(30) DEFAULT 'pending',
  `admin_notes` TEXT,
  `rejection_reason` TEXT,
  `approved_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `vaccine_batch_no` VARCHAR(50) DEFAULT NULL,
  `administered_by` VARCHAR(100) DEFAULT NULL,
  `hospital_remarks` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `idx_appointments_child_id` (`child_id`),
  KEY `idx_appointments_vaccine_id` (`vaccine_id`),
  KEY `idx_appointments_hospital_id` (`hospital_id`),
  KEY `idx_appointments_parent_id` (`parent_id`),
  CONSTRAINT `fk_appointments_child` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_vaccine` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `appointments` (`id`, `booking_reference`, `child_id`, `vaccine_id`, `hospital_id`, `parent_id`, `requested_date`, `requested_time_slot`, `status`, `admin_notes`, `rejection_reason`, `approved_at`, `completed_at`, `vaccine_batch_no`, `administered_by`, `hospital_remarks`, `created_at`) VALUES
(8, 'VAC-2026-A0F63', 5, 17, 7, 14, '2026-10-03', '09:00 AM - 10:00 AM', 'approved', 'Approved by Administrator. Transmitted to assigned hospital.', NULL, '2026-10-01 16:56:26', NULL, NULL, NULL, NULL, '2026-10-01 16:56:03');

-- Table: vaccination_records
CREATE TABLE `vaccination_records` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `child_id` INT NOT NULL,
  `vaccine_id` INT NOT NULL,
  `hospital_id` INT NOT NULL,
  `appointment_id` INT DEFAULT NULL,
  `vaccination_date` DATE NOT NULL,
  `batch_number` VARCHAR(50) DEFAULT NULL,
  `doctor_nurse_name` VARCHAR(100) DEFAULT NULL,
  `dose_number` INT DEFAULT 1,
  `status` VARCHAR(20) DEFAULT 'Vaccinated',
  `remarks` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vrecords_child_id` (`child_id`),
  KEY `idx_vrecords_vaccine_id` (`vaccine_id`),
  KEY `idx_vrecords_hospital_id` (`hospital_id`),
  CONSTRAINT `fk_vrecords_child` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vrecords_vaccine` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vrecords_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(30) DEFAULT 'info',
  `is_read` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(9, 1, 'New Hospital Registered', 'GEO Hospital Test registered in Karachi. Status is active.', 'info', 0, '2026-10-01 16:16:30'),
(10, 1, 'New Hospital Registered', 'SEO hospital registered in Karachi. Status is active.', 'info', 0, '2026-10-01 16:20:13'),
(11, 14, 'Welcome to e-Vaccination Portal', 'You can now register your child details and book vaccination appointments.', 'info', 1, '2026-10-01 16:46:30'),
(12, 14, 'Child Profile Registered: Haider', 'Haider''s immunization schedule is now active. You can review scheduled vaccines and book slots.', 'info', 1, '2026-10-01 16:48:17'),
(13, 14, 'Booking Request Submitted (VAC-2026-D2635)', 'Your request for Haider (Polio101) at SEO hospital on 2026-10-03 is currently pending Admin approval.', 'info', 1, '2026-10-01 16:48:47'),
(14, 1, 'New Appointment Request (VAC-2026-D2635)', 'Parent Abeera requested Polio101 for Haider at SEO hospital.', 'info', 0, '2026-10-01 16:48:47'),
(15, 14, 'Appointment Approved!', 'Your appointment VAC-2026-D2635 for Haider (Polio101) at SEO hospital has been approved for Oct 03, 2026.', 'success', 1, '2026-10-01 16:49:07'),
(16, 1, 'New Hospital Registered', 'GEO Hospital registered in Karachi. Status is active.', 'info', 0, '2026-10-01 16:52:18'),
(17, 14, 'Child Profile Registered: Haider', 'Haider''s immunization schedule is now active. You can review scheduled vaccines and book slots.', 'info', 1, '2026-10-01 16:55:30'),
(18, 14, 'Booking Request Submitted (VAC-2026-A0F63)', 'Your request for Haider (Polio107) at GEO Hospital on 2026-10-03 is currently pending Admin approval.', 'info', 1, '2026-10-01 16:56:03'),
(19, 1, 'New Appointment Request (VAC-2026-A0F63)', 'Parent Abeera requested Polio107 for Haider at GEO Hospital.', 'info', 0, '2026-10-01 16:56:03'),
(20, 14, 'Appointment Approved!', 'Your appointment VAC-2026-A0F63 for Haider (Polio107) at GEO Hospital has been approved for Oct 03, 2026.', 'success', 1, '2026-10-01 16:56:26'),
(21, 16, 'Welcome to e-Vaccination Portal', 'You can now register your child details and book vaccination appointments.', 'info', 1, '2026-10-05 14:17:50');

SET FOREIGN_KEY_CHECKS = 1;
