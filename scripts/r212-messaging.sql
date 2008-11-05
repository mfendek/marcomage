CREATE TABLE `messages` (
  `MessageID` int(10) unsigned NOT NULL,
  `Author` char(20) collate utf8_unicode_ci NOT NULL,
  `Recipient` char(20) collate utf8_unicode_ci NOT NULL,
  `Subject` varchar(30) collate utf8_unicode_ci NOT NULL,
  `Content` varchar(300) collate utf8_unicode_ci NOT NULL,
  `AuthorDelete` char(3) collate utf8_unicode_ci NOT NULL default 'no',
  `RecipientDelete` char(3) collate utf8_unicode_ci NOT NULL default 'no',
  `Unread` char(3) collate utf8_unicode_ci NOT NULL default 'yes',
  `Created` datetime NOT NULL,
  PRIMARY KEY  (`MessageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `logins` ADD `PreviousLogin` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';
