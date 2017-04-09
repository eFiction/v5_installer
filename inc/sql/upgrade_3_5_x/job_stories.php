<?php
/*
	Job definition for 'stories'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- stories
	- story relation tables
	- cache tables and fields

	2017-01-28: Update DB queries to be safer
				Better cache creation
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
	$limit = 100;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}stories`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
										S.rid as ratingid, 
										S.date, 
										S.updated, 
										S.validated, 
										S.completed, 
										S.rr as roundrobin, 
										SUM(C.wordcount) as wordcount, 
										COUNT(DISTINCT C.chapid) as chapters, 
										S.count
									FROM `{$fw->dbOld}stories`S
										LEFT JOIN `{$fw->dbOld}chapters`C ON ( C.sid = S.sid )
									GROUP BY S.sid
									ORDER BY S.sid ASC LIMIT {$step['items']},{$limit}
								) AS S1
								LEFT JOIN `{$fw->dbOld}reviews`R ON ( S1.sid = R.item AND R.type = 'ST' )
								LEFT JOIN `{$fw->dbOld}reviews`R1 ON ( S1.sid = R1.item AND R1.rating > 0 AND R1.type = 'ST' )
							GROUP BY S1.sid
							ORDER BY S1.sid ASC;");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories" );

		foreach($dataIn as $data)
		{
			switch($data['validated']) {
				case 0:
					$data['validated'] = '11';
					break;
				case 1:
					$data['validated'] = '31';
					break;
				case 2:
					$data['validated'] = '33';
			}

			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();
			
			$tracking->items++;
		}

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
	$count = 0;
	
	$dataIn = $fw->db3->exec("SELECT S.sid as id, S.featured as status FROM `{$fw->dbOld}stories`S WHERE S.featured > 0;");
	
	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."featured" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();

			$count++;
		}
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function stories_authors($job, $step)
{
	$fw = \Base::instance();

	$dataIn = $fw->db3->exec("SELECT S.sid, S.uid, 'M' as type FROM `{$fw->dbOld}stories`S ORDER BY S.sid ASC;");
	// currently setting co_authors as supporting authors
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT Ca.sid, Ca.uid, 'S' as 'type' FROM `{$fw->dbOld}coauthors`Ca ORDER BY Ca.sid ASC;") );

	// build the insert values
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', '{$data['uid']}', '{$data['type']}' )";

		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}stories_authors` (`sid`, `aid`, `type`) VALUES ".implode(", ",$values)."
							ON DUPLICATE KEY UPDATE type=type; " );
		// the ON DUPLICATE may look useless, but it makes sure that when an author is both author and co-author, the later is dropped
		$count = $fw->db5->count();
	}
	else $count = 0;
	
	// Cleanup, there are cases when an author was also set as co_author
	// Should be obsolete with unique key restraints
	/*
	$fw->db5->exec("DELETE S1 
			FROM `{$fw->dbNew}stories_authors`S1 
				INNER JOIN `{$fw->dbNew}stories_authors`S2 ON ( S1.sid=S2.sid AND S1.aid = S2.aid AND S1.lid > S2.lid );");
	$deleted = $fw->db5->count();
	*/

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function stories_categories($job, $step)
{
	$fw = \Base::instance();
	
	$dataIn = $fw->db3->exec("SELECT S.sid,C.catid
		FROM `{$fw->dbOld}stories`S
			INNER JOIN `{$fw->dbOld}categories`C ON (FIND_IN_SET(C.catid,S.catid)>0);");

	// build the insert values - numeric only
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', '{$data['catid']}' )";

		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}stories_categories` (`sid`, `cid`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function stories_tags($job, $step)
{
	$fw = \Base::instance();
	
	// get tags (formerly classes)
	$dataIn = $fw->db3->exec("SELECT S.sid,C.class_id as tid,'0' as `character`
				FROM `{$fw->dbOld}stories`S
					INNER JOIN `{$fw->dbOld}classes`C ON (FIND_IN_SET(C.class_id,S.classes)>0);");
	// get characters
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT S.sid,Ch.charid as tid,'1' as `character`
				FROM `{$fw->dbOld}stories`S
					INNER JOIN `{$fw->dbOld}characters`Ch ON (FIND_IN_SET(Ch.charid,S.charid)>0);") );
	
	$count = 0;

	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories_tags" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();

			$count++;
		}
	}
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function stories_recount_tags($job, $step)
{
	$fw = \Base::instance();

	$items = 0;
	do {
		$fw->db5->exec("UPDATE `{$fw->dbNew}tags` T1 
							LEFT JOIN
							(
								SELECT T.tid, COUNT( DISTINCT RT.sid ) AS counter 
								FROM `{$fw->dbNew}tags`T 
								LEFT JOIN `{$fw->dbNew}stories_tags`RT ON (RT.tid = T.tid AND RT.character = 0)
									WHERE T.count IS NULL
									GROUP BY T.tid
									LIMIT 0,25
							) AS T2 ON T1.tid = T2.tid
							SET T1.count = T2.counter WHERE T1.tid = T2.tid;");
		$count = $fw->db5->count();
		$items += $count;
	} while ( 0 < $count );
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $items,
							':id' => $step['id']
						]
					);
}

function stories_recount_characters($job, $step)
{
	$fw = \Base::instance();

	$items = 0;
	do {
		$fw->db5->exec("UPDATE `{$fw->dbNew}characters` C1 
							LEFT JOIN
							(
								SELECT C.charid, COUNT( DISTINCT RT.sid ) AS counter 
								FROM `{$fw->dbNew}characters`C
								LEFT JOIN `{$fw->dbNew}stories_tags`RT ON (RT.tid = C.charid AND RT.character = 1)
									WHERE C.count IS NULL
									GROUP BY C.charid
									LIMIT 0,25
							) AS C2 ON C1.charid = C2.charid
							SET C1.count = C2.counter WHERE C1.charid = C2.charid;");
		$count = $fw->db5->count();
		$items += $count;
	} while ( 0 < $count );
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $items,
							':id' => $step['id']
						]
					);
}

function stories_recount_categories($job, $step)
{
	$fw = \Base::instance();

	$items = 0;
	$fw->db5->exec("SET SESSION group_concat_max_len = 1000000;");
	$dataIn = $fw->db5->exec("SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories, GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
								FROM `{$fw->dbNew}categories`C 
									INNER JOIN (SELECT leveldown FROM `{$fw->dbNew}categories` WHERE `stats` = '' ORDER BY leveldown DESC LIMIT 0,1) c2 ON ( C.leveldown = c2.leveldown )
								LEFT JOIN `{$fw->dbNew}stories_categories`SC ON ( C.cid = SC.cid )
									LEFT JOIN `{$fw->dbNew}stories`S ON ( S.sid = SC.sid )
								LEFT JOIN `{$fw->dbNew}categories`C1 ON ( C.cid = C1.parent_cid )
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
				"UPDATE `{$fw->dbNew}categories`C SET C.stats = :stats WHERE C.cid = :cid",
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
	$limit = 100;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$fw->dbNew}stories`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT SELECT_OUTER.sid,
						GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
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
									FROM `{$fw->dbNew}stories` S1
									WHERE S1.cache_rating IS NULL
									LIMIT 0,{$limit}
								) AS S
								LEFT JOIN `{$fw->dbNew}ratings` Ra ON ( Ra.rid = S.ratingid )
								LEFT JOIN `{$fw->dbNew}stories_authors`rSA ON ( rSA.sid = S.sid )
									LEFT JOIN `{$fw->dbNew}users` U ON ( rSA.aid = U.uid )
								LEFT JOIN `{$fw->dbNew}stories_tags`rST ON ( rST.sid = S.sid )
									LEFT JOIN `{$fw->dbNew}tags` T ON ( T.tid = rST.tid AND rST.character = 0 )
										LEFT JOIN `{$fw->dbNew}tag_groups` TG ON ( TG.tgid = T.tgid )
									LEFT JOIN `{$fw->dbNew}characters` Ch ON ( Ch.charid = rST.tid AND rST.character = 1 )
								LEFT JOIN `{$fw->dbNew}stories_categories`rSC ON ( rSC.sid = S.sid )
									LEFT JOIN `{$fw->dbNew}categories` Cat ON ( rSC.cid = Cat.cid )
						)AS SELECT_OUTER
						GROUP BY sid ORDER BY sid ASC;");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$storyMap = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories" );
		foreach ( $dataIn as $item)
		{
			$tagblock['simple'] = upgradetools::cleanResult($item['tagblock']);
			if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
				$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];
			
			$storyMap->load(array("sid=?",$item['sid']));
			$storyMap->cache_authors	= json_encode(upgradetools::cleanResult($item['authorblock']));
			$storyMap->cache_tags		= json_encode($tagblock);
			$storyMap->cache_characters	= json_encode(upgradetools::cleanResult($item['characterblock']));
			$storyMap->cache_categories	= json_encode(upgradetools::cleanResult($item['categoryblock']));
			$storyMap->cache_rating		= json_encode(explode(",",$item['rating']));
			$storyMap->save();

			$tracking->items++;
			$tagblock = [];
		}
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