<?php
/*
	Job definition for 'contests'
	eFiction upgrade from version 3.5.x

	This is a collection of:
	- recommendations
	- recommendations relation tables
	- cache tables and fields

	2017-01-27: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"data"					=> "Copy old challenges",
		"relations"				=> "Contest relations",
		"cache"					=> "Build cache fields",
	);

function contests_data($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.medium");
	$i = 0;

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}challenges`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
									`chalid` as conid,
									`uid`,
									`title`,
									`summary` as description
								FROM `{$fw->dbOld}challenges`
								ORDER BY `chalid` ASC LIMIT {$step['items']},{$limit};");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."contests" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->description = preg_replace( ['/(?:\R|\r|\n)+/', '/<br\s*\/*>/'] , ['', "\n"] , $data['description'] );
			$newdata->active = 4;
			$newdata->votable= 3;
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

function contests_relations($job, $step)
{
	$fw = \Base::instance();

	// recid, relid, type
	// get characters
	$dataIn = $fw->db3->exec("SELECT C.chalid,Ch.charid as `relid`, 'CH' as 'type'
				FROM `{$fw->dbOld}challenges`C
					INNER JOIN `{$fw->dbOld}characters`Ch ON (FIND_IN_SET(Ch.charid,C.characters));");

	// get categories
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT C.chalid, Cat.catid as `relid`, 'CA' as 'type'
				FROM `{$fw->dbOld}challenges`C
					INNER JOIN `{$fw->dbOld}categories`Cat ON (FIND_IN_SET(Cat.catid,C.catid));") );

	// get stories
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT C.chalid, S.sid as `relid`,'ST' AS `type`
				FROM `{$fw->dbOld}challenges`C
					INNER JOIN `{$fw->dbOld}stories`S ON (FIND_IN_SET(C.chalid, S.challenges));") );

	// get series
	$dataIn = array_merge( $dataIn, $fw->db3->exec("SELECT C.chalid, S.seriesid as `relid`,'CO' AS `type`
				FROM `{$fw->dbOld}challenges`C
					INNER JOIN `{$fw->dbOld}series`S ON (FIND_IN_SET(C.chalid, S.challenges));") );

	if ( !empty($dataIn) )
	{
		// build the insert values, only numeric so bulk-insert
		foreach($dataIn as $data)
			$values[] = "( '{$data['chalid']}', '{$data['relid']}', '{$data['type']}' )";

		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}contest_relations` (`conid`, `relid`, `type`) VALUES ".implode(", ",$values)."; " );
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

function contests_cache($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.heavy");

	if ( $step['success'] == 0 )
	{
		$total = $fw->db5->exec("SELECT COUNT(*) as found FROM `{$fw->dbNew}contests`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db5->exec("SELECT
								Con.conid,
								GROUP_CONCAT(DISTINCT Chara.charid,',',Chara.charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
								GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
								GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock
									FROM
									(
										SELECT Con1.conid
											FROM `{$fw->dbNew}contests`Con1
											WHERE Con1.cache_tags IS NULL
											LIMIT 0,{$limit}
									) AS Con
										LEFT JOIN `{$fw->dbNew}contest_relations`rC ON ( rC.conid = Con.conid )
											LEFT JOIN `{$fw->dbNew}tags`T ON ( T.tid = rC.relid AND rC.type = 'T' )
												LEFT JOIN `{$fw->dbNew}tag_groups`TG ON ( TG.tgid = T.tgid )
											LEFT JOIN `{$fw->dbNew}characters`Chara ON ( Chara.charid = rC.relid AND rC.type = 'CH' )
											LEFT JOIN `{$fw->dbNew}categories`C ON ( C.cid = rC.relid AND rC.type = 'CA' )
									GROUP BY Con.conid;");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ( $dataIn as $item)
		{
			$fw->db5->exec
			(
					"UPDATE `{$fw->dbNew}contests` SET
						`cache_tags`		= :tagblock,
						`cache_characters`	= :characterblock,
						`cache_categories`	= :categoryblock
					WHERE conid = {$item['conid']} ;",
					[
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
