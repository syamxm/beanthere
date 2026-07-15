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
  `userID` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `drinkType` varchar(50) NOT NULL,
  `roastLevel` varchar(50) NOT NULL,
  `caffeineLevel` varchar(50) NOT NULL,
  `milkType` varchar(50) NOT NULL,
  `sugarLevel` varchar(50) NOT NULL,
  `toppings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `total` decimal(8,2) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `syrups` text DEFAULT NULL,
  `itemID` int(11) NOT NULL
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
  `used` tinyint(1) DEFAULT 0,
  `grant_period` char(7) DEFAULT NULL COMMENT 'YYYY-MM for monthly grants, NULL for points redemptions'
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
  `description` varchar(255) DEFAULT NULL,
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
  `stock` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `image_path`, `price`, `old_price`, `category`, `roast_level`, `caffeine_level`, `flavour_profile`, `origin`, `drink_type`, `sugar_level`, `bestMood`, `bestWeather`, `stock`) VALUES
(1, 'Espresso', 'Double shot of our house blend. Dark, syrupy, no distractions.', 'assets/images/espresso.jpg', 7.00, NULL, 'menu', 'dark', 'high', '[\"Bold\",\"Intense\",\"Bitter\"]', 'Sumatra', 'Hot', '0%', '[\"focused\",\"productive\"]', '[\"cold\",\"rainy\"]', 50),
(2, 'Latte', 'Silky steamed milk over a double shot. Our most-ordered cup.', 'assets/images/latte.jpg', 11.00, NULL, 'menu', 'medium', 'medium', '[\"Smooth\",\"Creamy\"]', 'Colombia', 'Hot', '25%', '[\"calm\",\"focused\",\"relaxed\"]', '[\"mildly cool\",\"cloudy weather\"]', 40),
(3, 'Cappuccino', 'Equal parts espresso, steamed milk and foam, dusted with cocoa.', 'assets/images/cappuccino.jpg', 11.00, NULL, 'menu', 'dark', 'medium', '[\"Bold\",\"Foamy\",\"Rich Espresso Taste\"]', 'Colombia', 'Hot', '0%', '[\"energized\",\"sociable\"]', '[\"cool mornings\",\"breezy afternoons\"]', 40),
(5, 'Caramel Macchiato', 'Vanilla milk marked with espresso and a caramel drizzle, over ice.', 'assets/images/caramel-macchiato.jpg', 13.50, NULL, 'menu', 'medium', 'medium', '[\"Sweet\",\"Caramel\",\"Vanilla\"]', 'Colombia', 'Iced', '75%', '[\"sweet craving\",\"indulgent\",\"romantic\"]', '[\"light rain\",\"chilly evenings\"]', 35),
(6, 'Americano', 'Double espresso lengthened with hot water. Clean and bracing.', 'assets/images/americano.jpg', 9.00, NULL, 'menu', 'dark', 'high', '[\"Bold\",\"Bitter\",\"Rich Espresso Taste\"]', 'Sumatra', 'Hot', '0%', '[\"serious\",\"contemplative\",\"productive\"]', '[\"cold\",\"rainy\"]', 45),
(18, 'Colombian Supremo Beans 250g', 'Whole beans, medium roast. Caramel sweetness with a walnut finish.', 'assets/images/colombian-supremo.jpg', 32.00, NULL, 'product', NULL, NULL, NULL, 'Colombia', NULL, NULL, NULL, NULL, 12),
(19, 'Ethiopian Yirgacheffe Beans 250g', 'Whole beans, light roast. Floral, tea-like, bright citrus notes.', 'assets/images/ethopian-yirgacheffe.jpg', 38.00, NULL, 'product', NULL, NULL, NULL, 'Ethiopia', NULL, NULL, NULL, NULL, 8),
(20, 'Vietnam Robusta Beans 250g', 'Whole beans, dark roast. Heavy body and serious caffeine.', 'assets/images/vietnam-robusta.jpg', 26.00, NULL, 'product', NULL, NULL, NULL, 'Vietnam', NULL, NULL, NULL, NULL, 15),
(21, 'Matcha Latte', 'Stone-ground matcha whisked into cold milk. Our coffee-free pick.', 'assets/images/matcha-latte.jpg', 13.00, NULL, 'menu', NULL, 'low', '[\"Earthy\",\"Grassy\",\"Sweet\"]', 'Japan', 'Iced', '50%', '[\"spiritual\",\"serene\"]', '[\"spring mornings\",\"sunny\",\"cool\"]', 30),
(22, 'Hazelnut Latte', 'House latte with toasted hazelnut syrup. Dessert-adjacent, not cloying.', 'assets/images/hazelnut-latte.jpg', 12.50, NULL, 'menu', 'medium', 'medium', '[\"Nutty\",\"Sweet\",\"Creamy\"]', 'Colombia', 'Hot', '50%', '[\"calm\",\"comforted\"]', '[\"rainy\",\"mildly cool\"]', 35),
(23, 'Vanilla Cold Brew', '18-hour cold brew over ice with a shot of vanilla. Smooth, strong.', 'assets/images/vanilla-cold-brew.jpg', 13.50, NULL, 'menu', 'dark', 'high', '[\"Vanilla\",\"Bold\",\"Smooth\"]', 'Ethiopia', 'Iced', '25%', '[\"focused\",\"energized\"]', '[\"sunny\",\"cool mornings\"]', 25),
(24, 'Honey Americano', 'Americano sweetened with raw honey. Bitter meets floral.', 'assets/images/honey-americano.jpg', 10.50, NULL, 'menu', 'medium', 'medium', '[\"Honey\",\"Bitter\",\"Sweet\"]', 'Brazil', 'Hot', '25%', '[\"productive\",\"warm-hearted\"]', '[\"cold\",\"rainy\"]', 40),
(25, 'Caramel Frappe', 'Blended ice, milk and caramel topped with cream. Basically a treat.', 'assets/images/caramel-frappe.jpg', 14.00, 16.00, 'menu', 'light', 'low', '[\"Caramel\",\"Milky\",\"Chilled\"]', 'Colombia', 'Iced', '100%', '[\"happy\",\"playful\"]', '[\"hot\",\"sunny\"]', 30),
(26, 'Mocha', 'Espresso and dark chocolate under steamed milk. Rich but balanced.', 'assets/images/mocha.jpg', 12.50, NULL, 'menu', 'medium', 'high', '[\"Chocolatey\",\"Rich\",\"Smooth\"]', 'Brazil', 'Hot', '50%', '[\"calm\",\"comforted\"]', '[\"cold\",\"rainy\"]', 35),
(27, 'Classic Iced Coffee', 'Chilled house blend with milk and a touch of palm sugar.', 'assets/images/iced-coffee.jpg', 8.50, NULL, 'menu', 'medium', 'medium', '[\"Refreshing\",\"Lightly Sweet\",\"Smooth\"]', 'Brazil', 'Iced', '50%', '[\"easygoing\",\"social\"]', '[\"hot\",\"sunny\"]', 45);

UPDATE `menu_items` SET `sort_order` = `id`;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `value`) VALUES
('store_open', '1'),
('closed_message', 'We are closed at the moment — see you soon for your next cup!');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderID` int(11) NOT NULL,
  `checkoutID` char(12) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `itemID` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `drinkType` varchar(50) DEFAULT NULL,
  `roastLevel` varchar(50) DEFAULT NULL,
  `caffeineLevel` varchar(50) DEFAULT NULL,
  `milkType` varchar(50) DEFAULT NULL,
  `sugarLevel` varchar(50) DEFAULT NULL,
  `toppings` text DEFAULT NULL,
  `syrups` text DEFAULT NULL,
  `delivery` varchar(50) DEFAULT NULL,
  `total` decimal(8,2) DEFAULT NULL,
  `delivery_fee` decimal(4,2) NOT NULL DEFAULT 0.00,
  `qty` int(11) DEFAULT NULL,
  `orderStatus` varchar(50) DEFAULT 'Pending',
  `statusSource` enum('auto','manual') NOT NULL DEFAULT 'auto',
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
  `authentication_status` tinyint(1) NOT NULL DEFAULT 0,
  `lifetime_points` int(11) NOT NULL DEFAULT 0,
  `theme` varchar(30) NOT NULL DEFAULT 'dark-roast',
  `accent_color` char(7) DEFAULT NULL
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
  `status` enum('active','expired','disabled') DEFAULT 'active',
  `type` enum('monthly','reward') NOT NULL DEFAULT 'monthly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`voucherID`, `code`, `discount_value`, `created_by`, `valid_from`, `valid_until`, `status`, `type`) VALUES
(8, 'BT555', 5.00, 1, '2025-06-30 00:00:00', '2025-07-12 00:00:00', 'active', 'monthly'),
(21, 'BT450', 22.00, 1, '2025-07-18 00:00:00', '2025-07-27 00:00:00', 'active', 'monthly'),
(22, 'Mt123', 33.00, 1, '2025-07-17 00:00:00', '2025-07-31 00:00:00', 'active', 'monthly'),
(23, 'REWARD5', 5.00, NULL, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 'active', 'reward'),
(24, 'REWARD15', 15.00, NULL, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 'active', 'reward'),
(25, 'REWARD25', 25.00, NULL, '2025-01-01 00:00:00', '2030-12-31 23:59:59', 'active', 'reward');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_ledger`
--

CREATE TABLE `loyalty_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `points_cost` int(11) NOT NULL,
  `voucherID` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `points_cost`, `voucherID`) VALUES
(1, '5% off any order', 100, 23),
(2, '15% off any order', 300, 24),
(3, '25% off any order', 600, 25);

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
  ADD UNIQUE KEY `userID` (`userID`);

--
-- Indexes for table `member_vouchers`
-- grant_period is part of the unique key: a monthly voucher lands once per
-- calendar month, while points redemptions leave it NULL and NULLs never
-- collide, so the same reward can be redeemed repeatedly.
--
ALTER TABLE `member_vouchers`
  ADD PRIMARY KEY (`memberVoucherID`),
  ADD UNIQUE KEY `member_voucher_period` (`membershipID`, `voucherID`, `grant_period`),
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
  ADD KEY `userID` (`userID`),
  ADD KEY `checkoutID` (`checkoutID`);

--
-- Indexes for table `users`
--
-- phone_number is deliberately not unique: families share one number.
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`voucherID`),
  ADD UNIQUE KEY `code` (`code`),
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
  MODIFY `voucherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--
-- Deleting a menu item clears it from carts; everything else is RESTRICT so
-- order and loyalty history cannot be silently destroyed.
--

ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_item` FOREIGN KEY (`itemID`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE RESTRICT;

ALTER TABLE `membership`
  ADD CONSTRAINT `fk_membership_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE RESTRICT;

ALTER TABLE `member_vouchers`
  ADD CONSTRAINT `fk_mv_membership` FOREIGN KEY (`membershipID`) REFERENCES `membership` (`membershipID`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_mv_voucher` FOREIGN KEY (`voucherID`) REFERENCES `vouchers` (`voucherID`) ON DELETE RESTRICT;

ALTER TABLE `loyalty_ledger`
  ADD CONSTRAINT `fk_ledger_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE RESTRICT;

ALTER TABLE `rewards`
  ADD CONSTRAINT `fk_rewards_voucher` FOREIGN KEY (`voucherID`) REFERENCES `vouchers` (`voucherID`) ON DELETE RESTRICT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Seed accounts. Passwords are NOT set here: the placeholder below is not a
-- valid bcrypt hash, so these accounts cannot log in until you set a real one.
-- Generate a hash and apply it after first boot:
--
--   docker exec beanthere-app php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
--   docker exec -it beanthere-db mysql -ubeanthere -p coffeebuddydb \
--     -e "UPDATE admins SET password='PASTE_HASH' WHERE username='admin';"
--   (same for the users table)
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'SET_PASSWORD_MANUALLY');

INSERT INTO `users` (`userID`, `username`, `password`, `phone_number`, `email`, `authentication_status`) VALUES
(1, 'testuser', 'SET_PASSWORD_MANUALLY', '0123456789', 'testuser@example.com', 1);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts` (rate limiting for user + admin login)
--

CREATE TABLE `login_attempts` (
  `identifier` varchar(191) NOT NULL COMMENT 'user:<username>|<ip> or ip:<scope>|<ip>',
  `attempts` int(11) NOT NULL DEFAULT 1,
  `first_attempt_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
