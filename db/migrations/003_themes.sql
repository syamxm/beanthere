-- Phase 5: color themes
-- Run manually against the existing database.

ALTER TABLE `users`
  ADD `theme` VARCHAR(30) NOT NULL DEFAULT 'dark-roast',
  ADD `accent_color` CHAR(7) DEFAULT NULL;
