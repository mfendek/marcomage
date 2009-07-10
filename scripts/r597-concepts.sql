INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`) VALUES ('6', 'Concepts', 'discuss card suggestions');
ALTER TABLE `concepts`  ADD `ThreadID` INT(10) UNSIGNED NOT NULL DEFAULT '0';
