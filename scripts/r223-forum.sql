ALTER TABLE `logins` ADD `UserType` char( 10 ) collate utf8_unicode_ci NOT NULL default 'user' AFTER `SessionID`;


CREATE TABLE `forum_posts` (
  `PostID` int(10) unsigned NOT NULL,
  `Author` char(20) collate utf8_unicode_ci NOT NULL,
  `Content` text collate utf8_unicode_ci NOT NULL,
  `Thread` int(10) NOT NULL,
  `Deleted` char(3) collate utf8_unicode_ci NOT NULL default 'no',
  `Created` datetime NOT NULL,
  PRIMARY KEY  (`PostID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `forum_threads` (
  `ThreadID` int(10) unsigned NOT NULL,
  `Title` varchar(50) collate utf8_unicode_ci NOT NULL,
  `Author` char(20) collate utf8_unicode_ci NOT NULL,
  `Priority` char(15) collate utf8_unicode_ci NOT NULL default 'normal',
  `Locked` char(3) collate utf8_unicode_ci NOT NULL default 'no',
  `Deleted` char(3) collate utf8_unicode_ci NOT NULL default 'no',
  `Section` int(10) NOT NULL,
  `Created` datetime NOT NULL,
  PRIMARY KEY  (`ThreadID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `forum_sections` (
  `SectionID` int(10) unsigned NOT NULL,
  `SectionName` varchar(50) collate utf8_unicode_ci NOT NULL,
  `Description` varchar(80) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`SectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`) VALUES 
(1, 'General', 'main discussion about MArcomage'),
(2, 'Development', 'suggest and discuss new features that could be added'),
(3, 'Support', 'report bugs, exploits and technical difficulties'),
(4, 'Contests', 'help MArcomage to become a better site'),
(5, 'Novels', 'discuss our fantasy novels section');
