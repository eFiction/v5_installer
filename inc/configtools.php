<?php

class configtools {
	
	public static function buildDSN ($fresh=FALSE)
	{
		/* DSN examples
		MySQL:
		mysql:host=localhost;dbname=testdb
		mysql:host=db.server.tld;port=3307;dbname=testdb
		mysql:unix_socket=/tmp/mysql.sock;dbname=testdb

		PGSQL:
		pgsql:host=localhost;port=5432;dbname=testdb;user=bruce;password=mypass

		MS SQL / Sybase:
		mssql:host=localhost;dbname=testdb
		sybase:host=localhost;dbname=testdb
		dblib:host=localhost;dbname=testdb

		SQLite:
		sqlite:/path/to/database/folder/file.sq3
		*/
		$fw = \Base::instance();
		
		foreach ( $fw['POST.new'] as $server => $settings)
		{
			if ( $fw["POST.new.{$server}.dbname"]=="" )
				$dsn[$server] = NULL;

			else
			{
				if($fw["POST.new.{$server}.driver"]=="mysql")
				{
					$dsn[$server] = "mysql:dbname=".$fw["POST.new.{$server}.dbname"];

					// {localhost or no host} and no port in dsn will trigger automatic socket connection attempt
					if(
							($fw["POST.new.{$server}.host"]=="localhost" OR $fw["POST.new.{$server}.host"]=="")
							AND $fw["POST.new.{$server}.port"]==""
							)
						$dsn[$server] .= ";host=localhost";

					// no hostname and non-numeric port shows a unix socket path.
					elseif($fw["POST.new.{$server}.host"]=="" AND !is_numeric($fw["POST.new.{$server}.port"]) )
						$dsn[$server] .= ";unix_socket=".$fw["POST.new.{$server}.port"]."";


					else
					{
						$dsn[$server] .= ";host=".$fw["POST.new.{$server}.host"];
						// Add port if numeric
						if ( is_numeric($fw["POST.new.{$server}.port"]) )
							$dsn[$server] .= ";port=".$fw["POST.new.{$server}.port"];
					}
				}
				elseif($fw['POST.new.dbdriver']=="pgsql")
				{
					// not yet
				}
				elseif($fw['POST.new.dbdriver']=="mssql")
				{
					// not yet
				}
			}
		}
		return $dsn;
	}
	
	public static function testConfig ($dsnTest)
	{
		$fw = \Base::instance();

		// Options
		$options = array(
			\PDO::ATTR_ERRMODE 			=> \PDO::ERRMODE_EXCEPTION, // generic attribute
			\PDO::ATTR_PERSISTENT 		=> TRUE,  // we want to use persistent connections
		);
		
		if($fw['POST.new.db5.driver']=="mysql")
			$options5 = $options + [ \PDO::MYSQL_ATTR_COMPRESS => TRUE ];
		else $options5 = $options;
		
		if($fw['POST.new.db3.driver']=="mysql")
			$options += [ \PDO::MYSQL_ATTR_COMPRESS => TRUE ]; // MySQL-specific attribute

		foreach ( $dsnTest  as $server => $dsn )
		{
			if ( $dsn==NULL )
			{
				$test[$server] = 1;
			}
			else
			{
				// reset connection
				unset($dbTest);

				// Test db connection
				try {
					$dbTest = new \DB\SQL ( $dsn, $fw["POST.new.{$server}.user"], $fw["POST.new.{$server}.pass"], $options );
					$test[$server] = 2;

					if ( $server == "db5" )
					{
						// probe for existing eFi5 tables
						$probe = $dbTest->exec(
										"SELECT table_name FROM INFORMATION_SCHEMA.TABLES
											WHERE table_schema = :dbname
											AND table_name LIKE :tablename",
												[ ':dbname'	=> $fw['POST.new.db5.dbname'], ':tablename' => $fw['POST.new.db5.prefix'].'menu_adminpanel'	]
										);
						if ( sizeof($probe)>0 )
							$test[$server] = 3;
					}
					elseif ( $server == "db3" )
					{
						$test['data'] = -1;
						$probeSQL = "SELECT `tableprefix`, `sitekey`, `sitename` 
										FROM `{$fw['POST.new.db3.dbname']}`.`{$fw['POST.new.db3.settings']}fanfiction_settings`";

						if ($fw['POST.new.db3.sitekey']>"")
						{
							try {
								$probe = $dbTest->exec(
										$probeSQL ." WHERE `sitekey` LIKE :sitekey",
										[ ':sitekey'	=> $fw['POST.new.db3.sitekey'] 	]
								);
								$test['data'] = 2;
								if ( $dbTest->count() !== 1 ) $test['data'] = 1;
							} catch (PDOException $e) {
								$test['data'] = 0;
							}
						}
						
						// Probe without given sitekey or when given sitekey did not work
						if ($fw['POST.new.db3.sitekey']=="" OR $test['data']==1)
						{
							try {
								$probe = $dbTest->exec(  $probeSQL 	);
								if ( $dbTest->count() === 1 )
								{
									$test['data'] = 2;
									$fw['POST.new.db3.sitekey'] = $probe[0]['sitekey'];
								}
								else $test['data'] = 1;
							} catch (PDOException $e) {
								$test['data'] = 0;
								$fw["POST.new.error.data"] = $e->getMessage();
							}
						}
						
						if ( $test['data']==2 )
						{
							$fw['POST.new.db3.prefix'] = $probe[0]['tableprefix'];
							$fw['POST.new.data.sitename'] = $probe[0]['sitename'];

							$testDoubleAuthors = "SELECT A1.penname 
													FROM `{$fw['POST.new.db3.dbname']}`.`{$probe[0]['tableprefix']}fanfiction_authors`A1 
														INNER JOIN `{$fw['POST.new.db3.dbname']}`.`{$probe[0]['tableprefix']}fanfiction_authors`A2 
													ON ( A1.penname = A2.penname AND A1.uid < A2.uid )";
							try {
								$probe = $dbTest->exec( $testDoubleAuthors );
								// double penname detected, upgrade will fail
								if ( sizeof($probe)>0 )
								{
									$test['data'] = 3;
									$fw["POST.new.error.doublename"] = $probe;
								}
								
							} catch (PDOException $e) {
								
							}
						}
					}

					// probe for supported charset (MySQL only)
					if($fw["POST.new.{$server}.driver"]=="mysql")
					{
						try {
						$dbTest->query("SET NAMES 'UTF8MB4'");
						$fw["POST.new.{$server}.charset"] = "UTF8MB4";
						} catch (PDOException $e) {
						$fw["POST.new.{$server}.charset"] = "UTF8";
						}
					}
				}
				catch (PDOException $e)
				{
					$test[$server] = 0;
					$fw["POST.new.error.{$server}"] = $e->getMessage();
					$fw["POST.new.{$server}.charset"] = "";
				}
			}
		}

		if ( isset($dbTest) AND $dsn AND NULL!==strpos($fw['POST.new.db5.prefix'],'fanfiction') )
		{
			$probe = $dbTest->exec(
							"SELECT table_name FROM INFORMATION_SCHEMA.TABLES
								WHERE table_schema = :dbname
								AND table_name LIKE :tablename",
									[ ':dbname'	=> $fw['POST.new.db5.dbname'], ':tablename' => $fw['POST.new.db5.prefix'].'classes'	]
							);
			if(sizeof($probe)>0) $test['db5'] = 4;
		}
		
		if ( NULL!==$fw['POST.admin'] )
		{
			if ( $fw['POST.admin.username']!="" )
				$test['admin']['username'] = TRUE;
			if ( $fw['POST.admin.mail']!="" )
				$test['admin']['mail'] = TRUE;

			if ( $fw['POST.admin.pass1']!="" )
			{
				if ($fw['POST.admin.pass1']==$fw['POST.admin.pass2'])
				{
					// Short password
					if ( 4>strlen($fw['POST.admin.pass1']) )
						$test['admin']['pass'] = 3;
					// All is good
					else
						$test['admin']['pass'] = 0;
				}
				// Password mismatch
				else
					$test['admin']['pass'] = 1;
			}
			// Password field empty
			else
				$test['admin']['pass'] = 2;

			// Only remember 'good' password
			if ( $test['admin']['pass'] > 0 )
				unset($fw['POST.admin.pass1'], $fw['POST.admin.pass2']);
		}
		
		return $test;
	}
	
}

?>