-- Phase 5 (R3 + R4 receipts): mock payment gateway.
-- Orders now start life as 'Awaiting Payment' and only become 'Order Received'
-- once the gateway callback confirms success. These columns carry what the
-- callback and the receipt need. orderStatus is a varchar, so the two new
-- status values ('Awaiting Payment', 'Payment Failed') need no schema change.
-- Run manually against the existing database.

ALTER TABLE `orders`
  ADD `member_voucher_id` INT(11) DEFAULT NULL COMMENT 'voucher reserved for this checkout, released if payment fails',
  ADD `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  ADD `payment_method` VARCHAR(30) DEFAULT NULL,
  ADD `card_last4` CHAR(4) DEFAULT NULL,
  ADD `paid_at` DATETIME DEFAULT NULL;
