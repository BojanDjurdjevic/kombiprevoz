-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2025 at 08:55 AM
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
-- Table structure for table `departures`
--

CREATE TABLE `departures` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `dep_orders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departures`
--

INSERT INTO `departures` (`id`, `driver_id`, `dep_orders`, `code`, `file_path`, `time`, `deleted`) VALUES
(6, 15, '2,83', '1265168KP', 'src/assets/pdfs/1265168KP.pdf', '2025-07-15 06:45:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `tour_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `places` int(1) NOT NULL DEFAULT 1,
  `add_from` varchar(225) NOT NULL,
  `add_to` varchar(225) NOT NULL,
  `date` date NOT NULL,
  `total` int(11) NOT NULL,
  `code` varchar(55) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `tour_id`, `user_id`, `places`, `add_from`, `add_to`, `date`, `total`, `code`, `file_path`, `driver_id`, `deleted`) VALUES
(1, 1, 1, 3, 'Gajeva 3', 'Primorska 5', '2025-07-02', 150, '3693691KP', NULL, NULL, 0),
(2, 1, 12, 2, 'Kočićeva 9', 'Zadarska 33', '2025-07-15', 100, '3693692KP', 'src/assets/pdfs/3693692KP.pdf', 15, 0),
(3, 1, 3, 4, 'Krilova 6', 'Rudarska 99', '2025-06-27', 200, '3693693KP', NULL, NULL, 0),
(4, 4, 3, 1, 'Krilova 6', 'Rudarska 99', '2025-06-28', 75, '3693694KP', NULL, NULL, 0),
(5, 4, 2, 4, 'Ljubina 22', 'Glavna 3', '2025-06-26', 150, '3693695KP', NULL, NULL, 0),
(6, 4, 1, 5, 'Balkanska 6', 'Jurice Štovca 102', '2025-06-28', 300, '3693696KP', NULL, NULL, 0),
(7, 6, 2, 4, 'Kočićeva 6', 'Mornarska 99', '2025-07-01', 320, '3693697KP', NULL, NULL, 0),
(9, 6, 1, 2, 'Gajeva 6', 'Mornarska 99', '2025-07-01', 160, '3693698KP', NULL, NULL, 0),
(83, 1, 10, 2, 'Gajeva 9', 'Primorska 18', '2025-07-15', 100, '1016996KP', 'src/assets/pdfs/1016996KP.pdf', 15, 0);

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
(2, 'Novi Sad', 'Rijeka', '1,2,5,6', '06:45:00', 6, 50, 8, 1),
(3, 'Novi Sad', 'Maribor', '1,3,5,6', '06:30:00', 7, 50, 8, 0),
(4, 'Beograd', 'Koper', '1,2,3,4,5,6', '06:30:00', 7, 75, 8, 0),
(5, 'Niš', 'Zagreb', '1,2,3,4,5,6', '06:15:00', 9, 80, 8, 0),
(6, 'Novi Sad', 'Split', '0,1,3,5', '06:45:00', 9, 80, 8, 0),
(7, '', '', '', '00:00:00', 0, 0, 0, 1);

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
(10, 'Bojan', 'pininfarina164@gmail.com', '$2y$10$W/aVDEgnyES/fC8zhZlw1.8OgvCB53e4JMoZlv7pED11G7VlGKV8u', 'Superadmin', 'Novi Sad', 'Gavrila Principa 6', '062640273', '2025-06-18 21:36:10', '2025-06-18 21:36:10', NULL, NULL, 0),
(11, 'Bogdan Savić', 'bogy@test.com', '$2y$10$936X99bl53ajMGMEYwsfLe07ZIbVkzhmNSPlPP02wOAiSSmLBGbn.', 'User', 'Novi Sad', 'Puškinova 9', '062648963', '2025-06-18 22:20:44', '2025-06-18 22:20:44', NULL, NULL, 0),
(12, 'Valentina Djurdjevic', 'valentajndj@gmail.com', '$2y$10$rZ1jC98UFRtIwL1Dj3x.nuBwhvVwGjsDHPCAUlYD/J2DmyHGlGRlS', 'User', 'Sremska Kamenica', 'Gavrila Principa 6', '0641178898', '2025-06-24 13:26:48', '2025-06-24 13:26:48', 'ad030c511bb58a74373aaa1b42f8766108cbe38f2c301fb9590025cef27ae6d0', '2025-06-24 16:53:20', 0),
(15, 'Bojan Giuliano', 'bojan.giuliano@gmail.com', '$2y$10$iEdXHXsUclGst6WXXpM14.L7Ual/bBV9IMyYuhF2Sqfb5upkRhkze', 'Driver', 'Novi Sad', 'Seljačkih Buna 29', '062640227', '2025-06-29 06:34:06', '2025-06-29 06:34:06', NULL, NULL, 0);

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
-- Indexes for table `departures`
--
ALTER TABLE `departures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`,`tour_id`,`user_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_orders_users_idx` (`user_id`),
  ADD KEY `fk_orders_tours1_idx` (`tour_id`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
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
-- AUTO_INCREMENT for table `departures`
--
ALTER TABLE `departures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
