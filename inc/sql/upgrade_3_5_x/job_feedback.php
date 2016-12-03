<?php
/*
	Job definition for 'feedback'
	eFiction upgrade from version 3.5.x
*/

$fw->jobSteps = array(
		"reviews"					=> "Copy reviews (without the comment)",
		"comments"					=> "Copy reviews comments",
		"news"					=> "Copy news comments",
);		
		
function feedback_reviews($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;
	$i = 0;
	
	$review_split = "<br><br><i>";
	
	//$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories" );

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}reviews`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
									reviewid, 
									item, 
									chapid, 
									IF(uid=0,reviewer,NULL) as reviewer, 
									uid, 
									SUBSTRING_INDEX(review, '{$review_split}', 1) as content, 
									date, 
									rating, 
									type 
								FROM `{$old}reviews` Rv ORDER BY Rv.reviewid LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		/* this is slower, but requires no quoting. will keep the code in case need arises
		foreach($dataIn as $data)
		{
			$i++;
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();

			$tracking->items = $tracking->items+1;
			$tracking->save();
		}*/
		// build the insert values

		foreach($dataIn as $data)
			$values[] = "( '{$data['reviewid']}', '{$data['item']}', '{$data['chapid']}', {$fw->db5->quote($data['reviewer'])}, '{$data['uid']}', {$fw->db5->quote($data['content'])}, '{$data['date']}', '{$data['rating']}', '{$data['type']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}feedback` (`fid`, `reference`, `reference_sub`, `writer_name`, `writer_uid`, `text`, `datetime`, `rating`, `type`) VALUES ".implode(", ",$values)."; " );
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

function feedback_comments($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;
	$i = 0;
	
	$review_split = "<br><br><i>";
	
	//$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories" );

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}reviews`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT reviewid, 
									IF(CA.uid IS NULL,S.uid,'-1') as uid, NULL as writer_name,
									TRIM(TRAILING '</i>' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(review, '{$review_split}', -1), ': ', -1) ) as content
								FROM `{$old}reviews` Rv 
									LEFT JOIN `{$old}stories`S ON ( S.sid = Rv.item )
									LEFT JOIN `{$old}coauthors`CA ON ( CA.sid = Rv.item )
								WHERE LOCATE('{$review_split}', Rv.review) > 0 GROUP BY Rv.reviewid ORDER BY Rv.date LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{

		/* this is slower, but requires no quoting. will keep the code in case need arises
		foreach($dataIn as $data)
		{
			$i++;
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();

			$tracking->items = $tracking->items+1;
			$tracking->save();
		}*/
		// build the insert values

		foreach($dataIn as $data)
		{
			$values[] = "( '{$data['reviewid']}', NULL, NULL, '{$data['uid']}', {$fw->db5->quote($data['content'])}, 'C' )";
		}
		
		$fw->db5->exec ( "INSERT INTO `{$new}feedback` (`reference`, `reference_sub`, `writer_name`, `writer_uid`, `text`, `type`) VALUES ".implode(", ",$values)."; " );
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

function feedback_news($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	$limit = 500;
	$i = 0;
		
	//$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."stories" );

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}comments`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT C.nid, C.uid, C.comment, C.time FROM `{$old}comments`C LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{

		/* this is slower, but requires no quoting. will keep the code in case need arises
		foreach($dataIn as $data)
		{
			$i++;
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();

			$tracking->items = $tracking->items+1;
			$tracking->save();
		}*/
		// build the insert values

		foreach($dataIn as $data)
			$values[] = "( '{$data['nid']}', '{$data['uid']}', {$fw->db5->quote($data['comment'])}, '{$data['time']}', 'N' )";
		
		$fw->db5->exec ( "INSERT INTO `{$new}feedback` ( `reference`, `writer_uid`, `text`, `datetime`, `type` ) VALUES ".implode(", ",$values)."; " );
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