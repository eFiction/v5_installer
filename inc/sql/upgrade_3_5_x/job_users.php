<?php
/*
	Job definition for 'users'
	eFiction upgrade from version 3.5.x
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
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	
	$fw->db5->exec ( "INSERT INTO `{$new}users`
							(`login`, `nickname`, `realname`, `password`, `email`, `registered` )
							VALUES
							('Guest', 'Guest', '', '', '', '0000-00-00 00:00:00');" );
							
	$fw->db5->exec ( "UPDATE `{$new}users` SET `uid` = '0' ;" );
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = 1 WHERE `id` = :id ", 
						[ 
							':id' => $step['id']
						]
					);
}

function users_copy($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	$limit = 100;
	$i = 0;
	
	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}authors` WHERE uid > 0;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT
								A.uid,
								A.penname,
								A.realname,
								IF(CAST(A.password AS CHAR(32))='0',NULL,A.password) as password,
								replace(A.email , ' ','') as email,
								A.date,
								AP.level,
								-- (SELECT 255 FROM `{$old}authorprefs` P where P.level = 1 AND P.uid = A.uid) as groups,
								A.bio,
								AP.newreviews, AP.newrespond, AP.alertson,
								AP.ageconsent, AP.tinyMCE, AP.sortby, AP.storyindex, -- to JSON
								AP.validated,
								COUNT(S.sid) as storycount
							FROM `{$old}authors`A
								LEFT JOIN `{$old}authorprefs`AP ON ( A.uid=AP.uid )
								LEFT JOIN `{$old}stories` S ON ( S.uid = A.uid )
							WHERE A.uid > 0
							GROUP BY A.uid
							ORDER BY A.date ASC LIMIT {$step['items']},{$limit};");
				
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		// build the insert values

		foreach($dataIn as $data)
		{
			$preferences =
			[
				"ageconsent"	=>	$data['ageconsent'],
				"useEditor"		=>	$data['tinyMCE'],
				"sortNew"		=>	$data['sortby'],
				"showTOC"		=>	$data['storyindex'],
				"language"		=>	'',
				"layout"		=>	'',
				"hideTags"		=>	'',
			];

			if ( $data['level']==1 )
				$data['groups'] = 255;

			elseif ( $data['validated']==1 )
				$data['groups'] = 13;

			elseif ( $data['storycount']>0 )
				$data['groups'] = 5;
			
			else
				$data['groups'] = 1;
			
			$values[] = "( '{$data['uid']}',
							{$fw->db5->quote($data['penname'])},
							{$fw->db5->quote($data['penname'])}, 
							{$fw->db5->quote($data['realname'])}, 
							{$fw->db5->quote($data['password'])}, 
							{$fw->db5->quote($data['email'])}, 
							'{$data['date']}', 
							'{$data['groups']}', 
							{$fw->db5->quote($data['bio'])},
							'{$data['newreviews']}', 
							'{$data['newrespond']}', 
							'{$data['alertson']}', 
							'".json_encode($preferences)."' )";
		}
		
		$fw->db5->exec ( "INSERT INTO `{$new}users` ( uid, login, nickname, realname, password, email, registered, groups, `about`, `alert_feedback`, `alert_comment`, `alert_favourite`, `preferences` ) VALUES ".implode(", ",$values)."; " );
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
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	$limit = 50;
	$i = 0;
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.pre_new']."user_fields" );
	
	$dataIn = $fw->db3->exec("SELECT `field_id`, `field_type`, `field_name`, `field_title`, `field_options`, `field_code_out`, `field_on`
							FROM `{$old}authorfields`
							ORDER BY `field_id` ASC;");	

	foreach($dataIn as $data)
	{
		$i++;
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
	}
	
	// Add avatar field (formerly image from author info)
	//$fw->db5->exec("INSERT INTO `{$new}user_fields` ( field_type, field_name, field_title ) VALUES ( 1, 'avatar', 'Avatar' );");

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $i,
							':id' => $step['id']
						]
					);

}

function users_info($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	$limit = 500;
	$i = 0;

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}authorinfo`Ai INNER JOIN `{$old}authorfields`Uf ON (Ai.field=Uf.field_id) WHERE uid > 0;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT Ai.uid, Ai.field, Ai.info
								FROM `{$old}authorinfo`Ai
									INNER JOIN `{$old}authorfields`Uf ON (Ai.field=Uf.field_id)
								ORDER BY Ai.uid, Ai.field ASC LIMIT {$step['items']},{$limit};");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ($dataIn as $data)
			$values[] = "( '{$data['uid']}',
							'{$data['field']}', 
							{$fw->db5->quote($data['info'])} )";
		
		$fw->db5->exec ( "INSERT INTO `{$new}user_info` ( `uid`, `field`, `info` ) VALUES ".implode(", ",$values)."; " );
		
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

function users_favourites($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
	$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
	$limit = 500;
	$i = 0;

	if ( $step['success'] == 0 )
	{
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$old}favorites` WHERE uid > 0;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}

	$dataIn = $fw->db3->exec("SELECT `uid`, `item`, `type`, `comments`
								FROM `{$old}favorites`
								ORDER BY uid, item, type ASC LIMIT {$step['items']},{$limit};");

	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'convert');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		foreach ($dataIn as $data)
			$values[] = "( '{$data['uid']}',
							'{$data['item']}', 
							'{$data['type']}', 
							{$fw->db5->quote($data['comments'])} )";
		
		$fw->db5->exec ( "INSERT INTO `{$new}user_favourites` ( `uid`, `item`, `type`, `comments` ) VALUES ".implode(", ",$values)."; " );
		
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