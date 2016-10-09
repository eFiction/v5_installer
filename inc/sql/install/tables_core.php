<?php
/*
SQL create tables
Used for new installs and upgrades from eFiction 3.5.x to 5.x (current)
*/

$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
$characterset = $fw['installerCFG.charset'];

$jobs = array
(
	"config"		=>	"Page config",
	"iconsets"		=>	"Layout: Iconset",
	"menu"			=>	"Page menus",
	"textblocks"	=>	"Textblocks (former: messages)",
	"chapters"		=>	"Chapters",
	"feedback"		=>	"Feedback (former: reviews & comments)",
	"users"			=>	"Users",
	"descriptors"	=>	"Story descriptors",	// This is a meta job
	"stories"		=>	"Stories",
	"series"		=>	"Series",
	"various"		=>	"Various",				// This is a meta job
//	"layout",		=>	"Layout",
);

// Only create tables
$tables = array (
	"bad_behavior"	=>	"Bad Behavior 2",
	"convert"		=>	"eFiction conversion table",
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
$_SESSION['skipped'] = array();


if(isset($upgrade)){
/* --------------------------------------------------------------------------------------------
* UTILITY TABLE to monitor upgrade progress*
	requires: -
-------------------------------------------------------------------------------------------- */
$core['convert'] = <<<EOF
DROP TABLE IF EXISTS `{$new}convert`;
CREATE TABLE `{$new}convert` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `job` tinytext NOT NULL,
  `joborder` tinyint(4) NOT NULL,
  `step` tinyint(4) NOT NULL,
  `job_description` tinytext NOT NULL,
  `step_function` tinytext NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `items` smallint(5) unsigned NOT NULL DEFAULT '0',
  `total` smallint(5) unsigned NOT NULL DEFAULT '0',
  `error` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} ;
EOF;
}


/* --------------------------------------------------------------------------------------------
* BAD BEHAVIOR *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['bad_behavior'] = <<<EOF
DROP TABLE IF EXISTS `{$new}bad_behavior`;
CREATE TABLE `{$new}bad_behavior` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  KEY `user_agent` (`user_agent`(10))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* CATEGORIES *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['categories'] = <<<EOF
DROP TABLE IF EXISTS `{$new}categories`;
CREATE TABLE `{$new}categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `parent_cid` int(11) NOT NULL DEFAULT '0',
  `category` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `leveldown` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL DEFAULT '0', -- can drop this ?
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
  `chapid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `chaptertext` mediumtext,
  `workingtext` mediumtext,
  `workingdate` timestamp NULL DEFAULT NULL,
  `endnotes` text,
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `wordcount` int(11) NOT NULL DEFAULT '0',
  `rating` tinyint(3) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
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
  `charid` int(11) NOT NULL,
  `catid` int(11) NOT NULL DEFAULT '0',
  `charname` tinytext NOT NULL,
  `biography` mediumtext NOT NULL,
  `image` tinytext NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`charid`), UNIQUE KEY `charname` (`charname`(64))
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
  `value` varchar(256) NOT NULL,
  `comment` tinytext,
  `admin_module` varchar(64) NOT NULL,
  `section_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `form_type` text NOT NULL,
  `to_config_file` tinyint(1) NOT NULL DEFAULT '1',
  `can_edit` tinyint(1) DEFAULT '1',
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
  `fid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reference` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `reference_sub` mediumint(9) unsigned DEFAULT NULL,
  `writer_name` varchar(60) DEFAULT NULL,
  `writer_uid` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `datetime` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `type` char(2) NOT NULL DEFAULT '',
  `moderation` int(11) DEFAULT NULL,
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
  `set_id` tinyint(4) unsigned NOT NULL,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) DEFAULT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `ip` int(11) unsigned DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(2) NOT NULL,
  `version` tinyint(1) NOT NULL,
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
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` int(2) NOT NULL,
  `link` varchar(256) DEFAULT NULL,
  `meta` varchar(128) DEFAULT NULL,
  `child_of` int(2) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
--NOTE--Page menu
--SPLIT--

DROP TABLE IF EXISTS `{$new}menu_adminpanel`;
CREATE TABLE `{$new}menu_adminpanel` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `label` tinytext NOT NULL COMMENT 'must match an ''AdminMenu_...'' entry in the language files!',
  `order` int(2) NOT NULL,
  `link` tinytext,
  `icon` varchar(64) DEFAULT '{ICON:blank}',
  `child_of` varchar(64) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `requires` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `evaluate` tinytext,
  PRIMARY KEY (`id`), KEY `child_of` (`child_of`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
--NOTE--Admin/moderation menu
--SPLIT--

DROP TABLE IF EXISTS `{$new}menu_userpanel`;
CREATE TABLE `{$new}menu_userpanel` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `label` tinytext NOT NULL,
  `order` int(2) NOT NULL,
  `link` tinytext,
  `icon` tinytext,
  `child_of` varchar(16) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `evaluate` tinytext DEFAULT NULL,
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
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender` int(10) unsigned NOT NULL,
  `recipient` int(10) unsigned NOT NULL,
  `date_sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_read` timestamp NULL DEFAULT NULL,
  `subject` tinytext NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFiction 5): new table for user messages';
EOF;


/* --------------------------------------------------------------------------------------------
* NEWS *
	requires: users
-------------------------------------------------------------------------------------------- */
$core['news'] = <<<EOF
DROP TABLE IF EXISTS `{$new}news`;
CREATE TABLE `{$new}news` (
  `nid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `headline` varchar(255) NOT NULL DEFAULT '',
  `newstext` text NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `comments` int(11) NOT NULL DEFAULT '0',
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
  `poll_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `option` int(11) NOT NULL DEFAULT '0',
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
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `rating` varchar(60) NOT NULL DEFAULT '',
  `rating_age` tinyint(2) NOT NULL DEFAULT '0',
  `rating_image` varchar(50) NULL DEFAULT NULL,
  `ratingwarning` tinyint(1) NOT NULL DEFAULT '0',
  `warningtext` text NOT NULL,
  PRIMARY KEY (`rid`),
  KEY `rating` (`rating`), KEY `rating_age` (`rating_age`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
* SERIES *
	requires: -
-------------------------------------------------------------------------------------------- */
$core['series'] = <<<EOF
DROP TABLE IF EXISTS `{$new}series`;
CREATE TABLE `{$new}series` (
  `seriesid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `open` tinyint(1) NOT NULL DEFAULT '0',
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `contests` varchar(200) NOT NULL DEFAULT '',
  `max_rating` tinytext NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  `words` int(10) unsigned DEFAULT NULL,
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
  `seriesid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `confirmed` int(11) NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
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
  `user` int(8) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastvisited` timestamp NOT NULL,
  `ip` int(12) unsigned NOT NULL DEFAULT '0',
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
  `guest_name` tinytext,
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
DROP TABLE IF EXISTS `{$new}stories`;
CREATE TABLE `{$new}stories` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `summary` text,
  `storynotes` text,
  `ratingid` tinyint(3) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `validated` char(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '-2 deleted, -1 draft, 0 w.i.p., 1 all done',
  `roundrobin` char(1) NOT NULL DEFAULT '0',
  `wordcount` int(11) NOT NULL DEFAULT '0',
  `ranking` tinyint(3) DEFAULT NULL COMMENT 'user rating, but name was ambigious with the age rating',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `chapters` smallint(6) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  `cache_authors` text,
  `cache_tags` text,
  `cache_characters` text,
  `cache_categories` text,
  `cache_rating` tinytext DEFAULT NULL,
  `moderation` int(11) DEFAULT NULL,
  PRIMARY KEY (`sid`), KEY `moderation` (`moderation`), KEY `title` (`title`), KEY `ratingid` (`ratingid`), KEY `completed` (`completed`), KEY `roundrobin` (`roundrobin`), KEY `validated` (`validated`), KEY `recent` (`updated`,`validated`)
 ) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--SPLIT--

DROP TABLE IF EXISTS `{$new}stories_authors`;
CREATE TABLE `{$new}stories_authors` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `aid` int(10) unsigned NOT NULL,
  `ca` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`lid`), KEY `relation` (`sid`,`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for story-author relations';
--NOTE--Story relation table: Authors
--SPLIT--

DROP TABLE IF EXISTS `{$new}stories_categories`;
CREATE TABLE `{$new}stories_categories` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lid`), UNIQUE KEY `relation` (`sid`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for story-category relations';
--NOTE--Story relation table: Categories
--SPLIT--

DROP TABLE IF EXISTS `{$new}featured`;
CREATE TABLE `{$new}featured` (
  `id` int(11) NOT NULL,
  `type` char(2) NOT NULL DEFAULT 'ST',
  `status` tinyint(1) DEFAULT NULL,
  `start` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  UNIQUE KEY `feature` (`id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for featured stories';
--NOTE--Story relation table: Featured
--SPLIT--

DROP TABLE IF EXISTS `{$new}stories_tags`;
CREATE TABLE `{$new}stories_tags` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `character` int(1) DEFAULT 0,
  PRIMARY KEY (`lid`), KEY `relation` (`sid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='(eFI5): new table for story-tag relations';
--NOTE--Story relation table: Tags
EOF;


/* --------------------------------------------------------------------------------------------
* TAGS *
	requires: tag_groups
-------------------------------------------------------------------------------------------- */
$core['tags'] = <<<EOF
DROP TABLE IF EXISTS `{$new}tags`;
CREATE TABLE `{$new}tags` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oldid` int(11) NOT NULL,
  `tgid` int(10) unsigned NOT NULL,
  `label` tinytext NOT NULL,
  `description` mediumtext NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`tid`), UNIQUE KEY `label` (`label`(64)), KEY `tgid` (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table';
--NOTE--Tag table
--SPLIT--

DROP TABLE IF EXISTS `{$new}tag_groups`;
CREATE TABLE `{$new}tag_groups` (
  `tgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `as_page` tinyint(1) NOT NULL DEFAULT 0,
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
  `sid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
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
  `groups` int(10) unsigned DEFAULT NULL,
  `curator` mediumint(8) unsigned DEFAULT NULL,
  `resettoken` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `about` mediumtext CHARACTER SET utf8 NULL,
  `moderation` int(11) DEFAULT NULL,
  `alert_feedback` tinyint(1) NOT NULL DEFAULT '0',
  `alert_comment` tinyint(1) NOT NULL DEFAULT '0',
  `alert_favourite` tinyint(1) NOT NULL DEFAULT '0',
  `preferences` text NOT NULL,
  PRIMARY KEY (`uid`), UNIQUE KEY `name1` (`login`), KEY `pass1` (`password`), KEY `moderation` (`moderation`), KEY `curator` (`curator`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table for users';
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_favourites`;
CREATE TABLE `{$new}user_favourites` (
  `fid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `item` int(11) NOT NULL DEFAULT '0',
  `type` char(2) NOT NULL DEFAULT '',
  `bookmark` BOOLEAN NOT NULL,
  `notify` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` tinyint(1) NOT NULL DEFAULT '2',
  `comments` text NOT NULL,
  PRIMARY KEY (`fid`), 
  UNIQUE KEY `byitem` (`item`,`type`,`bookmark`,`uid`) USING BTREE, 
  UNIQUE KEY `byuid` (`uid`,`type`,`bookmark`,`item`) USING BTREE,
  KEY `byuidlist` (`uid`,`type`,`bookmark`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--NOTE--User favourites
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_fields`;
CREATE TABLE `{$new}user_fields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_type` tinyint(4) NOT NULL DEFAULT '0',
  `field_name` varchar(30) NOT NULL DEFAULT ' ',
  `field_title` varchar(255) NOT NULL DEFAULT ' ',
  `field_options` text,
  `field_on` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--NOTE--User fields
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_info`;
CREATE TABLE `{$new}user_info` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `field` int(11) NOT NULL DEFAULT '0',
  `info` varchar(255) NOT NULL DEFAULT ' ',
  PRIMARY KEY (`uid`,`field`), KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
--NOTE--User info
--SPLIT--

DROP TABLE IF EXISTS `{$new}user_friends`;
CREATE TABLE `{$new}user_friends` (
  `link` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `friend` int(10) unsigned NOT NULL,
  `comment` varchar(512) NOT NULL,
  PRIMARY KEY (`link`), UNIQUE KEY `relation` (`uid`,`friend`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
--NOTE--User friends
EOF;


?>