-- Phase 3 (audit M3, M4, M7, M8, L6): money as DECIMAL, real constraints.
-- Run manually against the existing database. Take a dump first.
--
-- Step 1 checks for data that would make the constraints in step 3 fail.
-- Run it on its own: if any query returns rows, clean those rows by hand
-- before running the rest. The seeded/live database was clean when this was
-- written, so no destructive dedup is done automatically.

-- ---------------------------------------------------------------------------
-- Step 1 — pre-flight. Every one of these must return zero rows.
-- ---------------------------------------------------------------------------
SELECT 'duplicate username' AS problem, username, COUNT(*) AS n FROM users GROUP BY username HAVING n > 1;
SELECT 'duplicate email' AS problem, email, COUNT(*) AS n FROM users WHERE email IS NOT NULL AND email <> '' GROUP BY email HAVING n > 1;
SELECT 'duplicate voucher code' AS problem, code, COUNT(*) AS n FROM vouchers GROUP BY code HAVING n > 1;
SELECT 'duplicate membership' AS problem, userID, COUNT(*) AS n FROM membership GROUP BY userID HAVING n > 1;
SELECT 'orphan cart item' AS problem, c.cartID FROM cart c LEFT JOIN menu_items m ON c.itemID = m.id WHERE m.id IS NULL;
SELECT 'orphan cart user' AS problem, c.cartID FROM cart c LEFT JOIN users u ON c.userID = u.userID WHERE u.userID IS NULL;
SELECT 'orphan order' AS problem, o.orderID FROM orders o LEFT JOIN users u ON o.userID = u.userID WHERE u.userID IS NULL;
SELECT 'orphan member voucher' AS problem, mv.memberVoucherID FROM member_vouchers mv
  LEFT JOIN membership m ON mv.membershipID = m.membershipID
  LEFT JOIN vouchers v ON mv.voucherID = v.voucherID
  WHERE m.membershipID IS NULL OR v.voucherID IS NULL;
SELECT 'orphan ledger row' AS problem, l.id FROM loyalty_ledger l LEFT JOIN users u ON l.userID = u.userID WHERE u.userID IS NULL;

-- ---------------------------------------------------------------------------
-- Step 2 — money columns. float cannot hold RM values exactly; DECIMAL can.
-- ---------------------------------------------------------------------------
ALTER TABLE `cart` MODIFY `total` DECIMAL(8,2) DEFAULT NULL;
ALTER TABLE `orders` MODIFY `total` DECIMAL(8,2) DEFAULT NULL;

-- ---------------------------------------------------------------------------
-- Step 3 — uniqueness.
-- users.phone_number is deliberately NOT unique: families and couples share
-- one number, and a shared phone is not a duplicate account.
-- ---------------------------------------------------------------------------
ALTER TABLE `users`
  DROP INDEX `username`,
  ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `users`
  DROP INDEX `email`,
  ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `vouchers`
  DROP INDEX `code`,
  ADD UNIQUE KEY `code` (`code`);
ALTER TABLE `membership`
  DROP INDEX `userID`,
  ADD UNIQUE KEY `userID` (`userID`);

-- member_vouchers: a monthly voucher may be granted once per calendar month,
-- so the grant month is part of the key. Points redemptions leave grant_period
-- NULL, and NULLs never collide in a UNIQUE index — a member can redeem the
-- same reward voucher as often as they can pay for it.
ALTER TABLE `member_vouchers`
  ADD `grant_period` CHAR(7) DEFAULT NULL COMMENT 'YYYY-MM for monthly grants, NULL for points redemptions',
  ADD UNIQUE KEY `member_voucher_period` (`membershipID`, `voucherID`, `grant_period`);

-- Existing monthly rows: stamp them with the month they were assigned so the
-- cron does not re-grant them for that month.
UPDATE `member_vouchers` mv
  JOIN `vouchers` v ON mv.voucherID = v.voucherID
  SET mv.grant_period = DATE_FORMAT(mv.assigned_at, '%Y-%m')
  WHERE v.type = 'monthly' AND mv.assigned_at IS NOT NULL;

-- ---------------------------------------------------------------------------
-- Step 4 — foreign keys on the money and loyalty paths.
-- Deleting a menu item clears it from carts (nobody can check out a deleted
-- drink); everything else is RESTRICT so history cannot be silently destroyed.
-- ---------------------------------------------------------------------------
ALTER TABLE `cart`
  MODIFY `userID` INT(11) NOT NULL,
  MODIFY `itemID` INT(11) NOT NULL,
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
