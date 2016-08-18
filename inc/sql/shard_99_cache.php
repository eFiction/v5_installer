<?php

/* --------------------------------------------------------------------------------------------
																																											* CACHE *
requires: *
-------------------------------------------------------------------------------------------- */ 

$sql['data']['stories_blockcache'] = "SELECT 1;--NOTEStory Cache - populating table";

$sql['probe']['stories_blockcache'] = "SELECT S.sid FROM `{$new}stories`S WHERE S.cache_rating IS NULL LIMIT 0,1";

$sql['proc']['stories_blockcache'] = <<<EOF
SELECT SELECT_OUTER.sid,
GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
GROUP_CONCAT(DISTINCT uid,',',nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating,
COUNT(DISTINCT fid) AS reviews,
COUNT(DISTINCT chapid) AS chapters
FROM
(
	SELECT S.sid,C.chapid,
		F.fid,
		S.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
		U.uid, U.nickname,
		Cat.cid, Cat.category,
		TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
		Ch.charid, Ch.charname
		FROM
		(
			SELECT S1.*
			FROM `{$new}stories` S1
			WHERE S1.cache_rating IS NULL
			LIMIT 0,50
		) AS S
		LEFT JOIN `{$new}ratings` Ra ON ( Ra.rid = S.ratingid )
		LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
			LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
		LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
			LEFT JOIN `{$new}tags` T ON ( T.tid = rST.tid AND rST.character = 0 )
				LEFT JOIN `{$new}tag_groups` TG ON ( TG.tgid = T.tgid )
			LEFT JOIN `{$new}characters` Ch ON ( Ch.charid = rST.tid AND rST.character = 1 )
		LEFT JOIN `{$new}stories_categories`rSC ON ( rSC.sid = S.sid )
			LEFT JOIN `{$new}categories` Cat ON ( rSC.cid = Cat.cid )
		LEFT JOIN `{$new}chapters` C ON ( C.sid = S.sid )
		LEFT JOIN `{$new}feedback` F ON ( F.reference = S.sid AND F.type='ST' )
)AS SELECT_OUTER
GROUP BY sid ORDER BY sid ASC
EOF;
/*
$sql['init']['series_blockcache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series_blockcache` (
  `seriesid` mediumint(8) unsigned NOT NULL,
  `cache_tags` text,
  `cache_characters` text,
  `cache_authors` text,
  `cache_categories` text,
  `max_rating` tinytext NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  `words` int(10) unsigned NOT NULL,
  PRIMARY KEY (`seriesid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;
*/
$sql['data']['series_blockcache'] = "SELECT 1;--NOTESeries Cache - populating table";

//$sql['probe']['series_blockcache'] = "SELECT S.seriesid,C.seriesid from `{$new}series`S LEFT JOIN `{$new}series_blockcache`C ON S.seriesid = C.seriesid WHERE C.seriesid IS NULL LIMIT 0,1";
$sql['probe']['series_blockcache'] = "SELECT S.seriesid FROM `{$new}series`S WHERE S.words IS NULL LIMIT 0,1";

$sql['proc']['series_blockcache'] = <<<EOF
SELECT 
	SERIES.seriesid, 
	SERIES.tagblock, 
	SERIES.characterblock, 
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
			GROUP_CONCAT(DISTINCT Chara.charid,',',Chara.charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
			GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
			GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock,
			COUNT(DISTINCT Ch.chapid) as chapter_count, SUM(Ch.wordcount) as word_count
		FROM 
		(
			SELECT Ser1.seriesid
				FROM `{$new}series`Ser1
				WHERE Ser1.words IS NULL
				LIMIT 0,5
		) AS Ser
			LEFT JOIN `{$new}series_stories`TrS ON ( Ser.seriesid = TrS.seriesid )
				LEFT JOIN `{$new}stories`S ON ( TrS.sid = S.sid )
					LEFT JOIN `{$new}chapters`Ch ON ( Ch.sid = S.sid )
					LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
						LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
			LEFT JOIN `{$new}ratings`Ra ON ( Ra.rid = S.ratingid )
			LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
				LEFT JOIN `{$new}tags`T ON ( T.tid = rST.tid AND rST.character = 0 )
					LEFT JOIN `{$new}tag_groups`TG ON ( TG.tgid = T.tgid )
				LEFT JOIN `{$new}characters`Chara ON ( Chara.charid = rST.tid AND rST.character = 1 )
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