-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2026 at 01:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `healthcare_assistant`
--

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('hospital','clinic','dispensary') NOT NULL DEFAULT 'clinic',
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `name`, `type`, `lat`, `lng`, `phone`, `address`, `services`, `is_active`) VALUES
(1, 'Muhimbili National Hospital', 'hospital', -6.81200000, 39.28000000, '+2552221511', 'Dar es Salaam', '[\"emergency\",\"surgery\",\"maternity\",\"outpatient\"]', 1),
(2, 'Mbagala District Hospital', 'hospital', -6.90500000, 39.28000000, '+255222345678', 'Mbagala, Dar es Salaam', '[\"emergency\",\"maternity\",\"outpatient\"]', 1),
(3, 'Kibaha Health Centre', 'clinic', -6.76670000, 38.95000000, '+255232456789', 'Kibaha, Pwani', '[\"outpatient\",\"maternity\",\"immunization\"]', 1),
(4, 'Morogoro Regional Hospital', 'hospital', -6.82350000, 37.66000000, '+255234567890', 'Morogoro Town', '[\"emergency\",\"surgery\",\"outpatient\"]', 1),
(5, 'Bagamoyo Dispensary', 'dispensary', -6.44200000, 38.90400000, '+255234111222', 'Bagamoyo', '[\"outpatient\",\"immunization\"]', 1),
(6, 'Arusha Lutheran Medical Centre', 'hospital', -3.38690000, 36.68300000, '+255272504882', 'Arusha', '[\"emergency\",\"outpatient\",\"maternity\"]', 1);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `phone_hash` varchar(64) NOT NULL,
  `age_group` enum('child','adult','elderly') NOT NULL DEFAULT 'adult',
  `language` enum('en','sw') NOT NULL DEFAULT 'en',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `symptom_sessions`
--

CREATE TABLE `symptom_sessions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `symptoms` text NOT NULL,
  `urgency` enum('low','medium','high','emergency') NOT NULL,
  `ai_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_response`)),
  `language` enum('en','sw') NOT NULL DEFAULT 'en',
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_hash` (`phone_hash`);

--
-- Indexes for table `symptom_sessions`
--
ALTER TABLE `symptom_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_urgency` (`urgency`),
  ADD KEY `idx_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `symptom_sessions`
--
ALTER TABLE `symptom_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `symptom_sessions`
--
ALTER TABLE `symptom_sessions`
  ADD CONSTRAINT `symptom_sessions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
