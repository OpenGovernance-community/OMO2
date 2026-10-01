-- @migration
SET NAMES utf8mb4;

UPDATE `faq`
SET
    `question` = 'A quoi sert le switch Local / Enfants directs / Descendants ?',
    `answer` = 'Il permet d afficher le holon courant seul, avec ses enfants directs ou avec tous ses descendants.',
    `detail` = '<p>Le mode Local affiche uniquement les elements lies au holon courant.</p><p>Enfants directs ajoute les elements lies a ses enfants, tandis que Descendants couvre tout son sous-arbre.</p>',
    `updated` = NOW()
WHERE `id` = 3204
  AND `IDorganization` IS NULL
  AND (`IDholon` IS NULL OR `IDholon` = 0);
