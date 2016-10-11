<?php

class configtools {
	
	public static function sanitize($fresh=FALSE)
	{
		$fw = \Base::instance();

		if ( !$fresh )
			$fw['POST.new.db3'] = preg_replace("/[^0-9a-zA-Z_]/", "", $fw['POST.new.db3']);

		$fw['POST.new.db5'] = preg_replace("/[^0-9a-zA-Z_]/", "", $fw['POST.new.db5']);
		//print_r($fw['POST.new']);exit;
	}

	public static function buildDSN ($fresh=FALSE)
	{
		$fw = \Base::instance();
		
		foreach ( $fw['POST.new'] as $server => $settings)
		{
			if ( $fw["POST.new.{$server}.dbname"]=="" )
				$dsn[$server] = NULL;
			
			/*
			if ( $server == 'db3' AND $fw['POST.new.db3.dbname']=="" AND !$fresh )
			{
				$dsn['db3'] = NULL;
			}
			elseif ( $server == 'db5' AND $fw['POST.new.db5.dbname']=="" )
			{
				$dsn['db5'] = NULL;
			}
			*/
			else
			{
				if($fw["POST.new.{$server}.driver"]=="mysql")
				{
					$dsn[$server] = "mysql:dbname=".$fw["POST.new.{$server}.dbname"];

					// no hostname and non-numeric port shows a unix socket path.
					if($fw["POST.new.{$server}.host"]=="" AND !is_numeric($fw["POST.new.{$server}.port"]) )
						$dsn[$server] .= ";unix_socket=".$fw["POST.new.{$server}.port"]."";

					// {localhost or no host} and no port in dsn will trigger automatic socket connection attempt
					elseif
					(
						($fw["POST.new.{$server}.host"]=="localhost" OR $fw["POST.new.{$server}.host"]=="")
						AND $fw["POST.new.{$server}.port"]==""
					)
						$dsn[$server] .= ";host=localhost";

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
					
				}
				elseif($fw['POST.new.dbdriver']=="mssql")
				{
					
				}
			}
		}
//		print_r($dsn);exit;
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
						try {
							$dbTest->exec( 'SELECT 1 FROM `'.$fw['POST.new.db5.dbname'].'`.`'.$fw['POST.new.db5.prefix'].'config`' );
							echo $dbTest->count()."xxxx";
							$test[$server] = 3;
						} catch (PDOException $e) {
								echo "nichts";
								$test[$server] = 2;
								$fw['POST.new.db5.error'] = $e->getMessage();
						}
					}
					elseif ( $server == "db3" )
					{
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
								if ( $dbTest->count() !== 1 ) $test[1] = 1;
							} catch (PDOException $e) {
								$test['data'] = 0;
							}
						}
						// Probe without given sitekey
						else
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
							}
						}
						
						if ( $test['data']==2 )
						{
							$fw['POST.new.db3_prefix'] = $probe[0]['tableprefix'];
							$fw['POST.new.data.sitename'] = $probe[0]['sitename'];
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
					echo $server;

					$test[$server] = 0;
					$fw["POST.new.error.{$server}"] = $e->getMessage();
					$fw["POST.new.{$server}.charset"] = "";
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