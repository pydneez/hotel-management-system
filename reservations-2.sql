-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 05, 2025 at 12:07 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `res_id` int NOT NULL,
  `guest_id` int NOT NULL,
  `room_no` varchar(10) DEFAULT NULL,
  `checkin_date` date NOT NULL,
  `checkin_time` timestamp NULL DEFAULT NULL,
  `checkout_date` date NOT NULL,
  `checkout_time` timestamp NULL DEFAULT NULL,
  `num_adults` tinyint NOT NULL,
  `num_children` tinyint NOT NULL DEFAULT '0',
  `booking_date` datetime NOT NULL,
  `type_id` int NOT NULL,
  `total_nights` smallint NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Checked-In','Checked-Out','Cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`res_id`, `guest_id`, `room_no`, `checkin_date`, `checkin_time`, `checkout_date`, `checkout_time`, `num_adults`, `num_children`, `booking_date`, `type_id`, `total_nights`, `total_cost`, `status`) VALUES
(1, 1, '101', '2025-09-01', NULL, '2025-09-04', NULL, 2, 0, '2025-08-15 10:00:00', 2, 3, 7500.00, 'Checked-Out'),
(2, 2, '201', '2025-10-14', NULL, '2025-10-16', NULL, 1, 0, '2025-10-10 12:30:00', 1, 2, 3000.00, 'Checked-In'),
(3, 1, NULL, '2025-11-20', NULL, '2025-11-25', NULL, 3, 0, '2025-10-01 09:00:00', 3, 5, 22500.00, 'Confirmed');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`res_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_no` (`room_no`),
  ADD KEY `checkin_date` (`checkin_date`),
  ADD KEY `checkout_date` (`checkout_date`),
  ADD KEY `reservations_ibfk_3` (`type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `res_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_no`) REFERENCES `rooms` (`room_no`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`type_id`) REFERENCES `RoomTypes` (`type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
