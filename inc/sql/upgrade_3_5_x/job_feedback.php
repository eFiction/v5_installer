<?php
/*
	Job definition for 'feedback'
	eFiction upgrade from version 3.5.x

	2017-01-28: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"reviews"					=> "Copy reviews (without the comment)",
		"comments"					=> "Copy reviews comments",
		"news"					=> "Copy news comments",
);		
		
function feedback_reviews($job, $step)
{
	$fw = \Base::instance();
	$limit = 500;
	$i = 0;
	
	$review_split = "<br><br><i>";
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}reviews`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
									reviewid as fid, 
									item as reference, 
									chapid as reference_sub, 
									IF(uid=0,reviewer,NULL) as writer_name, 
									uid as writer_uid, 
									SUBSTRING_INDEX(review, '{$review_split}', 1) as text, 
									date as datetime, 
									rating, 
									type 
								FROM `{$fw->dbOld}reviews` Rv ORDER BY Rv.reviewid LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."feedback" );

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

function feedback_comments($job, $step)
{
	$fw = \Base::instance();
	$limit = 500;
	$i = 0;
	
	$review_split = "<br><br><i>";
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}reviews`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT reviewid as reference, 
									NULL as reference_sub,
									NULL as writer_name,
									IF(CA.uid IS NULL,S.uid,'-1') as writer_uid, 
									TRIM(TRAILING '</i>' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(review, '{$review_split}', -1), ': ', -1) ) as text
								FROM `{$fw->dbOld}reviews` Rv 
									LEFT JOIN `{$fw->dbOld}stories`S ON ( S.sid = Rv.item )
									LEFT JOIN `{$fw->dbOld}coauthors`CA ON ( CA.sid = Rv.item )
								WHERE LOCATE('{$review_split}', Rv.review) > 0 GROUP BY Rv.reviewid ORDER BY Rv.date LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."feedback" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->type = 'C';
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

function feedback_news($job, $step)
{
	$fw = \Base::instance();
	$limit = 500;
	$i = 0;
		
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}comments`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT 
									C.nid as reference, 
									NULL as reference_sub,
									NULL as writer_name,
									C.uid as writer_uid,
									C.comment as text,
									C.time as datetime
								FROM `{$fw->dbOld}comments`C
								LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."feedback" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->type = 'N';
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

?>