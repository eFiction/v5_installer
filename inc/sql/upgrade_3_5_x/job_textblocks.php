<?php
/*
	Job definition for 'textblocks'
	eFiction upgrade from version 3.5.x
	
	2017-01-28: Update DB queries
*/

$fw->jobSteps = array(
	"copy"	=> "Copy existing data",
	"mark"	=> "Mark the old admin block area as block_only",
	"add"	=> "Add a registration and a cookie consent page",
	);


function textblocks_copy($job, $step)
{
	// Copy the existing blocks
	$fw = \Base::instance();
	$i = 0;
	
	$newdata = new \DB\SQL\Mapper( $fw->db5, $fw['installerCFG.db5.prefix']."textblocks" );

	$dataIn = $fw->db3->exec("SELECT message_id as id, message_name as label, message_title as title, message_text as content, 1 as as_page FROM `{$fw->dbOld}messages`;");
	foreach($dataIn as $data)
	{
		$i++;
		$newdata->copyfrom($data);
		$newdata->save();
		$newdata->reset();
	}
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $i,
							':id' => $step['id']
						]
					);
}

function textblocks_mark($job, $step)
{
	$fw = \Base::instance();
	$i = 0;
	
	$fw->db5->exec("UPDATE `{$fw->dbNew}textblocks`T SET T.as_page=0 WHERE T.id IN(1,2,4,5,7,9);");
	
	$count = $fw->db5->count();
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function textblocks_add($job, $step)
{
	$fw = \Base::instance();
	
	$fw->db5->exec("INSERT INTO `{$fw->dbNew}textblocks` (`label`, `title`, `content`, `as_page`) VALUES
					('registration', '__Registration', 'By registering, you consent to the following rules: No BS-ing!', 0),
					('eucookie', '(EU) Cookie consent', 'Cookie stuff ...', '1');");
					
	$count = $fw->db5->count();
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

?>