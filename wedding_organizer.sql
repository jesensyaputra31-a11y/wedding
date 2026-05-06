-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 04:12 AM
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
-- Database: `wedding_organizer`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@wedding.com', '$2y$10$HWI4YhJTed8W3jNKBnfFQeuz1wV9JPTelA0yyoeIwj4zB9HXF727C', 'Super Administrator', NULL, 'super_admin', '2026-04-28 07:51:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invitations`
--

CREATE TABLE `invitations` (
  `id` int(11) NOT NULL,
  `guest_name` varchar(100) NOT NULL,
  `guest_email` varchar(100) NOT NULL,
  `wedding_id` int(11) DEFAULT NULL,
  `status` enum('sent','pending','cancelled') DEFAULT 'pending',
  `sent_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invitations`
--

INSERT INTO `invitations` (`id`, `guest_name`, `guest_email`, `wedding_id`, `status`, `sent_date`, `created_at`) VALUES
(1, 'jfrkrngu', 'yanti@gmail.com', 0, 'sent', '2026-04-29 16:55:21', '2026-04-29 09:55:21'),
(2, 'jrfii', 'yanti@gmail.com', 0, 'sent', '2026-04-29 17:27:42', '2026-04-29 10:27:42'),
(3, 'fssrijgrepo', 'yanti@gmail.com', 0, 'sent', '2026-04-29 21:21:34', '2026-04-29 14:21:34');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `guest_count` int(11) DEFAULT 100,
  `notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `payment_method`, `status`, `payment_date`, `notes`, `created_at`) VALUES
(1, 0, 20000000.00, 'Credit Card', 'completed', '2026-04-29 16:55:43', 'iiiii', '2026-04-29 09:55:43'),
(2, 0, 200000000.00, 'Bank Transfer', 'completed', '2026-04-29 21:33:31', 'BANAYAK BANGET', '2026-04-29 14:33:31'),
(3, 0, 9999999999999.99, 'Bank Transfer', 'completed', '2026-05-01 16:03:07', 'ijjnergrnyoiyh', '2026-05-01 09:03:07');

-- --------------------------------------------------------

--
-- Table structure for table `planner_ratings`
--

CREATE TABLE `planner_ratings` (
  `id` int(11) NOT NULL,
  `planner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `planner_ratings`
--

INSERT INTO `planner_ratings` (`id`, `planner_id`, `user_id`, `rating`, `created_at`) VALUES
(1, 1, 15, 5, '2026-04-29 11:30:19'),
(2, 2, 15, 5, '2026-04-29 11:57:33'),
(3, 3, 15, 4, '2026-04-29 11:57:45');

-- --------------------------------------------------------

--
-- Table structure for table `report_logs`
--

CREATE TABLE `report_logs` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `report_data` text DEFAULT NULL,
  `generated_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `full_name`, `phone`, `role`, `created_at`, `reset_token`, `reset_expires`, `status`) VALUES
(1, 'client@test.com', 'Test Client', 'client@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'client', '2026-04-28 02:30:01', NULL, NULL, 'Active'),
(2, 'jesen', '', 'yanti31@gmail.com', '$2y$10$7W7T7TFtWpN5cVsKv4v78udX2o/IeMnL.Gqrq8h0uFOai.WvauHbO', 'jesen', '02', 'client', '2026-04-28 03:12:09', '75742d4c32e92d8448afe53f358b8e076757c628c168807103c372f31bc90e79', '2026-04-29 18:44:31', 'Active'),
(13, 'yahhh', '', 'yahh@gmail.com', '$2y$10$nJuSPIpDGy9.ld46NnQfvOkadgjdDu3EUg/yBhh3CQgkcWnVbJAmS', 'yahhh', '00', 'client', '2026-04-28 09:09:09', NULL, NULL, 'Active'),
(14, 'admin2', '', 'admin@wedding.com', '$2y$10$7E9HYchzMij8xQwus/2fdOE9n6U4S2.zyya2bKq/byJpWpkZt3Roe', NULL, NULL, 'user', '2026-04-28 12:18:48', '4a421c2f7f16cd5d97183602cf0b7fe9b8d7a158baf348e110589b068d9a1072', '2026-04-29 18:30:39', 'Active'),
(15, 'jenyyy', '', 'jenyy@gmail.com', '$2y$10$CftynfjUARsJFkA6w77oU.hgWnNgEjCLSzwJ3atL6OwxPYqls5306', 'jenyyy', '', 'client', '2026-04-29 08:47:37', NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `weddings`
--

CREATE TABLE `weddings` (
  `id` int(11) NOT NULL,
  `couple` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `guests` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'Planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weddings`
--

INSERT INTO `weddings` (`id`, `couple`, `date`, `guests`, `status`, `created_at`) VALUES
(1, 'salma&rizki', '2026-07-21', 250, 'Planning', '2026-04-29 10:36:50'),
(2, 'Sarah & David', '2029-09-21', 200, 'Planning', '2026-04-29 14:34:29');

-- --------------------------------------------------------

--
-- Table structure for table `wedding_planners`
--

CREATE TABLE `wedding_planners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `weddings` int(11) DEFAULT 0,
  `rating` decimal(2,1) DEFAULT 0.0,
  `photo_url` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_ratings` int(11) DEFAULT 0,
  `ratings_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wedding_planners`
--

INSERT INTO `wedding_planners` (`id`, `name`, `weddings`, `rating`, `photo_url`, `phone`, `email`, `status`, `created_at`, `total_ratings`, `ratings_count`) VALUES
(1, 'Sarah Wijaya', 45, 4.9, 'https://randomuser.me/api/portraits/women/68.jpg', '+62 812 3456 7890', 'sarah@weddingplanner.com', 'active', '2026-04-29 10:54:56', 250, 51),
(2, 'Budi Santoso', 38, 4.8, 'https://randomuser.me/api/portraits/men/32.jpg', '+62 812 3456 7891', 'budi@weddingplanner.com', 'active', '2026-04-29 10:54:56', 195, 41),
(3, 'Dewi Anjani', 52, 4.9, 'https://randomuser.me/api/portraits/women/45.jpg', '+62 812 3456 7892', 'dewi@weddingplanner.com', 'active', '2026-04-29 10:54:56', 316, 65),
(4, 'Andre Gunawan', 41, 4.7, 'https://randomuser.me/api/portraits/men/55.jpg', '+62 812 3456 7893', 'andre@weddingplanner.com', 'active', '2026-04-29 10:54:56', 205, 43),
(5, 'Maya Sari', 36, 4.8, 'https://randomuser.me/api/portraits/women/89.jpg', '+62 812 3456 7894', 'maya@weddingplanner.com', 'active', '2026-04-29 10:54:56', 180, 38);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `planner_ratings`
--
ALTER TABLE `planner_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`planner_id`,`user_id`);

--
-- Indexes for table `report_logs`
--
ALTER TABLE `report_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `weddings`
--
ALTER TABLE `weddings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wedding_planners`
--
ALTER TABLE `wedding_planners`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `planner_ratings`
--
ALTER TABLE `planner_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `report_logs`
--
ALTER TABLE `report_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `weddings`
--
ALTER TABLE `weddings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wedding_planners`
--
ALTER TABLE `wedding_planners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
