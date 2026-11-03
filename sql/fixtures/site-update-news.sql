-- Connection-local tables: test publication without notifying real accounts.
CREATE TEMPORARY TABLE `site_update_notification_fixture` LIKE `notification`;
CREATE TEMPORARY TABLE `notification` LIKE `site_update_notification_fixture`;
CREATE TEMPORARY TABLE `site_update_user_fixture` LIKE `user`;
CREATE TEMPORARY TABLE `user` LIKE `site_update_user_fixture`;
