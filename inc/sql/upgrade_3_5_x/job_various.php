<?php
/*
	Job definition for 'various'
	eFiction upgrade from version 3.5.x
	
	2017-01-27: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"logs"			=> "Copy action log",
		"news"			=> "Copy news entries",
);		

if("+"==$fw['installerCFG.optional.shoutbox'][1])
// add shoutbox
$fw->jobSteps += array(
		"shoutbox"	=> "Copy shoutbox data"
);

if("+"==$fw['installerCFG.optional.poll'][1])
// add poll
$fw->jobSteps += array(
		"poll"	=> "Copy polls", 
		"poll_votes" => "Copy poll entries"
);

if("+"==$fw['installerCFG.optional.tracker'][1])
// add shoutbox
$fw->jobSteps += array(
		"tracker"	=> "Copy tracker data"
);

function various_logs($job, $step)
{
	$fw = \Base::instance();
	$limit = 250;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}log`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT L.log_id as id, L.log_action as action, L.log_uid as uid, L.log_ip as ip, L.log_timestamp as timestamp, L.log_type as type FROM `{$fw->dbOld}log`L LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."log" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->version = 0;
			$newdata->new = 0;
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

function various_news($job, $step)
{
	$fw = \Base::instance();
	$limit = 500;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}news`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
								N.nid, A.uid, N.title as headline, N.story as newstext, N.time as datetime, N.comments
								FROM `{$fw->dbOld}news`N
									LEFT JOIN `{$fw->dbOld}authors`A ON ( N.author = A.penname )
								ORDER BY N.time ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."news" );

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

function various_tracker($job, $step)
{
	$fw = \Base::instance();
	$limit = 500;

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}tracker`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
		}
		catch (PDOException $e)
		{
			// There's no source table, so we just finish with 0 entries
			$tracking->items = -1;
			$tracking->success = 2;
			$tracking->save();
			return TRUE; // escape plan
		}
	}
	
	$dataIn = $fw->db3->exec("SELECT `sid`, `uid`, `last_read`
								FROM `{$fw->dbOld}tracker` 
								ORDER BY sid, uid ASC LIMIT {$step['items']},{$limit};");
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', 
							'{$data['uid']}',
							'{$data['last_read']}' )";

		// only numeric values
		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}tracker` (`sid`, `uid`, `last_read`) VALUES ".implode(", ",$values)."; " );
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

function various_shoutbox($job, $step)
{
	$fw = \Base::instance();
	$limit = 500;

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}shoutbox`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
		}
		catch (PDOException $e)
		{
			// There's no source table, so we just finish with 0 entries
			$tracking->items = -1;
			$tracking->success = 2;
			$tracking->save();
			return TRUE; // escape plan
		}
	}

	$dataIn = $fw->db3->exec("SELECT 
									`shout_id` as id, 
									IF(shout_name REGEXP '[0-9]+',shout_name,0) as uid, 
									IF(shout_name REGEXP '[0-9]+',NULL,shout_name) as guest_name, 
									`shout_message` as message, 
									FROM_UNIXTIME(`shout_datestamp`) as date
								FROM `{$fw->dbOld}shoutbox` 
								ORDER BY shout_datestamp ASC LIMIT {$step['items']},{$limit};");
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."shoutbox" );

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

function various_poll($job, $step)
{
	$fw = \Base::instance();
	$limit = 20;

	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."poll" );

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}poll`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
		}
		catch (PDOException $e)
		{
			// There's no source table, so we just finish with 0 entries
			$tracking->items = -1;
			$tracking->success = 2;
			$tracking->save();
			return TRUE; // escape plan
		}
	}
	
	$dataIn = $fw->db3->exec("SELECT `poll_id`, `poll_question` as question, `poll_opts` as options, `poll_start` as start_date, `poll_end` as end_date, `poll_results` as results
								FROM `{$fw->dbOld}poll` 
								ORDER BY `poll_id` ASC LIMIT {$step['items']},{$limit};");
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);
	
	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
		{
			$data['results'] = ($data['results']==NULL) ? NULL : json_encode(explode("#",$data['results']));
			$data['options'] = json_encode(explode("|#|",$data['options']));

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

function various_poll_votes($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 50;

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}poll_votes`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
		}
		catch (PDOException $e)
		{
			// There's no source table, so we just finish with 0 entries
			$tracking->items = -1;
			$tracking->success = 2;
			$tracking->save();
			return TRUE; // escape plan
		}
	}
	
	$dataIn = $fw->db3->exec("SELECT `vote_id`, `vote_user`, `vote_opt`, `vote_poll`
								FROM `{$fw->dbOld}poll_votes` 
								ORDER BY `vote_id` ASC LIMIT {$step['items']},{$limit};");
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['vote_id']}', 
							'{$data['vote_user']}',
							'{$data['vote_opt']}',
							'{$data['vote_poll']}' )";

		// only numeric values
		$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}poll_votes` (`vote_id`, `poll_id`, `uid`, `option`) VALUES ".implode(", ",$values)."; " );
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

?>
