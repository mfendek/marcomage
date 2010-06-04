UPDATE `messages` SET `AuthorDelete` = 0 WHERE `AuthorDelete` = "no";
UPDATE `messages` SET `AuthorDelete` = 1 WHERE `AuthorDelete` = "yes";

ALTER TABLE `messages` CHANGE `AuthorDelete` `AuthorDelete` BOOL NOT NULL DEFAULT '0';

UPDATE `messages` SET `RecipientDelete` = 0 WHERE `RecipientDelete` = "no";
UPDATE `messages` SET `RecipientDelete` = 1 WHERE `RecipientDelete` = "yes";

ALTER TABLE `messages` CHANGE `RecipientDelete` `RecipientDelete` BOOL NOT NULL DEFAULT '0';

UPDATE `messages` SET `Unread` = 0 WHERE `Unread` = "no";
UPDATE `messages` SET `Unread` = 1 WHERE `Unread` = "yes";

ALTER TABLE `messages` CHANGE `Unread` `Unread` BOOL NOT NULL DEFAULT '1';
