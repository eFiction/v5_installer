<?php

// Landing page
$f3->route( 'GET @upgrade: /upgrade', 		'upgrade->base' );
$f3->route( 'GET /fresh', 					'install->base' );

// upgrade config
$f3->route( 'GET @upconfig: /upgrade/config', 'upgrade->config' );
$f3->route( 'POST /upgrade/config', 		'upgrade->saveConfig' );

// fresh config
$f3->route( 'GET @freshconfig: /fresh/config', 	'install->config' );
$f3->route( 'POST /fresh/config', 				'install->saveConfig' );

// chapter storage
$f3->route( 'GET /upgrade/chapters/@where', 'upgrade->storage' );
$f3->route( 'GET /fresh/chapters/@where', 	'install->storage' );

//actual upgrade steps
$f3->route( [ 'GET /upgrade/steps/@step' ,
			  'GET /upgrade/steps/@step/@sub' ],
											'upgrade->steps' );
$f3->route( [ 'GET /fresh/steps/@step' ,
			  'GET /fresh/steps/@step/@sub' ],
											'install->steps' );

// language selection
$f3->route( 'GET /language',			'language->show' );
$f3->route( 'GET /language/@language',	'language->change' );


?>
