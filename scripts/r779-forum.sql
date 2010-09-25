RENAME TABLE `forum_threads` TO `threads_tmp`;

CREATE TABLE `forum_threads` (
  `ThreadID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `Author` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Priority` char(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `Locked` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `Deleted` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `SectionID` int(10) NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `PostCount` int(10) unsigned NOT NULL DEFAULT '0',
  `LastAuthor` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastPost` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ThreadID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO `forum_threads` SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Deleted`, `SectionID`, `Created`, IFNULL(`post_count`, 0) as `PostCount`, IFNULL(`PostAuthor`, "") as `LastAuthor`, IFNULL(`last_post`,"0000-00-00 00:00:00") as `LastPost` FROM `threads_tmp` LEFT OUTER JOIN (SELECT `PostAuthor`, `posts1`.`ThreadID`, `last_post`, `post_count` FROM (SELECT DISTINCT `Author` as `PostAuthor`, `ThreadID`, `Created` FROM `forum_posts` WHERE `Deleted` = "no") as `posts1` INNER JOIN (SELECT `ThreadID`, MAX(`Created`) as `last_post`, COUNT(`PostID`) as `post_count` FROM `forum_posts` WHERE `Deleted` = "no" GROUP BY `ThreadID`) as `posts2` ON `posts1`.`ThreadID` = `posts2`.`ThreadID` AND `posts1`.`Created` = `posts2`.`last_post`) as `posts` USING (`ThreadID`);

DROP TABLE `threads_tmp`;

ALTER TABLE `forum_posts` ADD INDEX(`ThreadID`);
ALTER TABLE `forum_threads` ADD INDEX(`SectionID`);
