ALTER TABLE `replays_head`  ADD `ThreadID` INT(10) UNSIGNED NOT NULL DEFAULT '0';

UPDATE `forum_sections` SET `SectionOrder` = '9' WHERE `SectionID` = 5;
UPDATE `forum_sections` SET `SectionOrder` = '8' WHERE `SectionID` = 8;
UPDATE `forum_sections` SET `SectionOrder` = '7' WHERE `SectionID` = 4;

INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`, `SectionOrder`) VALUES ('9', 'Replays', 'discuss game replays', '6');
