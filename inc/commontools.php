<?php

class commontools {
	
	public static function storageSelect()
	{
		$fw = \Base::instance();
		/*
		scenarios:
		
		0: +local, +sqlite, +full utf8
		1:                  -full utf8
		2:         -sqlite, +full utf8
		3:         -sqlite, -full utf8
		4: -local, -sqlite
		5: -local, +sqlite
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
	
}

?>