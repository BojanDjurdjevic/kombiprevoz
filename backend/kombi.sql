-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 05:27 PM
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
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `ticket_id`, `sender_type`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(72, 10, 'admin', NULL, 'Zdravo Ana Banana! 👋\n\nHvala što ste nas kontaktirali. Vaš tiket broj: TKT-5EFAA96C.\n\nMolimo Vas da sačekate dok admin ne preuzme vašu konverzaciju. Odgovorićemo Vam u najkraćem mogućem roku.\n\nHvala na strpljenju! 😊', 1, '2025-12-19 14:49:19'),
(73, 10, 'customer', NULL, 'Da PONOVO testiramo email', 1, '2025-12-19 14:49:19'),
(74, 10, 'admin', 10, 'Hvala ti Enice Banenice, živa bila!', 1, '2025-12-19 14:50:34');

-- --------------------------------------------------------

--
-- Table structure for table `chat_tickets`
--

CREATE TABLE `chat_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `reservation_number` varchar(50) DEFAULT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_message_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_tickets`
--

INSERT INTO `chat_tickets` (`id`, `ticket_number`, `customer_name`, `customer_email`, `customer_phone`, `reservation_number`, `status`, `assigned_to`, `created_at`, `updated_at`, `last_message_at`) VALUES
(10, 'TKT-5EFAA96C', 'Ana Banana', 'ana@test.com', '062645852', '101256357', 'closed', NULL, '2025-12-19 14:49:19', '2025-12-19 14:50:46', '2025-12-19 14:50:34');

-- --------------------------------------------------------

--
-- Table structure for table `chat_typing`
--

CREATE TABLE `chat_typing` (
  `ticket_id` int(11) NOT NULL,
  `user_type` enum('customer','admin') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_typing`
--
-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(95) NOT NULL,
  `country_id` int(10) UNSIGNED NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `country_id`, `deleted`) VALUES
(1, 'Beograd', 1, 0),
(2, 'Novi Sad', 1, 0),
(3, 'Niš', 1, 0),
(4, 'Zagreb', 2, 0),
(5, 'Rijeka', 2, 0),
(6, 'Split', 2, 0),
(7, 'Ljubljana', 3, 0),
(8, 'Maribor', 3, 0),
(9, 'Koper', 3, 0),
(11, 'Sabatka', 1, 0),
(16, 'Beč', 6, 0),
(22, 'Berlin', 11, 0);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(95) NOT NULL,
  `file_path` varchar(225) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departures`
--

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
  `deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

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
  `date` date NOT NULL,
  `dep_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

-- --------------------------------------------------------

--
-- Table structure for table `order_logs`
--

CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_logs`
--

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
(1, 'Novi Sad', 'Rijeka', '0,1,2,5,6', '06:45:00', 6, 50, 8, 0),
(2, 'Rijeka', 'Novi Sad', '0,2,3,6', '06:45:00', 6, 50, 8, 0),
(3, 'Novi Sad', 'Maribor', '1,3,5,6', '06:30:00', 6, 50, 8, 0),
(4, 'Beograd', 'Koper', '1,2,3,4,5,6', '06:30:00', 7, 75, 8, 0),
(5, 'Niš', 'Zagreb', '1,3,5', '06:15:00', 9, 90, 8, 0),
(6, 'Novi Sad', 'Split', '0,2,4,5', '06:55:00', 9, 75, 8, 0),
(7, '', '', '', '00:00:00', 0, 0, 0, 2),
(8, 'Koper', 'Beograd', '0,1,3,5', '07:00:00', 7, 75, 8, 0);

-- --------------------------------------------------------

--
-- Table structure for table `t_pics`
--

CREATE TABLE `t_pics` (
  `id` int(11) NOT NULL,
  `file_path` varchar(150) NOT NULL,
  `city_id` int(11) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `t_pics`
--

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
  `is_demo` int(11) DEFAULT NULL,
  `deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `pass`, `status`, `city`, `address`, `phone`, `created_at`, `email_verified`, `reset_token_hash`, `reset_token_expires`, `is_demo`, `deleted`) VALUES
(1, 'Mika', 'mika@test.com', '369963', 'Driver', 'Novi Sad', 'Gajeva 3', '062639963', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, NULL, 0),
(2, 'Žika', 'zile@test.com', '333999', 'Driver', 'Novi Sad', 'Kočićeva 6', '063936888', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, NULL, 0),
(3, 'Pera', 'pera@test.com', '999333', 'Driver', 'Beograd', 'Gandijeva 303', '069357951', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, NULL, 0);
-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_logs`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_tickets`
--
ALTER TABLE `chat_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`);

--
-- Indexes for table `chat_typing`
--
ALTER TABLE `chat_typing`
  ADD PRIMARY KEY (`ticket_id`);

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
-- Indexes for table `order_logs`
--
ALTER TABLE `order_logs`
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
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `chat_tickets`
--
ALTER TABLE `chat_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `c_pics`
--
ALTER TABLE `c_pics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departures`
--
ALTER TABLE `departures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
-- AUTO_INCREMENT for table `order_logs`
--
ALTER TABLE `order_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `t_pics`
--
ALTER TABLE `t_pics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
