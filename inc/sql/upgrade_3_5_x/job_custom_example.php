<?php
/*
	Job definition for 'custom fields'
	eFiction upgrade from version 3.5.x
	

	2017-10-26: Creation
*/

/*
	Custom story table fields
*/
// Define fields to be added to table

/* <- remove to activate ->
$fw->customFields['stories'][] =
[
	"field"	=> "songtext",
	"type"	=> "varchar(99) NOT NULL",
	"after"	=> "trans_to",
];
$fw->customFields['stories'][] =
[
	"field"	=> "ihavenoidea",
	"type"	=> "varchar(99) NOT NULL",
	"after"	=> "songtext",
];
// If desired, add custom index
$fw->customIndex['stories'][] = "ADD KEY `songindex` (`songtext`(10));";
// Tell the script how to grab the data from the old table
$fw->customDataIn['stories'][] = "S.songtext";
$fw->customDataIn['stories'][] = "S.clueless AS ihavenoidea";
// No need for a data out definition here
// $fw->customDataOut['stories'][] = "";
<- remove to activate -> */


/*
	Custom authors/user table fields
*/

/* <- remove to activate ->
// Define fields to be added to table
$fw->customFields['users'][] =
[
	"field"	=> "",
	"type"	=> "",
	"after"	=> "",
];
// If desired, add custom index
$fw->customIndex['users'][] = "";
// Tell the script how to grab the data from the old table
$fw->customDataIn['users'][] = "A.fieldname";
// No need for a data out definition here
// $fw->customDataOut['users'][] = "";
<- remove to activate -> */

?>