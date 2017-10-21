<?php
/*
	Job definition for 'various'
	eFiction upgrade from version 3.5.x
	
	2017-01-27: Update DB queries to be safer
*/

$fw->jobSteps = array(
);		

if(1==$fw['installerCFG.optional.shoutbox'])
// add shoutbox
$fw->jobSteps += array(
		"shoutbox"	=> "Copy shoutbox data"
);


function various_shoutbox($job, $step)
{
	$fw = \Base::instance();
	
	$fw->db5->exec("INSERT INTO `{$fw->dbNew}shoutbox` (`uid`, `guest_name`, `message`, `date`) 
					VALUES
					('0', 'eFiction', 'Welcome to the shoutbox', CURRENT_TIMESTAMP);");

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
		[ 
			':items'	=> 1,
			':id'		=> $step['id']
		]
	);
}

?>
