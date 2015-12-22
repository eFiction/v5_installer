<?php
class installer {
	
	function __construct()
	{
		// reference to $f3
		$this->fw = Base::instance();

	}
	
	function base()
	{
		// Landing page
		$this->fw->set('content',"Installer -- todo");
	}
	
	function config ()
	{
		// Load config file from main project site
		$this->fw->set('content',"Fresh config -- todo");
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
		$this->fw['POST.new.dsn.5'] = $dsn[5].";charset=".$this->fw['POST.new.charset'];
		
		//save data and return to form
		$this->fw->dbCFG->write('config.json',$this->fw['POST.new']);
		$this->fw->reroute('config');
	}
	
}

?>
