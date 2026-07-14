-- Phase 1 (audit H2): separate monthly member vouchers from points-reward vouchers.
-- Only `monthly` vouchers are handed out by scripts/assign_voucher.php;
-- `reward` vouchers can only be obtained by spending loyalty points.
-- Run manually against the existing database.

ALTER TABLE `vouchers`
  ADD `type` ENUM('monthly','reward') NOT NULL DEFAULT 'monthly';

UPDATE `vouchers` SET `type` = 'reward'
  WHERE `code` IN ('REWARD5', 'REWARD15', 'REWARD25');
