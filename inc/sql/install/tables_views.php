<?php

$views = array
(
	"newsOverview"              => "V: News overview",
	"contestsList"              => "V: Contest list",
	"loadStoryReviews"          => "V: Story reviews",
	"loadStoryReviewComments"	=> "V: Story review comments",
	"ACPlogData"                => "V: ACP log data",
	"statsUserStoryReviews"     => "V: User review stats",
	"statsUserStoryFavBM"       => "V: User library stats",
);

$core['newsOverview'] = <<<EOF
DROP VIEW IF EXISTS `{$view}newsOverview`;
CREATE VIEW `{$view}newsOverview` AS
	SELECT N.nid, N.headline, N.newstext, N.comments, N.datetime, UNIX_TIMESTAMP(N.datetime) as timestamp, 
--	COUNT(DISTINCT F.fid) as comments,
	U.uid,U.username
	FROM `{$new}news`N
		LEFT JOIN `{$new}users`U ON ( U.uid = N.uid )
--		LEFT JOIN `{$new}feedback`F ON ( N.nid = F.reference AND F.type='N' )
	GROUP BY N.nid;
EOF;

$core['contestsList'] = <<<EOF
DROP VIEW IF EXISTS `{$view}contestsList`;
CREATE VIEW `{$view}contestsList` AS
	SELECT SQL_CALC_FOUND_ROWS
		C.conid, C.title, C.summary, C.concealed,
		IF
		(
			C.active='date',
			IF(
				C.date_open<NOW(),
				IF(
					C.date_close>NOW() OR C.date_close IS NULL,
					'active',
					'closed'
					),
				'preparing'
				),
			C.active
		) as active,
        IF(C.votable='date',IF(C.date_close<NOW() OR C.date_close IS NULL,IF(C.vote_close>NOW() OR C.vote_close IS NULL,'active','closed'),'preparing'),C.votable) as votable,
		UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_close) as vote_close, 
		C.cache_tags, C.cache_characters, C.cache_categories, C.cache_stories,
		U.username, COUNT(R.lid) as count
	FROM `{$new}contests`C
		LEFT JOIN `{$new}users`U ON ( C.uid = U.uid )
		LEFT JOIN `{$new}contest_relations`R ON ( C.conid = R.conid AND R.type='ST' )
	GROUP BY C.conid;
EOF;

$core['loadStoryReviews'] = <<<EOF
DROP VIEW IF EXISTS `{$view}loadStoryReviews`;
CREATE VIEW `{$view}loadStoryReviews` AS
	SELECT 
		F.fid as review_id, 
		Ch.inorder,
		F.text as review_text, 
		F.reference as review_story, 
		F.reference_sub as review_chapter, 
		IF(F.writer_uid>0,U.username,F.writer_name) as review_writer_name, 
		F.writer_uid as review_writer_uid, 
		UNIX_TIMESTAMP(F.datetime) as date_review
	FROM `{$new}feedback`F 
		LEFT JOIN `{$new}users`U ON ( F.writer_uid = U.uid )
		LEFT JOIN `{$new}chapters`Ch ON ( Ch.chapid = F.reference_sub )
	WHERE F.type='ST'
EOF;

$core['loadStoryReviewComments'] = <<<EOF
DROP VIEW IF EXISTS `{$view}loadStoryReviewComments`;
CREATE VIEW `{$view}loadStoryReviewComments` AS
	SELECT 
		F2.fid as comment_id, 
		F2.text as comment_text, 
		F2.reference_sub as parent_item, 
		IF(F2.writer_uid>0,U2.username,F2.writer_name) as comment_writer_name, 
		F2.writer_uid as comment_writer_uid,
		F2.reference as review_id, 
		UNIX_TIMESTAMP(F2.datetime) as date_comment
	FROM `{$new}feedback`F2
		LEFT JOIN `{$new}users`U2 ON ( F2.writer_uid = U2.uid )
	WHERE F2.type='C'
EOF;

$core['ACPlogData'] = <<<EOF
DROP VIEW IF EXISTS `{$view}ACPlogData`;
CREATE VIEW `{$view}ACPlogData` AS
	SELECT
		U.uid, U.username, 
		L.uid as uid_reg, L.id, L.action, L.ip, UNIX_TIMESTAMP(L.timestamp) as timestamp, L.type, L.subtype, L.version, L.new
	FROM `{$new}log`L
		LEFT JOIN `{$new}users`U ON L.uid=U.uid
EOF;

$core['statsUserStoryReviews'] = <<<EOF
DROP VIEW IF EXISTS `{$view}statsUserStoryReviews`;
CREATE VIEW `{$view}statsUserStoryReviews` AS
	SELECT
		rSA.sid, rSA.aid, rSA.type,
		F.datetime
	FROM `{$new}feedback`F
		INNER JOIN `{$new}stories_authors`rSA ON ( F.type='ST' AND F.reference = rSA.sid )
EOF;

$core['statsUserStoryFavBM'] = <<<EOF
DROP VIEW IF EXISTS `{$view}statsUserStoryFavBM`;
CREATE VIEW `{$view}statsUserStoryFavBM` AS
	SELECT
		rSA.sid, rSA.aid, rSA.type,
		F.bookmark, F.visibility, F.changed
	FROM `{$new}user_favourites`F
		INNER JOIN `{$new}stories_authors`rSA ON ( F.type='ST' AND F.item = rSA.sid )
EOF;

?>