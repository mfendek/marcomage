ALTER TABLE `games`  ADD `GameModes` SET('HiddenCards','FriendlyPlay') NOT NULL;
UPDATE `games` SET `GameModes` = CONCAT(`GameModes`,",HiddenCards") WHERE `HiddenCards` = "yes";
UPDATE `games` SET `GameModes` = CONCAT(`GameModes`,",FriendlyPlay") WHERE `FriendlyPlay` = "yes";
ALTER TABLE `games` DROP `HiddenCards`, DROP `FriendlyPlay`;
