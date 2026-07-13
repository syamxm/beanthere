-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 30, 2025 at 08:16 PM
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
-- Database: `coffeebuddydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--


-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cartID` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `drinkType` varchar(50) NOT NULL,
  `roastLevel` varchar(50) NOT NULL,
  `caffeineLevel` varchar(50) NOT NULL,
  `milkType` varchar(50) NOT NULL,
  `sugarLevel` varchar(50) NOT NULL,
  `toppings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `total` float DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `syrups` text DEFAULT NULL,
  `itemID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--


-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `membershipID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `join_date` datetime DEFAULT NULL,
  `status` enum('active','revoked','rejected','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership`
--


-- --------------------------------------------------------

--
-- Table structure for table `member_vouchers`
--

CREATE TABLE `member_vouchers` (
  `memberVoucherID` int(11) NOT NULL,
  `membershipID` int(11) NOT NULL,
  `voucherID` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_vouchers`
--


-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `price` decimal(6,2) NOT NULL,
  `old_price` decimal(6,2) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'menu',
  `roast_level` varchar(20) DEFAULT NULL,
  `caffeine_level` varchar(20) DEFAULT NULL,
  `flavour_profile` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `origin` varchar(50) DEFAULT NULL,
  `drink_type` varchar(50) DEFAULT NULL,
  `sugar_level` varchar(50) DEFAULT NULL,
  `bestMood` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `bestWeather` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `image_path`, `price`, `old_price`, `category`, `roast_level`, `caffeine_level`, `flavour_profile`, `origin`, `drink_type`, `sugar_level`, `bestMood`, `bestWeather`, `stock`) VALUES
(2, 'Latte', 'assets/images/latte.jpg', 12.00, 15.00, 'menu', 'medium', 'medium', '[\"Smooth\",\"Creamy\"]', 'Colombia', 'Iced', '100%', '[\"calm\",\"focused\",\"relaxed\"]', '[\"mildly cool\",\"cloudy weather\"]', 7),
(3, 'Cappuccino', 'assets/images/cappuccino.jpg', 12.00, 15.00, 'menu', 'dark', 'medium', '[\"Bold\",\"Rich Espresso Taste\"]', 'Colombia', 'Iced', '0%', '[\"energized\",\"sociable\"]', '[\"cool mornings\",\"breezy afternoons\"]', 0),
(5, 'Caramel Macchiato', 'assets/images/caramel-macchiato.jpg', 12.00, 15.00, 'menu', 'medium', 'medium', '[\"Sweet\",\"Caramel\",\"Vanilla\"]', 'Colombia', 'Iced', '0%', '[\"sweet craving\",\"indulgent\",\"romantic\"]', '[\"light rain\",\"chilly evenings\"]', 1),
(6, 'Americano', 'assets/images/americano.jpg', 12.00, 15.00, 'menu', 'dark', 'high', '[\"Bold\",\"Bitter\",\"Rich Espresso Taste\"]', 'Sumatra', 'Hot', '0%', '[\"serious\",\"contemplative\",\"productive\"]', '[\"cold\",\"rainy\",\"snowy\"]', 1),
(18, 'Colombian Supremo', 'assets/images/colombian-supremo.jpg', 30.00, 0.00, 'product', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(19, 'Ethopian Yirgacheffe', 'assets/images/ethopian-yirgacheffe.jpg', 30.00, 0.00, 'product', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(20, 'Vietnam Robusta', 'assets/images/vietnam-robusta.jpg', 30.00, 0.00, 'product', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(21, 'Matcha Latte', 'assets/images/matcha-latte.jpg', 12.00, 15.00, 'menu', '', 'low', '[\"Earthy\",\"Grassy\",\"Sweet\"]', '', 'Iced', '0%', '[\"spiritual\",\"serene\"]', '[\"spring mornings\",\"sunny\",\"cool\"]', 2),
(22, 'Hazelnut Latte', 'assets/images/hazelnut-latte.jpg', 13.00, 15.00, 'menu', 'medium', 'medium', '[\"Nutty\",\"Sweet\",\"Creamy\"]', 'Colombia', 'Iced', '50%', '[\"calm\",\"comforted\"]', '[\"rainy\",\"mildly cool\"]', 10),
(23, 'Vanilla Cold Brew', 'assets/images/vanilla-cold-brew.jpg', 14.00, 16.00, 'menu', 'dark', 'high', '[\"Vanilla\",\"Bold\",\"Smooth\"]', 'Ethiopia', 'Iced', '0%', '[\"focused\",\"energized\"]', '[\"sunny\",\"cool mornings\"]', 8),
(24, 'Honey Americano', 'assets/images/honey-americano.jpg', 12.00, 13.50, 'menu', 'medium', 'medium', '[\"Honey\",\"Bitter\",\"Sweet\"]', 'Brazil', 'Hot', '25%', '[\"productive\",\"warm-hearted\"]', '[\"cold\",\"rainy\"]', 5),
(25, 'Caramel Frappe', 'assets/images/caramel-frappe.jpg', 15.00, 17.00, 'menu', 'light', 'low', '[\"Caramel\",\"Milky\",\"Chilled\"]', 'Colombia', 'Iced', '75%', '[\"happy\",\"playful\"]', '[\"hot\",\"sunny\"]', 7),
(26, 'Mocha', 'assets/images/mocha.jpg', 19.99, 12.00, 'menu', 'Medium', 'High', '[\"Smooth\",\"Creamy\"]', 'Brazil', NULL, NULL, '[\"calm\",\"focused\",\"relaxed\"]', '[\"cold\",\"rainy\",\"snowy\"]', 10);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `drinkType` varchar(50) DEFAULT NULL,
  `roastLevel` varchar(50) DEFAULT NULL,
  `caffeineLevel` varchar(50) DEFAULT NULL,
  `milkType` varchar(50) DEFAULT NULL,
  `sugarLevel` varchar(50) DEFAULT NULL,
  `toppings` text DEFAULT NULL,
  `syrups` text DEFAULT NULL,
  `delivery` varchar(50) DEFAULT NULL,
  `total` float DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `orderStatus` varchar(50) DEFAULT 'Pending',
  `orderTime` datetime DEFAULT NULL,
  `lastStatusUpdate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reg_date` datetime NOT NULL DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `authentication_status` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--


-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `voucherID` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `discount_value` decimal(5,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `status` enum('active','expired','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`voucherID`, `code`, `discount_value`, `created_by`, `valid_from`, `valid_until`, `status`) VALUES
(8, 'BT555', 5.00, 1, '2025-06-30 00:00:00', '2025-07-12 00:00:00', 'active'),
(21, 'BT450', 22.00, 1, '2025-07-18 00:00:00', '2025-07-27 00:00:00', 'active'),
(22, 'Mt123', 33.00, 1, '2025-07-17 00:00:00', '2025-07-31 00:00:00', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cartID`),
  ADD KEY `itemID` (`itemID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`membershipID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `member_vouchers`
--
ALTER TABLE `member_vouchers`
  ADD PRIMARY KEY (`memberVoucherID`),
  ADD KEY `membershipID` (`membershipID`),
  ADD KEY `voucherID` (`voucherID`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD KEY `username` (`username`),
  ADD KEY `phone_number` (`phone_number`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`voucherID`),
  ADD KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `membership`
--
ALTER TABLE `membership`
  MODIFY `membershipID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `member_vouchers`
--
ALTER TABLE `member_vouchers`
  MODIFY `memberVoucherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `voucherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Seed accounts (dev defaults: admin/admin123, testuser/test123)
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$oqDWJi/8tTTmrK.402wVPuc.qEr8oDN6mETAbfOgeFkEH9qiUqrKS');

INSERT INTO `users` (`userID`, `username`, `password`, `phone_number`, `email`, `authentication_status`) VALUES
(1, 'testuser', '$2y$10$fGs7RXiOXszeJei646FZE.viU3trjVQL9GK6gyW2zPuUcMpkEMq.a', '0123456789', 'testuser@example.com', 1);
