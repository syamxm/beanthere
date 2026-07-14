-- Phase 2 (audit M1): login_attempts.identifier is now a composite key.
--   "user:<lowercased username>|<ip>"  — locks one account from one IP
--   "ip:<scope>|<ip>"                  — locks one IP across all usernames
--     (scopes: login, register, chat)
-- No data migration needed: old username-only rows simply stop matching and
-- age out of the window. Clear them so nobody stays locked under the old key.
-- Run manually against the existing database.

ALTER TABLE `login_attempts`
  MODIFY `identifier` VARCHAR(191) NOT NULL
  COMMENT 'user:<username>|<ip> or ip:<scope>|<ip>';

DELETE FROM `login_attempts` WHERE `identifier` NOT LIKE '%|%';
