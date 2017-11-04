<?php
/*
	Job definition for 'users'
	eFiction upgrade from version 3.5.x

	2017-01-28: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"guest"			=> "Create guest entry",
		"copy"			=> "Copy users",
		"fields"		=> "Copy user fields",
		"info"			=> "Copy and adapt user info",
		"favourites"	=> "Copy favourites",
);		
		
function users_guest($job, $step)
{
	$fw = \Base::instance();
	
	$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}users`
							(`login`, `nickname`, `realname`, `password`, `email`, `registered` )
							VALUES
							('Guest', 'Guest', '', '', '', '0000-00-00 00:00:00');" );
							
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}users` SET `uid` = '0' ;" );
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = 1 WHERE `id` = :id ", 
						[ 
							':id' => $step['id']
						]
					);
}

function users_copy($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.medium");
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}authors` WHERE uid > 0;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
								A.uid,
								A.penname as login,
								A.penname as nickname,
								A.realname,
								IF(CAST(A.password AS CHAR(32))='0',NULL,A.password) as password,
								replace(A.email , ' ','') as email,
								A.date as registered,
								A.bio as about,
								AP.newreviews as alert_feedback,
								AP.newrespond as alert_comment,
								AP.alertson as alert_favourite,
								AP.ageconsent, AP.tinyMCE, AP.sortby, AP.storyindex, -- to JSON
								{$fw->customfields}
								AP.level, AP.validated, COUNT(S.sid) as storycount -- for Level
							FROM `{$fw->dbOld}authors`A
								LEFT JOIN `{$fw->dbOld}authorprefs`AP ON ( A.uid=AP.uid )
								LEFT JOIN `{$fw->dbOld}stories` S ON ( S.uid = A.uid AND S.validated > 0 )
							WHERE A.uid > 0
							GROUP BY A.uid
							ORDER BY A.date ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."users" );

		foreach($dataIn as $data)
		{
			// groups
			if ( $data['level']==1 )
				$data['groups'] = 255;

			elseif ( $data['validated']==1 )
				$data['groups'] = 13;

			elseif ( $data['storycount']>0 )
				$data['groups'] = 5;
			
			else
				$data['groups'] = 1;
			
			$newdata->copyfrom($data);
			$newdata->preferences = json_encode([
				"ageconsent"	=>	$data['ageconsent'],
				"useEditor"		=>	$data['tinyMCE'],
				"sortNew"		=>	$data['sortby'],
				"showTOC"		=>	$data['storyindex'],
				"language"		=>	'',
				"layout"		=>	'',
				"hideTags"		=>	'',
			]);
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

function users_fields($job, $step)
{
	/*
		1 = URL
		2 = Options
		3 = yes/no
		4 = URL with ID
		5 = code -> now tpl field
		6 = text
	*/
	$fw = \Base::instance();
	$limit = $fw->get("limit.heavy");
	$count = 0;
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."user_fields" );
	
	$dataIn = $fw->db3->exec("SELECT `field_id`, `field_type`, `field_name`, `field_title`, `field_options`, `field_code_out`, `field_on` as enabled
							FROM `{$fw->dbOld}authorfields`
							ORDER BY `field_id` ASC;");	

	foreach($dataIn as $data)
	{
		if ( $data['field_type']==2 ) $data['field_options'] = json_encode(explode("|#|",$data['field_options']));
		if ( $data['field_type']==5 )
		{
			// Code fields are now tpl fields
			$data['field_options']=$data['field_code_out'];
			// Disable, so it won't show weird stuff before being modified
			$data['field_on']=0;
		}
		$newdata->copyfrom($data);
		$newdata->save();
		$newdata->reset();

		$count++;
	}
	
	// Add avatar field (formerly image from author info)
	//$fw->db5->exec("INSERT INTO `{$fw->dbNew}user_fields` ( field_type, field_name, field_title ) VALUES ( 1, 'avatar', 'Avatar' );");

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);

}

function users_info($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.xlight");
	$i = 0;

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}authorinfo`Ai INNER JOIN `{$fw->dbOld}authorfields`Uf ON (Ai.field=Uf.field_id) WHERE uid > 0;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT Ai.uid, Ai.field, Ai.info, Uf.field_type
								FROM `{$fw->dbOld}authorinfo`Ai
									INNER JOIN `{$fw->dbOld}authorfields`Uf ON (Ai.field=Uf.field_id)
								ORDER BY Ai.uid, Ai.field ASC LIMIT {$step['items']},{$limit};");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."user_info" );

		foreach($dataIn as $data)
		{
			$newdata->uid	= $data['uid'];
			$newdata->field = $data['field'];
			if ( $data['field_type']== 3)
			{
				if ( in_array(strtolower($data['info']),["yes", "ja", "evet"]) ) $data['info'] = 1;
				elseif ( in_array(strtolower($data['info']),["no", "nein", "hayır"]) ) $data['info'] = 0;
			}
			$newdata->info	= $data['info'];
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

function users_favourites($job, $step)
{
	$fw = \Base::instance();
	$limit = $fw->get("limit.xlight");
	$i = 0;

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}favorites` WHERE uid > 0;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT `uid`, `item`, `type`, `comments`
								FROM `{$fw->dbOld}favorites`
								ORDER BY uid, item, type ASC LIMIT {$step['items']},{$limit};");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."user_favourites" );

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

?>