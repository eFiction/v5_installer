<?php
/*
SQL convert from eFiction 3.5.x to 5.0.0
Optional tables (non-core)
*/

/*
	$optional['type'][ %name of module% ] = (int);
	
	1: Always install new table, copy data if old table exists (-> new core module)
	2: Probe for old data, create table and copy data if old table exists or is requested by user_error
	3: Install if requested by user

*/

$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
$characterset = $fw['installerCFG.charset'];

/* --------------------------------------------------------------------------------------------
																					* RECOMMENDATIONS *
requires: STORIES RELATION
-------------------------------------------------------------------------------------------- */
$probe['recommendations'] = "SELECT 1 FROM information_schema.tables WHERE table_schema = '{$fw['installerCFG.dbname']}' AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_recommendations'";

$optional['recommendations']['description'] = "Recommendations module";

$optional['recommendations']['type'] = 2;

$optional['recommendations']['steps'][] = array ( "recommendations", 0, "Recommendations, optional module" );

$optional['recommendations']['init'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}recommendations` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `author` varchar(200) NOT NULL,
  `summary` text,
  `comments` text,
  `uid` int(11) NOT NULL,
  `recname` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT 'broken',
  `catid` varchar(100) NOT NULL DEFAULT '0',
  `classes` varchar(200) NOT NULL DEFAULT '0',
  `charid` varchar(250) NOT NULL DEFAULT '0',
  `rid` varchar(25) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `featured` char(1) NOT NULL DEFAULT '0',
  `validated` char(1) NOT NULL DEFAULT '0',
  `completed` char(1) NOT NULL DEFAULT '0',
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`recid`),
  KEY `title` (`title`),
  KEY `catid` (`catid`),
  KEY `charid` (`charid`),
  KEY `rid` (`rid`),
  KEY `featured` (`featured`),
  KEY `validated` (`validated`),
  KEY `completed` (`completed`),
  KEY `classes` (`classes`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset};
EOF;

$optional['recommendations']['data'] = <<<EOF
INSERT INTO `{$new}recommendations`
	SELECT *
	FROM `{$old}recommendations`;--NOTECopy data
EOF;


/* --------------------------------------------------------------------------------------------
																								 * SHOUTBOX *
requires: -
-------------------------------------------------------------------------------------------- */
$probe['shoutbox'] = "SELECT 1 FROM information_schema.tables WHERE table_schema = '{$fw['installerCFG.dbname']}' AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_shoutbox'";

$optional['shoutbox']['description']  = "Shoutbox";

$optional['shoutbox']['type'] = 1;

$optional['shoutbox']['data'] = <<<EOF
INSERT INTO `{$new}shoutbox`
	( `id`, `uid`, `message`, `date` )
	SELECT
	`shout_id`, `shout_name`, `shout_message`, FROM_UNIXTIME(`shout_datestamp`) 
	FROM `{$old}shoutbox`;
EOF;


/* --------------------------------------------------------------------------------------------
																										  * POLL *
requires: -
-------------------------------------------------------------------------------------------- */
$probe['poll'] = "SELECT 1 FROM information_schema.tables WHERE table_schema = '{$fw['installerCFG.dbname']}' AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_poll'";

$optional['poll']['description']  = "Poll module";

$optional['poll']['type'] = 1;

$optional['poll']['data'] = <<<EOF
INSERT INTO `{$new}poll`
	SELECT *
	FROM `{$old}poll`;
--SPLIT--
INSERT INTO `{$new}poll_votes`
	SELECT *
	FROM `{$old}poll_votes`;
EOF;


/* --------------------------------------------------------------------------------------------
																					* TRACKER *
requires: -
-------------------------------------------------------------------------------------------- */
$probe['tracker'] = "SELECT 1 FROM information_schema.tables WHERE table_schema = '{$fw['installerCFG.dbname']}' AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_tracker'";

$optional['tracker']['description']  = "Track last read stories and chapters";

$optional['tracker']['type'] = 1;

$optional['tracker']['data'] = <<<EOF
INSERT INTO `{$new}tracker`
	SELECT *
	FROM `{$old}tracker`;--NOTECopy data
EOF;


/* --------------------------------------------------------------------------------------------
																					* CONTEST *
requires: STORY
-------------------------------------------------------------------------------------------- */
$probe['contest'] = "SELECT 1 FROM information_schema.tables WHERE table_schema = '{$fw['installerCFG.dbname']}' AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_challenges'";

$optional['contest']['description'] = "Contest module (previously named challenge)";

$optional['contest']['type'] = 2;

$optional['contest']['steps'][] = array ( "contest", 0, "Contest (former: challenges), optional module" );
$optional['contest']['steps'][] = array ( "contest_relation", 0, "Contest <-> Story relation" );

$optional['contest']['init'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}contest` (
  `chalid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `date_open` datetime DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `vote_closed` datetime DEFAULT NULL,
  `concealed` tinyint(1) NOT NULL,
  PRIMARY KEY (`chalid`),
  KEY `uid` (`uid`),
  KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for contests (aka challenges)';
EOF;

$optional['contest_relation']['init'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}contest_relation` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chalid` int(10) unsigned NOT NULL,
  `relid` int(10) unsigned NOT NULL,
  `type` set('category','tag','story','news') NOT NULL,
  PRIMARY KEY (`lid`),
  UNIQUE KEY `UNIQUE` (`relid`,`type`,`chalid`),
  KEY `chalid` (`chalid`),
  KEY `JOIN` (`relid`,`type`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for contest relations';
EOF;

$optional['contest']['data'] = <<<EOF
INSERT INTO `{$new}contest`
	( `chalid`, `uid`, `title`, `summary` )
	SELECT
	`chalid`, `uid`, `title`, `summary` 
	FROM `{$old}challenges`;
EOF;

$optional['contest_relation']['data'] = <<<EOF
INSERT INTO `{$new}contest_relation` ( `chalid`,`relid`, `type` )
	SELECT C.chalid,Cat.cid, 'category' as 'type'
		FROM `{$old}challenges`C
		INNER JOIN `{$new}categories`Cat ON (FIND_IN_SET(Cat.cid,C.catid));
--SPLIT--
INSERT INTO `{$new}contest_relation` ( `chalid`,`relid`, `type` )
	SELECT C.chalid,T.tid, 'tag' as 'type'
		FROM `{$old}challenges`C
		INNER JOIN `{$new}tags`T ON (FIND_IN_SET(T.oldid,C.characters));
--SPLIT--
INSERT INTO `{$new}contest_relation` (`chalid`, `relid`, `type`) 
		SELECT C.chalid,S.sid as `relid`,'story' AS `type` 
			FROM `{$old}challenges`C
			INNER JOIN `{$old}stories`S ON (FIND_IN_SET(C.chalid, S.challenges));
EOF;

?>