-- ------------------------- --
-- MArcomage database tables --
-- ------------------------- --

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 
-- Database: `arcomage`
-- 

CREATE DATABASE IF NOT EXISTS `arcomage` CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `arcomage`;

-- --------------------------------------------------------

--
-- Table structure for table `chats`
-- 

CREATE TABLE `chats` (
  `GameID` int(10) unsigned NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Message` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`GameID`,`Timestamp`,`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `concepts`
--

CREATE TABLE `concepts` (
  `CardID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `Rarity` enum('Common','Uncommon','Rare') COLLATE utf8_unicode_ci NOT NULL,
  `Bricks` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `Gems` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `Recruits` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `Effect` text COLLATE utf8_unicode_ci NOT NULL,
  `Keywords` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Picture` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blank.jpg',
  `Note` text COLLATE utf8_unicode_ci NOT NULL,
  `State` enum('waiting','rejected','interesting','implemented') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
  `Author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `LastChange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ThreadID` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`CardID`),
  KEY `LastChange` (`LastChange`),
  KEY `Author` (`Author`),
  KEY `State` (`State`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `decks`
-- 

CREATE TABLE `decks` (
  `DeckID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Deckname` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Ready` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Data` text COLLATE utf8_unicode_ci NOT NULL,
  `Note` text COLLATE utf8_unicode_ci NOT NULL,
  `Wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Draws` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Shared` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DeckID`),
  KEY `Username` (`Username`),
  KEY `Shared` (`Shared`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_posts`
-- 

CREATE TABLE `forum_posts` (
  `PostID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Content` text COLLATE utf8_unicode_ci NOT NULL,
  `ThreadID` int(10) NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`PostID`),
  KEY `ThreadID` (`ThreadID`),
  KEY `Created` (`Created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_sections`
-- 

CREATE TABLE `forum_sections` (
  `SectionID` tinyint(3) unsigned NOT NULL,
  `SectionName` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `SectionOrder` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`SectionID`),
  UNIQUE KEY `SectionOrder` (`SectionOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 
-- Dumping data for table `forum_sections`
-- 

INSERT INTO `forum_sections` (`SectionID`, `SectionName`, `Description`, `SectionOrder`) VALUES
(1, 'General', 'main discussion about MArcomage', 1),
(2, 'Development', 'suggest and discuss new features that could be added', 2),
(3, 'Support', 'report bugs, exploits and technical difficulties', 3),
(4, 'Contests', 'help MArcomage to become a better site', 8),
(5, 'Novels', 'discuss our fantasy novels section', 10),
(6, 'Concepts', 'discuss card suggestions', 5),
(7, 'Cards', 'discuss existing cards', 4),
(8, 'Off topic', 'everything else', 9),
(9, 'Replays', 'discuss game replays', 6),
(10, 'Decks', 'discuss shared decks', 7);

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_threads`
-- 

CREATE TABLE `forum_threads` (
  `ThreadID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Priority` enum('normal','important','sticky') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `Locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `SectionID` tinyint(3) unsigned NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `PostCount` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `LastAuthor` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastPost` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `CardID` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ThreadID`),
  KEY `SectionID` (`SectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `games`
-- 

CREATE TABLE `games` (
  `GameID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Player1` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Player2` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `State` enum('waiting','in progress','finished','P1 over','P2 over') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
  `Current` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Round` smallint(5) unsigned NOT NULL DEFAULT '1',
  `Winner` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Surrender` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EndType` enum('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
  `Last Action` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ChatNotification1` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `ChatNotification2` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `Data` text COLLATE utf8_unicode_ci NOT NULL,
  `DeckID1` int(10) unsigned NOT NULL DEFAULT '0',
  `DeckID2` int(10) unsigned NOT NULL DEFAULT '0',
  `Note1` text COLLATE utf8_unicode_ci NOT NULL,
  `Note2` text COLLATE utf8_unicode_ci NOT NULL,
  `GameModes` set('HiddenCards','FriendlyPlay','LongMode','AIMode') COLLATE utf8_unicode_ci NOT NULL,
  `Timeout` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `AI` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`GameID`),
  KEY `Player1` (`Player1`),
  KEY `Player2` (`Player2`),
  KEY `Current` (`Current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `logins`
-- 

CREATE TABLE `logins` (
  `Username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Password` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `SessionID` int(10) unsigned NOT NULL DEFAULT '0',
  `UserType` enum('user','moderator','supervisor','admin','squashed','limited','banned') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `Registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Last IP` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `Last Query` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `Notification` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  PRIMARY KEY (`Username`),
  KEY `Last Query` (`Last Query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `messages`
-- 

CREATE TABLE `messages` (
  `MessageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Recipient` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Subject` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `Content` text COLLATE utf8_unicode_ci NOT NULL,
  `AuthorDelete` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `RecipientDelete` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Unread` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `GameID` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MessageID`),
  KEY `Recipient` (`Recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `replays`
--

CREATE TABLE `replays` (
  `GameID` int(10) unsigned NOT NULL,
  `Player1` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Player2` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Rounds` smallint(5) unsigned NOT NULL DEFAULT '1',
  `Turns` smallint(5) unsigned NOT NULL DEFAULT '1',
  `Winner` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EndType` enum('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') COLLATE utf8_unicode_ci NOT NULL,
  `GameModes` set('HiddenCards','FriendlyPlay','LongMode','AIMode') COLLATE utf8_unicode_ci NOT NULL,
  `AI` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Started` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Finished` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Views` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ThreadID` int(10) unsigned NOT NULL DEFAULT '0',
  `Data` blob,
  PRIMARY KEY (`GameID`),
  KEY `Player1` (`Player1`),
  KEY `Player2` (`Player2`),
  KEY `EndType` (`EndType`),
  KEY `GameModes` (`GameModes`),
  KEY `Finished` (`Finished`),
  KEY `AI` (`AI`),
  KEY `ThreadID` (`ThreadID`),
  KEY `Winner` (`Winner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `scores`
-- 

CREATE TABLE `scores` (
  `Username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Level` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Exp` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Gold` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Wins` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Losses` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Draws` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `GameSlots` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Assassin` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Builder` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Carpenter` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Collector` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Desolator` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Dragon` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Gentle_touch` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Saboteur` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Snob` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Survivor` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Titan` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Quarry` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Magic` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Dungeons` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Rares` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Tower` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Wall` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `TowerDamage` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `WallDamage` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `Challenges` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `settings`
-- 

CREATE TABLE `settings` (
  `Username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Firstname` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Surname` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Birthdate` date NOT NULL DEFAULT '1000-01-01',
  `Gender` enum('none','male','female') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `Email` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Imnumber` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Country` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
  `Hobby` text COLLATE utf8_unicode_ci NOT NULL,
  `Avatar` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noavatar.jpg',
  `Status` enum('newbie','ready','quick','dnd','none') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'newbie',
  `FriendlyFlag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `BlindFlag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `LongFlag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Timezone` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `Insignias` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `PlayButtons` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Chatorder` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IntegratedChat` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Avatargame` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `OldCardLook` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Miniflags` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Reports` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Forum_notification` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Concepts_notification` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Skin` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `Background` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `DefaultFilter` enum('none','active','offline','all') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `Autorefresh` smallint(5) unsigned NOT NULL DEFAULT '0',
  `AutoAi` tinyint(2) unsigned NOT NULL DEFAULT '5',
  `RandomDeck` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `GameLimit` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Timeout` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `FoilCards` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `statistics`
-- 

CREATE TABLE `statistics` (
  `CardID` int(10) unsigned NOT NULL,
  `Played` int(10) unsigned NOT NULL DEFAULT '0',
  `Discarded` int(10) unsigned NOT NULL DEFAULT '0',
  `PlayedTotal` int(10) unsigned NOT NULL DEFAULT '0',
  `DiscardedTotal` int(10) unsigned NOT NULL DEFAULT '0',
  `Drawn` int(10) unsigned NOT NULL DEFAULT '0',
  `DrawnTotal` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`CardID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
