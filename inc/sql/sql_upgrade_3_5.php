<?php
/*
SQL convert from eFiction 3.5.x to 5.0.0
*/

$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
$path = "upgrade";

$chapterLocation =   ( $fw['installerCFG.chapters']=="filebase" ) ? "local" : "db";
$characterset = $fw['installerCFG.charset'];
$review_split = "<br><br><i>";

$init = array
(
	"info"	=>	"Create empty tables",
	"steps" => array (
								array ( "bad_behavior", 0, "Bad Behavior 2" ),
								array ( "chapters", 0, "Chapters" ),
								array ( "config", 0, "Page config" ),
								array ( "convert", 0, "eFiction conversion table" ),
								array ( "layout", 0, "Layout" ),
								array ( "iconsets", 0, "Layout: Iconset" ),
								array ( "menu", 0, "Page menus" ),
								array ( "menu_adminpanel", 0, "Admin panel menu" ), // meta entry
								array ( "menu_userpanel", 0, "User panel menu" ), // meta entry
								array ( "textblocks", 0, "Textblocks (former: messages)" ),
								array ( "tag_groups", 0, "Tag groups" ),
								array ( "tags", 0, "Tags" ),
								array ( "categories", 0, "Categories" ),
								array ( "stories_authors", 0, "Story relation table: authors" ),
								array ( "stories_categories", 0, "Story relation table: categories" ),
								array ( "stories_tags", 0, "Story relation table: tags" ),
								array ( "stories", 0, "Stories" ),
								array ( "ratings", 0, "Ratings" ),
								array ( "series", 0, "Series" ),
								array ( "series_stories", 0, "Stories in Series" ),
								array ( "users", 0, "Users" ),
								array ( "user_fields", 0, "User fields" ),
								array ( "user_info", 0, "User info" ),
								array ( "user_favourites", 0, "User favourites" ),
								array ( "user_friends", 0, "User friends" ),
								array ( "messaging", 0, "Messaging (new feature)" ),
								array ( "news", 0, "News" ),
								array ( "shoutbox", 0, "Shoutbox" ),
								array ( "sessions", 0, "Session" ),
								array ( "feedback", 0, "Feedback (former: reviews & comments)" ),
								array ( "poll", 0, "Polls" ),
								array ( "poll_votes", 0, "Poll votes" ),
								array ( "tracker", 0, "Story tracker (new feature)" ),
								array ( "stories_blockcache", 0, "Story cache" ),
								array ( "series_blockcache", 0, "Series cache" ),
								array ( "categories_statcache", 0, "Categories stats cache" ),
								array ( "stats_cache", 0, "Page stats cache" ),
									),
);
$_SESSION['skipped'] = array();

/* --------------------------------------------------------------------------------------------
																																										* CONVERT *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['convert'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}convert` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `job` tinytext NOT NULL,
  `joborder` tinyint(4) NOT NULL,
  `step` tinyint(4) NOT NULL,
  `job_description` tinytext NOT NULL,
  `step_description` tinytext NOT NULL,
  `code` mediumtext NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `items` smallint(5) unsigned NOT NULL DEFAULT '0',
  `error` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} ;
EOF;

/* --------------------------------------------------------------------------------------------
																									* CONFIG *
																									* USER MENU *
																									* ADMIN MENU *
																									* MENU *
																									* ICONSET *
																									* BAD BEHAVIOR *
																									* LAYOUT *
requires: -
-------------------------------------------------------------------------------------------- */
require_once('shard_00_core.php');


/* --------------------------------------------------------------------------------------------
																																									* TEXTBLOCKS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Copy messages, new table 'textblocks'",
	"steps" => array (
								array ( "textblocks", 0, "Copy data" ),
								array ( "textblocks", 1, "Mark the old admin block area as block_only" ),
								array ( "textblocks", 2, "Add a registration page" ),
									),
);

$sql['init']['textblocks'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}textblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `as_page` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `label` (`label`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['textblocks'] = <<<EOF
INSERT INTO `{$new}textblocks` ( `id`, `label`, `title`, `content`, `as_page` )
	SELECT message_id, message_name, message_title, message_text, 1
	FROM `{$old}messages`;
--SPLIT--
UPDATE `{$new}textblocks`T SET T.as_page=0 WHERE T.id IN(1,2,4,5,7,9);
--SPLIT--
INSERT INTO `{$new}textblocks` (`label`, `title`, `content`, `as_page`) VALUES
('registration', '__Registration', 'By registering, you consent to the following rules: No BS-ing!', 0);
EOF;

/* --------------------------------------------------------------------------------------------
																																								 * TAGS GROUPS*
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Tag groups and tags",
	"steps" => array (
								array ( "tag_groups", 0, "Import tag groups from class types" ),
								array ( "tag_groups", 1, "Add tag group 'character'" ),
								array ( "tags", 0, "Import tags from classes" ),
								array ( "tags", 1, "Import tags from characters" ),
									),
);

$sql['init']['tag_groups'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tag_groups` (
  `tgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `description` mediumtext,
  PRIMARY KEY (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table, eFiction 5';
EOF;

$sql['data']['tag_groups'] = <<<EOF
INSERT INTO `{$new}tag_groups` ( tgid, label, description )
	SELECT T.classtype_id, T.classtype_name, T.classtype_title
	FROM `{$old}classtypes` T;
--SPLIT--
INSERT INTO `{$new}tag_groups` ( label, description )
	VALUES ('characters', 'Character'); 
EOF;

/* --------------------------------------------------------------------------------------------
																																											 * TAGS *
requires: tag_groups
-------------------------------------------------------------------------------------------- */
$sql['init']['tags'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tags` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oldid` int(11) NOT NULL,
  `tgid` int(10) unsigned NOT NULL,
  `label` tinytext NOT NULL,
  `description` mediumtext NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`tid`), UNIQUE KEY `label` (`label`(64)), KEY `tgid` (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table';
EOF;

$sql['data']['tags'] = <<<EOF
INSERT INTO `{$new}tags` ( tid, tgid, label )
 SELECT C.class_id, C.class_type, C.class_name
   FROM  `{$old}classes` C;
 --SPLIT--
INSERT INTO `{$new}tags` ( oldid, tgid, label )
	SELECT C.charid, TG.tgid, C.charname
		FROM  `{$old}characters` C
		LEFT JOIN  `{$new}tag_groups` TG ON ( TG.label =  'characters' );
EOF;

/* --------------------------------------------------------------------------------------------
																																									* CATEGORIES *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story categories (e.g. fandoms, season)",
	"steps" => array (
								array ( "categories", 0, "Copy categories" ),
									),
);

$sql['init']['categories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `parent_cid` int(11) NOT NULL DEFAULT '0',
  `category` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `leveldown` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  `stats` text NOT NULL,
  PRIMARY KEY (`cid`), KEY `byparent` (`parent_cid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFI5): derived from _categories';
EOF;


//$sql['init']['categories_statcache'] = "SELECT 1;--NOTESeries Cache - empty table created";
$sql['data']['categories_statcache'] = "SELECT 2;--NOTECategory stats cache";

$sql['probe']['categories'] = "SELECT 1 FROM `{$new}categories`C INNER JOIN (SELECT leveldown FROM `{$new}categories` WHERE `stats` = '' ORDER BY leveldown DESC LIMIT 0,1) c2 ON ( C.leveldown = c2.leveldown )";

$sql['proc']['categories'] = "SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories, GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
	FROM `efiction_dev_a0011`.`efi6_categories`C 
	INNER JOIN (SELECT leveldown FROM `efiction_dev_a0011`.`efi6_categories` WHERE `stats` = '' ORDER BY leveldown DESC LIMIT 0,1) c2 ON ( C.leveldown = c2.leveldown )
	LEFT JOIN `efiction_dev_a0011`.`efi6_stories_categories`SC ON ( C.cid = SC.cid )
	LEFT JOIN `efiction_dev_a0011`.`efi6_stories`S ON ( S.sid = SC.sid )
	LEFT JOIN `efiction_dev_a0011`.`efi6_categories`C1 ON ( C.cid = C1.parent_cid )
GROUP BY C.cid";



$sql['data']['categories'] = <<<EOF
INSERT INTO `{$new}categories`
	( `cid`, `parent_cid`, `category`, `description`, `image`, `locked`, `leveldown`, `inorder`, `count` )
	SELECT C.catid, C.parentcatid, C.category, C.description, C.image, C.locked, C.leveldown, C.displayorder, C.numitems 
	FROM `{$old}categories`C;
--SPLIT--
UPDATE `{$new}categories` SET `parent_cid`= 0 WHERE `parent_cid`= '-1';
EOF;

/* --------------------------------------------------------------------------------------------
																																						* STORIES AUTHORS *
requires: tags, categories
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story relation table",
	"steps" => array (
								array ( "stories_authors", 0, "Story <-> Author relations" ),
								array ( "stories_authors", 1, "Story <-> Co-Author relations" ),
								array ( "stories_authors", 2, "Cleanup" ),
									),
);

$sql['init']['stories_authors'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_authors` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `aid` int(10) unsigned NOT NULL,
  `ca` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`lid`), KEY `relation` (`sid`,`aid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for story-author relations';
EOF;

$sql['data']['stories_authors'] = <<<EOF
INSERT INTO `{$new}stories_authors` (`sid`, `aid`)
	SELECT S.sid,S.uid
		FROM `{$old}stories`S ORDER BY S.sid ASC;
--SPLIT--
INSERT INTO `{$new}stories_authors` (`sid`, `aid`, `ca`)
	SELECT Ca.sid,Ca.uid, 1 as 'ca'
		FROM `{$old}coauthors`Ca ORDER BY Ca.sid ASC;
--SPLIT--
DELETE S1 
FROM `{$new}stories_authors`S1 
INNER JOIN `{$new}stories_authors`S2 ON ( S1.sid=S2.sid AND S1.aid = S2.aid AND S1.lid < S2.lid )
EOF;

/* --------------------------------------------------------------------------------------------
																																					* STORIES CATEGORIES *
requires: tags, categories
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story relation table",
	"steps" => array (
								array ( "stories_categories", 1, "Story <-> category relations" ),
									),
);

$sql['init']['stories_categories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_categories` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lid`), UNIQUE KEY `relation` (`sid`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for story-category relations';
EOF;

$sql['data']['stories_categories'] = <<<EOF
INSERT INTO `{$new}stories_categories` ( `sid`,`cid` )
	SELECT S.sid,C.cid
		FROM `{$old}stories`S
		INNER JOIN `{$new}categories`C ON (FIND_IN_SET(C.cid,S.catid)>0);
EOF;

/* --------------------------------------------------------------------------------------------
																																							 * STORIES TAGS *
requires: tags, categories
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story relation table",
	"steps" => array (
								array ( "stories_tags", 1, "Story <-> Tags relations (from classes)" ),
								array ( "stories_tags", 2, "Story <-> Tags relations (from characters)" ),
								array ( "stories_tags", 3, "Recount tags" ),
									),
);

$sql['init']['stories_tags'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_tags` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lid`), UNIQUE KEY `relation` (`sid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for story-tag relations';
EOF;

$sql['data']['stories_tags'] = <<<EOF
INSERT INTO `{$new}stories_tags` ( `sid`,`tid` )
	SELECT S.sid,T.tid
		FROM `{$old}stories`S
		INNER JOIN `{$new}tags` T ON (FIND_IN_SET(T.tid,S.classes)>0);--NOTEStory <-> Tags relations (from classes)
--SPLIT--
INSERT INTO `{$new}stories_tags` ( `sid`,`tid` )
	SELECT S.sid,T.tid
		FROM `{$old}stories`S
		INNER JOIN `{$new}tags`T ON (FIND_IN_SET(T.oldid,S.charid)>0);--NOTEStory <-> Tags relations (from characters)
--SPLIT--
--LOOP
UPDATE `{$new}tags` T1 
LEFT JOIN
(
	SELECT T.tid, COUNT( DISTINCT RT.sid ) AS counter 
	FROM `{$new}tags`T 
	LEFT JOIN `{$new}stories_tags`RT ON (RT.tid = T.tid )
		WHERE T.count IS NULL
		GROUP BY T.tid
		LIMIT 0,25
) AS T2 ON T1.tid = T2.tid
SET T1.count = T2.counter WHERE T1.tid = T2.tid--NOTERecount tags
EOF;

/* --------------------------------------------------------------------------------------------
																																										* STORIES *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story table",
	"steps" => array (
								array ( "stories", 0, "Import story information" ),
									),
);

$sql['init']['stories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `summary` text,
  `storynotes` text,
  `rid` varchar(25) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `print_cache` tinyint(1) NOT NULL DEFAULT '0',
  `featured` char(1) NOT NULL DEFAULT '0',
  `validated` char(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '-2 deleted, -1 draft, 0 w.i.p., 1 all done',
  `rr` char(1) NOT NULL DEFAULT '0',
  `wordcount` int(11) NOT NULL DEFAULT '0',
	`ranking` tinyint(3) DEFAULT NULL COMMENT 'user rating, but name was ambigious with the age rating',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`), KEY `title` (`title`), KEY `rid` (`rid`), KEY `featured` (`featured`), KEY `completed` (`completed`), KEY `rr` (`rr`), KEY `validateduid` (`validated`), KEY `recent` (`updated`,`validated`)
 ) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['stories'] = <<<EOF
INSERT INTO `{$new}stories`
	( `sid`, `title`, `summary`, `storynotes`, `rid`, `date`, `updated`, `featured`, `validated`, `completed`, `rr`, `wordcount`, `ranking`, 														`reviews`, `count` )
	SELECT
		S.sid, S.title, S.summary, S.storynotes, S.rid, S.date, S.updated, S.featured, S.validated, S.completed, S.rr, S.wordcount, (10*SUM(R.rating)/COUNT(R.reviewid)), S.reviews, S.count
	FROM `{$old}stories`S
		LEFT JOIN `{$old}reviews`R ON ( S.sid = R.item AND R.rating > 0 )
	GROUP BY S.sid
	ORDER BY S.sid ASC
EOF;

/* --------------------------------------------------------------------------------------------
																																										* RATINGS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story ratings",
	"steps" => array (
								array ( "ratings", 0, "Copy data" ),
									),
);

$sql['init']['ratings'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}ratings` (
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `rating` varchar(60) NOT NULL DEFAULT '',
  `rating_image` varchar(50) NULL DEFAULT NULL,
  `ratingwarning` tinyint(1) NOT NULL DEFAULT '0',
  `warningtext` text NOT NULL,
  PRIMARY KEY (`rid`),
  KEY `rating` (`rating`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['ratings'] = <<<EOF
INSERT INTO `{$new}ratings` ( `rid`, `rating`, `ratingwarning`, `warningtext` )
	SELECT `rid`, `rating`, `ratingwarning`, `warningtext`
	FROM `{$old}ratings`;
EOF;

/* --------------------------------------------------------------------------------------------
																								 * CHAPTERS *
requires: -
-------------------------------------------------------------------------------------------- */
require_once('shard_chapters.php');

/* --------------------------------------------------------------------------------------------
																																										 * SERIES *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Series",
	"steps" => array (
								array ( "series", 0, "Copy data" ),
								array ( "series_stories", 0, "Copy story <-> series relations" ),
									),
);

$sql['init']['series'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series` (
  `seriesid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `isopen` tinyint(4) NOT NULL DEFAULT '0',
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `challenges` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`seriesid`),
  KEY `owner` (`uid`,`title`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['series'] = <<<EOF
INSERT INTO `{$new}series`
	(`seriesid`, `title`, `summary`, `uid`, `isopen`, `rating`, `reviews`, `challenges` )
	SELECT
	`seriesid`, `title`, `summary`, `uid`, `isopen`, `rating`, `reviews`, `challenges`
	FROM `{$old}series`;
EOF;

/* --------------------------------------------------------------------------------------------
																																						* SERIES RELATION *
requires: SERIES
-------------------------------------------------------------------------------------------- */
$sql['init']['series_stories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series_stories` (
  `seriesid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `confirmed` int(11) NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`,`seriesid`),
  KEY `seriesid` (`seriesid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['series_stories'] = <<<EOF
INSERT INTO `{$new}series_stories`
	( `seriesid`, `sid`, `confirmed`, `inorder` )
	SELECT
	`seriesid`, `sid`, `confirmed`, `inorder`
	FROM `{$old}inseries`;
EOF;



/* --------------------------------------------------------------------------------------------
																																											 * USER *
requires: STORIES RELATION
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Users",
	"steps" => array (
								array ( "users", 0, "Create guest entry" ),
								array ( "users", 1, "Copy users, mark admins" ),
								array ( "users", 2, "Mark validated users" ),
								array ( "users", 3, "Mark authors" ),
								array ( "users", 4, "Set remaining users to active" ),
									),
);

$sql['init']['users'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}users` (
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
  PRIMARY KEY (`uid`), UNIQUE KEY `name1` (`login`), KEY `pass1` (`password`), KEY `curator` (`curator`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table for users';
EOF;

$sql['data']['users'] = <<<EOF
INSERT INTO `{$new}users` (`login`, `nickname`, `realname`, `password`, `email`, `registered` ) VALUES
('Guest', 'Guest', '', '', '', '0000-00-00 00:00:00');--NOTEAdd guest entry
--SPLIT--
UPDATE `{$new}users` SET `uid` = '0';--NOTEAdjust Guest ID
--SPLIT--
INSERT INTO `{$new}users`
	( uid, login, nickname, realname, password, email, registered, groups, `about` )
	SELECT
		A.uid, A.penname, A.penname, A.realname, IF(CAST(A.password AS CHAR(32))='0',NULL,A.password), replace(A.email , ' ',''), A.date, 
		(SELECT 255 FROM `{$old}authorprefs` P where P.level = 1 AND P.uid = A.uid), A.bio
	FROM `{$old}authors`A ORDER BY A.date ASC;--NOTECopy users (formerly called authors)
--SPLIT--
UPDATE `{$new}users`U
	INNER JOIN `{$old}authorprefs`AP ON ( U.uid=AP.uid ) 
	SET U.groups = 13 
	WHERE U.groups IS NULL AND AP.validated=1 AND U.uid > 0;--NOTEAssign `trusted author` group to validated authors
--SPLIT--
UPDATE `{$new}users`U 
	INNER JOIN `{$new}stories_authors` R1 ON ( R1.aid = U.uid  ) 
	SET U.groups = 5
	WHERE U.groups IS NULL AND U.uid > 0;--NOTEAssign `author`to all users with at least one story
--SPLIT--
UPDATE `{$new}users`U
	SET groups = 1 WHERE U.groups IS NULL AND U.uid > 0;--NOTESet remaining users as `active`
EOF;

/* --------------------------------------------------------------------------------------------
																																								* USER FIELDS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"User details",
	"steps" => array (
								array ( "user_fields", 0, "Copy user fields" ),
								array ( "user_info", 0, "Copy user info" ),
								array ( "user_favourites", 0, "Copy favourites" ),
									),
);

$sql['init']['user_fields'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_fields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_type` tinyint(4) NOT NULL DEFAULT '0',
  `field_name` varchar(30) NOT NULL DEFAULT ' ',
  `field_title` varchar(255) NOT NULL DEFAULT ' ',
  `field_options` text,
  `field_code_in` text,
  `field_code_out` text,
  `field_on` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['user_fields'] = <<<EOF
INSERT INTO `{$new}user_fields`
	SELECT * 
	FROM `{$old}authorfields`;
--SPLIT--
INSERT INTO `{$new}user_fields` ( field_type, field_name, field_title ) VALUES ( 2, 'avatar', 'Avatar' );
EOF;

/* --------------------------------------------------------------------------------------------
																																									* USER INFO *
requires: user_fields
-------------------------------------------------------------------------------------------- */
$sql['init']['user_info'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_info` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `field` int(11) NOT NULL DEFAULT '0',
  `info` varchar(255) NOT NULL DEFAULT ' ',
  PRIMARY KEY (`uid`,`field`), KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;
 
$sql['data']['user_info'] = <<<EOF
INSERT INTO `{$new}user_info`
	SELECT Ai.uid, Ai.field, Ai.info
	FROM `{$old}authorinfo`Ai
		INNER JOIN `{$new}user_fields`Uf ON (Ai.field=Uf.field_id)
	ORDER BY Ai.uid, Ai.field ASC;
--SPLIT--
INSERT INTO `{$new}user_info`
	SELECT A.uid, F.field_id, A.image 
		FROM `{$old}authors`A
			LEFT JOIN `{$new}user_fields`F ON ( F.field_name = 'avatar' )
	WHERE A.image NOT LIKE '';
EOF;

/* --------------------------------------------------------------------------------------------
																																						* USER_FAVOURITES *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['user_favourites'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_favourites` (
  `fid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `item` int(11) NOT NULL DEFAULT '0',
  `type` char(2) NOT NULL DEFAULT '',
  `notify` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` tinyint(1) NOT NULL DEFAULT '2',
  `comments` text NOT NULL,
  PRIMARY KEY (`fid`), UNIQUE KEY `byitem` (`item`,`type`,`uid`), UNIQUE KEY `byuid` (`uid`,`type`,`item`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['user_favourites'] = <<<EOF
INSERT INTO `{$new}user_favourites`
	( `uid`, `item`, `type`, `comments` )
	SELECT * FROM `{$old}favorites`;
EOF;

/* --------------------------------------------------------------------------------------------
																																							 * USER_FRIENDS *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['user_friends'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_friends` (
  `link` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `friend` int(10) unsigned NOT NULL,
  `comment` varchar(512) NOT NULL,
  PRIMARY KEY (`link`), UNIQUE KEY `relation` (`uid`,`friend`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																									* MESSAGING *
requires: users
-------------------------------------------------------------------------------------------- */
$sql['init']['messaging'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}messaging` (
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
$steps[] = array
(
	"info"	=>	"Page news",
	"steps" => array (
								array ( "news", 0, "Copy data" ),
									),
);

$sql['init']['news'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}news` (
  `nid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `headline` varchar(255) NOT NULL DEFAULT '',
  `newstext` text NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `comments` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nid`), KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['news'] = <<<EOF
INSERT INTO `{$new}news`
	( `nid`, `uid`, `headline`, `newstext`, `datetime`, `comments` )
	SELECT
		N.nid, U.uid, N.title, N.story, N.time, N.comments
	FROM `{$old}news`N
		LEFT JOIN `{$new}users`U ON ( N.author = U.login )
	ORDER BY N.time ASC;
EOF;

/* --------------------------------------------------------------------------------------------
																																									 * SHOUTBOX *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Shoutbox",
	"steps" => array (
								array ( "shoutbox", 0, "Copy data" ),
									),
);

$sql['init']['shoutbox'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}shoutbox` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(5) NOT NULL DEFAULT 0,
  `guest_name` tinytext,
  `message` varchar(200) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
																																										* SESSION *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['sessions'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}sessions` (
  `session` char(32) NOT NULL,
  `user` int(8) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastvisited` timestamp NOT NULL,
  `ip` int(12) unsigned NOT NULL DEFAULT '0',
  `admin` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session`),
  KEY `user` (`user`),
  KEY `session` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for sessions';
EOF;


/* --------------------------------------------------------------------------------------------
																																									 * FEEDBACK *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Feedback (reviews and comments)",
	"steps" => array (
								array ( "feedback", 0, "Create temporary table" ),
								array ( "feedback", 1, "Copy data to temporary table" ),
								array ( "feedback", 2, "Get reviews" ),
								array ( "feedback", 3, "Get the replies to above reviews, link to story author" ),
								array ( "feedback", 4, "Get news comments" ),
								array ( "feedback", 5, "Drop temporary table" ),
									),
);

$sql['init']['feedback'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}feedback` (
  `fid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reference` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `reference_sub` mediumint(9) unsigned DEFAULT NULL,
  `writer_name` varchar(60) DEFAULT NULL,
  `writer_uid` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `datetime` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `type` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`fid`), KEY `sub_ref` (`reference_sub`), KEY `by_uid` (`writer_uid`,`reference`,`type`), KEY `alias_story_chapter` (`reference`,`reference_sub`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['feedback'] = <<<EOF
INSERT INTO `{$new}feedback`
	(`fid`, `reference`, `reference_sub`, `writer_name`, `writer_uid`, `text`, `datetime`, `rating`, `type`)
	SELECT
		reviewid, 
		item, 
		chapid, 
		IF(uid=0,reviewer,NULL) as reviewer, 
		uid, 
		SUBSTRING_INDEX(review, '{$review_split}', 1), 
		date, 
		rating, 
		type 
	FROM `{$old}reviews` Rv ORDER BY Rv.reviewid;--NOTECopy Reviews
--SPLIT--
INSERT INTO `{$new}feedback`
	(`reference`, `reference_sub`, `writer_uid`, `text`, `type`)
	SELECT
		reviewid, 
		NULL, 
		U.uid,
		TRIM(TRAILING '</i>' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(review, '{$review_split}', -1), ': ', -1) ),
		'C'
	FROM `{$old}reviews` Rv 
		LEFT JOIN `{$new}stories_authors`SR ON ( Rv.item = SR.sid AND SR.ca=0 )
			LEFT JOIN `{$new}users`U ON ( SR.aid = U.uid )
	WHERE LOCATE('{$review_split}', Rv.review) > 0 ORDER BY Rv.date;--NOTECopy replies to reviews
--SPLIT--
INSERT INTO `{$new}feedback`
	( `reference`, `writer_uid`, `text`, `datetime`, `type` )
	SELECT
		C.nid, C.uid, C.comment, C.time, 'N'
	FROM `{$old}comments`C;--NOTECopy news comments
EOF;

/* --------------------------------------------------------------------------------------------
																																											 * POLL *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Polls and votes",
	"steps" => array (
								array ( "poll", 0, "Copy poll data" ),
								array ( "poll", 1, "Copy votes" ),
									),
);

$sql['init']['poll'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}poll` (
  `poll_id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_question` varchar(250) NOT NULL,
  `poll_opts` text NOT NULL,
  `poll_start` datetime NOT NULL,
  `poll_end` datetime DEFAULT NULL,
  `poll_results` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`poll_id`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
--SPLIT--
CREATE TABLE IF NOT EXISTS `{$new}poll_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `vote_user` int(11) NOT NULL DEFAULT '0',
  `vote_opt` int(11) NOT NULL DEFAULT '0',
  `vote_poll` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`),
  KEY `vote_user` (`vote_user`,`vote_poll`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																										* TRACKER *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['tracker'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tracker` (
  `sid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `last_chapter` smallint(5) unsigned NOT NULL,
  `last_read` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`uid`), KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
EOF;

/* --------------------------------------------------------------------------------------------
																																											* CACHE *
requires: *
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story cache",
	"steps" => array (
								array ( "stories_blockcache", 0, "Caching stats" ),
									),
);

$sql['init']['stories_blockcache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_blockcache` (
  `sid` int(11) unsigned NOT NULL,
  `tagblock` text,
  `authorblock` text,
  `categoryblock` text,
  `rating` tinytext NOT NULL,
  `reviews` smallint(5) unsigned NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['stories_blockcache'] = "SELECT 1;--NOTEStory Cache - empty table created";

$sql['probe']['stories_blockcache'] = "SELECT S.sid,C.sid from `{$new}stories`S LEFT JOIN `{$new}stories_blockcache`C ON S.sid = C.sid WHERE C.sid IS NULL LIMIT 0,1";

$sql['proc']['stories_blockcache'] = <<<EOF
SELECT SELECT_OUTER.sid,
GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
GROUP_CONCAT(DISTINCT uid,',',nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
GROUP_CONCAT(DISTINCT rid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating,
COUNT(DISTINCT fid) AS reviews,
COUNT(DISTINCT chapid) AS chapters
FROM
(
	SELECT S.sid,C.chapid,UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
		F.fid,
		S.rid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
		U.uid, U.nickname,
		Cat.cid, Cat.category,
		TG.description,TG.order,TG.tgid,T.label as tag,T.tid 
		FROM
		(
			SELECT S1.*
			FROM `{$new}stories` S1
			LEFT JOIN `{$new}stories_blockcache` B ON ( B.sid = S1.sid )
			WHERE B.sid IS NULL
			LIMIT 0,50
		) AS S
		LEFT JOIN `{$new}ratings` Ra ON ( Ra.rid = S.rid )
		LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
			LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
		LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
			LEFT JOIN `{$new}tags` T ON ( T.tid = rST.tid )
				LEFT JOIN `{$new}tag_groups` TG ON ( TG.tgid = T.tgid )
		LEFT JOIN `{$new}stories_categories`rSC ON ( rSC.sid = S.sid )
			LEFT JOIN `{$new}categories` Cat ON ( rSC.cid = Cat.cid )
		LEFT JOIN `{$new}chapters` C ON ( C.sid = S.sid )
		LEFT JOIN `{$new}feedback` F ON ( F.reference = S.sid AND F.type='ST' )
)AS SELECT_OUTER
GROUP BY sid ORDER BY sid ASC
EOF;

$sql['init']['series_blockcache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series_blockcache` (
  `seriesid` mediumint(8) unsigned NOT NULL,
  `tagblock` text,
  `authorblock` text,
  `categoryblock` text,
  `max_rating` tinytext NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  `words` int(10) unsigned NOT NULL,
  PRIMARY KEY (`seriesid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['series_blockcache'] = "SELECT 1;--NOTESeries Cache - empty table created";

$sql['probe']['series_blockcache'] = "SELECT S.seriesid,C.seriesid from `{$new}series`S LEFT JOIN `{$new}series_blockcache`C ON S.seriesid = C.seriesid WHERE C.seriesid IS NULL LIMIT 0,1";

$sql['proc']['series_blockcache'] = <<<EOF
SELECT 
	SERIES.seriesid, 
	SERIES.tagblock, 
	SERIES.authorblock, 
	SERIES.categoryblock, 
	CONCAT(rating,'||',max_rating_id) as max_rating, 
	chapter_count, 
	word_count
FROM
(
SELECT 
Ser.seriesid,
MAX(Ra.rid) as max_rating_id,
			GROUP_CONCAT(DISTINCT U.uid,',',U.nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
			GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
			GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock,
			COUNT(DISTINCT Ch.chapid) as chapter_count, SUM(Ch.wordcount) as word_count
		FROM 
		(
			SELECT Ser1.seriesid
				FROM `{$new}series`Ser1
				LEFT JOIN `{$new}series_blockcache`B ON ( B.seriesid = Ser1.seriesid )
				WHERE B.seriesid IS NULL
				LIMIT 0,5
		) AS Ser
			LEFT JOIN `{$new}series_stories`TrS ON ( Ser.seriesid = TrS.seriesid )
				LEFT JOIN `{$new}stories`S ON ( TrS.sid = S.sid )
					LEFT JOIN `{$new}chapters`Ch ON ( Ch.sid = S.sid )
					LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
						LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
			LEFT JOIN `{$new}ratings`Ra ON ( Ra.rid = S.rid )
			LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
				LEFT JOIN `{$new}tags`T ON ( T.tid = rST.tid )
					LEFT JOIN `{$new}tag_groups`TG ON ( TG.tgid = T.tgid )
			LEFT JOIN `{$new}stories_categories`rSC ON ( rSC.sid = S.sid )
				LEFT JOIN `{$new}categories`C ON ( rSC.cid = C.cid )
		GROUP BY Ser.seriesid
) AS SERIES
LEFT JOIN `{$new}ratings`R ON (R.rid = max_rating_id);
EOF;

/* --------------------------------------------------------------------------------------------
																																								* STATS CACHE *
requires: *
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Page stats cache table",
	"steps" => array (
								array ( "stats_cache", 0, "Users" ),
								array ( "stats_cache", 1, "Authors" ),
								array ( "stats_cache", 2, "Reviews" ),
								array ( "stats_cache", 3, "Stories" ),
								array ( "stats_cache", 4, "Chapters" ),
								array ( "stats_cache", 5, "Words" ),
									),
);

// numeric-only table, so CHARSET doesnt't matter much
$sql['init']['stats_cache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stats_cache` (
  `field` varchar(32) NOT NULL,
  `value` int(10) unsigned NOT NULL,
  `name` tinytext DEFAULT NULL,
  UNIQUE KEY `field` (`field`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['stats_cache'] = <<<EOF
INSERT INTO `{$new}stats_cache`
	SELECT 'users', COUNT(1), NULL FROM `{$new}users`U WHERE U.groups > 0;
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'authors', COUNT(1), NULL FROM `{$new}users`U WHERE ( U.groups & 4 );
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'reviews', COUNT(1), NULL FROM `{$new}feedback`F WHERE F.type='ST';
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'stories', COUNT(DISTINCT sid), NULL FROM `{$new}stories`S WHERE S.validated > 0;
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'chapters', COUNT(DISTINCT chapid), NULL FROM `{$new}chapters`C INNER JOIN `{$new}stories`S ON ( C.sid=S.sid AND S.validated > 0 );
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'words', SUM(C.wordcount), NULL FROM `{$new}chapters`C INNER JOIN `{$new}stories`S ON ( C.sid=S.sid AND S.validated > 0 );
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'newmember', 0, CONCAT_WS(',', U.uid, U.nickname) FROM `{$new}users`U WHERE U.groups>0 ORDER BY U.registered DESC LIMIT 1;
EOF;

?>