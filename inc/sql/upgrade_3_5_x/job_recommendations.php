<?php
/*
	Job definition for 'recommendations'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- recommendations
	- recommendations relation tables
	- cache tables and fields

	2017-01-28: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"data"					=> "Copy recommendations",
		"featured"				=> "List of featured recommendations",
		"relations"				=> "Recommendation relations",
		"cache"					=> "Build cache fields",
	);

function recommendations_data($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.medium");
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}recommendations`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
					Rec.recid,
					IF(Rec.uid>0,Rec.uid,0) as uid,
					IF(Rec.uid>0,NULL,Rec.recname) as guestname,
					`url`, 
					`title`, 
					`author`, 
					`summary`, 
					`comments` as comment, 
					`rid` as ratingid, 
					Rec.date, 
					`validated`, 
					`completed`,
					(10*SUM(R1.rating)/COUNT(R1.reviewid)) as ranking,
					COUNT(R.reviewid) as reviews
				FROM `{$fw->dbOld}recommendations`Rec
					LEFT JOIN `{$fw->dbOld}reviews`R ON ( Rec.recid = R.item AND R.type = 'RC' )
					LEFT JOIN `{$fw->dbOld}reviews`R1 ON ( Rec.recid = R1.item AND R1.rating > 0 AND R1.type = 'RC' )
				GROUP BY Rec.recid
				ORDER BY Rec.recid ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."recommendations" );

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

function recommendations_featured($job, $step)
{
	$fw = \Base::instance();
	$i = 0;
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."featured" );
	
	$dataIn = $fw->db3->exec("SELECT Rec.recid as id, Rec.featured as status, 'RC' as type FROM `{$fw->dbOld}recommendations`Rec WHERE Rec.featured > 0;");
	
	foreach($dataIn as $data)
	{
		$i++;
		$newdata->copyfrom($data);
		$newdata->save();
		$newdata->reset();
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $i,
							':id' => $step['id']
						]
					);
}

function recommendations_relations($job, $step)
{
	$fw = \Base::instance();
	
	// recid, relid, type
	// get tags (formerly classes)
	$dataIn = $fw->db3->exec("SELECT Rec.recid,C.class_id as relid,'T' as `type`
				FROM `{$fw->dbOld}recommendations`Rec
					INNER JOIN `{$fw->dbOld}classes`C ON (FIND_IN_SET(C.class_id,Rec.classes)>0);");
	// get characters
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT Rec.recid,Ch.charid as relid,'CH' as `type`
				FROM `{$fw->dbOld}recommendations`Rec
					INNER JOIN `{$fw->dbOld}characters`Ch ON (FIND_IN_SET(Ch.charid,Rec.charid)>0);") );
	// get categories
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT Rec.recid,Cat.catid as relid,'CA' as `type`
				FROM `{$fw->dbOld}recommendations`Rec
					INNER JOIN `{$fw->dbOld}categories`Cat ON (FIND_IN_SET(Cat.catid,Rec.catid)>0);") );

	if ( !empty($dataIn) )
	{	
		// build the insert values - no strings attached *pun*
		foreach($dataIn as $data)
			$values[] = "( '{$data['recid']}', '{$data['relid']}', '{$data['type']}' )";

		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}recommendation_relations` (`recid`, `relid`, `type`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function recommendations_cache($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.heavy");
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$fw->dbNew}recommendations`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT SELECT_OUTER.recid,
								GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
								GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
								GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
								GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating
								FROM
								(
									SELECT R.recid,
										R.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
										Cat.cid, Cat.category,
										TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
										Ch.charid, Ch.charname
										FROM
										(
											SELECT R1.*
											FROM `{$fw->dbNew}recommendations` R1
											WHERE R1.cache_tags IS NULL
											LIMIT 0,{$limit}
										) AS R
										LEFT JOIN `{$fw->dbNew}ratings` Ra ON ( Ra.rid = R.ratingid )
										LEFT JOIN `{$fw->dbNew}recommendation_relations`rRT ON ( rRT.recid = R.recid )
											LEFT JOIN `{$fw->dbNew}tags` T ON ( T.tid = rRT.relid AND rRT.type='T' )
												LEFT JOIN `{$fw->dbNew}tag_groups` TG ON ( TG.tgid = T.tgid )
											LEFT JOIN `{$fw->dbNew}characters` Ch ON ( Ch.charid = rRT.relid AND rRT.type = 'CH' )
											LEFT JOIN `{$fw->dbNew}categories` Cat ON ( Cat.cid = rRT.relid AND rRT.type = 'CA' )
								)AS SELECT_OUTER
								GROUP BY recid ORDER BY recid ASC;");
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$recommMap = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."recommendations" );
		foreach ( $dataIn as $item)
		{
			$recommMap->load(array("recid=?",$item['recid']));
			$recommMap->cache_tags			= json_encode(upgradetools::cleanResult($item['tagblock']));
			$recommMap->cache_characters	= json_encode(upgradetools::cleanResult($item['characterblock']));
			$recommMap->cache_categories	= json_encode(upgradetools::cleanResult($item['categoryblock']));
			$recommMap->cache_rating		= json_encode(explode(",",$item['rating']));
			$recommMap->save();

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

?>