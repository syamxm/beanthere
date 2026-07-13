-- Phase 2: loyalty & rewards
-- Run manually against the existing database.

CREATE TABLE `loyalty_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `users` ADD `lifetime_points` INT NOT NULL DEFAULT 0;

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `points_cost` int(11) NOT NULL,
  `voucherID` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `vouchers` (`code`, `discount_value`, `valid_from`, `valid_until`, `status`) VALUES
('REWARD5', 5.00, NOW(), '2030-12-31 23:59:59', 'active'),
('REWARD15', 15.00, NOW(), '2030-12-31 23:59:59', 'active'),
('REWARD25', 25.00, NOW(), '2030-12-31 23:59:59', 'active');

INSERT INTO `rewards` (`name`, `points_cost`, `voucherID`)
SELECT '5% off any order', 100, `voucherID` FROM `vouchers` WHERE `code` = 'REWARD5';
INSERT INTO `rewards` (`name`, `points_cost`, `voucherID`)
SELECT '15% off any order', 300, `voucherID` FROM `vouchers` WHERE `code` = 'REWARD15';
INSERT INTO `rewards` (`name`, `points_cost`, `voucherID`)
SELECT '25% off any order', 600, `voucherID` FROM `vouchers` WHERE `code` = 'REWARD25';
