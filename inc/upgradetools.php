<?php

class upgradetools {
	
	public static function sitedata()
	{
		// reads data from the v3 for later use and moves to next step
		$fw = \Base::instance();

		$probe = $fw->db3->exec
		(
//			'SELECT `sitename` , `slogan`, `siteemail`, `storiespath`, `store`, `itemsperpage`, `dateformat`, `timeformat`, `anonreviews` FROM `'.$fw['installerCFG.db3.dbname'].'`.`'.$fw['installerCFG.db3.settings'].'fanfiction_settings` WHERE `sitekey` LIKE :sitekey',
			'SELECT * FROM `'.$fw['installerCFG.db3.dbname'].'`.`'.$fw['installerCFG.db3.settings'].'fanfiction_settings` WHERE `sitekey` LIKE :sitekey',
			[
				':sitekey'	=> $fw['installerCFG.db3.sitekey']
			]
		)[0];
		
		$probe['anonreviews']		= ($probe['anonreviews']==1) ? 'TRUE' : 'FALSE';
		$probe['coauthallowed']		= ($probe['coauthallowed']==1) ? 'TRUE' : 'FALSE';
		$probe['roundrobins']		= ($probe['roundrobins']==1) ? 'TRUE' : 'FALSE';
		$probe['rateonly']			= ($probe['rateonly']==1) ? 'TRUE' : 'FALSE';
		$probe['reviewsallowed']	= ($probe['reviewsallowed']==1) ? 'TRUE' : 'FALSE';
		$probe['alertson']			= ($probe['alertson']==1) ? 'TRUE' : 'FALSE';

		$probe['story_validation']	= ($probe['autovalidate']==0) ? 'TRUE' : 'FALSE';
		$probe['author_self']		= ($probe['submissionsoff']==0) ? 'TRUE' : 'FALSE';

		$probe['displayindex']		= ($probe['displayindex']==0) ? 'FALSE' : 'TRUE';
		$probe['allowseries']		= ($probe['allowseries']==0) ? 'FALSE' : 'TRUE';
		$probe['newscomments']		= ($probe['newscomments']==0) ? 'FALSE' : 'TRUE';

		$probe['defaultsort']		= ($probe['defaultsort']==0) ? 'title' : 'date';
		
		$fw['installerCFG.optional'] = [];
		$fw['installerCFG.data'] = $probe;
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

		include('inc/sql/upgrade_3_5_x/probe_optional.php');

		$modules = array_keys($optional);
		foreach ( $modules as $module )
		{
			// Execute probe statement
			$fw->db3->exec($optional[$module]['probe']);
			// Log result
			$check[$module] = $fw->db3->count();

			if(empty($fw['installerCFG.optional'][$module]))
			{
				if($optional[$module]['type']==1)
						$fw['installerCFG.optional'][$module] = ($check[$module]) ? "*+" : "*?";
				if($optional[$module]['type']>1)
						$fw['installerCFG.optional'][$module] = ($check[$module]) ? "++" : "+-";
				$fw->dbCFG->write('config.json',$fw['installerCFG']);
			}
			if(@$sub[1]==$module)// AND $check[$module]>0)
			{
				/*
				if($sub[0]=="add")$fw['installerCFG.optional'][$module]=($optional[$module]['type']==1)?"*+":"+";
				if($sub[0]=="drop")	$fw['installerCFG.optional'][$module]=($optional[$module]['type']==1)?"*-":"-";
				*/
				if($sub[0]=="add")
				{
					if($optional[$module]['type']==1)
						$fw['installerCFG.optional'][$module]="*+";
					else
						$fw['installerCFG.optional'][$module]=($check[$module]>0)?"++":"+-";
					
				}
				if($sub[0]=="drop")
				{
					if($optional[$module]['type']==1)
						$fw['installerCFG.optional'][$module]="*-";
					else
						$fw['installerCFG.optional'][$module]="--";
				}
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
		// Not really a job-related task, but the data folder must exist and be protected
		if ( !is_dir('../data') ) mkdir ('../data');
		$ht = fopen( realpath('..').'/data/.htaccess', "w" );
		fwrite($ht, 'deny from all');
		fclose($ht);

		// create new tables in target database
		$fw = \Base::instance();

		$upgrade = TRUE;
		include('inc/sql/install/tables_core.php');
		include('inc/sql/install/tables_optional.php');

		$modulesDB = [];
		foreach ($fw['installerCFG.optional'] as $module => $setting )
		{
			if( $setting[0]=="+" )
			// optional module, add init sql and steps
			{
				$core[$module] = $optional[$module]['sql'];
				//$jobs = array_merge($jobs, $optional[$module]['steps']);

				if( $setting[1]=="-" )
				{
					// Just create tables
					$tables = array_merge($tables, $optional[$module]['steps']);
				}
				else
				{
					// Copy data, add job
					$jobs = array_merge($jobs, $optional[$module]['steps']);
				}
			}

			if ( $setting[0]!="-" ) $modulesDB[$module] = 1;
		}
		if ( sizeof($modulesDB)>0 )
		{
			$fw['installerCFG.modulesDB'] = json_encode($modulesDB);
			$fw->dbCFG->write('config.json',$fw['installerCFG']);
		}

		try
		{
			// abusing the try/catch to check if the config table exists
			$probe = $fw->db5->exec ( ($fw->get('PARAMS.sub')=="flush") ? 'SELECT error' : 'SELECT `value` FROM `'.$fw['installerCFG.db5.dbname'].'`.`'.$fw['installerCFG.db5.prefix'].'config` WHERE `name` LIKE \'version\'' );

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
				'sub'		=> 'flush',
				'message'	=> 'flush tables.'
			]);
			return Template::instance()->render('steps.htm');
		}
		catch (PDOException $e)
		{
			$errors=0;
			$reports=[];
			$fw->set('currently', "Creating tables");
			foreach ( array_merge($jobs, $tables) as $create => $label )
			{
				if(isset($core[$create]))
				{
					$sql_steps = explode("--SPLIT--", $core[$create]);
					foreach ( $sql_steps as $sql_step )
					{
						$sql_step = explode("--NOTE--", $sql_step);
						$r['step'] = isset($sql_step[1]) ? $sql_step[1] : $label;
						try {
							$fw->db5->exec ( $sql_step[0] );
							$r['class'] = 'success';
							$r['message'] = 'OK';
						}
						catch (PDOException $e) {
							$error = print_r($fw->db5->errorInfo(),TRUE);
							$r['class'] = 'error';
							$r['message'] = "ERROR (".$error.")".$sql_step[0] ;
							$errors++;
						}
						$reports[]=$r;
					}
				}
			}
			$fw->set('reports',$reports);
			if(!$errors)
			{
				// Init step counter
				$i=1;
				foreach ( $jobs as $create => $label )
				{
					$fw->db5->exec
					(
						"INSERT INTO `{$new}convert` 
						(`job`, 	`joborder`, 	`step`, 	`job_description` ) VALUES 
						(:job, 		:order,			0,			:desc_job		  );",
						[
							':job'			=>	$create,
							':order'		=>	$i++, 
							':desc_job'		=>	$label, 
						]
					);

				}
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
	
	public static function processJobs ()	// Step  #3 v2
	{
		$time_start = microtime(TRUE);
		$fw = \Base::instance();
		$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
		$step = $fw->get('PARAMS.step');
		
		$job = $fw->db5->exec ( "SELECT * FROM `{$new}convert` WHERE step = 0 AND success < 2 ORDER BY joborder, step ASC LIMIT 0,1");
		if($fw->db5->count()==0)
		{
			$fw->set('continue',
				[
					'message'	=> 'All jobs processed',
					'step'		=> $fw->get('PARAMS.step')+1
				]
			);
			return Template::instance()->render('steps.htm');
		}
		else
		{
			$fw->set('currently', $job[0]['job_description']);
			$path = realpath ( "./inc/sql/" );
			$file = "job_{$job[0]['job']}.php";

			if ( file_exists( $path."/upgrade_3_5_x/".$file ) )
				require_once( $path."/upgrade_3_5_x/".$file );

			elseif ( file_exists( $path."/install/".$file ) )
				require_once( $path."/install/".$file );

			else echo "Fehler!";

			jobStart($job[0]);

		}
		$fw->set('time_end', microtime(TRUE) - $time_start);
		return Template::instance()->render('steps.htm');
	}


	public static function buildConfig() 	// Step  #4
	{
		$fw = \Base::instance();
		$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
		
		// create instance of the final config file
		//$fw->newCFG = new \DB\Jig ( "../data" , \DB\Jig::FORMAT_JSON );
		//$mapper = new \DB\Jig\Mapper($fw->newCFG, 'config.json');
		
		$newCFG = 
		[
			"ACTIVE_DB" => "MYSQL",
			"DB_MYSQL"	=> array (
					"dsn" 			=> $fw->get('installerCFG.db5.dsn'),
					"user" 			=> $fw->get('installerCFG.db5.user'),
					"password"	=> $fw->get('installerCFG.db5.pass'),
				),
			"prefix" => $fw->get('installerCFG.db5.prefix')
		];
		
		$cfgFile = fopen("../data/config.php", "w");
		fwrite($cfgFile, "<?php\n\n");
		fwrite($cfgFile, '$config = '.var_export($newCFG,TRUE).';');
		fwrite($cfgFile, "\n\n?>");
		
		// Get entries from configuration table
		/*
		$cfgData = $fw->db5->exec("SELECT `name`, `value` FROM `{$new}config` WHERE `to_config_file` = 1  ORDER BY `name` ASC ");
		foreach ( $cfgData as $cfgItem)
		{
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
			{
				if ( NULL === $c = json_decode($cfgItem['value']) )
					$mapper->{$cfgItem['name'][0]} = $cfgItem['value'];
				else
					$mapper->{$cfgItem['name'][0]} = $c;
			}
		}
		*/

		// Get optional modules, that were enabled
		/*
		$modules = [];
		foreach ( $fw['installerCFG.optional'] as $moduleName => $moduleOpt )
		{
			if ( $moduleOpt[0]!="-" ) $modules[$moduleName] = 1;
		}
		if ( sizeof($modules)>0 ) $mapper->modules_enabled = $modules;
*/
		// Build page stat cache
		/*
		$statSQL = [
				"SET @users = (SELECT COUNT(*) FROM `{$new}users`U WHERE U.groups > 0);",
				"SET @authors = (SELECT COUNT(*) FROM `{$new}users`U WHERE ( U.groups & 4 ) );",
				"SET @reviews = (SELECT COUNT(*) FROM `{$new}feedback`F WHERE F.type='ST');",
				"SET @stories = (SELECT COUNT(DISTINCT sid) FROM `{$new}stories`S WHERE S.validated > 0 );",
				"SET @chapters = (SELECT COUNT(DISTINCT chapid) FROM `{$new}chapters`C INNER JOIN `{$new}stories`S ON ( C.sid=S.sid AND S.validated > 0 AND C.validated > 0) );",
				"SET @words = (SELECT SUM(C.wordcount) FROM `{$new}chapters`C INNER JOIN `{$new}stories`S ON ( C.sid=S.sid AND S.validated > 0 AND C.validated > 0) );",
				"SET @newmember = (SELECT CONCAT_WS(',', U.uid, U.nickname) FROM `{$new}users`U WHERE U.groups>0 ORDER BY U.registered DESC LIMIT 1);",
				"SELECT @users as users, @authors as authors, @reviews as reviews, @stories as stories, @chapters as chapters, @words as words, @newmember as newmember;",
			];
		$statsData = $fw->db5->exec($statSQL)[0];
		
		foreach($statsData as $statKey => $statValue)
		{
			$stats[$statKey] = ($statKey=="newmember") ? explode(",",$statValue) : $statValue;
			//->{$statKey} = ($statKey=="newmember") ? json_encode(explode(",",$statValue)) : $statValue;
		}
		$mapper->stats = $stats;
		$mapper->save();
		*/
		$fw->set('continue',
			[
				'message'	=> 'Configuration file built',
				'step'		=> $fw->get('PARAMS.step')+1
			]
		);

		return Template::instance()->render('steps.htm');
	}

	public static function moveFiles() 	// Step  #5
	{
		$fw = \Base::instance();
		$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
		
		if ( 1 )
		{
			$fw->db5->exec("DROP TABLE IF EXISTS `{$new}convert`;");
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

	public static function getChapterFile($item)
	{
		$fw = \Base::instance();
		$filename = realpath("../".$fw['installerCFG.data.storiespath']."/{$item['folder']}/{$item['chapter']}.txt");
		if ( file_exists($filename) )
		{
			return [ TRUE, file_get_contents ( $filename ) ];
			//$contents = file_get_contents ( $filename );
		}
		return [ FALSE ];
		//else echo "<span class='warning'>Not found: {$item['folder']}/{$item['chapter']}.txt</span><br />";

	}
	
	public static function cleanResult($messy)
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

function jobInit($data)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	$i = 1;
	
	if (empty($fw->jobSteps)) return FALSE;
	
	foreach ( $fw->jobSteps as $func => $desc )
	{
		
		$fw->db5->exec
		(
			"INSERT INTO `{$new}convert` 
			(`job`, 	`joborder`, 	`step`, 	`job_description`, `step_function` ) VALUES 
			(:job, 		:order,			:step,		:desc_job		 , :func_step );",
			[
				':job'			=>	$data['job'],
				':order'		=>	$data['joborder'],
				':step'			=>	$i++,
				':desc_job'		=>	$desc, 
				':func_step'	=>	$func, 
			]
		);
	}
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 1 WHERE `id` = :id ", [ ':id' => $data['id'] ] );
	return TRUE;
}


function jobStart($job)
{
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
	
	if ( $job['success'] == 0 )
	{
		if ( FALSE === jobInit($job) )
		{
			// error
		}
		else
		{
			// gut
		}
	}
	
	// Find the first open step and process it
	$step = $fw->db5->exec ( "SELECT * FROM `{$new}convert` WHERE step > 0 AND success < 2 AND joborder = :joborder ORDER BY step ASC LIMIT 0,1", [ ':joborder' => $job['joborder'] ]);
	if ( sizeof($step)>0 )
	{
		/*
		PHP 7 style:
		($job['job'].'_'.$step[0]['step_function'])($job, $step[0]);
		*/
		$func = $job['job'].'_'.$step[0]['step_function'];
		$func($job, $step[0]);
	}
	
	$stepReports = $fw->db5->exec ( "SELECT * FROM `{$new}convert` WHERE step > 0 AND joborder = :joborder ORDER BY step ASC", [ ':joborder' => $job['joborder'] ]);
	foreach ( $stepReports as $stepReport )
	{
		if ( $stepReport['success']==0 )
		{
			// This step has not even started doing anything
			$reports[] = [
				'step'		=> $stepReport['job_description'],
				'class'		=> 'warning',
				'message'	=> "open",
			];
			$toDo = TRUE;
		}
		elseif ( $stepReport['success']==1 )
		{
			if ( $stepReport['total']>0 ) $total = " of {$stepReport['total']}";
			else $total ="";
			// This step is currently doing something
			$reports[] = [
				'step'		=> $stepReport['job_description'],
				'class'		=> 'warning',
				'message'	=> "processed ".($stepReport['items'])."$total items so far.",
			];
			$toDo = TRUE;
		}
		elseif ( $stepReport['success']==2 )
		{
			// This one is done
			$reports[] = [
					'step'		=> $stepReport['job_description'],
					'class'		=> 'success',
					'message'	=> "OK ({$stepReport['items']} items)",
			];
		}
	}
	
	if ( isset($reports) ) $fw->set('reports', $reports );

	// setup redirect
	$fw->set('redirect', 3 );
	$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );

	if ( isset($toDo) )
	{
		$fw->set('continue', 
			[
				"step" 		=> 3,
				"message"	=> 'This page will automatically re-load until all steps have been completed',
				"message2"	=> " manually",
			]
		);
	}
	else
	{
		// mark this job as completed
		$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2 WHERE `id` = :id ", [ ':id' => $job['id'] ] );
		$fw->set('continue', 
			[
				"step" 		=> 3,
				"message"	=> 'All steps have been processed',
				"message2"	=> " with the next job",
			]
		);
	}
}

?>
