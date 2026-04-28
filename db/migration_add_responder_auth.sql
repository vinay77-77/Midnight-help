-- Run once on an existing midnight_help database (MySQL 8+).
-- Adds volunteer login fields to responders.

USE midnight_help;

ALTER TABLE responders
  ADD COLUMN email VARCHAR(254) NULL AFTER name,
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER email,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER verified;

-- Backfill placeholder rows (if any) before enforcing NOT NULL / UNIQUE.
UPDATE responders SET email = CONCAT('volunteer+', responder_id, '@example.invalid')
  WHERE email IS NULL OR email = '';

UPDATE responders SET password_hash = '$2y$10$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
  WHERE password_hash IS NULL OR password_hash = '';

ALTER TABLE responders
  MODIFY email VARCHAR(254) NOT NULL,
  MODIFY password_hash VARCHAR(255) NOT NULL,
  ADD UNIQUE KEY uq_responders_email (email);
