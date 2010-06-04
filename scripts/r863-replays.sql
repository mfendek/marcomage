UPDATE `replays_head` SET `Deleted` = 0 WHERE `Deleted` = "no";
UPDATE `replays_head` SET `Deleted` = 1 WHERE `Deleted` = "yes";

ALTER TABLE `replays_head` CHANGE `Deleted` `Deleted` BOOL NOT NULL DEFAULT '0';
