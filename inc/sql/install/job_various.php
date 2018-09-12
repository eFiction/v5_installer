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
		"shoutbox"	=> "Copy shoutbox data",
		"db_keys"	=> "Create foreign keys relations",
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

function various_db_keys($job, $step)
{
	$fw = \Base::instance();
	$FK_prefix = str_replace ('`.`', '_', $fw->dbNew );
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	// add foreign key restriction to drop all story_author relations when a story gets deleted
	$sql[] = "ALTER TABLE `{$fw->dbNew}stories_authors`
						ADD CONSTRAINT `{$FK_prefix}rSA_drop` FOREIGN KEY (`sid`) 
						REFERENCES `{$fw->dbNew}stories` (`sid`) 
						ON DELETE CASCADE 
						ON UPDATE NO ACTION;";

	// add foreign key restriction to drop all story_category relations when a story gets deleted
	$sql[] = "ALTER TABLE `{$fw->dbNew}stories_categories`
						ADD CONSTRAINT `{$FK_prefix}rSC_drop` FOREIGN KEY (`sid`) 
						REFERENCES `{$fw->dbNew}stories` (`sid`) 
						ON DELETE CASCADE 
						ON UPDATE NO ACTION;";

	// add foreign key restriction to drop all story_tag relations when a story gets deleted
	$sql[] = "ALTER TABLE `{$fw->dbNew}stories_tags`
						ADD CONSTRAINT `{$FK_prefix}rST_drop` FOREIGN KEY (`sid`) 
						REFERENCES `{$fw->dbNew}stories` (`sid`) 
						ON DELETE CASCADE 
						ON UPDATE NO ACTION;";

	foreach ( $sql as $addKey )
	{
		try
		{
			$fw->db5->exec($addKey);
			$tracking->items++;
		}
		catch (PDOException $e)
		{
			// If there is an issue creating a foreign key, we'll simply take note and move on, it's not that bad, might improve that at a later point
			$tracking->items--;
			$tracking->error = "Issue creating at least one foreign key";
		}
	}

	$tracking->success = 2;
	$tracking->save();
}


?>
