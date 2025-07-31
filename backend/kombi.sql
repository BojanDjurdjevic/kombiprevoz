-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2025 at 06:45 AM
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
-- Database: `kombi`
--

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(95) NOT NULL,
  `country_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `country_id`) VALUES
(1, 'Beograd', 1),
(2, 'Novi Sad', 1),
(3, 'Niš', 1),
(4, 'Zagreb', 2),
(5, 'Rijeka', 2),
(6, 'Split', 2),
(7, 'Ljubljana', 3),
(8, 'Maribor', 3),
(9, 'Koper', 3),
(11, 'Sabatka', 1);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(95) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`) VALUES
(1, 'Srbija'),
(2, 'Hrvatska'),
(3, 'Slovenija');

-- --------------------------------------------------------

--
-- Table structure for table `c_pics`
--

CREATE TABLE `c_pics` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `country_id` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departures`
--

CREATE TABLE `departures` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departures`
--

INSERT INTO `departures` (`id`, `driver_id`, `tour_id`, `code`, `file_path`, `date`, `deleted`) VALUES
(6, 15, 1, '1265168KP', 'src/assets/pdfs/1265168KP.pdf', '2025-07-15', 0),
(7, 15, 6, '1111111KP', NULL, '2025-07-15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total` int(11) DEFAULT NULL,
  `code` varchar(55) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `dep_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `code`, `file_path`, `dep_id`, `driver_id`, `deleted`) VALUES
(1, 1, 150, '3693691KP', NULL, NULL, NULL, 0),
(2, 12, 100, '3693692KP', 'src/assets/pdfs/3693692KP.pdf', 6, 15, 0),
(3, 3, 200, '3693693KP', NULL, NULL, NULL, 0),
(4, 3, 75, '3693694KP', NULL, NULL, NULL, 0),
(5, 2, 150, '3693695KP', NULL, NULL, NULL, 0),
(6, 1, 300, '3693696KP', NULL, NULL, NULL, 0),
(7, 2, 320, '3693697KP', NULL, 7, 15, 1),
(9, 1, 160, '3693698KP', NULL, 7, 15, 1),
(84, 12, 100, '3367684KP', 'src/assets/pdfs/3367684KP.pdf', NULL, NULL, 0),
(85, 12, 100, '3367688KP', 'src/assets/pdfs/3367688KP.pdf', NULL, NULL, 0),
(95, 10, 150, '3562999KP', 'src/assets/pdfs/3562999KP.pdf', NULL, NULL, 0),
(96, 10, 150, '3563464KP', 'src/assets/pdfs/3563464KP.pdf', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `places` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `add_from` varchar(255) NOT NULL,
  `add_to` varchar(255) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `tour_id`, `places`, `price`, `add_from`, `add_to`, `date`) VALUES
(3, 95, 1, 1, 50, 'Gavrila Principa 6', 'Mornarska 9', '2025-07-29'),
(4, 95, 2, 2, 100, 'Mornarska 9', 'Gavrila Principa 6', '2025-07-30'),
(5, 96, 1, 1, 50, 'Gavrila Principa 6', 'Primorska 3', '2025-07-29'),
(6, 96, 2, 2, 100, 'Primorska 6', 'Gavrila Principa 6', '2025-07-30');

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_city` varchar(55) NOT NULL,
  `to_city` varchar(55) NOT NULL,
  `departures` set('0','1','2','3','4','5','6') NOT NULL,
  `time` time NOT NULL,
  `duration` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 8,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tours`
--

INSERT INTO `tours` (`id`, `from_city`, `to_city`, `departures`, `time`, `duration`, `price`, `seats`, `deleted`) VALUES
(1, 'Novi Sad', 'Rijeka', '1,2,5,6', '06:45:00', 6, 50, 8, 0),
(2, 'Rijeka', 'Novi Sad', '0,2,3,6', '06:45:00', 6, 50, 8, 0),
(3, 'Novi Sad', 'Maribor', '1,3,5,6', '06:30:00', 7, 50, 8, 0),
(4, 'Beograd', 'Koper', '1,2,3,4,5,6', '06:30:00', 7, 75, 8, 0),
(5, 'Niš', 'Zagreb', '1,2,3,4,5,6', '06:15:00', 9, 80, 8, 0),
(6, 'Novi Sad', 'Split', '0,1,3,5', '06:45:00', 9, 80, 8, 0),
(7, '', '', '', '00:00:00', 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `t_pics`
--

CREATE TABLE `t_pics` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `city_id` int(11) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(55) NOT NULL,
  `email` varchar(225) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `status` enum('Superadmin','Admin','User','Driver') NOT NULL,
  `city` varchar(45) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(45) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `email_verified` timestamp NULL DEFAULT current_timestamp(),
  `reset_token_hash` varchar(72) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `pass`, `status`, `city`, `address`, `phone`, `created_at`, `email_verified`, `reset_token_hash`, `reset_token_expires`, `deleted`) VALUES
(1, 'Mika', 'mika@test.com', '369963', 'Driver', 'Novi Sad', 'Gajeva 3', '062639963', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, 0),
(2, 'Žika', 'zile@test.com', '333999', 'Driver', 'Novi Sad', 'Kočićeva 6', '063936888', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, 0),
(3, 'Pera', 'pera@test.com', '999333', 'Driver', 'Beograd', 'Gandijeva 303', '069357951', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, 0),
(4, 'Eni Baneni', 'ana@test.com', '$2y$10$IYp8siMrxDi44tYKBfzvP.Cws4G57EtEOW1yP6hmR.Q60cWTjtjPG', 'User', 'Novi Sad', 'Gogoljeva 99', '062635852', '2025-06-18 20:46:52', '2025-06-18 20:46:52', NULL, NULL, 0),
(10, 'Bojan Djurdjevic', 'pininfarina164@gmail.com', '$2y$10$4gbyD7svBFPgXMGD2BdhbuyzSn3BtiAcD2sp/0SYtUM8KtWW/WFlK', 'Superadmin', 'Novi Sad', 'Gavrila Principa 6', '062640273', '2025-06-18 21:36:10', '2025-06-18 21:36:10', '5eb8e020c72706c5bab4ded7b17b32b4aec2fefecf23f01acfd6d677c8b7b429', '2025-07-19 18:29:45', 0),
(11, 'Bogdan Savić', 'bogy@test.com', '$2y$10$936X99bl53ajMGMEYwsfLe07ZIbVkzhmNSPlPP02wOAiSSmLBGbn.', 'User', 'Novi Sad', 'Puškinova 9', '062648963', '2025-06-18 22:20:44', '2025-06-18 22:20:44', NULL, NULL, 0),
(12, 'Valentina Djurdjevic', 'valentajndj@gmail.com', '$2y$10$rZ1jC98UFRtIwL1Dj3x.nuBwhvVwGjsDHPCAUlYD/J2DmyHGlGRlS', 'User', 'Sremska Kamenica', 'Gavrila Principa 6', '0641178898', '2025-06-24 13:26:48', '2025-06-24 13:26:48', 'ad030c511bb58a74373aaa1b42f8766108cbe38f2c301fb9590025cef27ae6d0', '2025-06-24 16:53:20', 0),
(15, 'Bojan Giuliano', 'bojan.giuliano@gmail.com', '$2y$10$iEdXHXsUclGst6WXXpM14.L7Ual/bBV9IMyYuhF2Sqfb5upkRhkze', 'Driver', 'Novi Sad', 'Seljačkih Buna 29', '062640227', '2025-06-29 06:34:06', '2025-06-29 06:34:06', NULL, NULL, 0),
(17, 'Kasac Prasac', 'kas@test.com', '$2y$10$47nyYWGlwqPbCqxh33oouuzETHCTnv2fiVL.I3StnQjEPpwVFsIdu', 'User', 'Novi Sad', 'Takovska 9', '062333999', '2025-07-22 06:34:01', '2025-07-22 06:34:01', NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`,`country_id`),
  ADD KEY `fk_cities_countries1_idx` (`country_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `c_pics`
--
ALTER TABLE `c_pics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departures`
--
ALTER TABLE `departures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`,`user_id`) USING BTREE,
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_orders_users_idx` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_pics`
--
ALTER TABLE `t_pics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `c_pics`
--
ALTER TABLE `c_pics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departures`
--
ALTER TABLE `departures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `t_pics`
--
ALTER TABLE `t_pics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_cities_countries1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_tours1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
