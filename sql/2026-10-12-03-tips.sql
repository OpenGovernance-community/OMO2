-- @migration
CREATE TABLE IF NOT EXISTS `tip` (
    `id` int NOT NULL AUTO_INCREMENT,
    `text` varchar(500) NOT NULL,
    `link` varchar(1000) NOT NULL,
    `image` varchar(1000) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `tip_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tip` (`text`, `link`, `active`)
SELECT seed.text, seed.link, 1 FROM (
    SELECT 'Un doute sur une fonction ? La FAQ est accessible depuis le menu d aide en haut de la page.' AS text, '#|faq' AS link
    UNION ALL SELECT 'Envie de decouvrir le logiciel pas a pas ? Retrouvez les tutoriels dans le menu d aide.', '#|tutorials'
    UNION ALL SELECT 'Votre profil et vos preferences sont regroupes dans les parametres, en bas de la barre de gauche.', '#parameters'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `tip`);
