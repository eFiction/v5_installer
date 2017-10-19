<?php
/*
	Job definition for 'descriptors'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- characters
	- categories
	- ratings
	- tags

	2017-01-27: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"characters"	=> "Copy characters",
		"categories"	=> "Copy categories",
		"ratings"		=> "Copy (age) rating",
		"tag_groups"	=> "Import tag groups from class types",
		"tags"			=> "Import tags from classes",
	);


function descriptors_characters($job, $step)
{
	$fw = \Base::instance();
	
	$dataIn = $fw->db3->exec("SELECT
						`charid`, `catid`, `charname`, `bio` as biography, `image`
						FROM `{$fw->dbOld}characters`;");
	
	$count = 0;

	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."characters" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();
			
			$count++;
		}
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_categories($job, $step)
{
	$fw = \Base::instance();
	
	$dataIn = $fw->db3->exec("SELECT
							`catid` as cid,
							IF(`parentcatid`='-1',0,`parentcatid`) as parent_cid,
							`category`,
							`description`,
							`image`,
							`locked`,
							0 as `leveldown`,
							`displayorder` as inorder
						FROM `{$fw->dbOld}categories`;");
						
						// test: 0 as `leveldown`

	$count = 0;

	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."categories" );

		foreach($dataIn as $data)
		{
			// testing 'erce'
			// set the leveldown for the root categories to 0, all others require recount
			$relist[$data['cid']] = ($data['parent_cid']==0) ? 0 : NULL;
			
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();
			
			$count++;
		}

		// test
		while ( FALSE !== $reData = $newdata->load(['parent_cid > 0 AND leveldown = 0', $step['id'] ]) )
		{
			do
			{
				if ( NULL !== $relist[$newdata->parent_cid] )
				{
					$relist[$newdata->cid] = $relist[$newdata->parent_cid] + 1;
					$newdata->leveldown = $relist[$newdata->cid];
					$newdata->save();
				}
			}
			while ( $newdata->next() );
		}
		// end test
		
	}
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);

}

function descriptors_ratings($job, $step)
{
	$fw = \Base::instance();

	$dataIn = $fw->db3->exec("SELECT
						`rid`, `rating`, `ratingwarning`, `warningtext`
						FROM `{$fw->dbOld}ratings`;");

	$count = 0;

	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."ratings" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();
			
			$count++;
		}
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_tag_groups($job, $step)
{
	$fw = \Base::instance();

	$dataIn = $fw->db3->exec("SELECT
							`classtype_id` as tgid,
							`classtype_name` as label,
							`classtype_title` as description
						FROM `{$fw->dbOld}classtypes`;");

	$count = 0;

	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."tag_groups" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();
			
			$count++;
		}
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_tags($job, $step)
{
	$fw = \Base::instance();

	$dataIn = $fw->db3->exec("SELECT
						`class_id` as tid,
						`class_type` as tgid,
						`class_name` as label
						FROM `{$fw->dbOld}classes`;");

	$count = 0;

	if ( sizeof($dataIn)>0 )
	{
		$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."tags" );

		foreach($dataIn as $data)
		{
			$newdata->copyfrom($data);
			$newdata->save();
			$newdata->reset();
			
			$count++;
		}
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

