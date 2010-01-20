UPDATE `concepts` SET `ThreadID` = 0 WHERE `ThreadID` > 0 AND `ThreadID` IN (SELECT `ThreadID` FROM `forum_threads` WHERE `SectionID` = 6 AND `Deleted` = "yes");
