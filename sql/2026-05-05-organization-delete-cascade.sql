-- @migration
-- OpenMyOrganization
-- Cascades de suppression pour les organisations et leur structure

SET NAMES utf8mb4;

DELETE hp
FROM holonproperty hp
LEFT JOIN holon h ON h.id = hp.IDholon
WHERE h.id IS NULL;

DELETE hp
FROM holonproperty hp
LEFT JOIN property p ON p.id = hp.IDproperty
WHERE p.id IS NULL;

DELETE uh
FROM user_holon uh
LEFT JOIN holon h ON h.id = uh.IDholon
WHERE h.id IS NULL;

DELETE uo
FROM user_organization uo
LEFT JOIN organization o ON o.id = uo.IDorganization
WHERE o.id IS NULL;

DELETE inv
FROM invitation inv
LEFT JOIN organization o ON o.id = inv.IDorganization
WHERE o.id IS NULL;

DELETE hist
FROM history hist
LEFT JOIN organization o ON o.id = hist.IDorganization
WHERE hist.IDorganization IS NOT NULL
  AND o.id IS NULL;

DELETE oa
FROM organization_application oa
LEFT JOIN organization o ON o.id = oa.IDorganization
WHERE o.id IS NULL;

DELETE op
FROM organization_parcours op
LEFT JOIN organization o ON o.id = op.IDorganization
WHERE o.id IS NULL;

DELETE hs
FROM holon_share_link hs
LEFT JOIN organization o ON o.id = hs.IDorganization
WHERE o.id IS NULL;

DELETE hs
FROM holon_share_link hs
LEFT JOIN holon h ON h.id = hs.IDholon
WHERE h.id IS NULL;

DELETE p
FROM property p
LEFT JOIN holon h ON h.id = p.IDholon_organization
WHERE p.IDholon_organization IS NOT NULL
  AND h.id IS NULL;

DELETE alt
FROM alttext alt
LEFT JOIN document d ON d.id = alt.IDdocument
WHERE d.id IS NULL;

DELETE m
FROM media m
LEFT JOIN document d ON d.id = m.IDdocument
WHERE m.IDdocument IS NOT NULL
  AND d.id IS NULL;

UPDATE document d
LEFT JOIN organization o ON o.id = d.IDorganization
SET d.IDorganization = NULL
WHERE d.IDorganization IS NOT NULL
  AND o.id IS NULL;

UPDATE document d
LEFT JOIN holon h ON h.id = d.IDholon
SET d.IDholon = NULL
WHERE d.IDholon IS NOT NULL
  AND h.id IS NULL;

DELETE h
FROM holon h
LEFT JOIN organization o ON o.id = h.IDorganization
WHERE h.IDorganization IS NOT NULL
  AND o.id IS NULL;

UPDATE holon h
LEFT JOIN holon root_holon ON root_holon.id = h.IDholon_org
SET h.IDholon_org = NULL
WHERE h.IDholon_org IS NOT NULL
  AND root_holon.id IS NULL;

UPDATE holon h
LEFT JOIN holon parent_holon ON parent_holon.id = h.IDholon_parent
SET h.IDholon_parent = NULL
WHERE h.IDholon_parent IS NOT NULL
  AND parent_holon.id IS NULL;

UPDATE holon h
LEFT JOIN holon template_holon ON template_holon.id = h.IDholon_template
SET h.IDholon_template = NULL
WHERE h.IDholon_template IS NOT NULL
  AND template_holon.id IS NULL;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_organization` (`IDorganization`),
  ADD KEY IF NOT EXISTS `idx_document_holon` (`IDholon`);

ALTER TABLE `holon`
  ADD KEY IF NOT EXISTS `idx_holon_organization` (`IDorganization`),
  ADD KEY IF NOT EXISTS `idx_holon_root` (`IDholon_org`),
  ADD KEY IF NOT EXISTS `idx_holon_parent` (`IDholon_parent`),
  ADD KEY IF NOT EXISTS `idx_holon_template` (`IDholon_template`);

ALTER TABLE `holonproperty`
  ADD KEY IF NOT EXISTS `idx_holonproperty_holon` (`IDholon`),
  ADD KEY IF NOT EXISTS `idx_holonproperty_property` (`IDproperty`);

ALTER TABLE `property`
  ADD KEY IF NOT EXISTS `idx_property_root_holon` (`IDholon_organization`);

ALTER TABLE `user_organization`
  ADD KEY IF NOT EXISTS `idx_user_organization_organization_user` (`IDorganization`, `IDuser`);

ALTER TABLE `user_holon`
  ADD KEY IF NOT EXISTS `idx_user_holon_holon_user` (`IDholon`, `IDuser`);

ALTER TABLE `organization_parcours`
  ADD KEY IF NOT EXISTS `idx_organization_parcours_organization` (`IDorganization`);

ALTER TABLE `media`
  ADD KEY IF NOT EXISTS `idx_media_document` (`IDdocument`);

ALTER TABLE `alttext`
  ADD KEY IF NOT EXISTS `idx_alttext_document` (`IDdocument`);

ALTER TABLE `document`
  ADD CONSTRAINT `fk_document_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `document`
  ADD CONSTRAINT `fk_document_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `holon`
  ADD CONSTRAINT `fk_holon_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `holon`
  ADD CONSTRAINT `fk_holon_root` FOREIGN KEY (`IDholon_org`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `holon`
  ADD CONSTRAINT `fk_holon_parent` FOREIGN KEY (`IDholon_parent`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `holon`
  ADD CONSTRAINT `fk_holon_template` FOREIGN KEY (`IDholon_template`) REFERENCES `holon` (`id`) ON DELETE SET NULL;

ALTER TABLE `holonproperty`
  ADD CONSTRAINT `fk_holonproperty_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `holonproperty`
  ADD CONSTRAINT `fk_holonproperty_property` FOREIGN KEY (`IDproperty`) REFERENCES `property` (`id`) ON DELETE CASCADE;

ALTER TABLE `property`
  ADD CONSTRAINT `fk_property_root_holon` FOREIGN KEY (`IDholon_organization`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_organization`
  ADD CONSTRAINT `fk_user_organization_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_holon`
  ADD CONSTRAINT `fk_user_holon_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `invitation`
  ADD CONSTRAINT `fk_invitation_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `history`
  ADD CONSTRAINT `fk_history_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `organization_application`
  ADD CONSTRAINT `fk_organization_application_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `organization_parcours`
  ADD CONSTRAINT `fk_organization_parcours_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `holon_share_link`
  ADD CONSTRAINT `fk_holon_share_link_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;

ALTER TABLE `holon_share_link`
  ADD CONSTRAINT `fk_holon_share_link_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

ALTER TABLE `media`
  ADD CONSTRAINT `fk_media_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE;

ALTER TABLE `alttext`
  ADD CONSTRAINT `fk_alttext_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE;
