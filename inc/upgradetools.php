<?php

class upgradetools {
	
	public static function storageSelect()
	{
		$fw = \Base::instance();
		/*
		scenarios:
		
		0: +local, +sqlite, +full utf8
		1:                  -full utf8
		2:         -sqlite, +full utf8
		3:         -sqlite, -full utf8
		4: -local, +sqlite
		5: -local, -sqlite
		*/
		if ( $fw['installerCFG.dbhost']=="localhost" OR $fw['installerCFG.dbhost']=="127.0.0.1" OR ( $fw['installerCFG.dbhost']=="" AND !is_numeric($fw['installerCFG.dbport']) ) )
		{
			$scenario = 0;
			// Local database with additional SQLite support
			if (!extension_loaded('pdo_sqlite'))
				$scenario += 2;
			// Reduced UTF8 capabilities
			if ($fw['installerCFG.dbdriver']="mysql" AND $fw['installerCFG.charset']=="UTF8")
				$scenario += 1;
		}
		else
		{
			$scenario = 4;
			// Remote database with additional SQLite support
			if (extension_loaded('pdo_sqlite'))
				$scenario += 1;
		}
		return $scenario;
	}
	
	public static function sitedata()
	{
		// reads data from the v3 for later use and moves to next step
		$fw = \Base::instance();

		$probe = $fw->db3->exec
		(
			'SELECT `sitename` , `slogan`, `siteemail`, `storiespath`, `store`, `itemsperpage` FROM `'.$fw['installerCFG.dbname'].'`.`'.$fw['installerCFG.settings'].'fanfiction_settings` WHERE `sitekey` LIKE :sitekey',
			[
				':sitekey'	=> $fw['installerCFG.sitekey']
			]
		);
		
		$fw['installerCFG.optional'] = [];
		$fw['installerCFG.data'] = $probe[0];
		$fw->dbCFG->write('config.json',$fw['installerCFG']);

		if( file_exists("../version.php") )
		{
			// Trick the 3.5.x file into telling us which version we have:
			define("_CHARSET", "stub");
			include("../version.php");
		}
		if( empty( $version ) OR version_compare($version, 4, ">") )
		{
			$error = 
			[
				"No eFiction 3.5.x version file found!",
				" ",
				"This upgrade might possibly fail.",
			];
			$fw->set('error', implode("\n", $error) );
			$fw->set('link', [
				'step'		=> 1,
				'sub'			=> '',
				'message'	=> 'continue anyway.'
			]);
			return Template::instance()->render('steps.htm');
		}

		$fw->reroute('@steps(@step=1)');
	}

	public static function optional()	// Step  #1
	{
		$fw = \Base::instance();
		
		$sub = explode(".",$fw->get('PARAMS.sub'));

		include('inc/sql/sql_upgrade_3_5_optional.php');

		$modules = array_keys($probe);
		foreach ( $modules as $module )
		{
			// Execute probe statement
			$fw->db3->exec($probe[$module]);
			// Log result
			$check[$module] = $fw->db3->count();
			if(empty($fw['installerCFG.optional'][$module]))
			{
				if($optional[$module]['type']==1)
						$fw['installerCFG.optional'][$module] = ($check[$module]) ? "*+" : "**";
				if($optional[$module]['type']>1)
						$fw['installerCFG.optional'][$module] = "?";
				$fw->dbCFG->write('config.json',$fw['installerCFG']);
			}
			if(@$sub[1]==$module)
			{
				if($sub[0]=="add")	$fw['installerCFG.optional'][$module]=($optional[$module]['type']==1)?"*+":"+";
				if($sub[0]=="drop")	$fw['installerCFG.optional'][$module]=($optional[$module]['type']==1)?"*-":"-";
				$fw->dbCFG->write('config.json',$fw['installerCFG']);
				$fw->reroute('@steps(@step=1)');
			}
			$description[$module] = $optional[$module]['description'];
		}
		$fw->set('check', $check);
		$fw->set('description', $description);
		
		return Template::instance()->render('optional.htm');
	}
	
	public static function newTables ()	// Step  #2
	{
		// create new tables in target database
		$fw = \Base::instance();
		
		include('inc/sql/sql_upgrade_3_5.php');
		include('inc/sql/sql_upgrade_3_5_optional.php');
		
		$modulesDB = [];
		foreach ($fw['installerCFG.optional'] as $module => $setting )
		{
			if( $setting[0]=="+" )
			// optional module, add init sql and steps
			foreach($optional[$module]['steps'] as $step)
			{
				$sql['init'][$step[0]] = $optional[$step[0]]['init'];
				$init["steps"][] = $step;
			}

			if ( $setting[0]!="-" ) $modulesDB[$module] = 1;
		}
		if ( sizeof($modulesDB)>0 )
		{
			$fw['installerCFG.modulesDB'] = serialize($modulesDB);
			$fw->dbCFG->write('config.json',$fw['installerCFG']);
		}

		try {
			$probe = $fw->db3->exec ( 'SELECT `value` FROM `'.$fw['installerCFG.db_new'].'`.`'.$fw['installerCFG.pre_new'].'config` WHERE `name` LIKE \'version\'' );
			if(null!==$fw->get('PARAMS.sub') AND $fw->get('PARAMS.sub')=="flush")
			{
				foreach ( $init['steps'] as $tables ) {
					$fw->db3->exec ( "DROP TABLE IF EXISTS `{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}{$tables[0]}`;" );
				}
				$fw->reroute('@steps(@step=2)');
			}
			else
			{
				// Flush confirmation - todo -
				$error = 
				[
					"Tables already exist!",
					" ",
					"Change eFiction 5.x prefix in the config or",
					"flush tables before continuing.",
				];
				if (isset($probe[0]['value']) ) $error[] = "\nFound tables from version ".$probe[0]['value'];
				$fw->set('error', implode("\n", $error) );
				$fw->set('link', [
					'step'		=> 2,
					'sub'			=> 'flush',
					'message'	=> 'flush tables.'
				]);
				return Template::instance()->render('steps.htm');
			}
		}
		catch (PDOException $e) {
			$errors=0;
			$fw->set('currently', "Creating tables");
			foreach ( $init['steps'] as $create )
			{
				if(isset($sql['init'][$create[0]]))
				{
					$sql_steps = explode("--SPLIT--", $sql['init'][$create[0]]);
					foreach ( $sql_steps as $sql_step )
					{
						$r['step'] = $create[2];
						try {
							$fw->db3->exec ( $sql_step );
							$r['class'] = 'success';
							$r['message'] = 'OK';
						}
						catch (PDOException $e) {
							$error = print_r($fw->db3->errorInfo(),TRUE);
							$r['class'] = 'error';
							$r['message'] = "ERROR (".$error.")".$sql_step ;
							$errors++;
						}
						$reports[]=$r;
					}
				}
			}
			$fw->set('reports',$reports);
			if(!$errors)
			{
				$fw->set('continue', 
					[
						"step" 			=> $fw->get('PARAMS.step')+1,
						"message"		=> 'Tables created',
					]
				);
			}
			else $fw->set('error', "Errors");
			
			return Template::instance()->render('steps.htm');
		}
	}
	
	public static function installJobs ()	// Step  #3
	{
		// create create job list
		$fw = \Base::instance();
		$step = $fw->get('PARAMS.step');
		
		include('inc/sql/sql_upgrade_3_5.php');
		include('inc/sql/sql_upgrade_3_5_optional.php');

		foreach ($fw['installerCFG.optional'] as $module => $setting )
		{
			if( $setting[0]=="*" AND $setting[1]=="+" )
			{
				// New core module, add sql data
				$sql['data'][$module] = $optional[$module]['data'];
			}
			elseif( $setting[0]=="+" )
			{
				// optional module, add init and data
				foreach($optional[$module]['steps'] as $step)
				{
					$sql['data'][$step[0]] = $optional[$step[0]]['data'];
					$init["steps"][] = $step;
				}
			}
		}

		// Check jobs table
		$fw->db3->exec ( "SELECT 1 FROM `{$new}convert` LIMIT 0,1");
		if($fw->db3->count()>0)
		{
			$fw->set('continue',
				[
					'message'	=> 'Jobs already created',
					'step'		=> $fw->get('PARAMS.step')+1
				]
			);
			return Template::instance()->render('steps.htm');
		}
		
		$fw->set('currently', "Creating tables");
		// ugly !!!!
		$errors=0;
		$joborder=0;
		foreach ( $init['steps'] as $create )
		{
			if(isset($sql['data'][$create[0]]))
			{
				$joborder++;
				$sql_steps = explode("--SPLIT--", $sql['data'][$create[0]]);
				foreach($sql_steps as $stepid => $sql_step)
				{
					$sql_step = explode("--NOTE", $sql_step);
					$r['step'] = $create[2];
					try {
						$fw->db3->exec
							(
								"INSERT INTO `{$new}convert` 
								(`job`, 	`joborder`, 	`step`, 	`job_description`, 	`step_description`,		`code`) VALUES 
								(:job, 		:order,				:step, 		:desc_job,					:desc_step,						:code	);",
								[
									':job'				=>	$create[0],
									':order'			=>	$joborder, 
									':step'			=>	$stepid,
									':desc_job'	=>	$create[2], 
									':desc_step'	=>	isset($sql_step[1]) ? $sql_step[1] : "",
									':code'			=>	$sql_step[0],
								]
							);
						$r['class'] = 'success';
						$r['message'] = 'OK';
					}
					catch (PDOException $e)
					{
						$r['class'] = 'error';
						$r['message'] = "ERROR (".print_r($fw->db3->errorInfo(),TRUE).")" ;
						$errors++;
					}
				}
				$reports[]=$r;
			}
		}
		$fw->set('reports',$reports);

		if(!$errors)
		{
			$fw->set('continue', 
				[
					"step" 			=> $fw->get('PARAMS.step')+1,
					"message"		=> 'Jobs installed',
				]
			);
		}
		else $fw->set('error', "Errors");
			
		return Template::instance()->render('steps.htm');
	}
	
	public static function workJobs ()	// Step  #4
	{
		// create create job list
		$fw = \Base::instance();
		$step = $fw->get('PARAMS.step');

		$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
		$sql = "SELECT C.*, IF(LOCATE('--LOOP',C.code),SUBSTRING_INDEX(C.code, '--LOOP', -1),'NULL') as `loop`
						FROM (SELECT `joborder` as selection FROM `{$new}convert` WHERE success = 0 ORDER BY joborder ASC LIMIT 0,1) as C2
						LEFT JOIN `{$new}convert`C ON ( C.joborder=C2.selection )
					ORDER BY step ASC";

		$data = $fw->db3->exec ( $sql );
		
		if($fw->db3->count()>0)
		{
			$errors = 0;
			$fw->set('currently', $data[0]['job_description']);
			foreach ( $data as $job )
			{
				$r['step'] = $job['step_description'];
				if ( strpos( $job['code'], "DROP") && $errors )
				{
					$r['class'] = 'error';
					$r['message'] = "previous errors, not performing drop command in id #{$job['id']}";
				}
				elseif ( $job['success'] )
				{
					$r['class'] = 'success';
					$r['message'] = "OK (skipping {$job['items']} elements already done)";
				}
				else
				{
					if ( $job['loop'] == 'NULL' )
					{	
						try
						{
							$res = $fw->db3->exec ( $job['code'] );
							$affected = $fw->db3->count();
							$r['class'] = 'success';
							$r['message'] = "OK ({$affected} items)";
							$fw->db3->exec( "UPDATE `{$new}convert` SET `success` = 1, `items` = '".$affected."' WHERE `id` = {$job['id']} " );
						}
						catch (PDOException $e)
						{
							$error = $fw->db3->errorInfo();
							$errors++;
							$r['class'] = 'error';
							$r['message'] = "ERROR (".print_r($error,TRUE).") in id #".$job['id'];
							$fw->db3->exec( 
								"UPDATE `{$new}convert` SET `error` = :error WHERE `id` = {$job['id']} ",
								[ ":error" => print_r($error,TRUE) ]
							);
						}
					}
					else
					{
						try
						{
							$res = $fw->db3->exec ( $job['loop'] );
							if($fw->db3->count()==0)
							{
								$r['class'] = 'success';
								$r['message'] = "OK ({$job['items']} items)";
								$fw->db3->exec( "UPDATE `{$new}convert` SET `success` = 1 WHERE `id` = {$job['id']} " );
							}
							else
							{
								$items = $job['items'] + $fw->db3->count();
								$r['class'] = 'warning';
								$r['message'] = "processing ({$items} items so far)";
								$fw->db3->exec( "UPDATE `{$new}convert` SET `items` = '{$items}' WHERE `id` = {$job['id']} " );
							}
						}
						catch (PDOException $e)
						{
							$error = $fw->db3->errorInfo();
							$errors++;
							$r['class'] = 'error';
							$r['message'] = "ERROR (".print_r($error,TRUE).") in id #".$job['id'];
							$fw->db3->exec( 
								"UPDATE `{$new}convert` SET `error` = :error WHERE `id` = {$job['id']} ",
								[ ":error" => print_r($error,TRUE) ]
							);
						}
					}

				}
				$reports[]=$r;
			}
			$fw->set('reports',$reports);

			if ( $errors == 0 )
			{
				/* set redirect directives via javascript, using header can mess up the browser */
				$fw->set('redirect', $step );
				$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
				$fw->set('continue', 
					[
						"step" 			=> $step,
						"message"		=> 'This page will automatically re-load until all records have been compiled',
						"message2"	=> " manually",
					]
				);
			}
		}
		else
		{
			$fw->set('continue', 
				[
					"step" 			=> $step+1,
					"sub"			=> "init",
					"message"		=> 'Jobs processed',
					"message2"	=> " building the cache",
				]
			);
		}
		return Template::instance()->render('steps.htm');
	}

	public static function buildCache()	// Step  #5
	{
		$fw = \Base::instance();
		
		if(null!==$fw->get('PARAMS.sub') AND $fw->get('PARAMS.sub')=="init")
		{
			$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
			// Count total chapters and take note
			$fw->db5->exec("SELECT 1 FROM `{$new}stories`");
			$fw['installerCFG.storycount'] = $fw->db5->count();
			$fw->dbCFG->write('config.json',$fw['installerCFG']);
		}

		include('inc/sql/sql_upgrade_3_5.php');
		$step = $fw->get('PARAMS.step');

		$data = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'convert');
		$data->load(['job=?','stories_blockcache']);
		$stories_cached = $data->items - 1;

		$fw->set('currently', "Creating cache data");

		if($fw->db5->exec($sql['probe']['stories_blockcache']) AND $fw->db5->count()>0)
		{
			// Stories to cache!
			$reports[] = [
				'step'		=> $data->step_description,
				'class'		=> 'warning',
				'message'	=> "processing (".($data->items - 1)." of {$fw['installerCFG.storycount']} items so far)",
			];
			$items = $fw->db5->exec( $sql['proc']['stories_blockcache'] );
			$data->items = $data->items+$fw->db5->count();
			$data->save();

			foreach ( $items as $item)
			{
				$fw->db5->exec
				(
					"INSERT INTO `{$new}stories_blockcache` VALUES
					({$item['sid']}, :tagblock, :characterblock, :authorblock, :categoryblock, :rating, '{$item['reviews']}', '{$item['chapters']}' );",
					[
						':tagblock'			=> serialize(upgradetools::cleanResult($item['tagblock'])),
						':characterblock'	=> serialize(upgradetools::cleanResult($item['characterblock'])),
						':authorblock'		=> serialize(upgradetools::cleanResult($item['authorblock'])),
						':categoryblock'	=> serialize(upgradetools::cleanResult($item['categoryblock'])),
						':rating'			=> serialize(explode(",",$item['rating'])),
					]
				);
			}
			$fw->set('redirect', $step );
			$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
			$fw->set('continue', 
				[
					"step" 			=> $step,
					"message"		=> 'This page will automatically re-load until all records have been compiled',
					"message2"	=> " manually",
				]
			);
			//return "Story cache";
			$fw->set('reports',$reports);
		}
		elseif($fw->db5->exec($sql['probe']['series_blockcache']) AND $fw->db5->count()>0)
		{
			// Tell that stories have been completed
			$reports[] = [
				'step'		=> $data->step_description,
				'class'		=> 'success',
				'message'	=> "finished (".($data->items - 1)." items)",
			];
			// Prepare series job for access
			$data->load(['job=?','series_blockcache']);

			// Report current status
			$reports[] = [
				'step'		=> $data->step_description,
				'class'		=> 'warning',
				'message'	=> "processing (".($data->items - 1)." items so far)",
			];

			$items = $fw->db5->exec( $sql['proc']['series_blockcache'] );
			$data->items = $data->items+$fw->db5->count();
			$data->save();
			
			foreach ( $items as $item)
			{
				$fw->db5->exec
				(
					"INSERT INTO `{$new}series_blockcache` VALUES
					({$item['seriesid']}, :tagblock, :characterblock, :authorblock, :categoryblock, :max_rating, '{$item['chapter_count']}', '{$item['word_count']}');",
					[
						':tagblock'			=> serialize(upgradetools::cleanResult($item['tagblock'])),
						':characterblock'	=> serialize(upgradetools::cleanResult($item['characterblock'])),
						':authorblock'		=> serialize(upgradetools::cleanResult($item['authorblock'])),
						':categoryblock'	=> serialize(upgradetools::cleanResult($item['categoryblock'])),
						':max_rating'		=> serialize(explode(",",$item['max_rating'])),
					]
				);
			}
			$fw->set('redirect', $step );
			$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
			$fw->set('continue', 
				[
					"step" 			=> $step,
					"message"		=> 'This page will automatically re-load until all records have been compiled',
					"message2"	=> " manually",
				]
			);
			//return "Story cache";
			$fw->set('reports',$reports);
		}
		elseif($fw->db5->exec($sql['probe']['categories']) AND $fw->db5->count()>0)
		{
			// Tell that stories have been completed
			$reports[] = [
				'step'		=> $data->step_description,
				'class'		=> 'success',
				'message'	=> "finished (".($data->items - 1)." items)",
			];

			$data->load(['job=?','series_blockcache']);

			// Series also ...
			
			$reports[] = [
				'step'		=> $data->step_description,
				'class'		=> 'success',
				'message'	=> "finished (".($data->items - 1)." items)",
			];
			
			// Prepare series job for access
			$data->load(['job=?','categories_statcache']);

			// Report current status
			$reports[] = [
				'step'		=> $data->step_description,
				'class'		=> 'warning',
				'message'	=> "processing (".($data->items - 1)." items so far)",
			];
			
			$items = $fw->db5->exec( $sql['proc']['categories'] );// echo $sql['proc']['categories'];
			foreach ( $items as $item)
			{
				if ( $item['sub_categories']==NULL ) $sub = NULL;
				else
				{
					$sub_categories = explode("||", $item['sub_categories']);
					$sub_stats = explode("||", $item['sub_stats']);
					$sub_stats = array_map("unserialize", $sub_stats);
					foreach( $sub_categories as $key => $value )
					{
						$item['counted'] += $sub_stats[$key]['count'];
						$sub[$value] = $sub_stats[$key]['count'];
					}
				}
				$stats = serialize([ "count" => (int)$item['counted'], "cid" => $item['cid'], "sub" => $sub ]);
				unset($sub);
				$data->items = $data->items+1;
				$data->save();

				$fw->db5->exec
				(
					"UPDATE `{$new}categories`C SET C.stats = :stats WHERE C.cid = :cid",
					[ ":stats" => $stats, ":cid" => $item['cid'] ]
				);
			}
			
			$fw->set('redirect', $step );
			$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
			$fw->set('continue', 
				[
					"step" 			=> $step,
					"message"		=> 'This page will automatically re-load until all records have been compiled',
					"message2"	=> " manually",
				]
			);

			$fw->set('reports',$reports);
		}
		else
		{
			$fw->set('continue', 
				[
					"step" 			=> $step+1,
					"sub"			=> "init",
					"message"		=> 'All database jobs completed',
					"message2"	=> " with chapter processing",
				]
			);
		}

		return Template::instance()->render('steps.htm');
	}

	public static function processChapters() 	// Step  #6
	{
		$fw = \Base::instance();
		
		$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
		$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";

		if(null!==$fw->get('PARAMS.sub') AND $fw->get('PARAMS.sub')=="init")
		{
			// Count total chapters and take note
			$fw->db5->exec("SELECT 1 FROM `{$new}chapters`");
			$fw['installerCFG.chaptercount'] = $fw->db5->count();
			$fw->dbCFG->write('config.json',$fw['installerCFG']);
			
			// Init part, called when jumping from step #5
			if ( !is_dir('../data') ) mkdir ('../data');
			
			if ( file_exists(realpath('..').'/data/chapters.sq3')) unlink ( realpath('..').'/data/chapters.sq3' ) ;

			$fw->dbsqlite = new DB\SQL('sqlite:'.realpath('..').'/data/chapters.sq3');
			
			$fw->dbsqlite->begin();
			$fw->dbsqlite->exec ( "DROP TABLE IF EXISTS 'chapters'" );
			$fw->dbsqlite->exec ( "CREATE TABLE IF NOT EXISTS 'chapters' ('chapid' INTEGER PRIMARY KEY NOT NULL, 'sid' INTEGER, 'inorder' INTEGER,'chaptertext' BLOB);" );
			$fw->dbsqlite->commit();

			$ht = fopen( realpath('..').'/data/.htaccess', "w" );
			fwrite($ht, 'deny from all');
			fclose($ht);
			$fw->reroute('@steps(@step=6)');
		}
		
		$step = $fw->get('PARAMS.step');
		$source = $fw->get('installerCFG.data.store'); // "files" or "mysql"
		$target = $fw->get('installerCFG.chapters');	// "filebase" or "database"
		
		// SQL query to get chapter information - and content, depending on source
/*		$sql_get_chap = "SELECT A.aid as folder, Ch.chapid as chapter";
		if($target == "filebase") $sql_get_chap .= ", Ch.sid, Ch.inorder";
		if($source== "mysql") $sql_get_chap .= ", Ch.chaptertext";
		$sql_get_chap .= " FROM `{$new}chapters`Ch 
					INNER JOIN `{$new}stories_authors`A ON ( Ch.sid = A.sid AND A.ca = 0 )
					WHERE Ch.chapid > :lastid
					ORDER BY Ch.chapid ASC
				LIMIT 0,25";	*/
		$sql_get_chap = "SELECT Ch.uid as folder, Ch.chapid as chapter";
		if($target == "filebase") $sql_get_chap .= ", Ch.sid, Ch.inorder";
		if($source== "mysql") $sql_get_chap .= ", Ch.storytext as chaptertext";
		$sql_get_chap .= " FROM `{$old}chapters`Ch 
					WHERE Ch.chapid > :lastid
					ORDER BY Ch.chapid ASC
				LIMIT 0,25";

/*
		if ( $source=="mysql" AND $target=="database" )
		{
			// refuse to do stupid things
			// chapter text was copied during table setup
		}
*/
		if ( $target=="filebase" )
		{
			$fw->set('currently', "Relocating chapter data");

			// open filebase database
			$fw->dbsqlite = new DB\SQL('sqlite:'.realpath('..').'/data/chapters.sq3');
			$newchapter = new DB\SQL\Mapper($fw->dbsqlite,'chapters');
			
			// Probe for last finished ID
			try {
				$probe = $fw->dbsqlite->exec("SELECT `chapid`, COUNT(`chapid`) as current FROM 'chapters' ORDER BY `chapid`DESC LIMIT 0,1");
				$lastid = ( isset($probe[0]['chapid']) ) ? $probe[0]['chapid'] : 0;
			}
			catch (PDOException $e) {
				// Error
			}
			
			$chapters = $fw->db5->exec($sql_get_chap, [ ":lastid" => $lastid ] );
			
			if($fw->db5->count()>0)
			{
				foreach($chapters as $chapter)
				{
					$chaptertext = NULL;
					if ( $source=="files")
					{
						$s = upgradetools::getChapterFile($chapter);
						if ($s[0]) $chaptertext = mb_convert_encoding ($s[1], "UTF-8", mb_detect_encoding($s[1], 'UTF-8, ISO-8859-1'));
						else{
							// report error
						}
					}
					elseif( $source=="mysql")
					{
						$chaptertext = $chapter['chaptertext'];
					}
					else
					{
						// Error
					}
					
					$newchapter->chapid		= $chapter['chapter'];
					$newchapter->sid			= $chapter['sid'];
					$newchapter->inorder	= $chapter['inorder'];
					$newchapter->chaptertext	= $chaptertext;
					$newchapter->save();
					$newchapter->reset();
				}
				$reports[] = [
						'step'		=> 'Chapter id '.$chapter['chapter'],
						'class'		=> 'warning',
						'message'	=> "processed ".($probe[0]['current']+$fw->db5->count())." of {$fw->get('installerCFG.chaptercount')} items so far.",
				];
				$fw->set('reports', $reports );

				$fw->set('redirect', $step );
				$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
				$fw->set('continue', 
					[
						"step" 			=> $step,
						"message"		=> 'This page will automatically re-load until all records have been compiled',
						"message2"	=> " manually",
					]
				);
			}
			else
			{
				$fw->set('continue', 
					[
						"step" 			=> $step+1,
						//"sub"			=> "init",
						"message"		=> 'All chapters have been relocated',
						"message2"	=> " with backing up old data and installing new files",
					]
				);
			}
		}
		elseif ( $target=="database" )
		{
			$fw->set('currently', "Relocating chapter data");
			// Target database implies source files
			// Probe for last finished ID
			try {
				$probe = $fw->db5->exec("SELECT `chapid`, COUNT(`chapid`) as current FROM `{$new}chapters` WHERE `chaptertext` IS NULL ORDER BY `chapid` ASC LIMIT 0,1");
				$lastid = ( isset($probe[0]['chapid']) ) ? ($probe[0]['chapid']-1) : FALSE;
			}
			catch (PDOException $e) {
				// Error
			}
			
			// Load chapters to be processed
			$chapters = $fw->db5->exec($sql_get_chap, [ ":lastid" => $lastid ] );
			
			if($lastid!==FALSE)
			{
				// Map handler
				$updatechapter = new DB\SQL\Mapper($fw->db5, $fw->get('installerCFG.pre_new').'chapters');

				foreach($chapters as $chapter)
				{
					$s = upgradetools::getChapterFile($chapter);
					if ($s[0]) $chaptertext = mb_convert_encoding ($s[1], "UTF-8", mb_detect_encoding($s[1], 'UTF-8, ISO-8859-1'));
					else{
						// report error
					}
					
					$updatechapter->load(array('chapid=?',$chapter['chapter']));
					$updatechapter->chaptertext = $chaptertext;
					$updatechapter->save();
				}
				$reports[] = [
						'step'		=> 'Chapter id '.$chapter['chapter'],
						'class'		=> 'warning',
						'message'	=> "processed ".($fw->get('installerCFG.chaptercount')-$probe[0]['current'])." of {$fw->get('installerCFG.chaptercount')} items so far.",
				];
				$fw->set('reports', $reports );

				$fw->set('redirect', $step );
				$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
				$fw->set('continue', 
					[
						"step" 			=> $step,
						"message"		=> 'This page will automatically re-load until all records have been compiled',
						"message2"	=> " manually",
					]
				);
			}
			else
			{
				$fw->set('continue', 
					[
						"step" 			=> $step+1,
						//"sub"			=> "init",
						"message"		=> 'All chapters have been relocated',
						"message2"	=> " with backing up old data and installing new files",
					]
				);
			}
		}
		return Template::instance()->render('steps.htm');
	}
	
	public static function moveFiles() 	// Step  #7
	{
		$fw = \Base::instance();
		$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
		
		// create instance of the final config file
		$fw->newCFG = new \DB\Jig ( "../data" , \DB\Jig::FORMAT_JSON );
		$mapper = new \DB\Jig\Mapper($fw->newCFG, 'config.json');
		
		$mapper->ACTIVE_DB = "MYSQL";
		$mapper->DB_MYSQL = array (
				"dsn" 			=> $fw->get('installerCFG.dsn.5'),
				"user" 			=> $fw->get('installerCFG.dbuser'),
				"password"	=> $fw->get('installerCFG.dbpass'),
			);
		$mapper->prefix = $fw->get('installerCFG.pre_new');
		
		$cfgData = $fw->db5->exec("SELECT `name`, `value` FROM `{$new}config` WHERE `to_config_file` = 1  ORDER BY `name` ASC ");
		foreach ( $cfgData as $cfgItem)
		{
			/* experimental */
			if ( $cfgItem['value'] == "TRUE") $cfgItem['value'] = TRUE;
			elseif ( $cfgItem['value'] == "FALSE") $cfgItem['value'] = FALSE;

			$cfgItem['name'] = explode("__", $cfgItem['name']);

			if ( isset($cfgItem['name'][1]) )
			{	
				// nested key structures, like bb2__verbose -> bb2[verbose]
				if ( empty( $mapper->{$cfgItem['name'][0]} ) ) $mapper->{$cfgItem['name'][0]} = [];
				$mapper->{$cfgItem['name'][0]}[$cfgItem['name'][1]] = $cfgItem['value'];
			}
			else
				$mapper->{$cfgItem['name'][0]} = $cfgItem['value'];
		}
		$modules = [];
		foreach ( $fw['installerCFG.optional'] as $moduleName => $moduleOpt )
		{
			if ( $moduleOpt[0]!="-" ) $modules[$moduleName] = 1;
		}
		if ( sizeof($modules)>0 ) $mapper->modules_enabled = $modules;
		$mapper->save();
		
		if ( 1 )
		{
			return "Test mode, not moving files or making changes to your eFiction 3.5.x installation at this point!.<br />Thanks for testing the eFiction 5 installer.";
		}
		
		if ( !is_dir('./backup') ) mkdir ('./backup');

		if( file_exists("../version.php") )
		{
			// Trick the 3.5.x file into telling us which version we have:
			define("_CHARSET", "stub");
			include("../version.php");
		}

		if( empty( $version ) OR version_compare($version, 4, "<") )
		{
			// found an old version file in base directory or no version file at all
			$movers = [ "admin", "blocks", "bridges", "browse", "default_tpls", "docs", "images", "includes", "languages", "modules", "skins", "stories", "tinymce", "toplists", "user" ];

			foreach ( $movers as $move )
			{
				if(is_dir("../".$move)) rename("../".$move, "./backup/".$move);
			}
			$efi3root = opendir("../");
			while (false !== ($entry = readdir($efi3root)))
			{
				if ( !in_array($entry, [".", ".." ] ) )
				{
					//if ( ($tmp = pathinfo($entry, PATHINFO_EXTENSION)) && $tmp=="php" OR $tmp=="html"  )
					//rename("../{$entry}", "./backup/{$entry}");
				}
			}
		
			// probe for remaining files
			rewinddir ($efi3root);
			while (false !== ($entry = readdir($efi3root)))
			{
				if ( !in_array($entry, [".", "..", "data", "install" ] ) )
				{
					if(is_dir("../".$entry)) $remaining['folders'][] =  $entry;
					else $remaining['files'][] =  $entry;
				}
				
			}
			if(isset($remaining)) $fw->set('remaining', $remaining);
			// old script was moved away, put new files in place
			$efi5root = opendir("./sources");
			while (false !== ($entry = readdir($efi5root)))
			{
				if ( $entry != "." && $entry != ".." )
				{
					//rename("./sources/{$entry}", "../{$entry}");
				}
			}
			
			// make note of the outcome in the config file
		}
		else
		{
			$error = 
			[
				"A version higher higher than 3.x.x was detected, did not attempt to move files.",
				" ",
				"Please move files manually, sources can be found in the install/sources folder.",
				" ",
				"If the files were previously moved successfully and you came here by reloading,",
				" this error can be ignored."
			];
			$fw->set('error', implode("\n", $error) );
		}

		return Template::instance()->render('upgraded.htm');
	}

	private static function getChapterFile($item)
	{
		$filename = realpath("../stories/{$item['folder']}/{$item['chapter']}.txt");
		if ( file_exists($filename) )
		{
			return [ TRUE, file_get_contents ( $filename ) ];
			//$contents = file_get_contents ( $filename );
		}
		return [ FALSE ];
		//else echo "<span class='warning'>Not found: {$item['folder']}/{$item['chapter']}.txt</span><br />";

	}
	
	private static function cleanResult($messy)
	{
		$mess = explode("||",$messy);
		$mess = (array_unique($mess));
		foreach ( $mess as $element )
		{
			$elements[] = explode(",",$element );
		}
		return($elements);
	}


}
?>
