<?php

/*
	This defines the amount of items to be processed in certain modules
	
	They are split into categories, based on their "weight"
	
	When experiencing timeouts, these values can be lowered to throttle the speed
*/

$f3->set("limit.xlight", 500); /*
 used in:
 
 Feedback - all steps
 Stories - Categories
		 - Tags
		 - Characters
 Users - Info
       - Favourites
 Various - News
         - Tracker
		 - Shoutbox
*/

$f3->set("limit.light",  250); /*
 used in:
 
 Various - Logs
*/

$f3->set("limit.medium", 100); /*
 used in:
 
 Chapters - Copy
 Contests - Data
 Recommendations - Data
 Series - Data
 Stories - Data
         - Cache
 Users - Copy
 Various - Poll votes
*/

$f3->set("limit.heavy",   50); /*
 used in:
 
 Contests - Cache
 Recommendations - Cache
 Users - Fields
 Various - Polls
*/

$f3->set("limit.xheavy",  25); /*
 used in:
 
 Series - Cache
 Stories - Recount characters
         - Recount tags
*/

$f3->set("limit.omgpwn",  10);
/*
somebody actually reading this?
*/

?>