ALTER TABLE `forum_threads` CHANGE `Section` `SectionID` INT( 10 ) NOT NULL;
ALTER TABLE `forum_posts` CHANGE `Thread` `ThreadID` INT( 10 ) NOT NULL;