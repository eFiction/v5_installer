<?php
/*
SQL create tables
Optional modules
Used for new installs and upgrades from eFiction 3.5.x to 5.x (current)
*/



/* --------------------------------------------------------------------------------------------
* CONTEST *
	requires: STORY
-------------------------------------------------------------------------------------------- */
$optional['contests']['steps'] = array ( "contests" => "Contest (former: challenges), optional module" );

$optional['contests']['sql'] = <<<EOF
DROP TABLE IF EXISTS `{$new}contests`;
CREATE TABLE `{$new}contests` (
  `conid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `date_open` datetime DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `vote_closed` datetime DEFAULT NULL,
  `concealed` ENUM('0','1') NOT NULL,
  `cache_tags` text,
  `cache_characters` text,
  `cache_categories` text,
  `cache_stories` text,
  PRIMARY KEY (`conid`),
  KEY `uid` (`uid`),
  KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for contests (aka challenges)';
--NOTE--Contest main table (optional module)
--SPLIT--

DROP TABLE IF EXISTS `{$new}contest_relations`;
CREATE TABLE `{$new}contest_relations` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conid` int(10) unsigned NOT NULL,
  `relid` int(10) unsigned NOT NULL,
  `type` ENUM('CA','CH','ST','T') NOT NULL COMMENT 'CA = category, CH = character, ST = story, T = tag';
  PRIMARY KEY (`lid`),
  UNIQUE KEY `UNIQUE` (`relid`,`type`,`conid`),
  KEY `conid` (`conid`),
  KEY `JOIN` (`relid`,`type`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for contest relations';
--NOTE--Contest relation table
EOF;


/* --------------------------------------------------------------------------------------------
* RECOMMENDATIONS *
	requires: STORIES RELATION
-------------------------------------------------------------------------------------------- */
$optional['recommendations']['steps'] = array ( "recommendations" => "Recommendations, optional module" );

$optional['recommendations']['sql'] = <<<EOF
DROP TABLE IF EXISTS `{$new}recommendations`;
CREATE TABLE `{$new}recommendations` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `guestname` varchar(50) DEFAULT NULL,
  `url` varchar(255) NOT NULL DEFAULT 'broken',
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `author` varchar(200) NOT NULL,
  `summary` text,
  `comment` text,
  `ratingid` varchar(25) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `validated` ENUM('0','1') NOT NULL DEFAULT '0',
  `completed` ENUM('0','1') NOT NULL DEFAULT '0',
  `ranking` tinyint(3) DEFAULT NULL COMMENT 'user rating, but name was ambigious with the age rating',
  `reviews` smallint(6) DEFAULT NULL,
  `cache_tags` text,
  `cache_characters` text,
  `cache_categories` text,
  `cache_rating` tinytext NOT NULL,
  PRIMARY KEY (`recid`),
  KEY `title` (`title`),
  KEY `validated` (`validated`),
  KEY `completed` (`completed`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset};
--NOTE--Recommendations main table (optional module)
--SPLIT--

DROP TABLE IF EXISTS `{$new}recommendation_relations`;
CREATE TABLE `{$new}recommendation_relations` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recid` int(10) NOT NULL,
  `relid` int(10) unsigned NOT NULL,
  `type` ENUM('CA','CH','T') NOT NULL DEFAULT 'T' COMMENT 'CA = category, CH = character, T = tag';
  PRIMARY KEY (`lid`), KEY `relation` (`recid`,`relid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for recommendation relations';
--NOTE--Recommendation relations table
EOF;

?>