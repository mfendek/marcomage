INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`) VALUES ('7', 'Balance changes', 'balance existing cards');
ALTER TABLE `forum_threads`  ADD `CardID` INT(10) UNSIGNED NOT NULL DEFAULT '0';
