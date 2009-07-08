-- ------------------------- --
-- MArcomage database tables --
-- ------------------------- --

-- 
-- Database: `arcomage`
-- 

CREATE DATABASE IF NOT EXISTS `arcomage` CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `arcomage`;

-- --------------------------------------------------------

--
-- Table structure for table `concepts`
--

CREATE TABLE `concepts` (
  `CardID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `Class` char(8) COLLATE utf8_unicode_ci NOT NULL,
  `Bricks` int(3) unsigned NOT NULL DEFAULT '0',
  `Gems` int(3) unsigned NOT NULL DEFAULT '0',
  `Recruits` int(3) unsigned NOT NULL DEFAULT '0',
  `Effect` text COLLATE utf8_unicode_ci NOT NULL,
  `Keywords` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Picture` char(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blank.jpg',
  `Note` text COLLATE utf8_unicode_ci NOT NULL,
  `State` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
  `Owner` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `LastChange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CardID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `decks`
-- 

CREATE TABLE `decks` (
  `Username` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Deckname` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Ready` binary(1) NOT NULL,
  `Data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`Username`,`Deckname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `games`
-- 

CREATE TABLE `games` (
  `GameID` int(10) unsigned NOT NULL,
  `Player1` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Player2` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `State` char(16) COLLATE utf8_unicode_ci NOT NULL,
  `Data` text COLLATE utf8_unicode_ci NOT NULL,
  `Note1` text COLLATE utf8_unicode_ci NOT NULL,
  `Note2` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`Player1`,`Player2`),
  UNIQUE KEY `GameID` (`GameID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chats`
-- 

CREATE TABLE `chats` (
  `GameID` int(10) unsigned NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Name` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Message` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`GameID`,`Timestamp`,`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `logins`
-- 

CREATE TABLE `logins` (
  `Username` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Password` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `SessionID` int(10) unsigned NOT NULL DEFAULT '0',
  `UserType` char(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `Last IP` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `Last Query` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `PreviousLogin` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `novels`
-- 

CREATE TABLE `novels` (
  `Novelname` char(30) COLLATE utf8_unicode_ci NOT NULL,
  `Chapter` char(30) COLLATE utf8_unicode_ci NOT NULL,
  `Page` int(10) unsigned NOT NULL,
  `Content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`Novelname`,`Chapter`,`Page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `scores`
-- 

CREATE TABLE `scores` (
  `Username` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Wins` int(10) unsigned NOT NULL DEFAULT '0',
  `Losses` int(10) unsigned NOT NULL DEFAULT '0',
  `Draws` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `settings`
-- 

CREATE TABLE `settings` (
  `Username` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Firstname` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Surname` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Birthdate` date NOT NULL DEFAULT '0000-00-00',
  `Gender` char(10) COLLATE utf8_unicode_ci NOT NULL,
  `Email` char(30) COLLATE utf8_unicode_ci NOT NULL,
  `Imnumber` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Country` char(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
  `Hobby` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `Avatar` char(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noavatar.jpg',
  `Timezone` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `Minimize` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Cardtext` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `Images` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `Keywords` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `Nationality` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Chatorder` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Avatargame` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `Avatarlist` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `Correction` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `OldCardLook` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Reports` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Forum_notification` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `Concepts_notification` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY  (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `messages`
-- 

CREATE TABLE `messages` (
  `MessageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Author` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Recipient` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Subject` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `Content` text COLLATE utf8_unicode_ci NOT NULL,
  `AuthorDelete` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `RecipientDelete` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Unread` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `GameID` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`MessageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_posts`
-- 

CREATE TABLE `forum_posts` (
  `PostID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Author` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Content` text COLLATE utf8_unicode_ci NOT NULL,
  `ThreadID` int(10) NOT NULL,
  `Deleted` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`PostID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_threads`
-- 

CREATE TABLE `forum_threads` (
  `ThreadID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Author` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Priority` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `Locked` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Deleted` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `SectionID` int(10) NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ThreadID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_sections`
-- 

CREATE TABLE `forum_sections` (
  `SectionID` int(10) unsigned NOT NULL,
  `SectionName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`SectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 
-- Dumping data for table `forum_sections`
-- 

INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`) VALUES 
(1, 'General', 'main discussion about MArcomage'),
(2, 'Development', 'suggest and discuss new features that could be added'),
(3, 'Support', 'report bugs, exploits and technical difficulties'),
(4, 'Contests', 'help MArcomage to become a better site'),
(5, 'Novels', 'discuss our fantasy novels section');
