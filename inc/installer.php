<?php
class installer {
	
	function __construct()
	{
		// reference to $f3
		$this->fw = Base::instance();

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
		$this->fw->set('content', Template::instance()->render('storage.htm'));
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
			case 0:
				$this->fw->set('content', installtools::sitedata() );
				break;
			/*
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
				*/
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
		configtools::sanitize(TRUE);
		// build the driver-specific DSN string
		$dsn = configtools::buildDSN(TRUE);

		// test
		$this->fw['POST.new.test'] = configtools::testConfig($dsn);
		
		// build final DSN strings
		$this->fw['POST.new.db5.dsn'] = $dsn['db5'].";charset=".$this->fw['POST.new.db5.charset'];
		
		//save data and return to form
		$this->fw->dbCFG->write('config.json',$this->fw['POST.new']);
		$this->fw->reroute('config');
	}
	
}

class installtools {
	
	public static function sitedata()
	{
		
	}
}

?>
