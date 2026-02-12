-- 1. LUODAAN TIETOKANTA
CREATE DATABASE IF NOT EXISTS `hieronta_varaus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hieronta_varaus`;

-- --------------------------------------------------------

-- 2. TAULU: treatments (Palvelut)
CREATE TABLE `treatments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `price` decimal(6,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `treatments` (`id`, `name`, `description`, `duration`, `price`) VALUES
(1, 'Hieronta 60min', 'Klassinen hieronta', 60, 55.00),
(2, 'Hieronta 90min', 'Rauhoittava pitkä hieronta', 90, 75.00);

-- --------------------------------------------------------

-- 3. TAULU: admins (Yrittäjät)
-- Korjattu email -> username
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oletustunnus: admin / Salasana: password123 
-- (Hash: $2y$10$e0NnD6s1GdXKfJ9C3t1t7u8hQYgTj9G7VsmFhM6rP7yL5C1K8j4Pa)
INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$Thjxl4H6d0/t9B.gWdrBCek3wTIYIwfW7jV8A0dyLVbbXittuNVLe');

-- --------------------------------------------------------

-- 4. TAULU: available_times (Vapaat slotit)
CREATE TABLE `available_times` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `treatment_id` int(11) NOT NULL DEFAULT 1,
  `available_date` date NOT NULL,
  `available_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_time_slot` (`available_date`, `available_time`),
  KEY `treatment_id` (`treatment_id`),
  CONSTRAINT `available_times_ibfk_1` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- 5. TAULU: appointments (Varaukset)
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_first_name` varchar(50) NOT NULL,
  `customer_last_name` varchar(50) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `treatment_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('booked','cancelled') DEFAULT 'booked',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_unique` (`appointment_date`,`appointment_time`),
  KEY `treatment_id` (`treatment_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;