<?php
/*
SQL create tables
Used for new installs and upgrades from eFiction 3.5.x to 5.x (current)
*/

$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
$characterset = $fw['installerCFG.db5.charset'];

$jobs = array
(
	"config"		=>	"Page config",
	"iconsets"		=>	"Layout: Iconset",
	"menu"			=>	"Page menus",
	"textblocks"	=>	"Textblocks (former: messages)",
	"users"			=>	"Users",
	"chapters"		=>	"Chapters",				// in case of install, just create chapter file if necessary
	"various"		=>	"Various",				// This is a meta job that will perform different database tasks
);

$jobs_upgrade = array
(
	"feedback"		=>	"Feedback (former: reviews & comments)",
	"descriptors"	=>	"Story descriptors",	// This is a meta job
	"stories"		=>	"Stories",
	"series"		=>	"Series",
//	"layout",		=>	"Layout",
);

// Only create tables
$tables = array (
	"bad_behavior"	=>	"Bad Behavior 2",
	"process"		=>	"eFiction process table",
	"sessions"		=>	"Session",
	"messaging"		=>	"Messaging (new feature)",
	// in job_descriptors
	"tags"			=>	"Tags",
	"characters"	=>	"Characters",
	"categories"	=>	"Categories",
	"ratings"		=>	"Ratings",
	// in job_various:
	"log"			=>	"Logs",
	"news"			=>	"News",
	"poll"			=>	"Polls",
	"shoutbox"		=>	"Shoutbox",
	"tracker"		=>	"Story tracker (new feature)",
);

// running an upgrade?
if(!empty($upgrade))
	// add the upgrades to the jobs
	$jobs = array_merge($jobs, $jobs_upgrade);

// fresh install
else
	// only create the upgrades tables
	$tables = array_merge($tables, $jobs_upgrade);

$_SESSION['skipped'] = array();


/* --------------------------------------------------------------------------------------------
* UTILITY TABLE to monitor progress*
	requires: -
-------------------------------------------------------------------------------------------- */
$core['process'] = <<<EOF
DROP TABLE IF EXISTS `{$new}process`;
CREATE TABLE `{$new}process` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `job` varchar(255) NOT NULL,
  `joborder` tinyint(3) NOT NULL,
  `step` tinyint(3) NOT NULL,
  `job_description` varchar(255) NOT NULL,
  `step_function` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `items` smallint(5) unsigned NOT NULL DEFAULT '0',
  `total` smallint(5) unsigned NOT NULL DEFAULT '0',
  `error` varchar(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} ;
EOF;


/* --------------------------------------------------------------------------------------------
* BAD BEHAVIOR *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['bad_behavior'] = <<<EOF
DROP TABLE IF EXISTS `{$new}bad_behavior`;
CREATE TABLE `{$new}bad_behavior` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `ip` text NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `request_method` text NOT NULL,
  `request_uri` text NOT NULL,
  `server_protocol` text NOT NULL,
  `http_headers` text NOT NULL,
  `user_agent` text NOT NULL,
  `request_entity` text NOT NULL,
  `key` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`(15)),
  KEY `user_agent` (`user_agent`(10)),
  KEY `key` (`key`(8))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* CATEGORIES *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['categories'] = <<<EOF
DROP TABLE IF EXISTS `{$new}categories`;
CREATE TABLE `{$new}categories` (
  `cid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `parent_cid` mediumint(8) NOT NULL DEFAULT '0',
  `category` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
  `locked` BOOLEAN NOT NULL DEFAULT FALSE,
  `leveldown` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `inorder` mediumint(8) NOT NULL DEFAULT '0',
  `counter` mediumint(8) NOT NULL DEFAULT '0' COMMENT 'might be obsolete',
  `stats` text NOT NULL,
  PRIMARY KEY (`cid`), KEY `byparent` (`parent_cid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFI5): derived from _categories';
EOF;


/* --------------------------------------------------------------------------------------------
* CHAPTERS *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['chapters'] = <<<EOF
DROP TABLE IF EXISTS `{$new}chapters`;
CREATE TABLE `{$new}chapters` (
  `chapid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `sid` mediumint(8) NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `inorder` mediumint(8) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `notes` text,
  `chaptertext` mediumtext,
  `workingtext` mediumtext,
  `workingdate` timestamp NULL DEFAULT NULL,
  `endnotes` text,
  `validated` tinyint(2) UNSIGNED ZEROFILL NOT NULL DEFAULT '00' COMMENT 'First digit is status, second can be an explanation (https://efiction.org/wiki/DataStructure)',
  `wordcount` mediumint(8) NOT NULL DEFAULT '0',
  `rating` tinyint(3) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `count` mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`chapid`), KEY `sid` (`sid`), KEY `inorder` (`inorder`), KEY `title` (`title`), KEY `validated` (`validated`), KEY `forstoryblock` (`sid`,`validated`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* CHARACTERS *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['characters'] = <<<EOF
DROP TABLE IF EXISTS `{$new}characters`;
CREATE TABLE `{$new}characters` (
  `charid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `catid` mediumint(8) NOT NULL DEFAULT '0',
  `charname` varchar(255) NOT NULL,
  `biography` mediumtext NOT NULL,
  `image` varchar(255) NOT NULL,
  `count` mediumint(8) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`charid`), KEY `charname` (`charname`(64))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table';
EOF;


/* --------------------------------------------------------------------------------------------
* CONFIG *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['config'] = <<<EOF
DROP TABLE IF EXISTS `{$new}config`;
CREATE TABLE `{$new}config` (
  `name` varchar(32) NOT NULL,
  `admin_module` varchar(64) NOT NULL,
  `section_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `value` varchar(256) NOT NULL,
  `form_type` text NOT NULL,
  `can_edit` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
EOF;


/* --------------------------------------------------------------------------------------------
* FEEDBACK *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['feedback'] = <<<EOF
DROP TABLE IF EXISTS `{$new}feedback`;
CREATE TABLE `{$new}feedback` (
  `fid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `reference` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `reference_sub` mediumint(9) unsigned DEFAULT NULL,
  `writer_name` varchar(60) DEFAULT NULL,
  `writer_uid` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `datetime` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `type` ENUM('C','N','RC','SE','ST') NOT NULL COMMENT 'C = comment, N = news, RC = recommendation, SE = series, ST = story',
  `moderation` mediumint(8) DEFAULT NULL,
  PRIMARY KEY (`fid`), KEY `sub_ref` (`reference_sub`), KEY `moderation` (`moderation`), KEY `by_uid` (`writer_uid`,`reference`,`type`), KEY `alias_story_chapter` (`reference`,`reference_sub`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* ICONSET *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['iconsets'] = <<<EOF
DROP TABLE IF EXISTS `{$new}iconsets`;
CREATE TABLE `{$new}iconsets` (
  `set_id` tinyint(3) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` text,
  PRIMARY KEY (`set_id`,`name`(30))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* LOG *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['log'] = <<<EOF
DROP TABLE IF EXISTS `{$new}log`;
CREATE TABLE `{$new}log` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `action` text,
  `uid` mediumint(8) NOT NULL DEFAULT '0',
  `ip` int(10) unsigned DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` ENUM('AM','BL','DL','EB','ED','LP','RE','RG','VS') NOT NULL,
  `subtype` char(1) DEFAULT NULL,
  `version` tinyint(1) NOT NULL,
  `new` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`id`), KEY `type` (`type`), KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* LAYOUT *
	requires: -
-------------------------------------------------------------------------------------------- */
/*
$core['layout'] = <<<EOF
DROP TABLE IF EXISTS `{$new}layout`;
CREATE TABLE `{$new}layout` (
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `layout` tinyint(3) unsigned NOT NULL,
  `setting` varchar(64) NOT NULL,
  `value` varchar(256) NOT NULL,
  PRIMARY KEY (`uid`,`layout`,`setting`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;
*/


/* --------------------------------------------------------------------------------------------
* MENU *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['menu'] = <<<EOF
DROP TABLE IF EXISTS `{$new}menu`;
CREATE TABLE `{$new}menu` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  `link` varchar(256) DEFAULT NULL,
  `meta` varchar(128) DEFAULT NULL,
  `child_of` tinyint(3) DEFAULT NULL,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
--NOTE--Page menu
--SPLIT--

DROP TABLE IF EXISTS `{$new}menu_adminpanel`;
CREATE TABLE `{$new}menu_adminpanel` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL COMMENT 'must match an ''AdminMenu_...'' entry in the language files!',
  `order` tinyint(3) NOT NULL,
  `link` varchar(128),
  `icon` varchar(64) DEFAULT '{ICON:blank}',
  `child_of` varchar(64) DEFAULT NULL,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `requires` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `evaluate` varchar(255),
  PRIMARY KEY (`id`), KEY `child_of` (`child_of`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
--NOTE--Admin/moderation menu
--SPLIT--

DROP TABLE IF EXISTS `{$new}menu_userpanel`;
CREATE TABLE `{$new}menu_userpanel` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `order` tinyint(3) NOT NULL,
  `link` varchar(128),
  `icon` varchar(64),
  `child_of` varchar(16) DEFAULT NULL,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `evaluate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu` (`child_of`,`order`),
  KEY `child_of` (`child_of`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
--NOTE--User panel menu
EOF;


/* --------------------------------------------------------------------------------------------
* MESSAGING *
	requires: users
-------------------------------------------------------------------------------------------- */
$core['messaging'] = <<<EOF
DROP TABLE IF EXISTS `{$new}messaging`;
CREATE TABLE `{$new}messaging` (
  `mid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `sent` mediumint(9) DEFAULT NULL,
  `sender` mediumint(8) unsigned NOT NULL,
  `recipient` mediumint(8) unsigned NOT NULL,
  `date_sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_read` timestamp NULL DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`mid`), KEY `sent` (`sent`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFiction 5): new table for user messages';
EOF;


/* --------------------------------------------------------------------------------------------
* NEWS *
	requires: users
-------------------------------------------------------------------------------------------- */
$core['news'] = <<<EOF
DROP TABLE IF EXISTS `{$new}news`;
CREATE TABLE `{$new}news` (
  `nid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) NOT NULL,
  `headline` varchar(255) NOT NULL DEFAULT '',
  `newstext` text NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `comments` mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nid`), KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* POLL *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['poll'] = <<<EOF
DROP TABLE IF EXISTS `{$new}poll`;
CREATE TABLE `{$new}poll` (
  `poll_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `question` varchar(250) NOT NULL,
  `options` text NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `results` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`poll_id`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
--NOTE--Poll main table
--SPLIT--

DROP TABLE IF EXISTS `{$new}poll_votes`;
CREATE TABLE `{$new}poll_votes` (
  `vote_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `poll_id` mediumint(8) NOT NULL DEFAULT '0',
  `uid` mediumint(8) NOT NULL DEFAULT '0',
  `option` mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`),
  KEY `vote_user` (`uid`,`poll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--NOTE--Poll votes
EOF;


/* --------------------------------------------------------------------------------------------
* RATINGS *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['ratings'] = <<<EOF
DROP TABLE IF EXISTS `{$new}ratings`;
CREATE TABLE `{$new}ratings` (
  `rid` smallint(6) NOT NULL AUTO_INCREMENT,
  `inorder` smallint(6) NOT NULL,
  `rating` varchar(60) NOT NULL DEFAULT '',
  `rating_age` tinyint(3) DEFAULT NULL,
  `rating_image` varchar(50) NULL DEFAULT NULL,
  `ratingwarning` BOOLEAN NOT NULL DEFAULT FALSE,
  `warningtext` text NOT NULL,
  PRIMARY KEY (`rid`),
  KEY `rating` (`rating`), KEY `inorder` (`inorder`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* SERIES *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['series'] = <<<EOF
DROP TABLE IF EXISTS `{$new}series`;
CREATE TABLE `{$new}series` (
  `seriesid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `parent_series` mediumint(8) unsigned DEFAULT NULL,
  `type` SET('S','C') NOT NULL DEFAULT 'S' COMMENT '\'S\' - Series, \'C\' - Collection',
  `title` varchar(200) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `uid` mediumint(8) NOT NULL DEFAULT '0',
  `open` tinyint(1) NOT NULL DEFAULT '0',
  `status` SET('H','P','A') NOT NULL DEFAULT 'P' COMMENT 'Applies only to collections (Hidden, Public, Archive)',
  `rating` tinyint(3) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `contests` varchar(200) NOT NULL DEFAULT '',
  `max_rating` varchar(64) NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  `words` mediumint(8) unsigned DEFAULT NULL,
  `cache_authors` text,
  `cache_tags` text,
  `cache_characters` text,
  `cache_categories` text,
  PRIMARY KEY (`seriesid`),
  KEY `owner` (`uid`,`title`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
--NOTE--Series
--SPLIT--

DROP TABLE IF EXISTS `{$new}series_stories`;
CREATE TABLE `{$new}series_stories` (
  `seriesid` mediumint(8) NOT NULL DEFAULT '0',
  `sid` mediumint(8) NOT NULL DEFAULT '0',
  `confirmed` mediumint(8) NOT NULL DEFAULT '0',
  `inorder` mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`,`seriesid`),
  KEY `seriesid` (`seriesid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
--NOTE--Stories <-> Series relations
EOF;


/* --------------------------------------------------------------------------------------------
* SESSION *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['sessions'] = <<<EOF
DROP TABLE IF EXISTS `{$new}sessions`;
CREATE TABLE `{$new}sessions` (
  `session` char(32) NOT NULL,
  `user` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastvisited` timestamp NOT NULL,
  `ip` int(10) unsigned DEFAULT NULL,
  `admin` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session`),
  KEY `user` (`user`),
  KEY `session` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for sessions';
EOF;


/* --------------------------------------------------------------------------------------------
* SHOUTBOX *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['shoutbox'] = <<<EOF
DROP TABLE IF EXISTS `{$new}shoutbox`;
CREATE TABLE `{$new}shoutbox` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(5) NOT NULL DEFAULT 0,
  `guest_name` varchar(255),
  `message` varchar(200) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* STORIES *
	requires: tags, categories, characters, rating
-------------------------------------------------------------------------------------------- */
$core['stories'] = <<<EOF
-- remove relation tables first to deal with the foreign keys (currently not used)
DROP TABLE IF EXISTS `{$new}stories_tags`;
DROP TABLE IF EXISTS `{$new}stories_authors`;
DROP TABLE IF EXISTS `{$new}stories_categories`;
DROP TABLE IF EXISTS `{$new}stories`;
CREATE TABLE `{$new}stories` (
  `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `summary` text,
  `storynotes` text,
  `ratingid` tinyint(3) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `validated` tinyint(2) UNSIGNED ZEROFILL NOT NULL DEFAULT '00' COMMENT 'First digit is status, second can be an explanation (http://efiction.org/wiki/DataStructure)',
  `completed` tinyint(2) UNSIGNED NOT NULL DEFAULT '1' COMMENT '0 deleted, 1 draft, 6 w.i.p., 9 all done',
  `roundrobin` char(1) NOT NULL DEFAULT '0',
  `wordcount` mediumint(8) NOT NULL DEFAULT '0',
  `ranking` tinyint(3) DEFAULT NULL COMMENT 'user rating, but name was ambigious with the age rating',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `chapters` smallint(6) NOT NULL DEFAULT '0',
  `count` mediumint(8) NOT NULL DEFAULT '0',
  `cache_authors` text,
  `cache_tags` text,
  `cache_characters` text,
  `cache_categories` text,
  `cache_rating` varchar(255) DEFAULT NULL,
  `moderation` mediumint(8) DEFAULT NULL,
  `translation` tinyint(1) NOT NULL DEFAULT '0',
  `trans_from` varchar(10) NOT NULL,
  `trans_to` varchar(10) NOT NULL,
  PRIMARY KEY (`sid`),
  KEY `moderation` (`moderation`),
  KEY `title` (`title`),
  KEY `ratingid` (`ratingid`),
  KEY `completed` (`completed`),
  KEY `roundrobin` (`roundrobin`),
  KEY `validated` (`validated`),
  KEY `recent` (`updated`,`validated`),
  KEY `translation` (`translation`,`trans_from`,`trans_to`)
 ) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
--SPLIT--

CREATE TABLE `{$new}stories_authors` (
  `lid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `sid` mediumint(8) NOT NULL,
  `aid` mediumint(8) unsigned NOT NULL,
  `type` ENUM('M','S','T') NOT NULL DEFAULT 'M' COMMENT 'M = main, S = supporting, T = translator',
  PRIMARY KEY (`lid`), UNIQUE KEY `fullrelation` (`sid`,`aid`,`type`), UNIQUE KEY `relation` (`sid`,`aid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for story-author relations';
--NOTE--Story relation table: Authors
--SPLIT--

CREATE TABLE `{$new}stories_categories` (
  `lid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `sid` mediumint(8) NOT NULL,
  `cid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`lid`), UNIQUE KEY `relation` (`sid`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for story-category relations';
--NOTE--Story relation table: Categories
--SPLIT--

DROP TABLE IF EXISTS `{$new}featured`;
CREATE TABLE `{$new}featured` (
  `id` mediumint(8) NOT NULL,
  `type` char(2) NOT NULL DEFAULT 'ST',
  `status` ENUM('1','2') NULL DEFAULT NULL COMMENT 'NULL = by date, 1 = manual current, 2 = manual past',
  `start` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `uid` mediumint(8) DEFAULT NULL,
  UNIQUE KEY `feature` (`id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for featured stories';
--NOTE--Story relation table: Featured
--SPLIT--

CREATE TABLE `{$new}stories_tags` (
  `lid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `sid` mediumint(8) NOT NULL,
  `tid` mediumint(8) unsigned NOT NULL,
  `character` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`lid`), KEY `relation` (`sid`,`tid`,`character`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for story-tag/character relations';
--NOTE--Story relation table: Tags
EOF;


/* --------------------------------------------------------------------------------------------
* TAGS *
	requires: tag_groups
-------------------------------------------------------------------------------------------- */
$core['tags'] = <<<EOF
DROP TABLE IF EXISTS `{$new}tags`;
CREATE TABLE `{$new}tags` (
  `tid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `oldid` mediumint(8) NOT NULL,
  `tgid` mediumint(8) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `count` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`tid`), UNIQUE KEY `label` (`label`(64)), KEY `tgid` (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table';
--NOTE--Tag table
--SPLIT--

DROP TABLE IF EXISTS `{$new}tag_groups`;
CREATE TABLE `{$new}tag_groups` (
  `tgid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` mediumint(8) NOT NULL DEFAULT '0',
  `description` mediumtext,
  PRIMARY KEY (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table, eFiction 5';
--NOTE--Tag groups
EOF;


/* --------------------------------------------------------------------------------------------
* TEXTBLOCKS *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['textblocks'] = <<<EOF
DROP TABLE IF EXISTS `{$new}textblocks`;
CREATE TABLE `{$new}textblocks` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `as_page` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Can be viewed as standalone page',
  PRIMARY KEY (`id`),
  KEY `label` (`label`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* TRACKER *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['tracker'] = <<<EOF
DROP TABLE IF EXISTS `{$new}tracker`;
CREATE TABLE `{$new}tracker` (
  `sid` mediumint(8) NOT NULL DEFAULT '0',
  `uid` mediumint(8) NOT NULL DEFAULT '0',
  `last_chapter` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_read` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`uid`), KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
EOF;


/* --------------------------------------------------------------------------------------------
* USER *
	requires: STORIES AUTHORS
-------------------------------------------------------------------------------------------- */
$core['users'] = <<<EOF
DROP TABLE IF EXISTS `{$new}users`;
CREATE TABLE `{$new}users` (
  `uid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(128) CHARACTER SET utf8 NOT NULL,
  `nickname` varchar(128) CHARACTER SET utf8 NOT NULL,
  `realname` text CHARACTER SET utf8 NOT NULL,
  `password` varchar(140) CHARACTER SET utf8 NOT NULL,
  `email` varchar(256) CHARACTER SET utf8 NOT NULL,
  `registered` datetime NOT NULL,
  `groups` mediumint(8) unsigned DEFAULT NULL,
  `curator` mediumint(8) unsigned DEFAULT NULL,
  `about` text CHARACTER SET utf8 NULL,
  `moderation` mediumint(8) DEFAULT NULL,
  `alert_feedback` BOOLEAN NOT NULL DEFAULT FALSE,
  `alert_comment` BOOLEAN NOT NULL DEFAULT FALSE,
  `alert_favourite` BOOLEAN NOT NULL DEFAULT FALSE,
  `preferences` text NOT NULL,
  `cache_feedback` text NOT NULL,
  `cache_messaging` text NOT NULL,
  PRIMARY KEY (`uid`), UNIQUE KEY `name1` (`login`), KEY `pass1` (`password`), KEY `moderation` (`moderation`), KEY `curator` (`curator`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table for users';
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_favourites`;
CREATE TABLE `{$new}user_favourites` (
  `fid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) NOT NULL DEFAULT '0',
  `item` mediumint(8) NOT NULL DEFAULT '0',
  `type` enum('AU','RC','SE','ST') NOT NULL,
  `bookmark` BOOLEAN NOT NULL DEFAULT FALSE,
  `notify` BOOLEAN NOT NULL DEFAULT FALSE,
  `visibility` ENUM('0','1','2','3') NOT NULL DEFAULT '2',
  `comments` text NOT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fid`), 
  UNIQUE KEY `byitem` (`item`,`type`,`bookmark`,`uid`) USING BTREE, 
  UNIQUE KEY `byuid` (`uid`,`type`,`bookmark`,`item`) USING BTREE,
  KEY `byuidlist` (`uid`,`type`,`bookmark`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--NOTE--User favourites
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_fields`;
CREATE TABLE `{$new}user_fields` (
  `field_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `field_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '255',
  `field_type` tinyint(3) NOT NULL DEFAULT '0',
  `field_name` varchar(30) NOT NULL DEFAULT ' ',
  `field_title` varchar(255) NOT NULL DEFAULT ' ',
  `field_options` text,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--NOTE--User fields
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_info`;
CREATE TABLE `{$new}user_info` (
  `uid` mediumint(8) NOT NULL DEFAULT '0',
  `field` mediumint(8) NOT NULL DEFAULT '0',
  `info` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`,`field`), KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--NOTE--User info
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_friends`;
CREATE TABLE `{$new}user_friends` (
  `link_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) UNSIGNED NOT NULL,
  `friend_id` mediumint(8) UNSIGNED NOT NULL,
  `note` varchar(255),
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`link_id`), UNIQUE KEY `relation` (`user_id`,`friend_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table for friend relations';
--NOTE--User friends
--SPLIT--

DROP TABLE IF EXISTS `{$new}authors`;
CREATE TABLE `{$new}authors` (
  `aid` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `info` text NOT NULL,
  PRIMARY KEY (`aid`), KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table for actual authors';
--NOTE--Authors
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_authors`;
CREATE TABLE `{$new}user_authors` (
  `lid` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) UNSIGNED NOT NULL,
  `aid` mediumint(8) UNSIGNED NOT NULL,
  `visibility` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`lid`), UNIQUE KEY `link` (`uid`,`aid`), KEY `visibility` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table for user author relations';
--NOTE--User Author relations
EOF;


?>