-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 20, 2025 at 06:42 AM
-- Server version: 5.7.44
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CancelGuestBooking` (IN `p_guest_id` INT, IN `p_res_id` INT, OUT `p_message_type` VARCHAR(10), OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_checkin_date DATE;
    DECLARE v_status ENUM('Pending','Confirmed','Checked-In','Checked-Out','Cancelled');
    DECLARE v_hours_until_checkin INT;
    DECLARE v_owner_guest_id INT;

    -- 1. Check if the reservation exists and belongs to the guest
    SELECT `checkin_date`, `status`, `guest_id` 
    INTO v_checkin_date, v_status, v_owner_guest_id
    FROM `reservations`
    WHERE `res_id` = p_res_id
    LIMIT 1;

    IF v_owner_guest_id IS NULL THEN
        SET p_message_type = 'error';
        SET p_message = 'Booking not found.';
    ELSEIF v_owner_guest_id != p_guest_id THEN
        SET p_message_type = 'error';
        SET p_message = 'You do not have permission to cancel this booking.';
    ELSEIF v_status NOT IN ('Confirmed', 'Pending') THEN
        -- Use CONCAT to include the variable status
        SET p_message_type = 'error';
        SET p_message = CONCAT('This booking cannot be cancelled (Status: ', v_status, ').');
    ELSE
        -- 2. Check the 24-hour cancellation window
        SET v_hours_until_checkin = TIMESTAMPDIFF(HOUR, NOW(), v_checkin_date);
        
        IF v_hours_until_checkin <= 24 THEN
            SET p_message_type = 'error';
            SET p_message = 'Cannot cancel: Check-in is within 24 hours.';
        ELSE
            -- 3. All checks passed. Perform the cancellation transaction.
            START TRANSACTION;
            
            -- Update Reservation
            UPDATE `reservations` SET `status` = 'Cancelled' WHERE `res_id` = p_res_id;
            
            -- Update Invoice. Set to 'Refunded' as the charge is now void.
            UPDATE `Invoices` SET `payment_status` = 'Refunded' WHERE `res_id` = p_res_id;
            
            COMMIT;
            
            SET p_message_type = 'success';
            SET p_message = 'Your booking has been successfully cancelled.';
        END IF;
    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CheckInGuest` (IN `p_res_id` INT, IN `p_room_no` VARCHAR(10))   BEGIN
    DECLARE v_room_status VARCHAR(50);
    DECLARE v_res_status ENUM('Pending','Confirmed','Checked-In','Checked-Out','Cancelled');
    DECLARE v_type_id_room INT;
    DECLARE v_type_id_res INT;

    SELECT `status`, `type_id` INTO v_res_status, v_type_id_res FROM `reservations` WHERE `res_id` = p_res_id LIMIT 1;
    SELECT `status`, `type_id` INTO v_room_status, v_type_id_room FROM `Rooms` WHERE `room_no` = p_room_no LIMIT 1;

    START TRANSACTION;

     IF v_res_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reservation not found.';
        ROLLBACK;
    ELSEIF v_res_status != 'Confirmed' AND v_res_status != 'Pending' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This guest cannot be checked in. Status is already Checked-In, Checked-Out, or Cancelled.';
        ROLLBACK;
    ELSEIF v_room_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Selected room number does not exist.';
        ROLLBACK;
    ELSEIF v_room_status != 'Available' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This room is not available. It might be Occupied, Cleaning, or under Maintenance.';
        ROLLBACK;
    ELSEIF v_type_id_room != v_type_id_res THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Room type mismatch. The selected room does not match the type on the reservation.';
        ROLLBACK;
    ELSE
        -- 1. Update reservation
        UPDATE `reservations`
        SET 
            `status` = 'Checked-In',
            `room_no` = p_room_no,
         	`checkin_date` = NOW()
        WHERE 
            `res_id` = p_res_id;
            
        -- 2. Update physical room
        UPDATE `Rooms` SET `status` = 'Occupied' WHERE `room_no` = p_room_no;
            
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CheckOutGuest` (IN `p_res_id` INT, IN `p_room_no` VARCHAR(10))   BEGIN
    DECLARE v_res_status ENUM('Pending','Confirmed','Checked-In','Checked-Out','Cancelled');

    SELECT `status` INTO v_res_status FROM `reservations` WHERE `res_id` = p_res_id LIMIT 1;

    START TRANSACTION;

    IF v_res_status != 'Checked-In' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Guest is not Checked-In.';
        ROLLBACK;
    ELSE
        
        UPDATE `reservations`
        SET 
            `status` = 'Checked-Out',
            `checkout_date` = NOW()
        WHERE 
            `res_id` = p_res_id;
            
        -- 2. Update room
        UPDATE `Rooms` SET `status` = 'Cleaning' WHERE `room_no` = p_room_no;
        
        -- 3. Update invoice
        UPDATE `Invoices` SET `payment_status` = 'Paid', `due_date` = CURDATE() WHERE `res_id` = p_res_id;
            
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateGuestReservation` (IN `p_guest_id` INT, IN `p_type_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_num_adults` INT, IN `p_num_children` INT, IN `p_payment_method` VARCHAR(50), OUT `p_res_id` INT, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_total_rooms_of_type INT;
    DECLARE v_conflicting_bookings INT;
    DECLARE v_total_cost DECIMAL(10, 2);
    DECLARE v_total_nights INT;
    -- REMOVED: v_new_invoice_id is no longer needed

    -- 1. Check for availability
    -- Find the total number of physical rooms of this type
    SELECT COUNT(*) INTO v_total_rooms_of_type
    FROM Rooms
    WHERE type_id = p_type_id AND status != 'Maintenance';

    -- Find the number of conflicting bookings for this room *type*
    SELECT COUNT(*) INTO v_conflicting_bookings
    FROM reservations
    WHERE type_id = p_type_id
      AND status != 'Cancelled'
      AND checkin_date < p_check_out
      AND checkout_date > p_check_in;

    -- If conflicting bookings >= total rooms, it's full
    IF v_conflicting_bookings >= v_total_rooms_of_type THEN
        SET p_message = 'Sorry, all rooms of this type are fully booked for the selected dates.';
        SET p_res_id = NULL;
    ELSE
        -- 2. Create the reservation
        START TRANSACTION;
        
        -- Insert reservation. `trg_BeforeReservationInsert` will auto-calculate
        -- total_nights and total_cost.
        INSERT INTO reservations (
            guest_id, room_no, type_id, checkin_date, checkout_date, 
            num_adults, num_children, booking_date, status
        ) VALUES (
            p_guest_id, NULL, p_type_id, p_check_in, p_check_out, 
            p_num_adults, p_num_children, NOW(), 'Confirmed'
        );

        -- Get the new reservation ID
        SET p_res_id = LAST_INSERT_ID();

        -- 3. Get the calculated cost from the trigger
        SELECT total_cost INTO v_total_cost
        FROM reservations
        WHERE res_id = p_res_id;
        
        -- 4. Create the corresponding (simplified) invoice
        INSERT INTO Invoices (res_id, guest_id, issue_date, due_date, total_amount, payment_status, payment_method)
        VALUES (
            p_res_id,
            p_guest_id,
            CURDATE(),
            p_check_in, -- Due on check-in
            v_total_cost,
            'Unpaid',
            p_payment_method
        );
        
        -- REMOVED: Step 5 (Insert into Invoice_Items) is no longer needed.
            
        COMMIT;
        SET p_message = 'Booking Confirmed!';
        
    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateMillionInvoice` ()   INSERT INTO invoices(
    invoice_id, res_id, guest_id, issue_date, due_date, 
    total_amount, payment_status, payment_method
) 
SELECT
    t.id + 10 as invoice_id, 
    (t.id % 24) + 1 as res_id, 
    (t.id % 6) + 1 as guest_id, 
    

    @issue_date := DATE(DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY)) as issue_date,
    
    DATE_ADD(@issue_date, INTERVAL (@days_multiplier := FLOOR(RAND() * 7) + 1) DAY) as due_date,
    
    (@days_multiplier * ELT(FLOOR(RAND() * 3) + 1, 1500, 2500, 4500)) as total_amount,
    

    IF(RAND() < 0.1, 'Refunded', 'Paid') as payment_status, 
    
    IF(RAND() > 0.5, 'Cash', 'Credit Card') as payment_method

FROM (
    SELECT 
        a.n + b.n*10 + c.n*100 + d.n*1000 + e.n*10000 + f.n*100000 + h.n*1000000 as id
    FROM 
        ten a CROSS JOIN ten b CROSS JOIN ten c CROSS JOIN ten d
        CROSS JOIN ten e CROSS JOIN ten f CROSS JOIN ten h
    WHERE
        h.n < 3 -- <-- THIS IS THE ONLY CHANGE
) as t$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateWalkInReservation` (IN `p_fname` VARCHAR(50), IN `p_lname` VARCHAR(50), IN `p_email` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_type_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_num_adults` INT, IN `p_num_children` INT, IN `p_payment_method` VARCHAR(50), OUT `p_res_id` INT, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_guest_id INT;
    DECLARE v_password_hash VARCHAR(255);
    
    -- Start a transaction for the guest check
    START TRANSACTION;

    -- 1. Find or Create the Guest
    SELECT guest_id INTO v_guest_id FROM Guests WHERE email = p_email LIMIT 1;
    
    IF v_guest_id IS NULL THEN
        -- Guest does not exist, create them
        SET v_password_hash = 'walkin_guest_no_pass'; -- Set a default non-login-able password
        INSERT INTO Guests (fname, lname, email, phone, password_hash, created_at)
        VALUES (p_fname, p_lname, p_email, p_phone, v_password_hash, NOW());
        
        SET v_guest_id = LAST_INSERT_ID();
    ELSE
        -- Optional: Guest exists, update their phone number
        UPDATE Guests SET phone = p_phone WHERE guest_id = v_guest_id;
    END IF;
    
    -- We can commit the guest part now
    COMMIT;
    
    -- 2. Now, call the main "engine" to handle the booking
    -- Note: We pass the 'v_guest_id' and the new 'p_payment_method'
    CALL sp_CreateGuestReservation(
        v_guest_id, p_type_id, p_check_in, p_check_out, 
        p_num_adults, p_num_children, p_payment_method,
        p_res_id, p_message
    );

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetDashboardAnalytics` ()   BEGIN
    /*
      This procedure is optimized for pure speed.
      It ONLY calculates operational metrics (Rooms & Guests).
      It skips all financial calculations.
    */
   
    -- 1. DECLARE variables
    DECLARE v_today DATE;
   
    DECLARE v_arrivals_count INT DEFAULT 0;
    DECLARE v_departures_count INT DEFAULT 0;
    DECLARE v_checked_in_count INT DEFAULT 0;
    DECLARE v_walkins_today INT DEFAULT 0;
   
    DECLARE v_rooms_clean INT DEFAULT 0;
    DECLARE v_rooms_occupied INT DEFAULT 0;
    DECLARE v_rooms_cleaning INT DEFAULT 0;
    DECLARE v_rooms_maintenance INT DEFAULT 0;

    -- 2. SET date variables
    SET v_today = CURDATE();

    -- 3. Query 1: Reservations (Uses idx_status_checkin, idx_status_checkout, idx_checkin_booking)
    SELECT
        COUNT(CASE WHEN status IN ('Confirmed', 'Pending') AND checkin_date = v_today THEN 1 END),
        COUNT(CASE WHEN status = 'Checked-In' AND checkout_date = v_today THEN 1 END),
        COUNT(CASE WHEN status = 'Checked-In' THEN 1 END),
        COUNT(CASE WHEN checkin_date = v_today AND DATE(booking_date) = v_today THEN 1 END)
    INTO
        v_arrivals_count, v_departures_count, v_checked_in_count, v_walkins_today
    FROM
        reservations;

    -- 4. Query 2: Rooms (Fast count on small table)
    SELECT
        COUNT(CASE WHEN status = 'Available' THEN 1 END),
        COUNT(CASE WHEN status = 'Occupied' THEN 1 END),
        COUNT(CASE WHEN status = 'Cleaning' THEN 1 END),
        COUNT(CASE WHEN status = 'Maintenance' THEN 1 END)
    INTO
        v_rooms_clean, v_rooms_occupied, v_rooms_cleaning, v_rooms_maintenance
    FROM
        Rooms;
       
    -- 5. Final SELECT: Return the 8 variables
    SELECT
        IFNULL(v_arrivals_count, 0) AS arrivals_count,
        IFNULL(v_departures_count, 0) AS departures_count,
        IFNULL(v_checked_in_count, 0) AS checked_in_count,
        IFNULL(v_walkins_today, 0) AS walkins_today,
       
        IFNULL(v_rooms_clean, 0) AS rooms_clean,
        IFNULL(v_rooms_occupied, 0) AS rooms_occupied,
        IFNULL(v_rooms_cleaning, 0) AS rooms_cleaning,
        IFNULL(v_rooms_maintenance, 0) AS rooms_maintenance;
       
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetFinancialAnalytics` (IN `p_selected_year` INT, IN `p_selected_month` INT)   BEGIN
    /*
      This procedure calculates all financial KPIs for a
      given year/month period.
    */
   
    -- 1. DECLARE all variables
    DECLARE v_revenue DECIMAL(14, 2);
    DECLARE v_refunds DECIMAL(14, 2);
    DECLARE v_unpaid DECIMAL(14, 2);
    DECLARE v_total_bookings INT;
    DECLARE v_total_cancellations INT;

    -- 2. Define the date range based on input
    DECLARE v_start_date DATE;
    DECLARE v_end_date DATE;

    IF p_selected_month = 0 THEN
        -- Full Year Logic: Jan 1 to Dec 31 of selected year
        SET v_start_date = MAKEDATE(p_selected_year, 1);
        SET v_end_date = LAST_DAY(MAKEDATE(p_selected_year, 12));
    ELSE
        -- Specific Month Logic: First day to Last day of selected month
        SET v_start_date = MAKEDATE(p_selected_year, 1) + INTERVAL (p_selected_month - 1) MONTH;
        SET v_end_date = LAST_DAY(v_start_date);
    END IF;

    -- 3. Get all financials from Invoices in one query
    -- This uses your idx_status_issue_date index for speed
    SELECT
        IFNULL(SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END), 0),
        IFNULL(SUM(CASE WHEN payment_status = 'Refunded' THEN total_amount ELSE 0 END), 0),
        IFNULL(SUM(CASE WHEN payment_status = 'Unpaid' THEN total_amount ELSE 0 END), 0)
    INTO
        v_revenue, v_refunds, v_unpaid
    FROM
        Invoices
    WHERE
        issue_date BETWEEN v_start_date AND v_end_date;

    -- 4. Get all booking/cancellation counts from Reservations
    -- This uses your idx_booking_date index for speed
    SELECT
        COUNT(invoice_id),
        COUNT(CASE WHEN payment_status= 'Refunded' THEN 1 END)
    INTO
        v_total_bookings, v_total_cancellations
    FROM
        invoices
    WHERE
        issue_date BETWEEN v_start_date AND v_end_date;

    -- 5. Final SELECT: Return all variables as a single row result set
    SELECT
        v_revenue AS total_revenue,
        v_refunds AS total_refunds,
        (v_revenue - v_refunds) AS net_revenue,
        v_unpaid AS total_unpaid,
        v_total_bookings AS total_bookings,
        v_total_cancellations AS total_cancellations,
        -- Calculate Avg. Booking Value, avoiding division by zero
        (CASE WHEN v_total_bookings > 0 THEN v_revenue / v_total_bookings ELSE 0 END) AS avg_booking_value;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetTopRoomRevenues` (IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    SELECT
        rt.type_name,
        COUNT(i.invoice_id) AS total_bookings,
        IFNULL(SUM(i.total_amount), 0) AS total_revenue
    FROM Invoices i
    -- The optimizer will now use idx_covering_revenue to fly through this table
    JOIN reservations r ON i.res_id = r.res_id
    JOIN RoomTypes rt ON r.type_id = rt.type_id
    WHERE
        i.payment_status = 'Paid'
        AND i.issue_date BETWEEN p_start_date AND p_end_date
    GROUP BY rt.type_id
    ORDER BY total_revenue DESC
    LIMIT 10;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SearchAvailableRoomTypes` (IN `p_check_in` DATE, IN `p_check_out` DATE, IN `p_num_guests` INT, IN `p_num_nights` INT)   BEGIN
    /*
     * This procedure finds all room types that are available
     * for a given date range and guest count.
     */
    SELECT 
        rt.*,
        -- Subquery to get the first image
        (SELECT rti.image_url 
         FROM RoomTypeImages rti 
         WHERE rti.type_id = rt.type_id 
         LIMIT 1) AS main_image_url,
         
        -- Call our existing function to get the total cost
        fn_CalculateTotalCost(rt.type_id, p_num_nights) AS total_cost 
    FROM 
        RoomTypes rt
    WHERE 
        -- 1. Check capacity
        rt.capacity >= p_num_guests
    AND
        -- 2. Check for availability (this is the complex part)
        -- This checks if the number of rooms *of this type* that are 
        -- *already booked* in this timeframe is LESS THAN the
        -- total number of physical rooms of this type available.
        (
            SELECT COUNT(r.room_no) -- Total physical rooms of this type
            FROM Rooms r
            WHERE r.type_id = rt.type_id 
            AND r.status != 'Maintenance'
        ) 
        >
        (
            SELECT COUNT(res.res_id) -- Total conflicting bookings for this type
            FROM reservations res
            WHERE res.type_id = rt.type_id
            AND res.status != 'Cancelled'
            -- The Overlap Check:
            AND res.checkin_date < p_check_out
            AND res.checkout_date > p_check_in
        )
    ORDER BY
        rt.base_price ASC;

END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_CalculateTotalCost` (`r_type_id` INT, `r_num_nights` INT) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
DECLARE v_base_price DECIMAL(10, 2);

-- Get the base price for the given room type
SELECT base_price INTO v_base_price
FROM RoomTypes
WHERE type_id = r_type_id;

-- Return the total cost
RETURN (v_base_price * r_num_nights);


END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `emp_id` int(11) NOT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password_hash` char(60) NOT NULL,
  `role` enum('Admin','Staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`emp_id`, `fname`, `lname`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'John', 'Jasper', 'admin@hms.com', '$2y$10$cFm3a.dBSWpZOD8iWvwt8ucnr7G08tpOCki9ZSkUKNnngIj.o6Bam', 'Admin', '2025-10-25 04:31:16'),
(2, 'Robin', 'Hood', 'staff@hms.com', '$2y$10$qC92jgiXmIBCMimyu61dTuDu4YGxLEkDKbwOPr6HY3DEl3fnQgJ2i', 'Staff', '2025-10-25 04:31:16'),
(5, 'Minnie', 'Stark', 'admin2@hms.com', '$2y$10$bcxpf5uHxZd8BfjnvxCGHu.gx.6dSpPus5leLhsgJOUBs1zcbN9w6', 'Admin', '2025-11-18 04:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `Guests`
--

CREATE TABLE `Guests` (
  `guest_id` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` char(60) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Guests`
--

INSERT INTO `Guests` (`guest_id`, `fname`, `lname`, `email`, `password_hash`, `phone`, `created_at`) VALUES
(1, 'Sam', 'Khan', 'sam@email.com', '$2y$10$C6BmYm9dPKMiTsNEuDs46ulkfR9PswiDQDvpw0PtDRHGqE8kvL6Xi', '+66812345678', '2025-10-25 01:55:30'),
(2, 'Som', 'Dee', 'som@email.com', '$2y$10$SfLbAlYeqYDWReDAyZEG4.cJpEwbiWkUaGMkQXauQJ4ybbd8FszIW', '+66898765432', '2025-10-25 01:55:30'),
(3, 'Jane', 'Doe', 'jane@email.com', '$2y$10$Z3Wi6ARkR3Xu8H6tJygEieOi/Q4GgbwOHd2dpFAs6Gkgc/VpM1fz.', '1122334455', '2025-11-05 12:13:54'),
(4, 'John', 'Doe', 'john@email.com', '$2y$10$XRMZZIRCuk4JOF7NRpJhP.I/.mo.OqyWvQ/n.Uu7siDEd4gmgL45C', '+66987654321', '2025-11-05 12:13:54'),
(5, 'Robert', 'Hopkins', 'robert@email.com', '$2y$10$FCE3gEfEgV49QaDVOH/Z1OCJIwxoYYIJHnZrP4pOf/dw71Y3QfACq', '+66987654322', '2025-11-06 06:01:03'),
(6, 'Jessica', 'Wolf', 'jessica@email.com', '$2y$10$vwxVfcSFlWvsWGFjH4m4W.TaDPImtYscuqbaUAcq6O9ePIQYJTF5y', '+992987654632', '2025-11-06 06:18:36'),
(7, 'Jasper', 'Rens', 'jasper@email.com', '$2y$10$FHIhfwcdzXcjfRva77Qc/um8xzXYdGzye6i7U0IRTZWQeO0hDO/ka', '+639878766543', '2025-11-06 06:31:44'),
(8, 'KiKi', 'Cortes', 'kiki@email.com', '$2y$10$qhtEe57/Tbl85Dj9yw7D9eLsbQwQPINDXT8sRNvTJpov80gPfJ.R6', '+66989878765', '2025-11-06 06:39:16'),
(13, 'Mimi', 'Cortes', 'mimi@email.com', '$2y$10$AegyWJLoJKvHk09bah4kFOytLJwbzfGVgDRmGahF5pCYcJuby8RTC', '+66876546321', '2025-11-06 06:53:37');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('Unpaid','Paid','Refunded') NOT NULL DEFAULT 'Unpaid',
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `res_id`, `guest_id`, `issue_date`, `due_date`, `total_amount`, `payment_status`, `payment_method`) VALUES
(1, 35, 8, '2025-11-08', '2025-11-08', 2500.00, 'Unpaid', 'Cash'),
(2, 36, 8, '2025-11-08', '2025-11-18', 2500.00, 'Paid', 'Credit Card'),
(3, 37, 8, '2025-11-08', '2025-11-11', 4500.00, 'Unpaid', 'Cash'),
(4, 38, 8, '2025-11-08', '2025-11-12', 7500.00, 'Refunded', 'Cash'),
(5, 39, 8, '2025-11-08', '2025-11-18', 13500.00, 'Paid', 'Cash'),
(6, 40, 8, '2025-11-08', '2025-11-12', 5000.00, 'Refunded', 'Cash'),
(7, 41, 5, '2025-11-09', '2025-11-14', 2500.00, 'Refunded', 'Cash'),
(8, 42, 7, '2025-11-15', '2025-11-18', 3000.00, 'Paid', 'Credit Card'),
(9, 43, 13, '2025-11-18', '2025-11-18', 3000.00, 'Unpaid', 'Credit Card');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `res_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_no` varchar(10) DEFAULT NULL,
  `checkin_date` datetime NOT NULL,
  `checkout_date` datetime NOT NULL,
  `num_adults` tinyint(4) NOT NULL,
  `num_children` tinyint(4) NOT NULL DEFAULT '0',
  `booking_date` datetime NOT NULL,
  `type_id` int(11) NOT NULL,
  `total_nights` smallint(6) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Checked-In','Checked-Out','Cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`res_id`, `guest_id`, `room_no`, `checkin_date`, `checkout_date`, `num_adults`, `num_children`, `booking_date`, `type_id`, `total_nights`, `total_cost`, `status`) VALUES
(1, 1, NULL, '2025-09-01 00:00:00', '2025-09-04 00:00:00', 2, 0, '2025-08-15 10:00:00', 2, 3, 7500.00, 'Cancelled'),
(2, 2, NULL, '2025-10-14 00:00:00', '2025-10-16 00:00:00', 1, 0, '2025-10-10 12:30:00', 1, 2, 3000.00, 'Cancelled'),
(3, 1, NULL, '2025-11-20 00:00:00', '2025-11-25 00:00:00', 3, 0, '2025-10-01 09:00:00', 3, 5, 22500.00, 'Confirmed'),
(4, 4, '301', '2024-12-22 00:00:00', '2024-12-27 00:00:00', 2, 2, '2024-10-15 14:30:00', 3, 5, 22500.00, 'Checked-Out'),
(5, 2, '101', '2025-02-10 00:00:00', '2025-02-12 00:00:00', 1, 0, '2025-02-01 09:10:00', 1, 2, 3000.00, 'Checked-Out'),
(6, 5, '201', '2025-07-15 00:00:00', '2025-07-20 00:00:00', 2, 1, '2025-05-20 20:00:00', 2, 5, 12500.00, 'Checked-Out'),
(7, 1, '102', '2025-11-01 00:00:00', '2025-11-04 00:00:00', 2, 0, '2025-10-28 11:00:00', 1, 3, 4500.00, 'Checked-Out'),
(8, 2, '202', '2025-11-03 00:00:00', '2025-11-07 00:00:00', 1, 0, '2025-10-15 08:00:00', 2, 4, 10000.00, 'Checked-Out'),
(9, 3, '302', '2025-11-05 00:00:00', '2025-11-10 00:00:00', 2, 2, '2025-11-01 17:45:00', 3, 5, 22500.00, 'Checked-Out'),
(10, 6, NULL, '2025-11-20 00:00:00', '2025-11-22 00:00:00', 2, 0, '2025-11-02 10:15:00', 1, 2, 3000.00, 'Confirmed'),
(11, 2, NULL, '2025-12-30 00:00:00', '2026-01-02 00:00:00', 4, 0, '2025-10-30 13:00:00', 3, 3, 13500.00, 'Confirmed'),
(12, 4, NULL, '2025-09-15 00:00:00', '2025-09-17 00:00:00', 2, 0, '2025-09-01 10:00:00', 2, 2, 5000.00, 'Cancelled'),
(13, 1, NULL, '2026-01-10 00:00:00', '2026-01-17 00:00:00', 2, 1, '2025-11-04 16:30:00', 2, 7, 17500.00, 'Confirmed'),
(14, 5, '103', '2025-11-01 00:00:00', '2025-11-03 00:00:00', 2, 1, '2025-10-20 09:00:00', 1, 2, 3000.00, 'Checked-Out'),
(15, 2, '203', '2025-11-02 00:00:00', '2025-11-04 00:00:00', 1, 0, '2025-10-22 10:00:00', 2, 2, 5000.00, 'Checked-Out'),
(16, 6, '303', '2025-11-01 00:00:00', '2025-11-05 00:00:00', 3, 0, '2025-10-25 11:00:00', 3, 4, 18000.00, 'Checked-Out'),
(17, 1, '101', '2025-11-03 00:00:00', '2025-11-05 00:00:00', 2, 0, '2025-10-30 12:00:00', 1, 2, 3000.00, 'Checked-Out'),
(18, 4, '102', '2025-11-04 00:00:00', '2025-11-05 00:00:00', 1, 0, '2025-11-03 13:00:00', 1, 1, 1500.00, 'Checked-Out'),
(19, 3, '201', '2025-11-04 00:00:00', '2025-11-06 00:00:00', 2, 1, '2025-10-29 14:00:00', 2, 2, 5000.00, 'Checked-Out'),
(20, 5, '301', '2025-11-02 00:00:00', '2025-11-08 00:00:00', 4, 0, '2025-10-28 15:00:00', 3, 6, 27000.00, 'Checked-Out'),
(21, 5, '103', '2025-11-05 00:00:00', '2025-11-07 00:00:00', 1, 0, '2025-11-01 16:00:00', 1, 2, 3000.00, 'Checked-Out'),
(22, 3, '203', '2025-11-05 00:00:00', '2025-11-06 00:00:00', 2, 0, '2025-11-02 17:00:00', 2, 1, 2500.00, 'Checked-Out'),
(23, 6, '101', '2025-11-03 00:00:00', '2025-11-06 00:00:00', 2, 0, '2025-10-27 18:00:00', 1, 3, 4500.00, 'Checked-Out'),
(24, 6, '101', '2025-11-10 00:00:00', '2025-11-12 00:00:00', 1, 0, '2025-11-01 09:30:00', 1, 2, 3000.00, 'Checked-Out'),
(26, 1, '107', '2025-11-15 00:00:00', '2025-11-18 15:45:42', 2, 0, '2025-11-03 11:30:00', 2, 1, 2500.00, 'Checked-Out'),
(27, 2, '204', '2025-11-18 00:00:00', '2025-11-20 00:00:00', 1, 1, '2025-11-04 12:30:00', 1, 2, 3000.00, 'Checked-Out'),
(28, 3, NULL, '2025-11-22 00:00:00', '2025-11-25 00:00:00', 2, 0, '2025-11-05 08:30:00', 2, 3, 7500.00, 'Confirmed'),
(29, 1, NULL, '2025-11-25 00:00:00', '2025-11-30 00:00:00', 2, 0, '2025-11-05 09:00:00', 3, 5, 22500.00, 'Confirmed'),
(30, 2, NULL, '2025-11-28 00:00:00', '2025-11-30 00:00:00', 2, 0, '2025-11-05 10:00:00', 1, 2, 3000.00, 'Confirmed'),
(31, 3, NULL, '2025-11-29 00:00:00', '2025-11-30 00:00:00', 1, 0, '2025-11-05 11:00:00', 1, 1, 1500.00, 'Confirmed'),
(32, 1, NULL, '2025-11-15 00:00:00', '2025-11-20 00:00:00', 2, 0, '2025-11-01 12:00:00', 3, 5, 22500.00, 'Cancelled'),
(33, 2, NULL, '2025-11-20 00:00:00', '2025-11-23 00:00:00', 2, 1, '2025-11-03 13:00:00', 2, 3, 7500.00, 'Cancelled'),
(34, 13, '201', '2025-11-06 00:00:00', '2025-11-08 00:00:00', 2, 0, '2025-11-05 14:36:33', 1, 2, 3000.00, 'Checked-Out'),
(35, 8, '106', '2025-11-08 00:00:00', '2025-11-09 00:00:00', 3, 0, '2025-11-08 20:21:06', 2, 1, 2500.00, 'Checked-Out'),
(36, 8, '105', '2025-11-08 00:00:00', '2025-11-18 15:45:38', 3, 0, '2025-11-08 20:22:54', 2, 1, 2500.00, 'Checked-Out'),
(37, 8, '109', '2025-11-11 00:00:00', '2025-11-12 00:00:00', 2, 1, '2025-11-08 20:51:08', 3, 1, 4500.00, 'Checked-Out'),
(38, 8, NULL, '2025-11-12 00:00:00', '2025-11-15 00:00:00', 1, 2, '2025-11-08 22:42:27', 2, 3, 7500.00, 'Cancelled'),
(39, 8, '209', '2025-11-11 00:00:00', '2025-11-18 15:45:40', 2, 2, '2025-11-08 23:40:11', 3, 3, 13500.00, 'Checked-Out'),
(40, 8, NULL, '2025-11-12 00:00:00', '2025-11-14 00:00:00', 2, 0, '2025-11-08 23:49:44', 2, 2, 5000.00, 'Cancelled'),
(41, 5, NULL, '2025-11-14 00:00:00', '2025-11-15 00:00:00', 3, 0, '2025-11-09 14:13:33', 2, 1, 2500.00, 'Cancelled'),
(42, 7, '101', '2025-11-15 00:00:00', '2025-11-18 15:45:45', 1, 0, '2025-11-15 20:01:47', 1, 2, 3000.00, 'Checked-Out'),
(43, 13, '204', '2025-11-18 00:00:00', '2025-11-20 00:00:00', 2, 0, '2025-11-18 14:07:10', 1, 2, 3000.00, 'Checked-Out'),
(44, 4, '102', '2025-11-18 00:00:00', '2025-11-19 00:00:00', 1, 0, '2025-11-18 14:46:50', 1, 1, 1500.00, 'Checked-In'),
(45, 4, '110', '2025-11-18 15:27:09', '2025-11-20 00:00:00', 3, 0, '2025-11-18 15:22:58', 3, 2, 9000.00, 'Checked-In'),
(46, 4, NULL, '2025-11-20 00:00:00', '2025-11-22 00:00:00', 1, 0, '2025-11-18 15:28:51', 1, 2, 3000.00, 'Confirmed');

--
-- Triggers `reservations`
--
DELIMITER $$
CREATE TRIGGER `trg_BeforeReservationInsert` BEFORE INSERT ON `reservations` FOR EACH ROW BEGIN
    DECLARE v_num_nights INT;
    
    -- 1. Automatically calculate the number of nights
    SET v_num_nights = DATEDIFF(NEW.checkout_date, NEW.checkin_date);
    
    -- 2. Set the total_nights column on the new row
    SET NEW.total_nights = v_num_nights;
    
    -- 3. Automatically calculate and set the total_cost
    SET NEW.total_cost = fn_CalculateTotalCost(NEW.type_id, v_num_nights);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `Rooms`
--

CREATE TABLE `Rooms` (
  `room_no` varchar(10) NOT NULL,
  `type_id` int(11) NOT NULL,
  `status` enum('Available','Occupied','Cleaning','Maintenance') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Rooms`
--

INSERT INTO `Rooms` (`room_no`, `type_id`, `status`) VALUES
('103', 1, 'Available'),
('201', 1, 'Available'),
('202', 1, 'Available'),
('301', 1, 'Available'),
('302', 1, 'Available'),
('304', 1, 'Available'),
('102', 1, 'Occupied'),
('101', 1, 'Cleaning'),
('204', 1, 'Cleaning'),
('104', 1, 'Maintenance'),
('203', 1, 'Maintenance'),
('303', 1, 'Maintenance'),
('108', 2, 'Available'),
('205', 2, 'Available'),
('206', 2, 'Available'),
('207', 2, 'Available'),
('208', 2, 'Available'),
('305', 2, 'Available'),
('306', 2, 'Available'),
('307', 2, 'Available'),
('308', 2, 'Available'),
('105', 2, 'Cleaning'),
('106', 2, 'Cleaning'),
('107', 2, 'Cleaning'),
('210', 3, 'Available'),
('309', 3, 'Available'),
('310', 3, 'Available'),
('110', 3, 'Occupied'),
('109', 3, 'Cleaning'),
('209', 3, 'Cleaning');

-- --------------------------------------------------------

--
-- Table structure for table `roomtypeimages`
--

CREATE TABLE `roomtypeimages` (
  `image_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `roomtypeimages`
--

INSERT INTO `roomtypeimages` (`image_id`, `type_id`, `image_url`) VALUES
(1, 1, '/uploads/rooms/room_690251f1e4c231.88421588.jpg'),
(11, 1, '/uploads/rooms/room_69026758d1ad88.73011051.jpg'),
(37, 3, '/uploads/rooms/room_69058fcbe00f65.68345851.jpg'),
(38, 3, '/uploads/rooms/room_69058fcbe547d9.18926461.webp'),
(39, 2, '/uploads/rooms/room_69058fd979d676.60798420.jpg'),
(40, 2, '/uploads/rooms/room_69058fd97b00e6.27030170.jpg'),
(42, 1, '/uploads/rooms/room_69103f8e4aaf86.05340762.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `roomtypes`
--

CREATE TABLE `roomtypes` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `capacity` tinyint(4) NOT NULL,
  `amenities` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `roomtypes`
--

INSERT INTO `roomtypes` (`type_id`, `type_name`, `description`, `base_price`, `capacity`, `amenities`) VALUES
(1, 'Standard', 'A comfortable 25sqm room with one queen-size bed and a city view.', 1500.00, 2, 'Wi-Fi, TV, Air Conditioning, Mini-fridge, Hairdryer'),
(2, 'Deluxe', 'A spacious 35sqm room with a plush king-size bed, work desk, and soaking tub.', 2500.00, 3, 'WiFi, TV, Mini-bar'),
(3, 'Suite', 'A 50sqm suite with two double beds and a separate living area with a sofa.', 4500.00, 4, 'All Deluxe amenities, King Bed,Kitchenette, Jacuzzi');

-- --------------------------------------------------------

--
-- Table structure for table `ten`
--

CREATE TABLE `ten` (
  `n` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `ten`
--

INSERT INTO `ten` (`n`) VALUES
(0),
(1),
(2),
(3),
(4),
(5),
(6),
(7),
(8),
(9);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_reservationdetails`
-- (See below for the actual view)
--
CREATE TABLE `view_reservationdetails` (
`res_id` int(11)
,`checkin_date` datetime
,`checkout_date` datetime
,`status` enum('Pending','Confirmed','Checked-In','Checked-Out','Cancelled')
,`room_no` varchar(10)
,`type_id` int(11)
,`guest_id` int(11)
,`num_adults` tinyint(4)
,`num_children` tinyint(4)
,`total_nights` smallint(6)
,`total_cost` decimal(10,2)
,`fname` varchar(50)
,`lname` varchar(50)
,`guest_name` varchar(101)
,`guest_email` varchar(100)
,`guest_phone` varchar(15)
,`type_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_todaycheckin`
-- (See below for the actual view)
--
CREATE TABLE `view_todaycheckin` (
`res_id` int(11)
,`guest_name` varchar(101)
,`room_no` varchar(10)
,`type_name` varchar(50)
,`checkin_date` datetime
,`checkout_date` datetime
,`status` enum('Pending','Confirmed','Checked-In','Checked-Out','Cancelled')
);

-- --------------------------------------------------------

--
-- Structure for view `view_reservationdetails`
--
DROP TABLE IF EXISTS `view_reservationdetails`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_reservationdetails`  AS SELECT `r`.`res_id` AS `res_id`, `r`.`checkin_date` AS `checkin_date`, `r`.`checkout_date` AS `checkout_date`, `r`.`status` AS `status`, `r`.`room_no` AS `room_no`, `r`.`type_id` AS `type_id`, `r`.`guest_id` AS `guest_id`, `r`.`num_adults` AS `num_adults`, `r`.`num_children` AS `num_children`, `r`.`total_nights` AS `total_nights`, `r`.`total_cost` AS `total_cost`, `g`.`fname` AS `fname`, `g`.`lname` AS `lname`, concat(`g`.`fname`,' ',`g`.`lname`) AS `guest_name`, `g`.`email` AS `guest_email`, `g`.`phone` AS `guest_phone`, `rt`.`type_name` AS `type_name` FROM ((`reservations` `r` join `guests` `g` on((`r`.`guest_id` = `g`.`guest_id`))) join `roomtypes` `rt` on((`r`.`type_id` = `rt`.`type_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_todaycheckin`
--
DROP TABLE IF EXISTS `view_todaycheckin`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_todaycheckin`  AS SELECT `res`.`res_id` AS `res_id`, concat(`g`.`fname`,' ',`g`.`lname`) AS `guest_name`, `r`.`room_no` AS `room_no`, `rt`.`type_name` AS `type_name`, `res`.`checkin_date` AS `checkin_date`, `res`.`checkout_date` AS `checkout_date`, `res`.`status` AS `status` FROM (((`reservations` `res` join `guests` `g` on((`res`.`guest_id` = `g`.`guest_id`))) join `rooms` `r` on((`res`.`room_no` = `r`.`room_no`))) join `roomtypes` `rt` on((`r`.`type_id` = `rt`.`type_id`))) WHERE ((`res`.`status` = 'Confirmed') AND (`res`.`checkin_date` <= curdate())) ORDER BY `res`.`checkin_date` ASC, concat(`g`.`fname`,' ',`g`.`lname`) ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `Guests`
--
ALTER TABLE `Guests`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_2` (`email`),
  ADD KEY `idx_name` (`lname`,`fname`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `res_id` (`res_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `idx_covering_revenue` (`payment_status`,`issue_date`,`res_id`,`total_amount`),
  ADD KEY `idx_fast_analytics` (`issue_date`,`payment_status`,`total_amount`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`res_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_no` (`room_no`),
  ADD KEY `checkin_date` (`checkin_date`),
  ADD KEY `checkout_date` (`checkout_date`),
  ADD KEY `reservations_ibfk_3` (`type_id`),
  ADD KEY `idx_status_checkin` (`status`,`checkin_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `Rooms`
--
ALTER TABLE `Rooms`
  ADD PRIMARY KEY (`room_no`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `idx_type_status` (`type_id`,`status`);

--
-- Indexes for table `roomtypeimages`
--
ALTER TABLE `roomtypeimages`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `roomtypes`
--
ALTER TABLE `roomtypes`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `ten`
--
ALTER TABLE `ten`
  ADD PRIMARY KEY (`n`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Guests`
--
ALTER TABLE `Guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `res_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `roomtypeimages`
--
ALTER TABLE `roomtypeimages`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `roomtypes`
--
ALTER TABLE `roomtypes`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `Guests` (`guest_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_no`) REFERENCES `rooms` (`room_no`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`type_id`) REFERENCES `RoomTypes` (`type_id`);

--
-- Constraints for table `Rooms`
--
ALTER TABLE `Rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `roomtypes` (`type_id`);

--
-- Constraints for table `roomtypeimages`
--
ALTER TABLE `roomtypeimages`
  ADD CONSTRAINT `roomtypeimages_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `roomtypes` (`type_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
