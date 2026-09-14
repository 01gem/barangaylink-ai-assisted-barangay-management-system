-- Create database for the application
CREATE DATABASE IF NOT EXISTS `barangay_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `barangay_system`;

CREATE TABLE IF NOT EXISTS `residents` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `address` varchar(100) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `barangay_officials` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` enum('staff','admin') NOT NULL DEFAULT 'staff',
  `address` varchar(100) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` varchar(100) NOT NULL DEFAULT '',
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account_type` enum('resident','official') NOT NULL,
  `account_id` int NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_account` (`account_type`,`account_id`,`used`,`expires_at`),
  KEY `idx_otp_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(50) NOT NULL,
  `resident_id` int(10) NOT NULL,
  `resident_name` varchar(100) NOT NULL,
  `resident_email` varchar(100) DEFAULT NULL,
  `document_type` varchar(100) NOT NULL,
  `purpose` varchar(150) NOT NULL,
  `date_requested` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_no` (`reference_no`),
  FOREIGN KEY (`resident_id`) REFERENCES `residents`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `resident_id` int(10) DEFAULT NULL,
  `reference_no` varchar(50) NOT NULL,
  `resident_name` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `location_text` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `date_filed` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `official_note` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_no` (`reference_no`),
  FOREIGN KEY (`resident_id`) REFERENCES `residents`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `resident_id` int(10) DEFAULT NULL,
  `title` varchar(120) NOT NULL,
  `body` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`resident_id`) REFERENCES `residents`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `official_id` int(10) DEFAULT NULL,
  `official_name` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_reference` varchar(100) DEFAULT NULL,
  `details` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`official_id`) REFERENCES `barangay_officials`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `expires_at` DATETIME NULL,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `posted_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`posted_by`) REFERENCES `barangay_officials`(`id`)
);

CREATE TABLE IF NOT EXISTS `local_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `service_name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `contact_number` VARCHAR(20),
  `address` VARCHAR(255),
  `operating_hours` VARCHAR(100),
  `description` TEXT,
  `posted_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`posted_by`) REFERENCES `barangay_officials`(`id`)
);

INSERT INTO `barangay_officials` (`id`, `fname`, `lname`, `username`, `role`, `address`,
 `contact`, `password`, `position`, `status`, `created_at`) VALUES (NULL, 'Gem', 'Dulduco',
  'gem', 'admin', 'Purok 2, Barangay Sampaguita', '09060312740', '$2y$10$mYgU3xgq32rcE219Q5zXvOkzDni4GcLNT4i.sa9/X3KMoTy6lnZQ2', 'Barangay Captain',
   'active', CURRENT_TIMESTAMP
);

--password unhashed: gemgem