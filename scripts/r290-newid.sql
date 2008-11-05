UPDATE `messages` SET `MessageID` = `MessageID` + 1 ORDER BY `MessageID` DESC;
ALTER TABLE `messages` CHANGE `MessageID` `MessageID` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;

UPDATE `forum_posts` SET `PostID` = `PostID` + 1 ORDER BY `PostID` DESC;
ALTER TABLE `forum_posts` CHANGE `PostID` `PostID` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;
