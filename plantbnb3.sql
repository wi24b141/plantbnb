-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2026 at 03:35 AM
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
-- Database: `plantbnb3`
--

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`favorite_id`, `user_id`, `listing_id`, `created_at`) VALUES
(1, 1, 2, '2026-01-07 02:34:58'),
(2, 3, 1, '2026-01-09 02:33:29');

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
  `care_sheet_path` varchar(255) DEFAULT NULL,
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

INSERT INTO `listings` (`listing_id`, `user_id`, `listing_type`, `title`, `description`, `listing_photo_path`, `care_sheet_path`, `location_approx`, `start_date`, `end_date`, `experience`, `price_range`, `status`, `created_at`) VALUES
(1, 1, 'offer', 'Providing plant care for all varieties of plants', 'I am providing plant care for all varieties of plants!', 'uploads/listings/695dc4ad09dce_Pink_flower.jpg', NULL, 'Wien', '2026-01-07', '2026-01-31', 'all', '20€ per month', 'active', '2026-01-07 02:27:57'),
(2, 2, 'need', 'Red Prayer', 'This is my red prayer plant!', 'uploads/listings/695dc5e27b3b7_Red Prayer Plant.jpeg', 'uploads/caresheets/695dc5e27b6c5_Eingangsrechnungen_Moebelfix_Oktober2025.pdf', 'Wien', '2026-01-07', '2026-01-31', 'Expert', '5€ per month', 'active', '2026-01-07 02:33:06');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`) VALUES
(1, 2, 1, 'Hello Julia, can you look after my plant for the next month?', 1, '2026-01-07 02:30:55'),
(2, 1, 2, 'Hello Alois, thanks for your request! Sounds good! Which exact dates do you need?', 1, '2026-01-07 02:34:30');

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
(1, 1, 'all types', 'all', 'all'),
(2, 2, 'Red Prayer', 'little', 'sunlight');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `rater_user_id` int(11) NOT NULL,
  `rated_user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`rating_id`, `rater_user_id`, `rated_user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 2, 3, NULL, '2026-01-08 23:02:07'),
(2, 1, 2, 3, NULL, '2026-01-08 23:02:13'),
(3, 1, 3, 4, NULL, '2026-01-09 02:31:59'),
(4, 3, 1, 4, NULL, '2026-01-09 02:32:52');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `profile_photo_path`, `verification_document_path`, `bio`, `role`, `is_verified`, `created_at`, `remember_token`) VALUES
(1, 'Julia', 'julia@email.com', '$2y$10$w/Oor0kvl2Qi2za7E3t89u7j5SeujVs7VcAThcfyCOmMKt7vyy7Om', 'uploads/profiles/695dc4310b4a3_dug puppy profile photo.jpg', NULL, 'Hello my name is Julia and I like plants!', 'admin', 0, '2026-01-07 02:25:06', NULL),
(2, 'Alois', 'alois@email.com', '$2y$10$0eZq7gcaxMxt3R8Gd46OyeZWd8ZkxkLZZlgVsNOWJsQzX/sNCHp5K', 'uploads/profiles/695dc4ec49796_istockphoto-959866606-612x612.jpg', 'uploads/verification/69602f9e5f8f7_austrian drivers licence dummy.jpg', 'Hello my name is Alois.', 'user', 1, '2026-01-07 02:28:34', NULL),
(3, 'antonia', 'antonia@gmail.com', '$2y$10$5q26KimyhT17M.2i9BDvqOBnFehJT9JsdhyJrho/4IltJL/eB3gjy', 'uploads/profiles/69604669ca273_Tabby_cat_with_visible_nictitating_membrane.jpg', NULL, 'Hello I am Antonia!', 'user', 0, '2026-01-09 00:03:36', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `unique_user_listing` (`user_id`,`listing_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_listing_id` (`listing_id`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`plant_id`),
  ADD KEY `idx_listing_id` (`listing_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `idx_rater_user_id` (`rater_user_id`),
  ADD KEY `idx_rated_user_id` (`rated_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_remember_token` (`remember_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `listing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `plant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `plants`
--
ALTER TABLE `plants`
  ADD CONSTRAINT `plants_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`rater_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`rated_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
