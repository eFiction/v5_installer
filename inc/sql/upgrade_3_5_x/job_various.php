<?php
/*
	Job definition for 'various'
	eFiction upgrade from version 3.5.x
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
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}log`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT L.log_id, L.log_action, L.log_uid, L.log_ip, L.log_timestamp, L.log_type FROM `{$old}log`L LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['log_id']}', 
							{$fw->db5->quote($data['log_action'])},
							'{$data['log_uid']}',
							'{$data['log_ip']}',
							'{$data['log_timestamp']}',
							'{$data['log_type']}',
							1 )";

		$fw->db5->exec ( "INSERT INTO `{$new}log` VALUES ".implode(", ",$values)."; " );
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

function various_news($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}news`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
								N.nid, A.uid, N.title, N.story, N.time, N.comments
								FROM `{$old}news`N
									LEFT JOIN `{$old}authors`A ON ( N.author = A.penname )
								ORDER BY N.time ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['nid']}', 
							'{$data['uid']}',
							{$fw->db5->quote($data['title'])},
							{$fw->db5->quote($data['story'])},
							'{$data['time']}',
							'{$data['comments']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}news` VALUES ".implode(", ",$values)."; " );
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

function various_tracker($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}tracker`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
								FROM `{$old}tracker` 
								ORDER BY sid, uid ASC LIMIT {$step['items']},{$limit};");
	
	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['sid']}', 
							'{$data['uid']}',
							'{$data['last_read']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}tracker` (`sid`, `uid`, `last_read`) VALUES ".implode(", ",$values)."; " );
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

function various_shoutbox($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}shoutbox`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
	
	$dataIn = $fw->db3->exec("SELECT `shout_id`, `shout_name`, `shout_message`, FROM_UNIXTIME(`shout_datestamp`) as shout_datestamp
								FROM `{$old}shoutbox` 
								ORDER BY shout_datestamp ASC LIMIT {$step['items']},{$limit};");
	
	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( {$data['shout_id']}, 
							{$data['shout_name']},
							{$fw->db5->quote($data['shout_message'])},
							'{$data['shout_datestamp']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}shoutbox` (`id`, `uid`, `message`, `date`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
		
		$tracking->items = $tracking->items+$count;
		$tracking->save();
	}

	if ( $count == 0 OR $count <= $limit )
	{
		// There was either nothing to be done, or there are no elements left for the next run
		$tracking->success = 2;
		$tracking->save();
	}
}

function various_poll($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 20;

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."poll" );

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}poll`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
								FROM `{$old}poll` 
								ORDER BY `poll_id` ASC LIMIT {$step['items']},{$limit};");
	
	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
		{
			$data['results'] = ($data['results']==NULL) ? NULL : json_encode(explode("#",$data['results']));
			$data['options'] = json_encode(explode("|#|",$data['options']));

			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();

			$tracking->items = $tracking->items+1;
			$tracking->save();
		}
	}

	if ( $count == 0 OR $count <= $limit )
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

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( $step['success'] == 0 )
	{
		try
		{
			$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}poll_votes`;")[0]['found'];
			$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
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
								FROM `{$old}poll_votes` 
								ORDER BY `vote_id` ASC LIMIT {$step['items']},{$limit};");
	
	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['vote_id']}', 
							'{$data['vote_user']}',
							'{$data['vote_opt']}',
							'{$data['vote_poll']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}poll_votes` (`vote_id`, `poll_id`, `uid`, `option`) VALUES ".implode(", ",$values)."; " );
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

?>
