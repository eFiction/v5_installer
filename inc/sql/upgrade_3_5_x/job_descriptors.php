<?php
/*
	Job definition for 'descriptors'
	eFiction upgrade from version 3.5.x
	
	This is a collection of:
	- characters
	- categories
	- ratings
	- tags
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
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	
	$dataIn = $fw->db3->exec("SELECT
						`charid`, `catid`, `charname`, `bio`, `image`
						FROM `{$old}characters`;");
	
	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "( '{$data['charid']}',
							'{$data['catid']}',
							{$fw->db5->quote($data['charname'])},
							{$fw->db5->quote($data['bio'])},
							{$fw->db5->quote($data['image'])} )";

		$fw->db5->exec ( "INSERT INTO `{$new}characters` (`charid`, `catid`, `charname`, `biography`, `image`) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_categories($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";
	
	$dataIn = $fw->db3->exec("SELECT
						`catid`, IF(`parentcatid`='-1',0,`parentcatid`) as parentcatid, `category`, `description`, `image`, `locked`, `leveldown`, `displayorder`
						FROM `{$old}categories`;");

	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "(  '{$data['catid']}',
							'{$data['parentcatid']}',
							{$fw->db5->quote($data['category'])},
							{$fw->db5->quote($data['description'])},
							{$fw->db5->quote($data['image'])},
							'{$data['locked']}',
							'{$data['leveldown']}',
							'{$data['displayorder']}' )";

		$fw->db5->exec ( "INSERT INTO `{$new}categories` ( `cid`, `parent_cid`, `category`, `description`, `image`, `locked`, `leveldown`, `inorder` ) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_ratings($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";

	$dataIn = $fw->db3->exec("SELECT
						`rid`, `rating`, `ratingwarning`, `warningtext`
						FROM `{$old}ratings`;");

	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "(  '{$data['rid']}',
							{$fw->db5->quote($data['rating'])},
							'{$data['ratingwarning']}',
							{$fw->db5->quote($data['warningtext'])} )";

		$fw->db5->exec ( "INSERT INTO `{$new}ratings` ( `rid`, `rating`, `ratingwarning`, `warningtext` ) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_tag_groups($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";

	$dataIn = $fw->db3->exec("SELECT
						`classtype_id`, `classtype_name`, `classtype_title`
						FROM `{$old}classtypes`;");

	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "(  '{$data['classtype_id']}',
						{$fw->db5->quote($data['classtype_name'])},
						{$fw->db5->quote($data['classtype_title'])} )";

		$fw->db5->exec ( "INSERT INTO `{$new}tag_groups` ( `tgid`, `label`, `description` ) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

function descriptors_tags($job, $step)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$old = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";

	$dataIn = $fw->db3->exec("SELECT
						`class_id`, `class_type`, `class_name`
						FROM `{$old}classes`;");

	if ( sizeof($dataIn)>0 )
	{
		foreach($dataIn as $data)
			$values[] = "(  '{$data['class_id']}',
							'{$data['class_type']}',
							{$fw->db5->quote($data['class_name'])} )";

		$fw->db5->exec ( "INSERT INTO `{$new}tags` ( `tid`, `tgid`, `label` ) VALUES ".implode(", ",$values)."; " );
		$count = $fw->db5->count();
	}
	else $count = 0;

	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' 	 => $step['id']
						]
					);
}

