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
	
	public static function jobInit($data)
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


	public static function jobStart($job)
	{
		$fw = \Base::instance();
		$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";
		
		if ( $job['success'] == 0 )
		{
			if ( FALSE === commontools::jobInit($job) )
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
		$fw->set('redirect', 3 );
		$fw->set('onload', ' onLoad="setTimeout(\'delayedRedirect()\', 3000)"' );
		
		if ( isset($toDo) )
		{
			$fw->set('continue', 
				[
					"step" 		=> 3,
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
					"step" 		=> 3,
					"message"	=> 'All steps have been processed',
					"message2"	=> " with the next job",
				]
			);
		}
	}
}

?>