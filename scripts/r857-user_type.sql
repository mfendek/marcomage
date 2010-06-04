ALTER TABLE `logins` CHANGE `UserType` `UserType` ENUM('user','moderator','supervisor','admin','squashed','limited','banned') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user';
