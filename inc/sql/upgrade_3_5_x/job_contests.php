<?php
/*
	Job definition for 'contests'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- recommendations
	- recommendations relation tables
	- cache tables and fields
*/

$fw->jobSteps = array(
		"data"					=> "Copy old challenges",
		"relations"				=> "Contest relations",
		"cache"					=> "Build cache fields",
	);

function contests_data($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 100;
	$i = 0;
	
	//$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories" );
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$old}challenges`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT
								`chalid`, `uid`, `title`, `summary` 
								FROM `{$old}challenges`
								ORDER BY `chalid` ASC LIMIT {$step['items']},{$limit};");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		// build the insert values
		foreach($dataIn as $data)
			$values[] = "( '{$data['chalid']}',
							'{$data['uid']}',			
							{$fw->db5->quote($data['title'])},
							{$fw->db5->quote($data['summary'])} )";

		$fw->db5->exec ( "INSERT INTO `{$new}contests` ( `conid`, `uid`, `title`, `summary` ) VALUES ".implode(", ",$values)."; " );
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

function contests_relations($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	
	// recid, relid, type
	// get characters
	$dataIn = $fw->db3->exec("SELECT C.chalid,Ch.charid as `relid`, 'CH' as 'type'
				FROM `{$old}challenges`C
					INNER JOIN `{$old}characters`Ch ON (FIND_IN_SET(Ch.charid,C.characters));");

	// get categories
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT C.chalid, Cat.catid as `relid`, 'CA' as 'type'
				FROM `{$old}challenges`C
					INNER JOIN `{$old}categories`Cat ON (FIND_IN_SET(Cat.catid,C.catid));") );

	// get stories
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT C.chalid, S.sid as `relid`,'ST' AS `type` 
				FROM `{$old}challenges`C
					INNER JOIN `{$old}stories`S ON (FIND_IN_SET(C.chalid, S.challenges));") );

	// build the insert values
	foreach($dataIn as $data)
		$values[] = "( '{$data['chalid']}', '{$data['relid']}', '{$data['type']}' )";

	$fw->db5->exec ( "INSERT INTO `{$new}contest_relations` (`conid`, `relid`, `type`) VALUES ".implode(", ",$values)."; " );
	$count = $fw->db5->count();
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function contests_cache($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$limit = 50;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$new}contests`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT 
								Con.conid,
								GROUP_CONCAT(DISTINCT S.sid,',',S.title,',',U.uid,',',U.nickname ORDER BY charname ASC SEPARATOR '||') AS storyblock,
								GROUP_CONCAT(DISTINCT Chara.charid,',',Chara.charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
								GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
								GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock
									FROM 
									(
										SELECT Con1.conid
											FROM `{$new}contests`Con1
											WHERE Con1.cache_tags IS NULL
											LIMIT 0,{$limit}
									) AS Con
										LEFT JOIN `{$new}contest_relations`rC ON ( rC.conid = Con.conid )
											LEFT JOIN `{$new}stories`S ON ( S.sid = rC.relid and rC.type='ST' )
												LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
													LEFT JOIN `{$new}users`U ON ( U.uid = rSA.aid )
											LEFT JOIN `{$new}tags`T ON ( T.tid = rC.relid AND rC.type = 'T' )
												LEFT JOIN `{$new}tag_groups`TG ON ( TG.tgid = T.tgid )
											LEFT JOIN `{$new}characters`Chara ON ( Chara.charid = rC.relid AND rC.type = 'CH' )
											LEFT JOIN `{$new}categories`C ON ( C.cid = rC.relid AND rC.type = 'CA' )
									GROUP BY Con.conid;");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			$fw->db5->exec
			(
					"UPDATE `{$new}contests` SET 
						`cache_stories`		= :storyblock,
						`cache_tags`		= :tagblock,
						`cache_characters`	= :characterblock,
						`cache_categories`	= :categoryblock
					WHERE conid = {$item['conid']} ;",
					[
						':storyblock'		=> json_encode(upgradetools::cleanResult($item['storyblock'])),
						':tagblock'			=> json_encode(upgradetools::cleanResult($item['tagblock'])),
						':characterblock'	=> json_encode(upgradetools::cleanResult($item['characterblock'])),
						':categoryblock'	=> json_encode(upgradetools::cleanResult($item['categoryblock'])),
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