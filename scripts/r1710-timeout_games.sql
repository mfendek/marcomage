ALTER TABLE `games`  ADD `Timeout` MEDIUMINT(5) UNSIGNED NOT NULL DEFAULT '0' AFTER `GameModes`;
ALTER TABLE `settings`  ADD `Timeout` MEDIUMINT(5) UNSIGNED NOT NULL DEFAULT '0' AFTER `GameLimit`;