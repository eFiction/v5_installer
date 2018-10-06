<?php
/*
	Job definition for 'chapters'
	eFiction upgrade from version 3.5.x

	2017-01-28: Update DB queries
*/

$fw->jobSteps = array(
		"copy"	=> "Copy chapters",
	);


function chapters_copy($job, $step)
{
	$fw = \Base::instance();

	$limit = $fw->get("limit.medium");
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
		else
		{
			
		}
		// Count total chapters and take note
		$total = $fw->db3->exec("SELECT COUNT(*) as found FROM `{$fw->dbOld}chapters`;")[0]['found'];
		$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 1, `total` = :total WHERE `id` = :id ", [ ':total' => $total, ':id' => $step['id'] ] );
	}
	
	$dataIn = $fw->db3->exec("SELECT COUNT(reviewid) as reviewsNew, Ch.uid as folder, Ch.chapid as chapter, Ch.*
								FROM `{$fw->dbOld}chapters`Ch 
								LEFT JOIN `{$fw->dbOld}reviews`R ON ( Ch.chapid = R.chapid AND R.type='ST' )
								GROUP BY Ch.chapid
								ORDER BY chapid ASC LIMIT {$step['items']},{$limit};");
	
	$tracking = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.db5.prefix').'process');
	$tracking->load(['id = ?', $step['id'] ]);

	if ( 0 < $count = sizeof($dataIn) )
	{
		$newchapter = new DB\SQL\Mapper($fw->db5,$fw->get('installerCFG.db5.prefix').'chapters');
		if ( $target == "filebase" )
		{
			$fw->dbsqlite = new DB\SQL('sqlite:'.realpath('..').'/data/chapters.sq3');
			$newchapterText = new DB\SQL\Mapper($fw->dbsqlite,'chapters');
		}

		foreach ( $dataIn as $chapterIn )
		{
			switch($chapterIn['validated']) {
				case 0:
					$chapterIn['validated'] = '11';
					break;
				case 1:
					$chapterIn['validated'] = '31';
					break;
				case 2:
					$chapterIn['validated'] = '33';
			}
				
			// Get chapter text, from file or DB
			if ( $source=="files")
			{
				$s = upgradetools::getChapterFile($chapterIn);
				if ($s[0])
					$chaptertext = mb_convert_encoding ($s[1], "UTF-8", mb_detect_encoding($s[1], 'UTF-8, ISO-8859-1'));
				else{
					//
					$chaptertext = "not found";
				}
			}
			else
			//elseif( $source=="mysql")
			{
				$chaptertext = $chapterIn['storytext'];
			}
			

			if ( $target=="filebase" )
			{
				// No text in Database
				$newchapter->chaptertext = NULL;
				
				// Store data in the filebase storage
				$newchapterText->chapid		 = $chapterIn['chapter'];
				$newchapterText->sid		 = $chapterIn['sid'];
				$newchapterText->inorder	 = $chapterIn['inorder'];
				$newchapterText->chaptertext = $chaptertext;
				$newchapterText->save();
				$newchapterText->reset();
			}
			else
			{
				$newchapter->chaptertext = $chaptertext;
			}

			$newchapter->chapid		= $chapterIn['chapter'];
			$newchapter->sid		= $chapterIn['sid'];
			$newchapter->title		= $chapterIn['title'];
			$newchapter->inorder	= $chapterIn['inorder'];
			$newchapter->notes		= $chapterIn['notes'];
			$newchapter->endnotes	= $chapterIn['endnotes'];
			$newchapter->validated	= $chapterIn['validated'];
			$newchapter->wordcount	= max(count(preg_split("/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}]{0,}/u",$chaptertext))-1, 0);
			$newchapter->rating		= $chapterIn['rating'];
			$newchapter->reviews	= $chapterIn['reviewsNew'];
			$newchapter->count		= $chapterIn['count'];
			$newchapter->save();
			$newchapter->reset();
			
			$tracking->items = $tracking->items+1;
		}
		// Only save once, this should work unless server halts prior to it
		$tracking->save();
	}
	
	if ( $count == 0 OR $tracking->items>=$tracking->total )
	{
		// There was either nothing to be done or we processed the total amount of items
		$tracking->success = 2;
		$tracking->save();
	}
}

?>