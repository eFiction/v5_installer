<?php
class upgrade {
	
	function __construct()
	{
		// reference to $f3
		$this->fw = Base::instance();
		$this->fw->set('module', 'Upgrade');
	}
	
	function beforeRoute()
	{
		// alias routes
		$this->fw->route('GET @storage:	/upgrade/chapters/@where',	'upgrade->storage');
		$this->fw->route('GET @steps:	/upgrade/steps/@step',		'upgrade->steps');
		$this->fw->route('GET @stepsub:	/upgrade/steps/@step/@sub',	'upgrade->steps');
		
		$step_requested = $this->fw->get('PARAMS.step');
		$step_remembered = isset($this->fw['installerCFG.step']) ? $this->fw['installerCFG.step'] : 0;

		if ( $step_requested == "reset" )
		{
			$this->fw['installerCFG.step'] = 0;
			$this->fw->dbCFG->write('config.json',$this->fw['installerCFG']);
			$this->fw->reroute('@upgrade');
		}
		elseif ( is_numeric($step_requested)  AND $step_requested > $step_remembered )
		{
			// remember new step and continue
			$this->fw['installerCFG.step'] = $step_requested;
			$this->fw->dbCFG->write('config.json',$this->fw['installerCFG']);
		}
		elseif ( (is_numeric($step_requested)  AND $step_requested < $step_remembered) OR ( $step_requested==NULL AND $step_remembered > 0 ) )
		{
			// Tell about the current step and let user decide to continue or restart from scratch
			$this->fw->set('content', "Already done, reset?");
			$this->fw->set('resume', $step_remembered);
			//$this->fw->reroute('@steps(@step='.$step_remembered.')');
		}
	}
	
	function base()
	{
		if(null!==$resume=$this->fw->get('resume'))
		{
			$this->fw->set('content', Template::instance()->render('resume.htm'));
			return TRUE;
		}
		// See if the DB connection has been set up and checked, if not force to config
		if
		( 
			empty($this->fw['installerCFG.test']) 		// no test on config
			OR @$this->fw['installerCFG.test.db3']<2 	// can't connect to db3
			OR @$this->fw['installerCFG.test.data']<2	// db3 data not found
			OR @$this->fw['installerCFG.test.db5']<2	// can't connect to db5
			OR @$this->fw['installerCFG.test.db5']>3 	// prefix conflict
		)
			$this->fw->reroute('@config');
		// Say Hi and show, which storage for chapter data is available and offer advise
		$this->fw->set('scenario', commontools::storageSelect() );
		$this->fw->set('content', Template::instance()->render('storage.htm'));
	}
	
	function config ()
	{
		$this->fw->set('content', Template::instance()->render('config_upgrade.htm'));
	}
	
	function error ($error="")
	{
		$this->fw->set('error', $error);
		$this->fw->set('content', Template::instance()->render('error.htm'));
		//echo "ugly error: ".$error;exit;
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
			$this->fw->db3 = new \DB\SQL ( $this->fw['installerCFG.db3.dsn'], $this->fw['installerCFG.db3.user'], $this->fw['installerCFG.db3.pass'], $options );
		}
		catch (PDOException $e)
		{
			$this->error ( $e->getMessage() );
			return FALSE;
		}

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
			case 0:
				$this->fw->set('content', upgradetools::sitedata() );
				break;
			case 1:
				$this->fw->set('content', upgradetools::optional() );
				break;
			case 2:
				$this->fw->set('content', upgradetools::newTables() );
				break;
			case 3:
				$this->fw->set('content', upgradetools::processJobs() );
				break;
			case 4:
				$this->fw->set('content', upgradetools::buildConfig() );
				break;
			case 5:
				$this->fw->set('content', upgradetools::moveFiles() );
				break;
			default:
				$this->fw->reroute('@steps(@step=0)');
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
		//configtools::sanitize();
		
		if ( isset($this->fw['POST.new.db5.same_user']) )
		{
			$this->fw['POST.new.db5.user'] = $this->fw['POST.new.db3.user'];
			$this->fw['POST.new.db5.pass'] = $this->fw['POST.new.db3.pass'];
		}
		if ( isset($this->fw['POST.new.db5.same_server']) )
		{
			$this->fw['POST.new.db5.host'] = $this->fw['POST.new.db3.host'];
			$this->fw['POST.new.db5.port'] = $this->fw['POST.new.db3.port'];
		}

		// build the driver-specific DSN string
		$dsn = configtools::buildDSN();

		// test
		$this->fw['POST.new.test'] = configtools::testConfig($dsn);

		// build final DSN strings
		$this->fw['POST.new.db3.dsn'] = $dsn['db3'].";charset=".$this->fw['POST.new.db3.charset'];
		$this->fw['POST.new.db5.dsn'] = $dsn['db5'].";charset=".$this->fw['POST.new.db5.charset'];
		
		//save data and return to form
		$this->fw->dbCFG->write('config.json',$this->fw['POST.new']);

		$this->fw->reroute('config',false);
		exit;
	}
	
}

?>
