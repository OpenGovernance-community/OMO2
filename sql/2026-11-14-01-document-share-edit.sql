-- @migration

ALTER TABLE document_share_link
    ADD COLUMN IF NOT EXISTS allow_edit tinyint(1) NOT NULL DEFAULT 0;
