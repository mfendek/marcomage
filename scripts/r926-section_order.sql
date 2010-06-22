ALTER TABLE `forum_sections`  ADD `SectionOrder` INT(2) UNSIGNED NOT NULL;

UPDATE `forum_sections` SET `SectionOrder` = '1' WHERE `forum_sections`.`SectionID` = 1;
UPDATE `forum_sections` SET `SectionOrder` = '2' WHERE `forum_sections`.`SectionID` = 2;
UPDATE `forum_sections` SET `SectionOrder` = '3' WHERE `forum_sections`.`SectionID` = 3;
UPDATE `forum_sections` SET `SectionOrder` = '6' WHERE `forum_sections`.`SectionID` = 4;
UPDATE `forum_sections` SET `SectionOrder` = '8' WHERE `forum_sections`.`SectionID` = 5;
UPDATE `forum_sections` SET `SectionOrder` = '5' WHERE `forum_sections`.`SectionID` = 6;
UPDATE `forum_sections` SET `SectionOrder` = '4' WHERE `forum_sections`.`SectionID` = 7;
UPDATE `forum_sections` SET `SectionOrder` = '7' WHERE `forum_sections`.`SectionID` = 8;

ALTER TABLE `forum_sections` ADD UNIQUE(`SectionOrder`);
