CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `consent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','technician') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `phone` varchar(40) NOT NULL DEFAULT '',
  `specialty` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `jobs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `technician_id` int(10) UNSIGNED NOT NULL,
  `tracking_code` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `appliance` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `serial` varchar(255) NOT NULL DEFAULT '',
  `reported_issue` text NOT NULL,
  `accessories` varchar(255) NOT NULL DEFAULT '',
  `item_condition` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(50) NOT NULL DEFAULT 'Received',
  `diagnostic_fee` decimal(10,2) NOT NULL DEFAULT 500.00,
  `parts` decimal(10,2) NOT NULL DEFAULT 0.00,
  `labor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quote_decision` enum('approve','decline') DEFAULT NULL,
  `estimated_completion` date DEFAULT NULL,
  `warranty_until` date DEFAULT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `picked_up_at` datetime DEFAULT NULL,
  `quote_parts_json` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_code` (`tracking_code`),
  KEY `customer_id` (`customer_id`),
  KEY `technician_id` (`technician_id`),
  KEY `status` (`status`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `customer_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` int(10) UNSIGNED NOT NULL,
  `kind` enum('message','cancellation','warranty') NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `customer_requests_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `job_events` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` int(10) UNSIGNED NOT NULL,
  `action` text NOT NULL,
  `actor` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `job_events_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `job_notes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` int(10) UNSIGNED NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `customer_visible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `job_notes_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `job_notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(255) NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`,`sent_at`),
  CONSTRAINT `job_notifications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `job_photos` (
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `mime` varchar(32) NOT NULL,
  `image_data` mediumblob NOT NULL,
  PRIMARY KEY (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `recorded_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `positions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rate_limits` (
  `bucket` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `saved_logins` (
  `selector` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`selector`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `saved_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `settings` (
  `field` varchar(80) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tracking_otp` (
  `job_id` int(10) UNSIGNED NOT NULL,
  `challenge` char(32) NOT NULL,
  `digest` varchar(255) NOT NULL,
  `issued_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`job_id`),
  CONSTRAINT `tracking_otp_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
