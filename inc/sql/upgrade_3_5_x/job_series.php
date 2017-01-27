<?php
/*
	Job definition for 'series'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- series
	- series relation tables
	- cache tables and fields
*/

$fw->jobSteps = array(
		"data"					=> "Copy series table",//
		"stories"				=> "Series <-> Story relations", //
		"cache"					=> "Build cache fields",
	);


function series_data($job, $step)
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
					Ser.seriesid, Ser.title, Ser.summary, Ser.uid, Ser.isopen, Ser.challenges,
					COUNT(DISTINCT Ch.chapid) as chapter_count, SUM(Ch.wordcount) as word_count, COUNT(DISTINCT R.reviewid) as reviews,
					inS2.seriesid as parent_series
					FROM `{$old}series`Ser
					LEFT JOIN `{$old}inseries`inS ON ( Ser.seriesid = inS.seriesid AND inS.subseriesid = 0 )
						LEFT JOIN `{$old}chapters`Ch ON ( Ch.sid = inS.sid )
						LEFT JOIN `{$old}reviews`R ON ( R.item = Ser.seriesid AND R.type='SE' )
					LEFT JOIN `{$old}inseries`inS2 ON ( Ser.seriesid = inS2.subseriesid )
				GROUP BY Ser.seriesid
				ORDER BY Ser.seriesid ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['seriesid']}', 
							'{$data['parent_series']}',
							{$fw->db5->quote($data['title'])},
							{$fw->db5->quote($data['summary'])},
							'{$data['uid']}',
							'{$data['isopen']}',
							'{$data['reviews']}',
							'{$data['challenges']}',
							'{$data['chapter_count']}',
							'{$data['word_count']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}series` (`seriesid`, `parent_series`, `title`, `summary`, `uid`, `open`, `reviews`, `contests`, `chapters`, `words` ) VALUES ".implode(", ",$values)."; " );
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

function series_stories($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";

	$dataIn = $fw->db3->exec("SELECT `seriesid`, `sid`, `confirmed`, `inorder` FROM `{$old}inseries` WHERE `subseriesid` = 0;");

	// build the insert values
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['seriesid']}', '{$data['sid']}', '{$data['confirmed']}', '{$data['inorder']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}series_stories` ( `seriesid`, `sid`, `confirmed`, `inorder` ) VALUES ".implode(", ",$values)."; " );
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

function series_cache($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$limit = 20;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$new}series`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT 
								SERIES.seriesid, 
								SERIES.tagblock, 
								SERIES.characterblock, 
								SERIES.authorblock, 
								SERIES.categoryblock, 
								CONCAT(rating,'||',max_rating_id) as max_rating
							FROM
							(
							SELECT 
							Ser.seriesid,
							MAX(Ra.rid) as max_rating_id,
										GROUP_CONCAT(DISTINCT U.uid,',',U.nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
										GROUP_CONCAT(DISTINCT Chara.charid,',',Chara.charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
										GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
										GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock
									FROM 
									(
										SELECT Ser1.seriesid
											FROM `{$new}series`Ser1
											WHERE Ser1.cache_authors IS NULL
											LIMIT 0,{$limit}
									) AS Ser
										LEFT JOIN `{$new}series_stories`TrS ON ( Ser.seriesid = TrS.seriesid )
											LEFT JOIN `{$new}stories`S ON ( TrS.sid = S.sid )
												LEFT JOIN `{$new}ratings`Ra ON ( Ra.rid = S.ratingid )
												LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
													LEFT JOIN `{$new}tags`T ON ( T.tid = rST.tid AND rST.character = 0 )
														LEFT JOIN `{$new}tag_groups`TG ON ( TG.tgid = T.tgid )
													LEFT JOIN `{$new}characters`Chara ON ( Chara.charid = rST.tid AND rST.character = 1 )
												LEFT JOIN `{$new}stories_categories`rSC ON ( rSC.sid = S.sid )
													LEFT JOIN `{$new}categories`C ON ( rSC.cid = C.cid )
												LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
													LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
									GROUP BY Ser.seriesid
							) AS SERIES
							LEFT JOIN `{$new}ratings`R ON (R.rid = max_rating_id);");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			$fw->db5->exec
			(
					"UPDATE `{$new}series` SET 
						`cache_authors`		= :authorblock,
						`cache_tags`		= :tagblock,
						`cache_characters`	= :characterblock,
						`cache_categories`	= :categoryblock,
						`max_rating`		= :max_rating
					WHERE seriesid = {$item['seriesid']} ;",
					[
						':authorblock'		=> json_encode(upgradetools::cleanResult($item['authorblock'])),
						':tagblock'			=> json_encode(upgradetools::cleanResult($item['tagblock'])),
						':characterblock'	=> json_encode(upgradetools::cleanResult($item['characterblock'])),
						':categoryblock'	=> json_encode(upgradetools::cleanResult($item['categoryblock'])),
						':max_rating'		=> json_encode(explode(",",$item['max_rating'])),
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