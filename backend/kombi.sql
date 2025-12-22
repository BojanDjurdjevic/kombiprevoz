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
(2, 2, 'customer', NULL, 'Pozdrav!', 1, '2025-12-15 23:09:46'),
(3, 2, 'customer', NULL, 'Hej hej', 1, '2025-12-15 23:26:17'),
(4, 2, 'admin', 10, 'Zdravo draga Eni', 1, '2025-12-15 23:26:18'),
(5, 2, 'customer', NULL, 'Zdravo, kako ste', 1, '2025-12-15 23:50:21'),
(6, 2, 'admin', 10, 'Dobro, a ti?', 1, '2025-12-16 00:13:50'),
(7, 2, 'customer', NULL, 'Odlično!', 1, '2025-12-16 00:15:15'),
(8, 2, 'admin', 10, 'E pa lepo, kako mogu da pomognem?', 1, '2025-12-16 00:27:52'),
(9, 2, 'customer', NULL, 'Zanima me do kada radite? Mislim vaša podrška...', 1, '2025-12-16 00:28:29'),
(10, 2, 'admin', 10, 'Radimo 24/7', 1, '2025-12-16 00:30:41'),
(11, 2, 'admin', 10, '😊', 1, '2025-12-16 00:31:22'),
(12, 2, 'admin', 10, 'Imaš li još neka pitanja? 👍', 0, '2025-12-16 15:32:54'),
(13, 2, 'admin', 10, 'Ako ne, želimo ti ugodan dan! ✅', 0, '2025-12-16 15:34:04'),
(14, 3, 'admin', 10, 'Dobar dan Valentina, kako mogu da Vam pomognem?', 1, '2025-12-16 15:42:07'),
(15, 3, 'customer', NULL, 'Dobar dan!', 1, '2025-12-16 15:44:33'),
(16, 3, 'customer', NULL, 'Želela bih da znam tačno vreme polaska', 1, '2025-12-16 16:19:48'),
(17, 3, 'admin', 10, 'Dobićete tačno vreme pre polasksa', 1, '2025-12-16 16:21:03'),
(18, 3, 'admin', 10, 'Odnosno, dan pre polaska će vas nazvati Vaš vozač, tada pravi raspored 😊', 1, '2025-12-16 16:34:51'),
(19, 3, 'customer', NULL, 'aha, odlično, hvala Vam!', 1, '2025-12-16 16:36:16'),
(20, 3, 'admin', 10, 'Nema na čemu! 😊', 1, '2025-12-16 17:11:50'),
(21, 3, 'admin', 10, 'Da li Vam još kako možemo pomoći?', 1, '2025-12-16 17:12:45'),
(22, 3, 'customer', NULL, 'Ne za sada. To je to, hvala Vam!', 1, '2025-12-16 17:13:15'),
(23, 3, 'admin', 10, 'U redu, prijatan dan želim! 👍', 1, '2025-12-16 17:14:19'),
(24, 3, 'customer', NULL, 'Hvala ', 1, '2025-12-16 17:14:47'),
(25, 4, 'customer', NULL, 'Dobro veče!', 1, '2025-12-17 15:17:10'),
(26, 4, 'admin', 10, 'Dobro veče draga Eni! 😊', 1, '2025-12-17 15:18:06'),
(27, 4, 'admin', 10, 'Kako mogu da ti pomognem?', 1, '2025-12-17 15:18:49'),
(28, 4, 'customer', NULL, 'Htela sam da pitam, da li imate vožnje za Grčku?', 1, '2025-12-17 15:19:22'),
(29, 4, 'admin', 10, 'Na žalost ne, ali imamo u planu uskoro...', 1, '2025-12-17 15:20:40'),
(30, 4, 'customer', NULL, 'Oh to bi baš bilo lepo', 1, '2025-12-17 15:21:15'),
(31, 4, 'customer', NULL, 'Da li znate od kada tačno?', 1, '2025-12-17 15:21:48'),
(32, 4, 'admin', 10, 'Pa trebalo bi od maja naredne godine. Najbolje da proverite još jednom u Aprilu 😊', 1, '2025-12-17 15:23:26'),
(33, 4, 'customer', NULL, 'OK', 1, '2025-12-17 15:23:46'),
(34, 4, 'admin', 10, 'Da li mogu još nekako da pomognem?', 1, '2025-12-17 15:24:39'),
(35, 4, 'customer', NULL, 'Ne, to je sve za sada. Hvala!', 1, '2025-12-17 15:25:29'),
(36, 4, 'admin', 10, 'U redu Enice draga', 1, '2025-12-17 15:28:47'),
(37, 4, 'admin', 10, 'Želim ti prijatno veče! 👍', 1, '2025-12-17 15:33:47'),
(38, 4, 'customer', NULL, 'Hvala, takođe!', 1, '2025-12-17 15:34:10'),
(39, 5, 'customer', NULL, 'Dobro veče opet!', 1, '2025-12-18 17:05:13'),
(40, 5, 'admin', 10, 'Dobro veče Enice', 1, '2025-12-18 17:05:47'),
(41, 5, 'customer', NULL, 'Kako ste?', 1, '2025-12-18 17:09:12'),
(42, 5, 'admin', 10, 'Dobro a ti?', 1, '2025-12-18 17:11:10'),
(43, 5, 'customer', NULL, 'Odlično!', 1, '2025-12-18 17:18:27'),
(44, 5, 'admin', 10, 'Pa super! Kako mogu da ti pomognem?', 1, '2025-12-18 17:19:14'),
(45, 5, 'customer', NULL, 'Do kada mogu da otkažem rezervaciju?', 1, '2025-12-18 17:21:46'),
(46, 5, 'admin', 10, 'Do 48h pre polaska, kroz tvoj nalog, ali ako je baš frka, možeš nas i kontaktirati ovde do 24h ranije', 1, '2025-12-18 17:22:43'),
(47, 5, 'customer', NULL, 'U redu! ', 1, '2025-12-18 17:28:55'),
(48, 5, 'admin', 10, 'Imaš li još pitanja?', 1, '2025-12-18 17:29:30'),
(49, 5, 'customer', NULL, 'A koji su razlozi za otkazivanje nakon tih 48h?', 1, '2025-12-18 17:31:38'),
(50, 5, 'admin', 10, 'U principu ne moraju biti neki razlozi, sve do 24 h pre polaska se možemo organizovati, nakon toga, ne možemo više otkazati', 1, '2025-12-18 17:32:52'),
(51, 5, 'customer', NULL, 'U redu, hvala Vam!', 1, '2025-12-18 17:33:39'),
(52, 5, 'admin', 10, 'Nema na čemu! Prijatno veče 😊', 1, '2025-12-18 17:36:15'),
(53, 6, 'customer', NULL, 'Dobar dan želim!', 1, '2025-12-19 13:46:29'),
(54, 6, 'admin', 10, 'Dobar dan Enice kako si danas?', 1, '2025-12-19 13:47:22'),
(55, 6, 'customer', NULL, 'Odlično! A vi?', 1, '2025-12-19 13:48:19'),
(56, 6, 'admin', 10, 'Isto tako! Kako mogu da pomognem?', 1, '2025-12-19 13:49:01'),
(57, 6, 'customer', NULL, 'Da li imate neku slobodnu vožnju za Zagreb, za sutra?', 1, '2025-12-19 13:49:53'),
(58, 6, 'admin', 10, 'Imamo dva mesta', 1, '2025-12-19 13:50:41'),
(59, 6, 'customer', NULL, 'Odlično! Baš mi trebaju 2 mesta. Rezervišem!', 1, '2025-12-19 13:51:53'),
(60, 7, 'customer', NULL, 'Oprostite, nisam Vam rekla adresu.\n\nAko može u Braće Ribnikar 33, Novi Sad', 1, '2025-12-19 13:53:20'),
(61, 7, 'admin', 10, 'Može naravno!', 1, '2025-12-19 13:55:10'),
(62, 7, 'admin', 10, 'Vidimo se sutra u 07:15 ujutru', 1, '2025-12-19 13:55:42'),
(63, 7, 'admin', 10, 'Na adresi koju ste naveli', 1, '2025-12-19 13:56:09'),
(64, 7, 'admin', 10, 'Da li treba još nešto?', 1, '2025-12-19 13:56:56'),
(65, 7, 'customer', NULL, 'Ne hvala! To bi bilo sve za sada.', 1, '2025-12-19 13:57:58'),
(66, 8, 'admin', NULL, 'Zdravo Ana Banana! 👋\n\nHvala što ste nas kontaktirali. Vaš tiket broj: TKT-E1339519.\n\nMolimo Vas da sačekate dok admin ne preuzme vašu konverzaciju. Odgovorićemo Vam u najkraćem mogućem roku.\n\nHvala na strpljenju! 😊', 1, '2025-12-19 14:15:47'),
(67, 8, 'customer', NULL, 'Dobar dan opet!', 1, '2025-12-19 14:15:47'),
(68, 8, 'admin', 10, 'Admin Bojan Djurdjevic je preuzeo Vašu konverzaciju i uskoro će Vam odgovoriti. 👨‍💼', 1, '2025-12-19 14:16:37'),
(69, 8, 'admin', 10, 'Hvala ti Enice na testingu, ugodan dan ti želim! 👍', 1, '2025-12-19 14:17:49'),
(70, 9, 'admin', NULL, 'Zdravo Ana Banana! 👋\n\nHvala što ste nas kontaktirali. Vaš tiket broj: TKT-56299451.\n\nMolimo Vas da sačekate dok admin ne preuzme vašu konverzaciju. Odgovorićemo Vam u najkraćem mogućem roku.\n\nHvala na strpljenju! 😊', 0, '2025-12-19 14:46:58'),
(71, 9, 'customer', NULL, 'Da testiramo email', 1, '2025-12-19 14:46:58'),
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
(2, 'TKT-53A51B18', 'Eni Baneni', 'ana@test.com', '456852159', NULL, 'closed', 10, '2025-12-15 23:09:46', '2025-12-16 15:34:18', '2025-12-16 15:34:04'),
(3, 'TKT-D6F979B5', 'Valentina Đ', 'valentajndj@gmail.com', '0641178898', '10123456', 'closed', 10, '2025-12-16 15:40:31', '2025-12-16 17:15:35', '2025-12-16 17:14:47'),
(4, 'TKT-976ADBF6', 'Eni Baneni', 'ana@test.com', '753159654', NULL, 'closed', 10, '2025-12-17 15:17:10', '2025-12-17 15:34:48', '2025-12-17 15:34:10'),
(5, 'TKT-449F1139', 'Enica', 'ana@test.com', '159654752', NULL, 'closed', 10, '2025-12-18 17:05:13', '2025-12-18 17:37:09', '2025-12-18 17:36:15'),
(6, 'TKT-735569F9', 'Ana Banana', 'ana@test.com', '062645852', '101256357', 'closed', NULL, '2025-12-19 13:46:29', '2025-12-19 13:52:30', '2025-12-19 13:51:53'),
(7, 'TKT-8D04142E', 'Ana Banana', 'ana@test.com', '062645852', '101256357', 'closed', 10, '2025-12-19 13:53:20', '2025-12-19 14:15:17', '2025-12-19 13:57:58'),
(8, 'TKT-E1339519', 'Ana Banana', 'ana@test.com', '062645852', '101256357', 'closed', 10, '2025-12-19 14:15:47', '2025-12-19 14:18:16', '2025-12-19 14:17:49'),
(9, 'TKT-56299451', 'Ana Banana', 'ana@test.com', '062645852', '101256357', 'closed', NULL, '2025-12-19 14:46:58', '2025-12-19 14:49:07', NULL),
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

INSERT INTO `chat_typing` (`ticket_id`, `user_type`, `user_id`, `updated_at`) VALUES
(2, 'admin', 10, '2025-12-16 15:33:54'),
(3, 'customer', NULL, '2025-12-16 17:14:42'),
(4, 'customer', NULL, '2025-12-17 15:34:10'),
(5, 'admin', 10, '2025-12-18 17:36:11'),
(6, 'customer', NULL, '2025-12-19 13:51:53'),
(7, 'customer', NULL, '2025-12-19 13:57:58'),
(8, 'admin', 10, '2025-12-19 14:17:49'),
(10, 'admin', 10, '2025-12-19 14:50:34');

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

INSERT INTO `countries` (`id`, `name`, `file_path`, `deleted`) VALUES
(1, 'Srbija', 'src/assets/img/countries/Srbija.jpg', 0),
(2, 'Hrvatska', 'src/assets/img/countries/Hrvatska.jpg', 0),
(3, 'Slovenija', 'src/assets/img/countries/Slovenija.jpg', 0),
(6, 'Austrija', 'src/assets/img/countries/flag_68f9a20135a5e2.91739709.jpg', 0),
(11, 'Nemačka', 'src/assets/img/countries/Nemačka.jpg', 0);

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

INSERT INTO `departures` (`id`, `driver_id`, `tour_id`, `code`, `file_path`, `date`, `created_at`, `deleted`) VALUES
(6, 15, 1, '1265168KP', 'src/assets/pdfs/1265168KP.pdf', '2025-07-15', '2025-10-10 07:52:29', 0),
(7, 15, 6, '1111111KP', NULL, '2025-07-15', '2025-10-10 07:52:29', 1),
(20, 15, 1, '0820652KP', 'src/assets/pdfs/0820652KP.pdf', '2025-10-20', '2025-10-23 19:27:49', 1);

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

INSERT INTO `orders` (`id`, `user_id`, `total`, `code`, `file_path`, `deleted`) VALUES
(1, 1, 150, '3693691KP', NULL, 0),
(2, 12, 100, '3693692KP', 'src/assets/pdfs/3693692KP.pdf', 0),
(3, 3, 200, '3693693KP', NULL, 0),
(4, 3, 75, '3693694KP', NULL, 0),
(5, 2, 150, '3693695KP', NULL, 0),
(6, 1, 300, '3693696KP', NULL, 0),
(7, 2, 320, '3693697KP', NULL, 1),
(9, 1, 160, '3693698KP', NULL, 1),
(84, 12, 100, '3367684KP', 'src/assets/pdfs/3367684KP.pdf', 0),
(85, 12, 100, '3367688KP', 'src/assets/pdfs/3367688KP.pdf', 0),
(95, 10, 200, '3562999KP', 'src/assets/pdfs/3562999KP.pdf', 0),
(96, 10, 150, '3563464KP', 'src/assets/pdfs/3563464KP.pdf', 0);

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

INSERT INTO `order_items` (`id`, `order_id`, `tour_id`, `places`, `price`, `add_from`, `add_to`, `date`, `dep_id`, `driver_id`, `deleted`) VALUES
(3, 95, 1, 2, 100, 'Gavrila Principa 99', 'Mornarska 24', '2025-12-23', 20, 15, 0),
(4, 95, 2, 2, 100, 'Mornarska 21', 'Gavrila Principa 6', '2025-12-28', NULL, NULL, 0),
(5, 96, 1, 1, 50, 'Gavrila Principa 9', 'Primorska 3', '2025-10-21', NULL, NULL, 0),
(6, 96, 2, 2, 100, 'Primorska 6', 'Gavrila Principa 6', '2025-10-25', NULL, NULL, 0);

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

INSERT INTO `order_logs` (`id`, `order_id`, `changed_by`, `action`, `field_changed`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 10, 'Ažuriranje', 'Datum Polaska', '2025-10-20', '2025-12-23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 22:53:31'),
(2, 4, 10, 'Ažuriranje', 'Datum povratka', '2025-12-24', '2025-12-27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 23:06:56'),
(3, 4, 10, 'Ažuriranje', 'Adresa od/do', 'Polazak: Mornarska 18 / Dolazak: Gavrila Principa 9', 'Polazak: Mornarska 21 / Dolazak: Gavrila Principa 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 23:25:33'),
(4, 4, 10, 'Ažuriranje', 'Broj mesta', '3', '2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 23:25:38'),
(5, 4, 10, 'Ažuriranje', 'Datum povratka', '2025-12-27', '2025-12-28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 23:25:40'),
(6, 5, 10, 'Otkazivanje', 'deleted', '0', '1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 23:40:47'),
(7, 5, 10, 'Ponovno aktiviranje', 'deleted', '1', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-20 23:41:01');

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

INSERT INTO `t_pics` (`id`, `file_path`, `city_id`, `deleted`) VALUES
(9, 'src/assets/img/cities/city_6903ea2a133932.46236822.jpg', 16, 0),
(10, 'src/assets/img/cities/city_6903ea2a9dec05.87184673.jpg', 16, 0),
(11, 'src/assets/img/cities/city_6903ea2ab5edc0.62995318.jpg', 16, 0),
(12, 'src/assets/img/cities/city_6903ea2adb5928.65739115.jpg', 16, 0),
(26, 'src/assets/img/cities/city_6904d35a2c5b01.76613459.jpg', 22, 0),
(27, 'src/assets/img/cities/city_6904d35a39b2a8.58285983.jpg', 22, 0),
(28, 'src/assets/img/cities/city_6904d35a9cb287.20381711.jpg', 22, 0),
(29, 'src/assets/img/cities/city_6904d35aa55778.06697946.jpg', 22, 0),
(30, 'src/assets/img/cities/city_6904d35aaba916.56004514.jpg', 22, 0),
(31, 'src/assets/img/cities/city_6904d35ab097b5.96130353.jpg', 22, 0),
(32, 'src/assets/img/cities/city_692337e7640ed4.26510502.jpg', 6, 0),
(33, 'src/assets/img/cities/city_692337e76f11a1.66236385.jpg', 6, 0),
(34, 'src/assets/img/cities/city_6925d5398f7621.49378305.jpg', 4, 0),
(35, 'src/assets/img/cities/city_6925d5399a7bc0.84045031.jpg', 4, 0),
(36, 'src/assets/img/cities/city_6925d5399c9a76.71481396.jpg', 4, 0),
(37, 'src/assets/img/cities/city_6925d5399ed109.30835864.jpg', 4, 0),
(38, 'src/assets/img/cities/city_6925d539a14161.97402555.jpg', 4, 0),
(39, 'src/assets/img/cities/city_6925d539a39745.77676297.jpg', 4, 0),
(40, 'src/assets/img/cities/city_6925d53a0668b1.73917701.jpg', 4, 0),
(41, 'src/assets/img/cities/city_6925d53a2bfa20.20996154.jpg', 4, 0),
(42, 'src/assets/img/cities/city_6925d53a2e3090.63234165.jpg', 4, 0),
(43, 'src/assets/img/cities/city_6925d53a2fcbe6.24334709.jpg', 4, 0),
(44, 'src/assets/img/cities/city_692609ec7f8e04.02963643.jpg', 5, 0),
(45, 'src/assets/img/cities/city_692609ec8b66a6.95585605.jpg', 5, 0),
(46, 'src/assets/img/cities/city_692609ec8ee049.77933215.jpg', 5, 0),
(47, 'src/assets/img/cities/city_692609ec909e36.62551223.jpg', 5, 0),
(48, 'src/assets/img/cities/city_692609ec93bd59.80519086.jpg', 5, 0),
(49, 'src/assets/img/cities/city_692609ec95bea9.39148429.jpg', 5, 0),
(50, 'src/assets/img/cities/city_692609ec994e89.85807114.jpg', 5, 0),
(51, 'src/assets/img/cities/city_692609ec9b6583.41578934.jpg', 5, 0),
(52, 'src/assets/img/cities/city_692609ec9e7ea7.98015879.jpg', 5, 0),
(53, 'src/assets/img/cities/city_692609eca0f1f6.80103562.jpg', 5, 0),
(54, 'src/assets/img/cities/city_692609eca368e2.90752730.jpg', 5, 0);

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
(3, 'Pera', 'pera@test.com', '999333', 'Driver', 'Beograd', 'Gandijeva 303', '069357951', '2025-05-22 20:07:25', '2025-05-22 20:07:25', NULL, NULL, NULL, 0),
(4, 'Eni Baneni', 'ana@test.com', '$2y$10$IYp8siMrxDi44tYKBfzvP.Cws4G57EtEOW1yP6hmR.Q60cWTjtjPG', 'User', 'Novi Sad', 'Gogoljeva 66', '062635852', '2025-06-18 20:46:52', '2025-06-18 20:46:52', NULL, NULL, NULL, 0),
(10, 'Bojan Djurdjevic', 'pininfarina164@gmail.com', '$2y$10$4gbyD7svBFPgXMGD2BdhbuyzSn3BtiAcD2sp/0SYtUM8KtWW/WFlK', 'Superadmin', 'Novi Sad', 'Gavrila Principa 6', '062640273', '2025-06-18 21:36:10', '2025-06-18 21:36:10', '5eb8e020c72706c5bab4ded7b17b32b4aec2fefecf23f01acfd6d677c8b7b429', '2025-07-19 18:29:45', NULL, 0),
(11, 'Bogdan Savić', 'bogy@test.com', '$2y$10$936X99bl53ajMGMEYwsfLe07ZIbVkzhmNSPlPP02wOAiSSmLBGbn.', 'User', 'Novi Sad', 'Puškinova 9', '062648963', '2025-06-18 22:20:44', '2025-06-18 22:20:44', NULL, NULL, NULL, 0),
(12, 'Valentina Djurdjevic', 'valentajndj@gmail.com', '$2y$10$rZ1jC98UFRtIwL1Dj3x.nuBwhvVwGjsDHPCAUlYD/J2DmyHGlGRlS', 'User', 'Sremska Kamenica', 'Gavrila Principa 6', '0641178898', '2025-06-24 13:26:48', '2025-06-24 13:26:48', 'ad030c511bb58a74373aaa1b42f8766108cbe38f2c301fb9590025cef27ae6d0', '2025-06-24 16:53:20', NULL, 0),
(15, 'Bojan Giuliano', 'bojan.giuliano@gmail.com', '$2y$10$iEdXHXsUclGst6WXXpM14.L7Ual/bBV9IMyYuhF2Sqfb5upkRhkze', 'Driver', 'Novi Sad', 'Seljačkih Buna 29', '062640227', '2025-06-29 06:34:06', '2025-06-29 06:34:06', NULL, NULL, NULL, 0),
(17, 'Kasac Prasac', 'kas@test.com', '$2y$10$47nyYWGlwqPbCqxh33oouuzETHCTnv2fiVL.I3StnQjEPpwVFsIdu', 'User', 'Novi Sad', 'Takovska 9', '062333999', '2025-07-22 06:34:01', '2025-07-22 06:34:01', NULL, NULL, NULL, 0),
(18, 'Filip ŠoŠtar', 'fitafoh316@idwager.com', '$2y$10$lwNtLVWk.clcS.ZGDoJ9Le9x.Z6eBcvKxAbxdMQgPGzAOUh3BC5zi', 'Driver', 'Novi Sad', 'Gogoljeva', '063963369', '2025-11-29 14:31:55', '2025-11-29 14:31:55', NULL, NULL, NULL, 0);

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

INSERT INTO `user_logs` (`id`, `user_id`, `changed_by`, `action`, `field_changed`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 4, 10, 'status_change', 'status', 'User', 'Driver', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:01:24'),
(2, 4, 10, 'status_change', 'status', 'Driver', 'User', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:03:51'),
(3, 4, 10, 'update', 'address', 'ana@test.com', 'ana@test.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:03:51'),
(4, 4, 10, 'status_change', 'status', 'Driver', 'User', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:03:51'),
(5, 4, 10, 'status_change', 'status', 'User', 'Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:08:50'),
(6, 4, 10, 'update', 'address', 'Gogoljeva 33', 'Gogoljeva 66', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:08:50'),
(7, 4, 10, 'update', 'phone', '062635853', '062635852', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 12:08:50'),
(8, 4, 10, 'status_change', 'status', 'Admin', 'User', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-15 22:37:40');

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
