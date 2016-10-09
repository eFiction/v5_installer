<?php
/*
	Job definition for 'recommendations'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- recommendations
	- recommendations relation tables
	- cache tables and fields
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
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	$limit = 100;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}recommendations`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
					Rec.recid,
					IF(Rec.uid>0,Rec.uid,0) as uid,
					IF(Rec.uid>0,NULL,Rec.recname) as guestname,
					`url`, 
					`title`, 
					`author`, 
					`summary`, 
					`comments`, 
					`rid`, 
					Rec.date, 
					`validated`, 
					`completed`,
					(10*SUM(R1.rating)/COUNT(R1.reviewid)) as ranking,
					COUNT(R.reviewid) as reviews
				FROM `{$old}recommendations`Rec
					LEFT JOIN `{$old}reviews`R ON ( Rec.recid = R.item AND R.type = 'RC' )
					LEFT JOIN `{$old}reviews`R1 ON ( Rec.recid = R1.item AND R1.rating > 0 AND R1.type = 'RC' )
				GROUP BY Rec.recid
				ORDER BY Rec.recid ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		// build the insert values

		foreach($dataIn as $data)
			$values[] = "( '{$data['recid']}', 
							'{$data['uid']}',
							{$fw->db5->quote($data['guestname'])},
							{$fw->db5->quote($data['url'])},
							{$fw->db5->quote($data['title'])},
							{$fw->db5->quote($data['author'])},
							{$fw->db5->quote($data['summary'])},
							{$fw->db5->quote($data['comments'])},
							'{$data['rid']}',
							'{$data['date']}',
							'{$data['validated']}',
							'{$data['completed']}',
							'{$data['ranking']}',
							'{$data['reviews']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}recommendations` ( `recid`, `uid`, `guestname`, `url`, `title`, `author`, `summary`, `comment`, `ratingid`, `date`, `validated`, `completed`, `ranking`, `reviews` ) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
		
		$tracking->items = $tracking->items+$count;
		$tracking->save();
	}

	if ( $count == 0 OR $count < $limit )
	{
		// There was either nothing to be done, or there are no elements left for the next run
		$tracking->success = 2;
		$tracking->save();
	}
}

function recommendations_featured($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	$i = 0;
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.pre_new']."featured" );
	
	$dataIn = $fw->db3->exec("SELECT Rec.recid as id, Rec.featured as status, 'RC' as type FROM `{$old}recommendations`Rec WHERE Rec.featured > 0;");
	
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

function recommendations_relations($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	
	// recid, relid, type
	// get tags (formerly classes)
	$dataIn = $fw->db3->exec("SELECT Rec.recid,C.class_id as relid,'T' as `type`
				FROM `{$old}recommendations`Rec
					INNER JOIN `{$old}classes`C ON (FIND_IN_SET(C.class_id,Rec.classes)>0);");
	// get characters
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT Rec.recid,Ch.charid as relid,'CH' as `type`
				FROM `{$old}recommendations`Rec
					INNER JOIN `{$old}characters`Ch ON (FIND_IN_SET(Ch.charid,Rec.charid)>0);") );
	// get categories
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT Rec.recid,Cat.catid as relid,'CA' as `type`
				FROM `{$old}recommendations`Rec
					INNER JOIN `{$old}categories`Cat ON (FIND_IN_SET(Cat.catid,Rec.catid)>0);") );

	// build the insert values
	foreach($dataIn as $data)
		$values[] = "( '{$data['recid']}', '{$data['relid']}', '{$data['type']}' )";

	$fw->db5->exec ( "INSERT INTO `{$new}recommendation_relations` (`recid`, `relid`, `type`) VALUES ".implode(", ",$values)."; " );
	$count = $fw->db5->count();
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function recommendations_cache($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$limit = 50;
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$new}recommendations`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
											FROM `{$new}recommendations` R1
											WHERE R1.cache_tags IS NULL
											LIMIT 0,{$limit}
										) AS R
										LEFT JOIN `{$new}ratings` Ra ON ( Ra.rid = R.ratingid )
										LEFT JOIN `{$new}recommendation_relations`rRT ON ( rRT.recid = R.recid )
											LEFT JOIN `{$new}tags` T ON ( T.tid = rRT.relid AND rRT.type='T' )
												LEFT JOIN `{$new}tag_groups` TG ON ( TG.tgid = T.tgid )
											LEFT JOIN `{$new}characters` Ch ON ( Ch.charid = rRT.relid AND rRT.type = 'CH' )
											LEFT JOIN `{$new}categories` Cat ON ( Cat.cid = rRT.relid AND rRT.type = 'CA' )
								)AS SELECT_OUTER
								GROUP BY recid ORDER BY recid ASC;");
	
	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			$fw->db5->exec
			(
				"UPDATE `{$new}recommendations` SET 
					`cache_tags`		= :tagblock,
					`cache_characters`	= :characterblock,
					`cache_categories`	= :categoryblock,
					`cache_rating`		= :rating
				WHERE recid = {$item['recid']} ;",
				[
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

	if ( $count == 0 OR $count < $limit )
	{
		// There was either nothing to be done, or there are no elements left for the next run
		$tracking->success = 2;
		$tracking->save();
	}
}

?>