-- @migration
-- LMS homework admin-only flag

SET NAMES utf8mb4;

ALTER TABLE `homework`
    ADD COLUMN `onlyAdmin` tinyint(1) NOT NULL DEFAULT 0 AFTER `detail`;
