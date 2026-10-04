-- @migration
ALTER TABLE `decision_proposal`
    ADD COLUMN `start_at` DATETIME NULL,
    ADD COLUMN `end_at` DATETIME NULL,
    ADD COLUMN `timezone` VARCHAR(64) NULL;

ALTER TABLE `event`
    ADD COLUMN `IDdecision_proposal` INT NULL,
    ADD UNIQUE KEY `uq_event_decision_proposal` (`IDdecision_proposal`),
    ADD CONSTRAINT `fk_event_decision_proposal` FOREIGN KEY (`IDdecision_proposal`)
        REFERENCES `decision_proposal` (`id`) ON DELETE SET NULL;
