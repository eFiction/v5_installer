<?php
class installer {
	
	function __construct()
	{
		// reference to $f3
		$this->fw = Base::instance();

	}
	
	function beforeRoute()
	{
		
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
		$this->fw->set('scenario', upgradetools::storageSelect() );
		$this->fw->set('content', Template::instance()->render('storage.htm'));
	}
	
	function config ()
	{
		$this->fw->set('content', Template::instance()->render('config_new.htm'));
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

?>
