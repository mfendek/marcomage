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
-- Table structure for table `chat`
-- 

CREATE TABLE `chat` (
  `game_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`game_id`,`created_at`,`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `concept`
--

CREATE TABLE `concept` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `rarity` enum('Common','Uncommon','Rare') COLLATE utf8_unicode_ci NOT NULL,
  `bricks` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `gems` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `recruits` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `effect` text COLLATE utf8_unicode_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `picture` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blank.jpg',
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `state` enum('waiting','rejected','interesting','implemented') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
  `author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`card_id`),
  KEY `modified_at` (`modified_at`),
  KEY `author` (`author`),
  KEY `state` (`state`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `deck`
-- 

CREATE TABLE `deck` (
  `deck_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `deck_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `is_ready` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `draws` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_shared` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`deck_id`),
  KEY `username` (`username`),
  KEY `is_shared` (`is_shared`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_post`
-- 

CREATE TABLE `forum_post` (
  `post_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `thread_id` int(10) NOT NULL,
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `thread_id` (`thread_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `forum_section`
-- 

CREATE TABLE `forum_section` (
  `section_id` tinyint(3) unsigned NOT NULL,
  `section_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `section_order` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `section_order` (`section_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 
-- Dumping data for table `forum_section`
-- 

INSERT INTO `forum_section` (`section_id`, `section_name`, `description`, `section_order`) VALUES
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
-- Table structure for table `forum_thread`
-- 

CREATE TABLE `forum_thread` (
  `thread_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `priority` enum('normal','important','sticky') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `is_locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `section_id` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `post_count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `last_author` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_post` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `reference_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`thread_id`),
  KEY `section_id` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table `game`
-- 

CREATE TABLE `game` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player1` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `player2` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `state` enum('waiting','in progress','finished','P1 over','P2 over') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
  `current` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `round` smallint(5) unsigned NOT NULL DEFAULT '1',
  `winner` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `surrender` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `outcome_type` enum('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
  `last_action_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `chat_notification1` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `chat_notification2` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `deck_id1` int(10) unsigned NOT NULL DEFAULT '0',
  `deck_id2` int(10) unsigned NOT NULL DEFAULT '0',
  `note1` text COLLATE utf8_unicode_ci NOT NULL,
  `note2` text COLLATE utf8_unicode_ci NOT NULL,
  `game_modes` set('HiddenCards','FriendlyPlay','LongMode','AIMode') COLLATE utf8_unicode_ci NOT NULL,
  `turn_timeout` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ai_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`game_id`),
  KEY `player1` (`player1`),
  KEY `player2` (`player2`),
  KEY `current` (`current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `login`
-- 

CREATE TABLE `login` (
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_type` enum('user','moderator','supervisor','admin','squashed','limited','banned') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_ip` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `last_activity_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `notification_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  PRIMARY KEY (`username`),
  KEY `last_activity_at` (`last_activity_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `message`
-- 

CREATE TABLE `message` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `recipient` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `is_deleted_author` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_deleted_recipient` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_unread` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `replay`
--

CREATE TABLE `replay` (
  `game_id` int(10) unsigned NOT NULL,
  `player1` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `player2` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `rounds` smallint(5) unsigned NOT NULL DEFAULT '1',
  `turns` smallint(5) unsigned NOT NULL DEFAULT '1',
  `winner` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `outcome_type` enum('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') COLLATE utf8_unicode_ci NOT NULL,
  `game_modes` set('HiddenCards','FriendlyPlay','LongMode','AIMode') COLLATE utf8_unicode_ci NOT NULL,
  `ai_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `views` smallint(5) unsigned NOT NULL DEFAULT '0',
  `data` blob,
  PRIMARY KEY (`game_id`),
  KEY `player1` (`player1`),
  KEY `player2` (`player2`),
  KEY `outcome_type` (`outcome_type`),
  KEY `game_modes` (`game_modes`),
  KEY `finished_at` (`finished_at`),
  KEY `ai_name` (`ai_name`),
  KEY `winner` (`winner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `score`
-- 

CREATE TABLE `score` (
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `level` smallint(5) unsigned NOT NULL DEFAULT '0',
  `exp` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `gold` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `wins` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `losses` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `draws` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `game_slots` smallint(5) unsigned NOT NULL DEFAULT '0',
  `assassin` smallint(5) unsigned NOT NULL DEFAULT '0',
  `builder` smallint(5) unsigned NOT NULL DEFAULT '0',
  `carpenter` smallint(5) unsigned NOT NULL DEFAULT '0',
  `collector` smallint(5) unsigned NOT NULL DEFAULT '0',
  `desolator` smallint(5) unsigned NOT NULL DEFAULT '0',
  `dragon` smallint(5) unsigned NOT NULL DEFAULT '0',
  `gentle_touch` smallint(5) unsigned NOT NULL DEFAULT '0',
  `saboteur` smallint(5) unsigned NOT NULL DEFAULT '0',
  `snob` smallint(5) unsigned NOT NULL DEFAULT '0',
  `survivor` smallint(5) unsigned NOT NULL DEFAULT '0',
  `titan` smallint(5) unsigned NOT NULL DEFAULT '0',
  `quarry` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `magic` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `dungeons` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `rares` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `tower` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `wall` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `tower_damage` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `wall_damage` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ai_challenges` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `setting`
-- 

CREATE TABLE `setting` (
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `surname` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `birth_date` date NOT NULL DEFAULT '1000-01-01',
  `gender` enum('none','male','female') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `email` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `im_number` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
  `hobby` text COLLATE utf8_unicode_ci NOT NULL,
  `avatar` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noavatar.jpg',
  `status` enum('newbie','ready','quick','dnd','none') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'newbie',
  `friendly_flag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blind_flag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `long_flag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `timezone` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `keyword_insignia` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `play_card_button` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `chat_reverse_order` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `integrated_chat` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `old_card_look` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `card_mini_flag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `battle_report` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `forum_notification` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `concept_notification` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `skin` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `game_bg_image` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `default_player_filter` enum('none','active','offline','all') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `auto_refresh_timer` smallint(5) unsigned NOT NULL DEFAULT '0',
  `auto_ai_timer` tinyint(2) unsigned NOT NULL DEFAULT '5',
  `use_random_deck` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `unique_game_opponent` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `game_turn_timeout` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `foil_cards` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `statistic`
-- 

CREATE TABLE `statistic` (
  `card_id` int(10) unsigned NOT NULL,
  `played` int(10) unsigned NOT NULL DEFAULT '0',
  `discarded` int(10) unsigned NOT NULL DEFAULT '0',
  `played_total` int(10) unsigned NOT NULL DEFAULT '0',
  `discarded_total` int(10) unsigned NOT NULL DEFAULT '0',
  `drawn` int(10) unsigned NOT NULL DEFAULT '0',
  `drawn_total` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
