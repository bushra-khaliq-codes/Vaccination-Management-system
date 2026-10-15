
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `vaccination_records`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `children`;
DROP TABLE IF EXISTS `vaccines`;
DROP TABLE IF EXISTS `hospitals`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

--  1. Table structure for table `users`
-- Role-based authentication: Admin, Parent, and Hospital Staff
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'parent', 'hospital') NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table structure for table `hospitals`
-- Verified healthcare providers authorized to administer vaccines
CREATE TABLE `hospitals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `reg_number` VARCHAR(50) DEFAULT NULL,
  `city` VARCHAR(80) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `status` ENUM('active', 'pending_approval', 'inactive') DEFAULT 'active',
  `operating_hours` VARCHAR(100) DEFAULT '08:00 AM - 05:00 PM',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table structure for table `vaccines`
-- Immunization catalog with WHO recommended ages and stock levels
CREATE TABLE `vaccines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `disease_prevented` VARCHAR(255) NOT NULL,
  `recommended_age` VARCHAR(100) NOT NULL,
  `recommended_days_from_birth` INT DEFAULT 0,
  `doses_required` INT DEFAULT 1,
  `status` ENUM('available', 'unavailable') DEFAULT 'available',
  `stock_quantity` INT DEFAULT 100,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table structure for table `children`
-- Infant profiles registered by parents with DOB for schedule calc
CREATE TABLE `children` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `blood_group` VARCHAR(10) DEFAULT NULL,
  `birth_hospital` VARCHAR(150) DEFAULT NULL,
  `allergies` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table structure for table `appointments`
-- Booking requests submitted by parent, approved by admin, completed by hospital
CREATE TABLE `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(30) NOT NULL UNIQUE,
  `child_id` INT NOT NULL,
  `vaccine_id` INT NOT NULL,
  `hospital_id` INT NOT NULL,
  `parent_id` INT NOT NULL,
  `requested_date` DATE NOT NULL,
  `requested_time_slot` VARCHAR(30) NOT NULL,
  `status` ENUM('pending', 'approved', 'completed', 'missed', 'rejected') DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `vaccine_batch_no` VARCHAR(50) DEFAULT NULL,
  `administered_by` VARCHAR(100) DEFAULT NULL,
  `hospital_remarks` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table structure for table `vaccination_records`
-- Permanent clinical records of administered doses & lot numbers
CREATE TABLE `vaccination_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `child_id` INT NOT NULL,
  `vaccine_id` INT NOT NULL,
  `hospital_id` INT NOT NULL,
  `appointment_id` INT DEFAULT NULL,
  `vaccination_date` DATE NOT NULL,
  `batch_number` VARCHAR(50) DEFAULT NULL,
  `doctor_nurse_name` VARCHAR(100) DEFAULT NULL,
  `dose_number` INT DEFAULT 1,
  `status` ENUM('Vaccinated', 'Not Vaccinated') DEFAULT 'Vaccinated',
  `remarks` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table structure for table `notifications`
-- In-app alert messages for parents, hospitals, and admins
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'reminder') DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DUMPING DATA FOR TABLES
-- 1. Users (Passwords: admin123, parent123, hospital123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`) VALUES
(1, 'System Administrator', 'admin@evaccine.org', '$2y$10$UoWpI1O2qF38D9nJ3.XjkeYvj8dsq9j9wUe81g3Hq0oGz107Nrq6m', 'admin', '+1-800-555-0199', 'Central Health Department HQ'),
(2, 'Sarah Jenkins', 'parent@evaccine.org', '$2y$10$tZ8lM5p2QW39rT4.Abcdeu8Ffgh12345678901234567890123456', 'parent', '+1-555-014-8821', '42 Elm Street, Springfield'),
(3, 'David Miller', 'david@evaccine.org', '$2y$10$tZ8lM5p2QW39rT4.Abcdeu8Ffgh12345678901234567890123456', 'parent', '+1-555-019-3342', '789 Oakridge Ave, Springfield'),
(4, 'City General Staff', 'cityhospital@evaccine.org', '$2y$10$0dEvT7n1qW99rY6.Xyz12u9Habc98765432109876543210987654', 'hospital', '+1-555-011-2000', '100 Medical Center Blvd, Springfield'),
(5, 'Metro Pediatric Staff', 'metrocare@evaccine.org', '$2y$10$0dEvT7n1qW99rY6.Xyz12u9Habc98765432109876543210987654', 'hospital', '+1-555-012-3400', '45 Healthcare Plaza, Springfield'),
(6, 'St. Mary\'s Staff', 'stmarys@evaccine.org', '$2y$10$0dEvT7n1qW99rY6.Xyz12u9Habc98765432109876543210987654', 'hospital', '+1-555-013-7800', '12 Hope Parkway, Springfield');

-- 2. Hospitals
INSERT INTO `hospitals` (`id`, `user_id`, `name`, `reg_number`, `city`, `location`, `phone`, `email`, `status`, `operating_hours`) VALUES
(1, 4, 'City General Children Hospital', 'HOSP-SP-101', 'Springfield', '100 Medical Center Blvd, Central Springfield', '+1-555-011-2000', 'cityhospital@evaccine.org', 'active', '08:00 AM - 06:00 PM'),
(2, 5, 'Metro Pediatric Care & Maternity', 'HOSP-SP-102', 'Springfield', '45 Healthcare Plaza, North Springfield', '+1-555-012-3400', 'metrocare@evaccine.org', 'active', '08:30 AM - 05:00 PM'),
(3, 6, 'St. Mary\'s Family Health Center', 'HOSP-SP-103', 'Springfield', '12 Hope Parkway, South Springfield', '+1-555-013-7800', 'stmarys@evaccine.org', 'active', '09:00 AM - 04:30 PM');

-- 3. Vaccines (Catalog starts clean - Admin registers vaccines as required)
-- 4. Children (Registry starts clean - Parents register children upon login)
-- 5. Appointments (Initially clean - populated when fresh requests are booked)
-- 6. Completed Vaccination Records (Initially clean - logged by hospitals upon administration)

