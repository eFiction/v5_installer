<?php
/*
	Job definition for 'chapters'
	eFiction upgrade from version 3.5.x

	2017-01-28: Update DB queries
*/

$fw->jobSteps = array(
		"copy"	=> "Create chapter file if required",
	);


function chapters_copy($job, $step)
{
	// Chapters copy is a 1-pass module, doing the entire chapter relocation
	$fw = \Base::instance();

	$limit = 100;
	$report = [];

	$source = $fw->get('installerCFG.data.store'); // "files" or "mysql"
	$target = $fw->get('installerCFG.chapters');	// "filebase" or "database"

	// Initialize
	if ( $step['success'] == 0 )
	{
		// drop an existing chapter DB file
		if ( file_exists(realpath('..').'/data/chapters.sq3')) unlink ( realpath('..').'/data/chapters.sq3' ) ;
		
		// if we need the filebase storage, initialize it now
		if ( $target == "filebase" )
		{
			$fw->dbsqlite = new DB\SQL('sqlite:'.realpath('..').'/data/chapters.sq3');

			$fw->dbsqlite->begin();
			$fw->dbsqlite->exec ( "DROP TABLE IF EXISTS 'chapters'" );
			$fw->dbsqlite->exec ( "CREATE TABLE IF NOT EXISTS 'chapters' ('chapid' INTEGER PRIMARY KEY NOT NULL, 'sid' INTEGER, 'inorder' INTEGER,'chaptertext' BLOB);" );
			$fw->dbsqlite->commit();
			unset($fw->dbsqlite);
		}
	}

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => 1,
							':id' => $step['id']
						]
					);
}

?>