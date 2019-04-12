<?php
/*
	Job definition for 'users'
	eFiction upgrade from version 3.5.x

	2017-01-28: Update DB queries to be safer
*/

$fw->jobSteps = array(
		"guest"			=> "Create guest entry",
		"admin"			=> "Create admin account",
);		
		
function users_guest($job, $step)
{
	$fw = \Base::instance();
	
	$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}users`
							(`login`, `username`, `realname`, `password`, `email`, `registered` )
							VALUES
							('Guest', 'Guest', '', '', '', '0000-00-00 00:00:00');" );
							
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}users` SET `uid` = '0' ;" );
	$fw->db5->exec ( "ALTER TABLE `{$fw->dbNew}users` auto_increment = 1 ROW_FORMAT = COMPACT;" );
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = 1 WHERE `id` = :id ", 
						[ 
							':id' => $step['id']
						]
					);
}

function users_admin($job, $step)
{
	$fw = \Base::instance();
	
	$passwordhash = password_hash( $fw['installerCFG.admin.pass1'], PASSWORD_DEFAULT );

	$fw->db5->exec ( "INSERT INTO `{$fw->dbNew}users`
							(`login`, `username`, `realname`, `password`, `email`, `registered`, `groups` )
							VALUES
							(:login, :username, '', '{$passwordhash}', :email, NOW(), 255);", 
						[ 
							':login'	=> $fw['installerCFG.admin.username'],
							':username'	=> $fw['installerCFG.admin.username'],
							':email'	=> $fw['installerCFG.admin.mail'],
						]
					);
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = 1 WHERE `id` = :id ", 
						[ 
							':id' => $step['id']
						]
					);
}


?>