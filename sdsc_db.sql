-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 04:40 AM
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
-- Database: `sdsc_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `item_condition` enum('Good Condition','Under Maintenance','Lost') DEFAULT 'Good Condition',
  `date_borrowed` datetime DEFAULT NULL,
  `date_returned` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `item_name`, `category`, `quantity`, `status`, `item_condition`, `date_borrowed`, `date_returned`) VALUES
(2, '', '', 0, '', 'Good Condition', NULL, NULL),
(3, 'hammer', 'tool', 4, 'Available', 'Good Condition', '2026-03-05 06:20:59', '2026-03-05 06:21:15'),
(4, 'room101', 'key', 2, 'Available', 'Good Condition', '2026-03-05 06:54:55', '2026-03-05 06:55:52'),
(5, 'Room 301 Key', 'Key', 2, 'Available', 'Good Condition', '2026-03-05 06:12:32', '2026-03-05 06:13:36'),
(6, 'Room 302 Key', 'Key', 1, 'Available', 'Good Condition', NULL, NULL),
(7, 'Epson Projector A', 'Equipment', 1, 'Available', 'Good Condition', NULL, NULL),
(8, 'HDMI Cable 1', 'Equipment', 5, 'Available', 'Good Condition', NULL, '2026-03-05 06:13:36'),
(9, 'key', 'Tool', 1, 'Available', 'Good Condition', NULL, NULL),
(11, 'room 303 key', 'Key', 1, 'Available', 'Good Condition', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `borrower_name` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `item_id`, `borrower_name`, `student_id`, `purpose`, `request_date`, `return_date`, `status`) VALUES
(1, 3, 'paye ', 'c22-358', NULL, '2026-01-20 02:47:08', NULL, 'Returned'),
(2, 3, 'glenn', '09312', NULL, '2026-01-20 02:55:23', NULL, 'Returned'),
(3, 4, 'Glenn', '1223', NULL, '2026-01-20 03:07:52', NULL, 'Returned'),
(4, 4, 'glenn', '09312', NULL, '2026-01-24 03:54:19', NULL, 'Returned'),
(5, 8, 'msaratiaw', 'dawdaw', NULL, '2026-02-18 03:28:34', NULL, 'Returned'),
(6, 5, 'Userdwda', 'dawdaw', NULL, '2026-02-18 03:29:47', NULL, 'Returned'),
(7, 5, 'Userdwa', 'daw', NULL, '2026-02-18 03:29:52', NULL, 'Returned'),
(8, 3, 'Student Account', '09312', NULL, '2026-02-21 23:28:45', NULL, 'Returned'),
(9, 3, 'Professor Account', '09312', NULL, '2026-02-21 23:30:53', NULL, 'Returned'),
(10, 4, 'Student Account', '21312', 'dawwda', '2026-03-04 15:19:15', NULL, 'Returned'),
(11, 4, 'Professor Account', '21312', 'dawwda', '2026-03-04 22:54:55', '2026-03-05 06:55:52', 'Returned');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `admin_folder` int(50) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `admin_folder`) VALUES
(1, 'Student Account', 'student@test.com', '12345', 'student', 0),
(2, 'Professor Account', 'prof@test.com', '12345', 'faculty', 0),
(3, 'System Administrator', 'admin@test.com', 'admin123', 'admin', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
