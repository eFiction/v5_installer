<?php
/*
SQL convert from eFiction 3.5.x to 5.0.0
Probe for optional modules
*/

/*
	$optional['type'][ %name of module% ] = (int);
	
	1: Always install new table, copy data if old table exists (-> new core module)
	2: Probe for old data, create table and copy data if old table exists or is requested by user_error
	3: Install if requested by user

*/


/* --------------------------------------------------------------------------------------------
* RECOMMENDATIONS *
	requires: STORIES RELATION
-------------------------------------------------------------------------------------------- */
$optional['recommendations'] = array
(
	"probe"			=> "SELECT 1 FROM information_schema.tables 
							WHERE table_schema = '{$fw['installerCFG.dbname']}' 
							AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_recommendations'",
	"description"	=>	"Recommendations module",
	"type"			=>	2,
/*	"steps"			=>	array (
							array ( "recommendations", 0, "Recommendations, optional module" ),
							array ( "recommend_tags", 0, "Recommendation <-> Tags relations" ),
							array ( "recommend_cache", 0, "Recommendation cache" ),
						),*/
);


/* --------------------------------------------------------------------------------------------
* CONTEST *
	requires: STORY
-------------------------------------------------------------------------------------------- */
$optional['contests'] = array
(
	"probe"			=>	"SELECT 1 FROM information_schema.tables 
							WHERE table_schema = '{$fw['installerCFG.dbname']}' 
							AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_challenges'",
	"description"	=>	"Contest module (previously named challenge)",
	"type"			=>	2,
/*	"steps"			=>	array (
							array ( "contest", 0, "Contest (former: challenges), optional module" ),
							array ( "contest_relation", 0, "Contest <-> Story relation" ),
						)*/
);


/* --------------------------------------------------------------------------------------------
* SHOUTBOX *
	requires: -
-------------------------------------------------------------------------------------------- */
$optional['shoutbox'] = array
(
	"probe"			=> "SELECT 1 FROM information_schema.tables 
							WHERE table_schema = '{$fw['installerCFG.dbname']}' 
							AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_shoutbox'",
	"description"	=>	"Shoutbox",
	"type"			=>	1,
);


/* --------------------------------------------------------------------------------------------
* POLL *
	requires: -
-------------------------------------------------------------------------------------------- */
$optional['poll'] = array
(
	"probe"			=>	"SELECT 1 FROM information_schema.tables 
							WHERE table_schema = '{$fw['installerCFG.dbname']}' 
							AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_poll'",
	"description"	=>	"Poll module",
	"type"			=>	1,
);


/* --------------------------------------------------------------------------------------------
* TRACKER *
	requires: -
-------------------------------------------------------------------------------------------- */
$optional['tracker'] = array
(
	"probe"			=>	"SELECT 1 FROM information_schema.tables 
							WHERE table_schema = '{$fw['installerCFG.dbname']}' 
							AND table_name = '{$fw['installerCFG.pre_old']}fanfiction_tracker'",
	"description"	=>	"Track last read stories and chapters",
	"type"			=>	1,
);




?>