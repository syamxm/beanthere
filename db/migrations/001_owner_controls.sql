-- Phase 1: owner controls
-- Run manually against the existing database.

ALTER TABLE `menu_items` ADD `sort_order` INT NOT NULL DEFAULT 0;
UPDATE `menu_items` SET `sort_order` = `id`;

CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`name`, `value`) VALUES
('store_open', '1'),
('closed_message', 'We are closed at the moment — see you soon for your next cup!');
