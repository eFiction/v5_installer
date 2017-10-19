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
		$this->fw->set('content', Template::instance()->render('storage_fresh.htm'));
	}
	
	function config ()
	{
		$this->fw->set('content', Template::instance()->render('config_new.htm'));
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
			/*
			case 3:
				$this->fw->set('content', upgradetools::processJobs() );
				break;
			case 4:
				$this->fw->set('content', upgradetools::buildConfig() );
				break;
			case 5:
				$this->fw->set('content', upgradetools::moveFiles() );
				break;
				*/
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

	public static function newTables ()	// Step  #2
	{
		// Not really a job-related task, but the data folder must exist and be protected
		if ( !is_dir('../data') ) mkdir ('../data');
		$ht = fopen( realpath('..').'/data/.htaccess', "w" );
		fwrite($ht, 'deny from all');
		fclose($ht);

		// create new tables in target database
		$fw = \Base::instance();

		$upgrade = FALSE;
	}
	
}

?>
