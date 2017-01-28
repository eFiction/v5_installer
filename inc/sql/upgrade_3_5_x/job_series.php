<?php
/*
	Job definition for 'series'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- series
	- series relation tables
	- cache tables and fields
	
	2017-01-27: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"data"					=> "Copy series table",
		"stories"				=> "Series <-> Story relations",
		"cache"					=> "Build cache fields",
	);


function series_data($job, $step)
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
						Ser.seriesid, 
						inS2.seriesid as parent_series, 
						Ser.title,
						Ser.summary,
						Ser.uid,
						Ser.isopen as open,
						COUNT(DISTINCT R.reviewid) as reviews,
						Ser.challenges as contests,
						COUNT(DISTINCT Ch.chapid) as chapters,
						SUM(Ch.wordcount) as words
					FROM `{$fw->dbOld}series`Ser
					LEFT JOIN `{$fw->dbOld}inseries`inS ON ( Ser.seriesid = inS.seriesid AND inS.subseriesid = 0 )
						LEFT JOIN `{$fw->dbOld}chapters`Ch ON ( Ch.sid = inS.sid )
						LEFT JOIN `{$fw->dbOld}reviews`R ON ( R.item = Ser.seriesid AND R.type='SE' )
					LEFT JOIN `{$fw->dbOld}inseries`inS2 ON ( Ser.seriesid = inS2.subseriesid )
				GROUP BY Ser.seriesid
				ORDER BY Ser.seriesid ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."series" );

		foreach($dataIn as $data)
		{
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

function series_stories($job, $step)
{
	$fw = \Base::instance();

	$dataIn = $fw->db3->exec("SELECT `seriesid`, `sid`, `confirmed`, `inorder` FROM `{$fw->dbOld}inseries` WHERE `subseriesid` = 0;");

	// build the insert values, only numeric, so bulk-insert
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['seriesid']}', '{$data['sid']}', '{$data['confirmed']}', '{$data['inorder']}' )";

		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}series_stories` ( `seriesid`, `sid`, `confirmed`, `inorder` ) VALUES ".implode(", ",$values)."; " );
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

function series_cache($job, $step)
{
	$fw = \Base::instance();
	$limit = 20;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$fw->dbNew}series`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
											FROM `{$fw->dbNew}series`Ser1
											WHERE Ser1.cache_authors IS NULL
											LIMIT 0,{$limit}
									) AS Ser
										LEFT JOIN `{$fw->dbNew}series_stories`TrS ON ( Ser.seriesid = TrS.seriesid )
											LEFT JOIN `{$fw->dbNew}stories`S ON ( TrS.sid = S.sid )
												LEFT JOIN `{$fw->dbNew}ratings`Ra ON ( Ra.rid = S.ratingid )
												LEFT JOIN `{$fw->dbNew}stories_tags`rST ON ( rST.sid = S.sid )
													LEFT JOIN `{$fw->dbNew}tags`T ON ( T.tid = rST.tid AND rST.character = 0 )
														LEFT JOIN `{$fw->dbNew}tag_groups`TG ON ( TG.tgid = T.tgid )
													LEFT JOIN `{$fw->dbNew}characters`Chara ON ( Chara.charid = rST.tid AND rST.character = 1 )
												LEFT JOIN `{$fw->dbNew}stories_categories`rSC ON ( rSC.sid = S.sid )
													LEFT JOIN `{$fw->dbNew}categories`C ON ( rSC.cid = C.cid )
												LEFT JOIN `{$fw->dbNew}stories_authors`rSA ON ( rSA.sid = S.sid )
													LEFT JOIN `{$fw->dbNew}users` U ON ( rSA.aid = U.uid )
									GROUP BY Ser.seriesid
							) AS SERIES
							LEFT JOIN `{$fw->dbNew}ratings`R ON (R.rid = max_rating_id);");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			$fw->db5->exec
			(
					"UPDATE `{$fw->dbNew}series` SET 
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
			$tracking->items++;
		}
		//$tracking->items = $tracking->items+$count;
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