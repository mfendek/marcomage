-- removes the four avatar visibility settings
ALTER TABLE `settings` DROP `Online`, DROP `Offline`, DROP `Inactive`, DROP `Dead` ;

-- add a new, single, simplified setting instead
ALTER TABLE `settings` ADD `Avatarlist` CHAR( 3 ) NOT NULL DEFAULT 'yes' AFTER `Avatargame` ;
