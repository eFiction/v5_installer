<?php
class install {
	
	function __construct()
	{
		// reference to $f3
		$this->fw = Base::instance();
		$this->fw->set('module', 'Installer');
	}
	
	function beforeRoute()
	{
		// alias routes
		$this->fw->route('GET @storage: /fresh/chapters/@where',	'installer->storage');
		$this->fw->route('GET @steps:	/fresh/steps/@step',		'installer->steps');
		$this->fw->route('GET @stepsub:	/fresh/steps/@step/@sub',	'installer->steps');

	}

	function base()
	{
		if(null!==$resume=$this->fw->get('resume'))
		{
			$this->fw->set('content', Template::instance()->render('resume.htm'));
			return TRUE;
		}
		// See if the DB connection has been set up and checked, if not force to config
		if(empty($this->fw['installerCFG.test']))	$this->fw->reroute('@config');
		// Say Hi and show, which storage for chapter data is available and offer advise
		$this->fw->set('scenario', commontools::storageSelect() );
		$this->fw->set('content', Template::instance()->render('install/storage.htm'));
	}
	
	function config ()
	{
		$this->fw->set('content', Template::instance()->render('install/config.htm'));
	}

	function steps ()
	{
		if(null!==$this->fw->get('resume'))
		{
			$this->fw->set('content', Template::instance()->render('resume.htm'));
			return TRUE;
		}
		// See if the DB connection has been set up and checked, if not force to config
		if(empty($this->fw['installerCFG.test']))	$this->fw->reroute('@config');

		// $this->fw->get('PARAMS.step')
		$options = array(
			\PDO::ATTR_ERRMODE 			=> \PDO::ERRMODE_EXCEPTION, // generic attribute
			\PDO::ATTR_PERSISTENT 		=> TRUE,  // we want to use persistent connections
			\PDO::MYSQL_ATTR_COMPRESS 	=> TRUE, // MySQL-specific attribute
		);

		try
		{
			$this->fw->db5 = new \DB\SQL ( $this->fw['installerCFG.db5.dsn'], $this->fw['installerCFG.db5.user'], $this->fw['installerCFG.db5.pass'], $options );
		}
		catch (PDOException $e)
		{
			$this->error ( $e->getMessage() );
			return FALSE;
		}

		switch($this->fw->get('PARAMS.step'))
		{
			case 1:
				$this->fw->set('content', installtools::settings() );
				break;
			case 2:
				$this->fw->set('content', installtools::optional() );
				break;
			case 3:
				$this->fw->set('content', installtools::newTables() );
				break;
			case 4:
				$this->fw->set('content', installtools::processJobs() );
				break;
			case 5:
				$this->fw->set('content', installtools::buildConfig() );
				break;
			case 6:
				$this->fw->set('content', installtools::moveFiles() );
				break;
			default:
				$this->fw->reroute('@steps(@step=1)');
				break;
		}
	}

	function storage ()
	{
		$this->fw['installerCFG.chapters'] = ($this->fw->get('PARAMS.where')=="database") ? "database" : "filebase";
		$this->fw->dbCFG->write('config.json',$this->fw['installerCFG']);
		$this->fw->reroute('@steps(@step=0)');
	}

	function saveConfig ()
	{
		// sanitize submitted data
		//configtools::sanitize(TRUE);
		
		// build the driver-specific DSN string
		$dsn = configtools::buildDSN(TRUE);

		// test
		$this->fw['POST.new.test'] = configtools::testConfig($dsn);
		
		// build final DSN strings
		$this->fw['POST.new.db5.dsn'] = $dsn['db5'].";charset=".$this->fw['POST.new.db5.charset'];
		
		$this->fw['POST.new.admin'] = $this->fw['POST.admin'];
		
		//save data and return to form
		$this->fw->dbCFG->write('config.json',$this->fw['POST.new']);

		$this->fw->reroute('config',false);
	}
	
}

class installtools {
	
	public static function settings()
	{
		$fw = \Base::instance();
		
		$sub = explode(".",$fw->get('PARAMS.sub'));
		
		$fw['options'] = 
		[
			'reviewsallowed'	=> [ 'type'=>'boolean', 'default'=>1 ],
			'anonreviews'		=> [ 'type'=>'boolean', 'default'=>0 ],
			'newscomments'		=> [ 'type'=>'boolean', 'default'=>1 ],
			'tinyMCE'			=> [ 'type'=>'boolean', 'default'=>1 ],
			'imageupload'		=> [ 'type'=>'boolean', 'default'=>0 ],
			'coauthallowed'		=> [ 'type'=>'boolean', 'default'=>1 ],
			'roundrobins'		=> [ 'type'=>'boolean', 'default'=>0 ],
			'rateonly'			=> [ 'type'=>'boolean', 'default'=>1 ],
			'alertson'			=> [ 'type'=>'boolean', 'default'=>1 ],
			'logging'			=> [ 'type'=>'boolean', 'default'=>1 ],
			'agestatement'		=> [ 'type'=>'boolean', 'default'=>1 ],
			'story_validation'	=> [ 'type'=>'boolean', 'default'=>1 ],
			'author_self'		=> [ 'type'=>'boolean', 'default'=>0 ],
			'displayindex'		=> [ 'type'=>'boolean', 'default'=>1 ],
			'allowseries'		=> [ 'type'=>'boolean', 'default'=>1 ],
		//	'defaultsort'		=> [ 'type'=>'boolean', 'default'=>1 ],
		//	'linkrange',		=> [ 'ln'=>'', 'field'=>'numeric', 'default'=>1 ],
		];
		if ( empty($fw['installerCFG.data']) )
		{
			foreach ( $fw['options'] as $key => $value )
			$fw["installerCFG.data.{$key}"] = $value['default'];
			$fw->dbCFG->write('config.json',$fw['installerCFG']);
		}
		
		if ( sizeof($sub)>1 AND isset($fw['options'][$sub[1]]) )
		{
			$fw["installerCFG.data.{$sub[1]}"] = $sub[2];
			$fw->dbCFG->write('config.json',$fw['installerCFG']);
			$fw->reroute('@steps(@step=1)');
		}
		
		return Template::instance()->render('install/settings.htm');
	}
	
	public static function optional()
	{
		$fw = \Base::instance();
		$sub = explode(".",$fw->get('PARAMS.sub'));

		$fw['optional'] = 
		[
			'recommendations'	=> FALSE,
			'contests'			=> FALSE,
			'shoutbox'			=> TRUE,
			'tracker'			=> TRUE,
			'poll'				=> TRUE,
		];
		
		foreach ( $fw['optional'] as $module => $core )
		{
			if(!isset($fw['installerCFG.optional'][$module]))
			{
				// Enable all modules by default
				$fw['installerCFG.optional'][$module] = 1;
				$fw->dbCFG->write('config.json',$fw['installerCFG']);
			}
			
			if(@$sub[1]==$module AND $fw['optional'][$module]===FALSE)
			{
				if($sub[0]=="add")
					$fw['installerCFG.optional'][$module] = 1;

				if($sub[0]=="drop")
					$fw['installerCFG.optional'][$module] = 0;

				$fw->dbCFG->write('config.json',$fw['installerCFG']);
				$fw->reroute('@steps(@step=2)');
			}
		}
		
		return Template::instance()->render('install/optional.htm');
	}

	public static function newTables ()	// Step  #3
	{
		// Not really a job-related task, but the data folder must exist and be protected
		if ( !is_dir('../data') ) mkdir ('../data');
		$ht = fopen( realpath('..').'/data/.htaccess', "w" );
		fwrite($ht, 'deny from all');
		fclose($ht);

		// create new tables in target database
		$fw = \Base::instance();

		$upgrade = FALSE;
		include('inc/sql/install/tables_core.php');
		include('inc/sql/install/tables_optional.php');

		$modulesDB = [];
		foreach ($fw['installerCFG.optional'] as $module => $setting )
		{
			if( $setting==1 AND isset($optional[$module]) )
			{
			// optional module, add init sql and steps
				$core[$module] = $optional[$module]['sql'];
				$tables = array_merge($tables, $optional[$module]['steps']);
				$modulesDB[$module] = 1;
			}
			
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
				'step'		=> 3,
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

	public static function processJobs ()	// Step  #4
	{
		$time_start = microtime(TRUE);
		$fw = \Base::instance();
		
		$fw->dbNew = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";

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

			if ( file_exists( $path."/install/".$file ) )
				require_once( $path."/install/".$file );

			else echo "Fehler!";

			jobStart($job[0]);

		}
		$fw->set('time_end', microtime(TRUE) - $time_start);
		return Template::instance()->render('steps.htm');
	}
	
	public static function buildConfig() 	// Step  #5
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

	public static function moveFiles() 	// Step  #6
	{
		$fw = \Base::instance();
		$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
		
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
		/*
		PHP 7 style:
		($job['job'].'_'.$step[0]['step_function'])($job, $step[0]);
		*/
		$func = $job['job'].'_'.$step[0]['step_function'];
		$func($job, $step[0]);
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
	$fw->set('redirect', 4 );
	$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
	
	if ( isset($toDo) )
	{
		$fw->set('continue', 
			[
				"step" 		=> 4,
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
				"step" 		=> 4,
				"message"	=> 'All steps have been processed',
				"message2"	=> " with the next job",
			]
		);
	}
}

?>
