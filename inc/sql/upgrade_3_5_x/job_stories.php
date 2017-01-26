<?php
/*
	Job definition for 'stories'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- stories
	- story relation tables
	- cache tables and fields
*/

$fw->jobSteps = array(
		"data"					=> "Copy story table",
		"featured"				=> "List of featured stories",
		"authors"				=> "Story <-> (co)Author relations",
		"categories"			=> "Story <-> Category relations",
		"tags"					=> "Story <-> Tag relations",
		"recount_tags"			=> "Recount tags",
		"recount_characters"	=> "Recount characters",
		"recount_categories"	=> "Recount categories",
		"cache"					=> "Build cache fields",
	);

function stories_data($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 100;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}stories`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
								S1.*,
								(10*SUM(R1.rating)/COUNT(R1.reviewid)) as ranking, 
								COUNT(DISTINCT R.reviewid) as reviews
								FROM
								(
									SELECT
										S.sid, 
										S.title, 
										S.summary, 
										S.storynotes, 
										S.rid, 
										S.date, 
										S.updated, 
										S.validated, 
										S.completed, 
										S.rr as roundrobin, 
										SUM(C.wordcount) as wordcount, 
										COUNT(DISTINCT C.chapid) as chapters, 
										S.count
									FROM `{$old}stories`S
										LEFT JOIN `{$old}chapters`C ON ( C.sid = S.sid )
									GROUP BY S.sid
									ORDER BY S.sid ASC LIMIT {$step['items']},{$limit}
								) AS S1
								LEFT JOIN `{$old}reviews`R ON ( S1.sid = R.item AND R.type = 'ST' )
								LEFT JOIN `{$old}reviews`R1 ON ( S1.sid = R1.item AND R1.rating > 0 AND R1.type = 'ST' )
							GROUP BY S1.sid
							ORDER BY S1.sid ASC;");
				
/*	$dataIn = $fw->db3->exec("SELECT
					S.sid, 
					S.title, 
					S.summary, 
					S.storynotes, 
					S.rid, 
					S.date, 
					S.updated, 
					S.validated, 
					S.completed, 
					S.rr as roundrobin, 
					SUM(C.wordcount) as wordcount, 
					(10*SUM(R1.rating)/COUNT(R1.reviewid)) as ranking, 
					COUNT(DISTINCT R.reviewid) as reviews, 
					COUNT(DISTINCT C.chapid) as chapters, 
					S.count
				FROM `{$old}stories`S
					LEFT JOIN `{$old}reviews`R ON ( S.sid = R.item AND R.type = 'ST' )
					LEFT JOIN `{$old}reviews`R1 ON ( S.sid = R1.item AND R1.rating > 0 AND R1.type = 'ST' )
					LEFT JOIN `{$old}chapters`C ON ( C.sid = S.sid )
				GROUP BY S.sid
				ORDER BY S.sid ASC LIMIT {$step['items']},{$limit};");*/
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		// build the insert values
		foreach($dataIn as $data)
		{
			switch($data['validated']) {
				case 0:
					$data['validated'] = '01';
					break;
				case 1:
					$data['validated'] = '21';
					break;
				case 2:
					$data['validated'] = '23';
			}

			$values[] = "( '{$data['sid']}', 
							{$fw->db5->quote($data['title'])},
							{$fw->db5->quote($data['summary'])},
							{$fw->db5->quote($data['storynotes'])},
							'{$data['rid']}',
							'{$data['date']}',
							'{$data['updated']}',
							'{$data['validated']}',
							'{$data['completed']}',
							'{$data['roundrobin']}',
							'{$data['wordcount']}',
							'{$data['ranking']}',
							'{$data['reviews']}',
							'{$data['chapters']}',
							'{$data['count']}' )";
		}

		$fw->db5->exec ( "INSERT INTO `{$new}stories` (`sid`, `title`, `summary`, `storynotes`, `ratingid`, `date`, `updated`, `validated`, `completed`, `roundrobin`, `wordcount`, `ranking`, `reviews`, `chapters`, `count`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
		
		$tracking->items = $tracking->items+$count;
		$tracking->save();
	}

	if ( $count == 0 OR $tracking->items>=$tracking->total )
	{
		// There was either nothing to be done, or there are no elements left for the next run
		$tracking->success = 2;
		$tracking->save();
	}
}

function stories_featured($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$i = 0;
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."featured" );
	
	$dataIn = $fw->db3->exec("SELECT S.sid as id, S.featured as status FROM `{$old}stories`S WHERE S.featured > 0;");
	
	foreach($dataIn as $data)
	{
		$i++;
		$newdata->copyfrom($data);
		$newdata->save();
		$newdata->reset();
	}

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $i,
							':id' => $step['id']
						]
					);
}

function stories_authors($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";

	$dataIn = $fw->db3->exec("SELECT S.sid, S.uid, '0' as ca FROM `{$old}stories`S ORDER BY S.sid ASC;");
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT Ca.sid, Ca.uid, 1 as 'ca' FROM `{$old}coauthors`Ca ORDER BY Ca.sid ASC;") );

	// build the insert values
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', '{$data['uid']}', '{$data['ca']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}stories_authors` (`sid`, `aid`, `ca`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;
	
	// Cleanup, there are cases when an author also is set as co_author
	$fw->db5->exec("DELETE S1 
			FROM `{$new}stories_authors`S1 
				INNER JOIN `{$new}stories_authors`S2 ON ( S1.sid=S2.sid AND S1.aid = S2.aid AND S1.lid > S2.lid );");
	$deleted = $fw->db5->count();

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count-$deleted,
							':id' => $step['id']
						]
					);
}

function stories_categories($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	
	$dataIn = $fw->db3->exec("SELECT S.sid,C.catid
		FROM `{$old}stories`S
			INNER JOIN `{$old}categories`C ON (FIND_IN_SET(C.catid,S.catid)>0);");

	// build the insert values
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', '{$data['catid']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}stories_categories` (`sid`, `cid`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function stories_tags($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	
	// get tags (formerly classes)
	$dataIn = $fw->db3->exec("SELECT S.sid,C.class_id as tid,'0' as `character`
				FROM `{$old}stories`S
					INNER JOIN `{$old}classes`C ON (FIND_IN_SET(C.class_id,S.classes)>0);");
	// get characters
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT S.sid,Ch.charid as tid,'1' as `character`
				FROM `{$old}stories`S
					INNER JOIN `{$old}characters`Ch ON (FIND_IN_SET(Ch.charid,S.charid)>0);") );

	// build the insert values
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', '{$data['tid']}', '{$data['character']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}stories_tags` (`sid`, `tid`, `character`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function stories_recount_tags($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";

	$items = 0;
	do {
		$fw->db5->exec("UPDATE `{$new}tags` T1 
							LEFT JOIN
							(
								SELECT T.tid, COUNT( DISTINCT RT.sid ) AS counter 
								FROM `{$new}tags`T 
								LEFT JOIN `{$new}stories_tags`RT ON (RT.tid = T.tid AND RT.character = 0)
									WHERE T.count IS NULL
									GROUP BY T.tid
									LIMIT 0,25
							) AS T2 ON T1.tid = T2.tid
							SET T1.count = T2.counter WHERE T1.tid = T2.tid;");
		$count = $fw->db5->count();
		$items += $count;
	} while ( 0 < $count );
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $items,
							':id' => $step['id']
						]
					);
}

function stories_recount_characters($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";

	$items = 0;
	do {
		$fw->db5->exec("UPDATE `{$new}characters` C1 
							LEFT JOIN
							(
								SELECT C.charid, COUNT( DISTINCT RT.sid ) AS counter 
								FROM `{$new}characters`C
								LEFT JOIN `{$new}stories_tags`RT ON (RT.tid = C.charid AND RT.character = 1)
									WHERE C.count IS NULL
									GROUP BY C.charid
									LIMIT 0,25
							) AS C2 ON C1.charid = C2.charid
							SET C1.count = C2.counter WHERE C1.charid = C2.charid;");
		$count = $fw->db5->count();
		$items += $count;
	} while ( 0 < $count );
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $items,
							':id' => $step['id']
						]
					);
}

function stories_recount_categories($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";

	$items = 0;

	$dataIn = $fw->db5->exec("SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories, GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
								FROM `{$new}categories`C 
									INNER JOIN (SELECT leveldown FROM `{$new}categories` WHERE `stats` = '' ORDER BY leveldown DESC LIMIT 0,1) c2 ON ( C.leveldown = c2.leveldown )
								LEFT JOIN `{$new}stories_categories`SC ON ( C.cid = SC.cid )
									LEFT JOIN `{$new}stories`S ON ( S.sid = SC.sid )
								LEFT JOIN `{$new}categories`C1 ON ( C.cid = C1.parent_cid )
							GROUP BY C.cid;");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			if ( $item['sub_categories']==NULL ) $sub = NULL;
			else
			{
				$sub_categories = explode("||", $item['sub_categories']);
				$sub_stats = explode("||", $item['sub_stats']);
				$sub_stats = array_map("json_decode", $sub_stats);

				foreach( $sub_categories as $key => $value )
				{
					$item['counted'] += $sub_stats[$key]->count;
					$sub[$value] = $sub_stats[$key]->count;
				}
			}
			$stats = json_encode([ "count" => (int)$item['counted'], "cid" => $item['cid'], "sub" => $sub ]);
			unset($sub);

			$fw->db5->exec
			(
				"UPDATE `{$new}categories`C SET C.stats = :stats WHERE C.cid = :cid",
				[ ":stats" => $stats, ":cid" => $item['cid'] ]
			);
			$items++;
		}
		$tracking->items = $tracking->items + $items;
		$tracking->save();
	}

	if ( $count == 0 )
	{
		// There was either nothing to be done, or there are no elements left for the next run
		$tracking->success = 2;
		$tracking->save();
	}
}

function stories_cache($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$limit = 50;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$new}stories`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT SELECT_OUTER.sid,
						GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
						GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
						GROUP_CONCAT(DISTINCT uid,',',nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
						GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
						GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating
						FROM
						(
							SELECT S.sid,
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
									LIMIT 0,{$limit}
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
						)AS SELECT_OUTER
						GROUP BY sid ORDER BY sid ASC;");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			$fw->db5->exec
			(
				"UPDATE `{$new}stories` SET 
					`cache_authors`		= :authorblock,
					`cache_tags`		= :tagblock,
					`cache_characters`	= :characterblock,
					`cache_categories`	= :categoryblock,
					`cache_rating`		= :rating
				WHERE sid = {$item['sid']} ;",
				[
					':authorblock'		=> json_encode(upgradetools::cleanResult($item['authorblock'])),
					':tagblock'			=> json_encode(upgradetools::cleanResult($item['tagblock'])),
					':characterblock'	=> json_encode(upgradetools::cleanResult($item['characterblock'])),
					':categoryblock'	=> json_encode(upgradetools::cleanResult($item['categoryblock'])),
					':rating'			=> json_encode(explode(",",$item['rating'])),
				]
			);
		}
		$tracking->items = $tracking->items+$count;
		$tracking->save();
	}

	if ( $count == 0 OR $tracking->items>=$tracking->total )
	{
		// There was either nothing to be done, or there are no elements left for the next run
		$tracking->success = 2;
		$tracking->save();
	}
}

?>