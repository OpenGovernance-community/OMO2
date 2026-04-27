-- @migration
-- OpenMyOrganization
-- Migration distante:
-- 1. conversion de la base et des tables utf8mb3 vers utf8mb4
-- 2. normalisation des organisations de démonstration
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-04-20-remote-utf8mb4-and-org-normalization.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;
SET @schema_name = DATABASE();

-- Verifie la base selectionnee
SELECT @schema_name AS current_database;

-- Passe la base en utf8mb4 par defaut
SET @sql = CONCAT(
    'ALTER DATABASE `',
    @schema_name,
    '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Convertit les tables connues du schema qui etaient en utf8mb3 dans le dump
ALTER TABLE `aiprompt` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `alttext` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `document` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `faq_choice` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `holon` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `holonproperty` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `media` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `mission_faq` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `organization` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `organization_parcours` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `parameter` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `property` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `propertyformat` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pv` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `qr` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tips` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `translation` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `typeholon` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_faq_response` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_holon` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_login_token` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_organization` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_remember` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Normalise les deux organisations de démonstration.
-- Ces UPDATE sont sans danger si les valeurs sont deja correctes.
UPDATE organization
SET
    name = 'Org1',
    shortname = 'org1',
    domain = 'org1.opengov.tools'
WHERE id = 1
   OR shortname = 'orga1'
   OR shortname = 'instantz'
   OR domain = 'orga1.localhost'
   OR domain = 'instantz.org';

UPDATE organization
SET
    name = 'Org2',
    shortname = 'org2',
    domain = 'org2.opengov.tools'
WHERE id = 2
   OR shortname = 'orga2'
   OR shortname = 'trajets'
   OR domain = 'orga2.localhost'
   OR domain = 'trajets.org';

-- Verifications rapides
SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME = @schema_name;

SELECT TABLE_NAME, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @schema_name
  AND TABLE_NAME IN ('organization', 'holon', 'aiprompt', 'document', 'pv');

SELECT id, name, shortname, domain
FROM organization
ORDER BY id;
