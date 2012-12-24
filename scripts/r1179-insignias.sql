ALTER TABLE `settings`  ADD `Insignias` BOOL NOT NULL DEFAULT '1' AFTER `Images`;
UPDATE `settings` SET `Insignias` = 0;
