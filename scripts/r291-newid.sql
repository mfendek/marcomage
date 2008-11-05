UPDATE `forum_threads` SET `ThreadID` = `ThreadID` + 1 ORDER BY `ThreadID` DESC;
ALTER TABLE `forum_threads` CHANGE `ThreadID` `ThreadID` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;
UPDATE `forum_posts` SET `Thread` = `Thread` + 1;
