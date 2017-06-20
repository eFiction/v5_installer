<?php
class language {
	
	function __construct(\Base $f3)
	{
		$f3->set('module', 'Language');
	}

	function show(\Base $f3)
	{
		$f3->set('content', 'Languages');
	}
	
	function change(\Base $f3, $params)
	{
		if ( "" != $language = $params['language'] )
		{
			$f3['installerCFG.language'] = $language;
			$f3->dbCFG->write('config.json',$f3->installerCFG);
		}
		$f3->reroute('/',false);
	}
	
}

?>