-- @migration
ALTER TABLE `mission`
    MODIFY `video` varchar(1000) DEFAULT NULL;
