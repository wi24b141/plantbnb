-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 02:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `plantbnbdatabase`
--

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `listing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `listing_type` enum('offer','need') NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `listing_photo_path` varchar(255) DEFAULT NULL,
  `location_approx` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `price_range` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`listing_id`, `user_id`, `listing_type`, `title`, `description`, `listing_photo_path`, `location_approx`, `start_date`, `end_date`, `experience`, `price_range`, `status`, `created_at`) VALUES
(1, 1, 'need', 'Phalaenopsis', 'This is my Phalaenopsis plant!', 'uploads/listings/6925a6c48a878_Phalaenopsis plant.jpeg', 'Wien', '2025-11-25', '2025-12-24', 'Expert', '100€ per month', 'active', '2025-11-25 12:53:24'),
(2, 1, 'offer', 'I care for all types of plants', 'I love plants! I love plantbnb!', NULL, 'Salzburg', '2025-11-25', '2026-04-22', 'Expert', '10€ per day', 'active', '2025-11-25 13:24:53'),
(3, 2, 'need', 'African Violettray plant', 'This African Violettray plant. Please do not let it die!!!', 'uploads/listings/6925aecb806b5_africanviolettraytable-7dce7e1fcc954d94830dc69504a04994.jpg', 'Wien', '2025-11-25', '2026-02-25', 'Intermediate', '10€ per week', 'active', '2025-11-25 13:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `plant_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `plant_type` varchar(100) NOT NULL,
  `watering_needs` text NOT NULL,
  `light_needs` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`plant_id`, `listing_id`, `plant_type`, `watering_needs`, `light_needs`) VALUES
(1, 1, 'Phalaenopsis', 'Two times per day', 'do not know how much light'),
(2, 2, 'all plants', 'all watering needs', 'sunlight or indoor light'),
(3, 3, 'African Violettray plant', 'Little water', 'indoor light');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `verification_document_path` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `profile_photo_path`, `verification_document_path`, `bio`, `role`, `is_verified`, `created_at`) VALUES
(1, 'franz', 'franz@gmail.com', '$2y$10$yalSG3cnV/FQ.U8pKTOQX.wZdLGxsY.CxxHibiAj2r1AgFSq.Wld2', 'uploads/profiles/692597cd0c220_Tabby_cat_with_visible_nictitating_membrane.jpg', 'uploads/verification/6925a1395625a_dummy austrian drivers licence.jpg', 'My name is Franz. Please admire my cat in my profile picture.', 'user', 0, '2025-11-25 11:47:03'),
(2, 'Berta', 'Berta@gmail.com', '$2y$10$vbfqaeHI9a/Eew2jAAcdO.gGZb2olT/W3V87B93zXKxpD5nOYqpnO', 'uploads/profiles/692598651bf9e_picture of dug puppy.jpg', NULL, 'I am Berta and I love plantbnb!', 'user', 0, '2025-11-25 11:51:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`plant_id`),
  ADD KEY `idx_listing_id` (`listing_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `plant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `plants`
--
ALTER TABLE `plants`
  ADD CONSTRAINT `plants_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
