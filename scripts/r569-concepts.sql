CREATE TABLE IF NOT EXISTS `concepts` (
  `CardID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `Class` char(8) COLLATE utf8_unicode_ci NOT NULL,
  `Bricks` int(3) unsigned NOT NULL DEFAULT '0',
  `Gems` int(3) unsigned NOT NULL DEFAULT '0',
  `Recruits` int(3) unsigned NOT NULL DEFAULT '0',
  `Effect` text COLLATE utf8_unicode_ci NOT NULL,
  `Keywords` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Picture` char(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blank.jpg',
  `Note` text COLLATE utf8_unicode_ci NOT NULL,
  `State` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
  `Owner` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `LastChange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CardID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
