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

$optional['recommendations'] = array
(
	"description"	=>	"Recommendations module",
	"type"			=>	2,
	"steps"			=>	array (
							array ( "recommendations", 0, "Recommendations, optional module" ),
							array ( "recommend_tags", 0, "Recommendation <-> Tags relations" ),
							array ( "recommend_cache", 0, "Recommendation cache" ),
						),
);

/*
$optional['recommendations']['description'] = "Recommendations module";

$optional['recommendations']['type'] = 2;

$optional['recommendations']['steps'][]  = array ( "recommendations", 0, "Recommendations, optional module" );
*/

$optional['recommendations']['init'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}recommendations` (
  `recid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT 'broken',
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `author` varchar(200) NOT NULL,
  `summary` text,
  `comment` text,
  `catid` varchar(100) NOT NULL DEFAULT '0',
  `ratingid` varchar(25) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `validated` char(1) NOT NULL DEFAULT '0',
  `completed` char(1) NOT NULL DEFAULT '0',
  `ranking` tinyint(3) DEFAULT NULL COMMENT 'user rating, but name was ambigious with the age rating',
  `reviews` smallint(6) DEFAULT NULL,
  `cache_tags` text,
  `cache_characters` text,
  `cache_categories` text,
  `cache_rating` tinytext NOT NULL,
  PRIMARY KEY (`recid`),
  KEY `title` (`title`),
  KEY `catid` (`catid`),
  KEY `validated` (`validated`),
  KEY `completed` (`completed`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset};
EOF;

$optional['recommend_tags']['init'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}recommend_tags` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recid` int(10) NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `character` int(1) DEFAULT 0,
  PRIMARY KEY (`lid`), KEY `relation` (`recid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for recommendation-tag relations';
EOF;

$optional['recommendations']['data'] = <<<EOF
INSERT INTO `{$new}recommendations`
	( `recid`, `uid`, `url`, `title`, `author`, `summary`, `comment`, `catid`, `ratingid`, `date`, `validated`, `completed`, `ranking` )
	SELECT `recid`, `uid`, `url`, `title`, `author`, `summary`, `comments`, `catid`, `rid`, `date`, `validated`, `completed`, `rating`
	FROM `{$old}recommendations`;--NOTECopy data
--SPLIT--
INSERT INTO `{$new}stories_featured` ( `sid`,`status`, `type` )
	SELECT R.recid, R.featured, 'RC'
		FROM `{$old}recommendations`R
			WHERE R.featured > 0;--NOTEFeatured flag from old recommendations table
EOF;

$optional['recommend_tags']['data'] = <<<EOF
INSERT INTO `{$new}recommend_tags` ( `recid`,`tid` )
	SELECT R.recid,T.tid
		FROM `{$old}recommendations`R
		INNER JOIN `{$new}tags` T ON (FIND_IN_SET(T.tid,R.classes)>0);--NOTERecommendation <-> Tags relations (from classes)
--SPLIT--
INSERT INTO `{$new}recommend_tags` ( `recid`,`tid`,`character` )
	SELECT R.recid,C.charid,'1'
		FROM `{$old}recommendations`R
		INNER JOIN `{$new}characters`C ON (FIND_IN_SET(C.charid,R.charid)>0);--NOTERecommendation <-> Tags relations (from characters)
EOF;

$optional['recommend_cache']['init'] = "SELECT 1;--NOTERecommendation cache (stub)";
$optional['recommend_cache']['data'] = "SELECT 1;--NOTERecommendation cache";

$sql['probe']['recommend_cache'] = "SELECT R.recid FROM `{$new}recommendations`R WHERE R.reviews IS NULL LIMIT 0,1";

$sql['proc']['recommend_cache'] = <<<EOF
SELECT SELECT_OUTER.recid,
GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating,
COUNT(DISTINCT fid) AS reviews
FROM
(
	SELECT R.recid,
		F.fid,
		R.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
		Cat.cid, Cat.category,
		TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
		Ch.charid, Ch.charname
		FROM
		(
			SELECT R1.*
			FROM `{$new}recommendations` R1
			WHERE R1.reviews IS NULL
			LIMIT 0,25
		) AS R
		LEFT JOIN `{$new}ratings` Ra ON ( Ra.rid = R.ratingid )
		LEFT JOIN `{$new}recommend_tags`rRT ON ( rRT.recid = R.recid )
			LEFT JOIN `{$new}tags` T ON ( T.tid = rRT.tid AND rRT.character = 0 )
				LEFT JOIN `{$new}tag_groups` TG ON ( TG.tgid = T.tgid )
			LEFT JOIN `{$new}characters` Ch ON ( Ch.charid = rRT.tid AND rRT.character = 1 )
		LEFT JOIN `{$new}categories` Cat ON ( FIND_IN_SET(Cat.cid,R.catid) )
		LEFT JOIN `{$new}feedback` F ON ( F.reference = R.recid AND F.type='RC' )
)AS SELECT_OUTER
GROUP BY recid ORDER BY recid ASC
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