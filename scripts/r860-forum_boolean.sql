UPDATE `forum_posts` SET `Deleted` = 0 WHERE `Deleted` = "no";
UPDATE `forum_posts` SET `Deleted` = 1 WHERE `Deleted` = "yes";

ALTER TABLE `forum_posts` CHANGE `Deleted` `Deleted` BOOL NOT NULL DEFAULT '0';

UPDATE `forum_threads` SET `Deleted` = 0 WHERE `Deleted` = "no";
UPDATE `forum_threads` SET `Deleted` = 1 WHERE `Deleted` = "yes";
UPDATE `forum_threads` SET `Locked` = 0 WHERE `Locked` = "no";
UPDATE `forum_threads` SET `Locked` = 1 WHERE `Locked` = "yes";

ALTER TABLE `forum_threads` CHANGE `Deleted` `Deleted` BOOL NOT NULL DEFAULT '0';
ALTER TABLE `forum_threads` CHANGE `Locked` `Locked` BOOL NOT NULL DEFAULT '0';
