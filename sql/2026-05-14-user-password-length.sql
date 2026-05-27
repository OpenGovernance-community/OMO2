-- @migration
ALTER TABLE `user`
    MODIFY COLUMN `password` varchar(80) DEFAULT NULL;
