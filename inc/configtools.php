<?php

class configtools {
	
	public static function sanitize($fresh=FALSE)
	{
		$fw = \Base::instance();
		
		if($fresh)
			$fields = [ "db_new", "pre_new" ];
		else
			$fields = [ "dbname", "settings", "db_new", "pre_new" ];

		foreach ( $fields as $field )
		{
			$fw['POST.new.'.$field] = preg_replace("/[^0-9a-zA-Z_]/", "", $fw['POST.new.'.$field]);
		}
	}

	public static function buildDSN ($fresh=FALSE)
	{
		$fw = \Base::instance();
		
		if ( $fw['POST.new.dbname']=="" AND !$fresh ) return FALSE;
		
		if($fw['POST.new.dbdriver']=="mysql")
		{
//			$dsn = "mysql:dbname=".$fw['POST.new.dbname'];

			// no hostname and non-numeric port shows a unix socket path.
			if($fw['POST.new.dbhost']=="" AND !is_numeric($fw['POST.new.dbport']) )
				$dsn . ";unix_socket=".$fw['POST.new.dbport']."";

			// {localhost or no host} and no port in dsn will trigger automatic socket connection attempt
			elseif
			(
				($fw['POST.new.dbhost']=="localhost" OR $fw['POST.new.dbhost']=="")
				AND $fw['POST.new.dbport']==""
			)
				$d = ";host=localhost";

			else
			{
				$d = ";host=".$fw['POST.new.dbhost'];
				// Add port if numeric
				if ( is_numeric($fw['POST.new.dbport']) )
					$d .= ";port=".$fw['POST.new.dbport'];
			}
			if(!$fresh) $dsn[3] = "mysql:dbname=".$fw['POST.new.dbname'].$d;
			$dsn[5] = "mysql:dbname=".$fw['POST.new.db_new'].$d;
		}
		elseif($fw['POST.new.dbdriver']=="pgsql")
		{
			
		}
		elseif($fw['POST.new.dbdriver']=="mssql")
		{
			
		}
		return $dsn;
	}
	
	public static function testConfig ($dsn)
	{
		$fw = \Base::instance();

		$fw['POST.new.error'] = "";
		$fw['POST.new.data.sitename'] = "";
		$test=(string) "100";
		
		if ( $dsn===FALSE ) return $test;

		// Options
		$options = array(
			\PDO::ATTR_ERRMODE 			=> \PDO::ERRMODE_EXCEPTION, // generic attribute
			\PDO::ATTR_PERSISTENT 		=> TRUE,  // we want to use persistent connections
		);
		if($fw['POST.new.dbdriver']=="mysql")
			$options[\PDO::MYSQL_ATTR_COMPRESS] 	= TRUE; // MySQL-specific attribute

		// Default charset
		$fw['POST.new.charset'] = "UTF8";
		
		// Test db connection
		try {
			$dbTest = new \DB\SQL ( $dsn[3], $fw['POST.new.dbuser'], $fw['POST.new.dbpass'], $options );
			$test[0] = 2;
		} catch (PDOException $e) {
			$test[0] = 0;
			$fw['POST.new.error'] = $e->getMessage();
		}
		
		if($test[0])
		{
			// Probe with given sitekey
			if ($fw['POST.new.sitekey']>"")
			{
				try {
					$probe = $dbTest->exec(
												'SELECT `tableprefix`, `sitename` FROM `'.$fw['POST.new.dbname'].'`.`'.$fw['POST.new.settings'].'fanfiction_settings` WHERE `sitekey` LIKE :sitekey',
												[
													':sitekey'	=> $fw['POST.new.sitekey']
												]
											);
					$test[1] = 2;
					if ( $dbTest->count() !== 1 ) $test[1] = 1;
				} catch (PDOException $e) {
					$test[1] = 0;
				}
			}
			// Probe without given sitekey
			else
			{
				try {
					$probe = $dbTest->exec(
												'SELECT `tableprefix`, `sitekey`, `sitename` FROM `'.$fw['POST.new.dbname'].'`.`'.$fw['POST.new.settings'].'fanfiction_settings`'
											);
					if ( $dbTest->count() === 1 )
					{
						$test[1] = 2;
						$fw['POST.new.sitekey'] = $probe[0]['sitekey'];
					}
					else $test[1] = 1;
				} catch (PDOException $e) {
					$test[1] = 0;
				}
			}
			if ( $test[1] == 2 )
			{
				$fw['POST.new.pre_old'] = $probe[0]['tableprefix'];
				$fw['POST.new.data.sitename'] = $probe[0]['sitename'];
			}
			
			// Check access to the new database
			try {
				$dbTest5 = new \DB\SQL ( $dsn[5], $fw['POST.new.dbuser'], $fw['POST.new.dbpass'], $options );
				$test[2] = 2;
			} catch (PDOException $e) {
					$test[2] = 0;
				$fw['POST.new.error'] = $e->getMessage();
			}
			
			// Check if tables with selected prefix already exist in target database
			if ( $test[2] == 2 )
			{
				try {
					$dbTest->exec( 'SELECT 1 FROM `'.$fw['POST.new.db_new'].'`.`'.$fw['POST.new.pre_new'].'config`' );
					$test[2] = 1;
				} catch (PDOException $e) {
						$test[2] = 2;
						$fw['POST.new.error'] = $e->getMessage();
				}
			}

			// probe for supported charset (MySQL only)
			if($fw['POST.new.dbdriver']=="mysql")
			{
				try {
		   		$dbTest->query("SET NAMES 'UTF8MB4'");
		   		$fw['POST.new.charset'] = "UTF8MB4";
				} catch (PDOException $e) {
		   		$fw['POST.new.charset'] = "UTF8";
				}
			}
		}
		
		return $test;
	}

	public static function testFresh ($dsn)
	{
		
	}
}

?>