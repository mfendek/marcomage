ALTER TABLE `games` CHANGE `GameModes` `GameModes` SET('HiddenCards','FriendlyPlay','LongMode','AIMode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE `replays_head` CHANGE `GameModes` `GameModes` SET('HiddenCards','FriendlyPlay','LongMode','AIMode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
