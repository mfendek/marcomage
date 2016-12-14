UPDATE `forum_sections` SET `SectionOrder` = `SectionOrder` + 1 WHERE `SectionOrder` > 6 ORDER BY `SectionOrder` DESC;
INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`, `SectionOrder`) VALUES ('10', 'Decks', 'discuss shared decks', '7');
