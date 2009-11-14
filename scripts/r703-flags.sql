ALTER TABLE `settings`  ADD `FriendlyFlag` CHAR(3) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER `Status`;
ALTER TABLE `settings`  ADD `BlindFlag` CHAR(3) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER `FriendlyFlag`;
