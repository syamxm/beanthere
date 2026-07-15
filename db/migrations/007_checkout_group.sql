-- Phase 4 (R2 + R4): group order rows into one checkout, store the delivery fee
-- as its own column, and let the barista own a group's status.
-- Run manually against the existing database. Take a dump first.

ALTER TABLE `orders`
  ADD `checkoutID` CHAR(12) DEFAULT NULL AFTER `orderID`,
  ADD `itemID` INT(11) DEFAULT NULL AFTER `userID`,
  ADD `delivery_fee` DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER `total`,
  ADD `statusSource` ENUM('auto','manual') NOT NULL DEFAULT 'auto' AFTER `orderStatus`,
  ADD KEY `checkoutID` (`checkoutID`);

-- Backfill: rows placed by the same user at the same second were one checkout.
UPDATE `orders`
  SET `checkoutID` = LPAD(HEX(CRC32(CONCAT(userID, '-', UNIX_TIMESTAMP(orderTime)))), 12, '0')
  WHERE `checkoutID` IS NULL;

-- Backfill itemID by name so a cancelled historical order can restock.
-- Menu names are effectively unique; any unmatched row simply keeps itemID NULL
-- and is skipped by the restock step.
UPDATE `orders` o
  JOIN `menu_items` m ON o.name = m.name
  SET o.itemID = m.id
  WHERE o.itemID IS NULL;
