<?php

class upgradetools {
	
	public static function sitedata()
	{
		$fw = \Base::instance();

		// reads data from the v3 for later use and moves to next step
		$probe = $fw->db3->exec
		(
			'SELECT * FROM `'.$fw['installerCFG.db3.dbname'].'`.`'.$fw['installerCFG.db3.settings'].'fanfiction_settings` WHERE `sitekey` LIKE :sitekey',
			[
				':sitekey'	=> $fw['installerCFG.db3.sitekey']
			]
		)[0];
		
		$probe['anonreviews']		= ($probe['anonreviews']==1) 	? 'TRUE' : 'FALSE';
		$probe['tinyMCE']			= ($probe['tinyMCE']==1) 		? 'TRUE' : 'FALSE';
		$probe['imageupload']		= ($probe['imageupload']==1) 	? 'TRUE' : 'FALSE';
		$probe['coauthallowed']		= ($probe['coauthallowed']==1) 	? 'TRUE' : 'FALSE';
		$probe['roundrobins']		= ($probe['roundrobins']==1) 	? 'TRUE' : 'FALSE';
		$probe['rateonly']			= ($probe['rateonly']==1) 		? 'TRUE' : 'FALSE';
		$probe['reviewsallowed']	= ($probe['reviewsallowed']==1) ? 'TRUE' : 'FALSE';
		$probe['alertson']			= ($probe['alertson']==1) 		? 'TRUE' : 'FALSE';
		$probe['logging']			= ($probe['logging']==1) 		? 'TRUE' : 'FALSE';
		$probe['agestatement']		= ($probe['agestatement']==1) 	? 'TRUE' : 'FALSE';

		$probe['story_validation']	= ($probe['autovalidate']==0) 	? 'TRUE' : 'FALSE';
		$probe['author_self']		= ($probe['submissionsoff']==0) ? 'TRUE' : 'FALSE';

		$probe['displayindex']		= ($probe['displayindex']==0) 	? 'FALSE' : 'TRUE';
		$probe['allowseries']		= ($probe['allowseries']==0) 	? 'FALSE' : 'TRUE';
		$probe['newscomments']		= ($probe['newscomments']==0) 	? 'FALSE' : 'TRUE';

		$probe['defaultsort']		= ($probe['defaultsort']==0) 	? 'title' : 'date';
		
		$probe['linkrange']			=  intval(((int)$probe['linkrange'])/2);
		
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
			$warning = 
			[
				"No eFiction 3.5.x version file found!",
				" ",
				"This upgrade might possibly fail.",
			];
			$fw->set('warning', implode("\n", $warning) );
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
			if(@$sub[1]==$module)
			{
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
		
		return Template::instance()->render('upgrade/optional.htm');
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
		
		$path = realpath ( "./inc/sql/" );
		include( $path.'/install/tables_core.php');
		include( $path.'/install/tables_optional.php');
		include( $path.'/install/tables_views.php');
		if ( file_exists ( $path."/upgrade_3_5_x/job_custom.php" ) )
			require_once( $path."/upgrade_3_5_x/job_custom.php" );


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
			foreach ( array_merge($jobs, $tables, $views) as $create => $label )
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
				// let's see if we have a custom job to take care of
				if($errors==0 AND isset($fw->customFields[$create]))
				{
					$r['step'] = $r['step'] . " custom fields";
					foreach($fw->customFields[$create] as $custom)
					try {
						$fw->db5->exec(
							"ALTER TABLE `{$new}{$create}` ADD `{$custom['field']}` {$custom['type']} AFTER `{$custom['after']}`;"
						);
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
				// custom index also ?
				if($errors==0 AND isset($fw->customIndex[$create]))
				{
					$r['step'] = $r['step'] . " custom index";
					try {
						$fw->db5->exec(
							"ALTER TABLE `{$new}{$create}` ".implode(", ", $fw->customIndex[$create]).";"
						);
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
			$fw->set('reports',$reports);
			if(!$errors)
			{
				// Init step counter
				$i=1;
				foreach ( $jobs as $create => $label )
				{
					$fw->db5->exec
					(
						"INSERT INTO `{$new}process` 
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
		
		$fw->dbNew = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
		$fw->dbOld = "{$fw['installerCFG.db3.dbname']}`.`{$fw['installerCFG.db3.prefix']}fanfiction_";

		$step = $fw->get('PARAMS.step');
		
		$job = $fw->db5->exec ( "SELECT * FROM `{$fw->dbNew}process` WHERE step = 0 AND success < 2 ORDER BY joborder, step ASC LIMIT 0,1");
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
			
			if ( file_exists ( $path."/upgrade_3_5_x/job_custom.php" ) )
				require_once( $path."/upgrade_3_5_x/job_custom.php" );

			$fw->customfields = isset($fw->customDataIn[$job[0]['job']]) ? implode(",",$fw->customDataIn[$job[0]['job']])."," : "";
			
			jobStart($job[0]);
		}
		$fw->set('time_end', microtime(TRUE) - $time_start);
		return Template::instance()->render('steps.htm');
	}


	public static function buildConfig() 	// Step  #4
	{
		$fw = \Base::instance();
		
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
		fclose($cfgFile);
		
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
		
		$fw->db5->exec("DROP TABLE IF EXISTS `{$new}process`;");

		/*
		if ( 1 )
		{
			return "Test mode, not moving files or making changes to your eFiction 3.5.x installation at this point!.<br />Thanks for testing the eFiction 5 installer.";
		}
		*/
		
		$backup = "backup-".date("Y-m-d_H.i.s");
		mkdir ("../{$backup}");

		if( file_exists("../version.php") )
		{
			// Trick the 3.5.x file into telling us which version we have:
			define("_CHARSET", "stub");
			include("../version.php");
		}

		if( empty( $version ) OR version_compare($version, 4, "<") )
		{
			// found an old version file in base directory or no version file at all
			
			// moving away old folders
			$movers = [ 
				// folder names used by eFiction 3 or both versions
				"admin", "blocks", "bridges", "browse", "default_tpls", "docs", "images", "includes", "languages", "modules", "skins", "stories", "tinymce", "toplists", "user",
				// folder names used by eFiction 5
				"app", "lib", "template", "tmp"
			];
			foreach ( $movers as $move )
			{
				if(is_dir("../".$move)) rename("../".$move, "../{$backup}/{$move}");
			}
			// moving away old files
			$movers = [ 
				// files from eFiction 3
				".htaccess", "admin.php", "authors.php", "browse.php", "config.php", "contact.php", "header.php", "index.php", "maintenance.php", "news.php", "README.txt", "reviews.php", "rss.php", "search.php", "series.php", "stories.php", "template.php", "toplists.php", "update.php", "user.php", "version.php", "viewpage.php", "viewseries.php", "viewstory.php", "viewuser.php",
			];
			foreach ( $movers as $move )
			{
				if(is_file("../".$move)) rename("../".$move, "../{$backup}/{$move}");
			}

			// probe for remaining files and tell the user
			$efi3root = opendir("../");
			while (false !== ($entry = readdir($efi3root)))
			{
				if ( !in_array($entry, [".", "..", "data", "install", $backup ] ) )
				{
					if(is_dir("../".$entry)) $remaining['folders'][] =  $entry;
					else $remaining['files'][] =  $entry;
				}
			}
			if(isset($remaining))
			{
				$rem = fopen ( "../data/remaining.txt", "w" );
				if(isset($remaining['folders']))
				{
					fwrite( $rem, "Folders:\n" );
					fwrite( $rem, implode("\n", $remaining['folders'])."\n\n" );
				}
				if(isset($remaining['files']))
				{
					fwrite( $rem, "Files:\n" );
					fwrite( $rem, implode("\n", $remaining['files']) );
				}
				fclose( $rem );
				
				$fw->set('remaining', $remaining);
			}

			// old script was moved away, put new files in place
			// Scan source folder for zip files
			$sourcedir  = opendir('src');
			while (false !== ($filename = readdir($sourcedir)))
			{
				if ( !is_dir('src/'.$filename) AND pathinfo('src/'.$filename)['extension']=="zip"  )
					$files[] = $filename;
			}
			
			// See if we have potential source files
			if ( !isset($files) )
			{
				$fw->set('warn', "notfound" );
			}
			else
			{
				if ( sizeof($files)==1 )
					$sourcefile = "src/".$files[0];
				else $sourcefile = "src/sources.zip";
				
				// Open source files
				$zip = new ZipArchive;
				if ( TRUE === $zip->open($sourcefile) )
				{
					// check if the sources are within another folder
					if ( FALSE === $zip->locateName('app') )
						$folder = $zip->getNameIndex(0);

					// Can we extract the archive ?
					if ( TRUE === $zip->extractTo('../') )
						$zip->close();
					else
						$fw->set('warn', "extract" );
					
					/*
						downloaded from git, the source files are in another folder
						unfortunately, zip extract can't extract from within this folder, 
						so we have to move stuff around
					*/
					if (isset($folder))
					{
						$movefolder = opendir("../{$folder}");
						while (false !== ($entry = readdir($movefolder)))
						{
							if ( !in_array($entry, [".", "..", "data" ] ) )
								rename( "../{$folder}{$entry}", "../{$entry}" );
						}
						rename( "../{$folder}/data/config.ini", "../data/config.ini" );
						rename( "../{$folder}/data/.htaccess", "../data/.htaccess" );
						rmdir( "../{$folder}/data" );
						closedir($movefolder);
						rmdir( "../{$folder}" );
					}
					/*
						moved
					*/
				}
				else $fw->set('warn', "open" );
			}
			
			// make note of the outcome in the config file
		}
		else $fw->set('error', "version" );

		return Template::instance()->render('upgrade/upgraded.htm');
	}

	public static function lockInstaller() 	// Step  #6
	{
		$fw = \Base::instance();
		
		// purge settings to protect data
		$fw['installerCFG'] = [];
		$fw->dbCFG->write('config.json',$fw['installerCFG']);
		
		// lock the installer
		touch('lock.file');
		
		// tell the user that we are done
		return Template::instance()->render('upgrade/finished.htm');
	}

	public static function getChapterFile($item)
	{
		$fw = \Base::instance();
		$filename = realpath("../".$fw['installerCFG.data.storiespath']."/{$item['folder']}/{$item['chapter']}.txt");
		if ( file_exists($filename) )
		{
			$contents = file_get_contents ( $filename );
			if ( FALSE === mb_check_encoding ( $contents, 'UTF-8' ) )
			// adjust codepage if need be
			// WINDOWS-1252 = western Europe
				$contents = iconv('WINDOWS-1252', 'UTF-8', $contents);
			
			return [ TRUE, $contents ];
		}
		return [ FALSE ];
		//else echo "<span class='warning'>Not found: {$item['folder']}/{$item['chapter']}.txt</span><br />";
	}
	
	public static function cleanResult($messy)
	{
		if ( empty($messy) ) return NULL;
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
			"INSERT INTO `{$new}process` 
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
	$fw->db5->exec ( "UPDATE `{$new}process`SET `success` = 1 WHERE `id` = :id ", [ ':id' => $data['id'] ] );
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
	$step = $fw->db5->exec ( "SELECT * FROM `{$new}process` WHERE step > 0 AND success < 2 AND joborder = :joborder ORDER BY step ASC LIMIT 0,1", [ ':joborder' => $job['joborder'] ]);
	if ( sizeof($step)>0 )
	{
		($job['job'].'_'.$step[0]['step_function'])($job, $step[0]);
		/*
		PHP 5 style:
		$func = $job['job'].'_'.$step[0]['step_function'];
		$func($job, $step[0]);
		*/
	}
	
	$stepReports = $fw->db5->exec ( "SELECT * FROM `{$new}process` WHERE step > 0 AND joborder = :joborder ORDER BY step ASC", [ ':joborder' => $job['joborder'] ]);
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
		$fw->db5->exec ( "UPDATE `{$new}process`SET `success` = 2 WHERE `id` = :id ", [ ':id' => $job['id'] ] );
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
