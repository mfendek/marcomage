SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

RENAME TABLE `statistics` TO `statistic`;

ALTER TABLE `statistic` CHANGE `CardID` `card_id` INT(10) UNSIGNED NOT NULL,
CHANGE `Played` `played` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Discarded` `discarded` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `PlayedTotal` `played_total` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `DiscardedTotal` `discarded_total` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Drawn` `drawn` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `DrawnTotal` `drawn_total` INT(10) UNSIGNED NOT NULL DEFAULT '0';
