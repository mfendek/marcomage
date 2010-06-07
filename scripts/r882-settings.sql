UPDATE `settings` SET `FriendlyFlag` = 0 WHERE `FriendlyFlag` = "no";
UPDATE `settings` SET `FriendlyFlag` = 1 WHERE `FriendlyFlag` = "yes";

ALTER TABLE `settings` CHANGE `FriendlyFlag` `FriendlyFlag` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `BlindFlag` = 0 WHERE `BlindFlag` = "no";
UPDATE `settings` SET `BlindFlag` = 1 WHERE `BlindFlag` = "yes";

ALTER TABLE `settings` CHANGE `BlindFlag` `BlindFlag` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `Minimize` = 0 WHERE `Minimize` = "no";
UPDATE `settings` SET `Minimize` = 1 WHERE `Minimize` = "yes";

ALTER TABLE `settings` CHANGE `Minimize` `Minimize` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `Cardtext` = 0 WHERE `Cardtext` = "no";
UPDATE `settings` SET `Cardtext` = 1 WHERE `Cardtext` = "yes";

ALTER TABLE `settings` CHANGE `Cardtext` `Cardtext` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `Images` = 0 WHERE `Images` = "no";
UPDATE `settings` SET `Images` = 1 WHERE `Images` = "yes";

ALTER TABLE `settings` CHANGE `Images` `Images` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `Keywords` = 0 WHERE `Keywords` = "no";
UPDATE `settings` SET `Keywords` = 1 WHERE `Keywords` = "yes";

ALTER TABLE `settings` CHANGE `Keywords` `Keywords` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `Nationality` = 0 WHERE `Nationality` = "no";
UPDATE `settings` SET `Nationality` = 1 WHERE `Nationality` = "yes";

ALTER TABLE `settings` CHANGE `Nationality` `Nationality` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `Chatorder` = 0 WHERE `Chatorder` = "no";
UPDATE `settings` SET `Chatorder` = 1 WHERE `Chatorder` = "yes";

ALTER TABLE `settings` CHANGE `Chatorder` `Chatorder` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `Avatargame` = 0 WHERE `Avatargame` = "no";
UPDATE `settings` SET `Avatargame` = 1 WHERE `Avatargame` = "yes";

ALTER TABLE `settings` CHANGE `Avatargame` `Avatargame` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `Avatarlist` = 0 WHERE `Avatarlist` = "no";
UPDATE `settings` SET `Avatarlist` = 1 WHERE `Avatarlist` = "yes";

ALTER TABLE `settings` CHANGE `Avatarlist` `Avatarlist` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `Correction` = 0 WHERE `Correction` = "no";
UPDATE `settings` SET `Correction` = 1 WHERE `Correction` = "yes";

ALTER TABLE `settings` CHANGE `Correction` `Correction` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `OldCardLook` = 0 WHERE `OldCardLook` = "no";
UPDATE `settings` SET `OldCardLook` = 1 WHERE `OldCardLook` = "yes";

ALTER TABLE `settings` CHANGE `OldCardLook` `OldCardLook` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `Reports` = 0 WHERE `Reports` = "no";
UPDATE `settings` SET `Reports` = 1 WHERE `Reports` = "yes";

ALTER TABLE `settings` CHANGE `Reports` `Reports` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `Forum_notification` = 0 WHERE `Forum_notification` = "no";
UPDATE `settings` SET `Forum_notification` = 1 WHERE `Forum_notification` = "yes";

ALTER TABLE `settings` CHANGE `Forum_notification` `Forum_notification` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `Concepts_notification` = 0 WHERE `Concepts_notification` = "no";
UPDATE `settings` SET `Concepts_notification` = 1 WHERE `Concepts_notification` = "yes";

ALTER TABLE `settings` CHANGE `Concepts_notification` `Concepts_notification` BOOL NOT NULL DEFAULT '1';

UPDATE `settings` SET `GamesDetails` = 0 WHERE `GamesDetails` = "no";
UPDATE `settings` SET `GamesDetails` = 1 WHERE `GamesDetails` = "yes";

ALTER TABLE `settings` CHANGE `GamesDetails` `GamesDetails` BOOL NOT NULL DEFAULT '0';

UPDATE `settings` SET `RandomDeck` = 0 WHERE `RandomDeck` = "no";
UPDATE `settings` SET `RandomDeck` = 1 WHERE `RandomDeck` = "yes";

ALTER TABLE `settings` CHANGE `RandomDeck` `RandomDeck` BOOL NOT NULL DEFAULT '1';
