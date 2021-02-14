<?php
/*
	Job definition for 'chapters'
	eFiction upgrade from version 3.5.x
*/

$fw->jobSteps = array(
		"page"	=> "Create menu entries",
		"user"	=> "Create usermenu entries",
		"admin"	=> "Create admin menu",
	);


function menu_page($job, $step)
{
	// Page menu
	$fw = \Base::instance();

$sql = <<<EOF
INSERT INTO `{$fw->dbNew}menu` (`label`, `order`, `link`) VALUES
('Home', 		1, ''),
('Authors', 	2, 'authors'),
('Categories',	3, 'story/categories'),
('Updates',		4, 'story/updates'),
('Contests',	5, 'story/contests'),
('Search',		6, 'story/search'),
('Help',		7, 'home/page/help');
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ",
						[
							':items' => $count,
							':id' => $step['id']
						]
					);
}


function menu_admin($job, $step)
{
	// Admin menu
	$fw = \Base::instance();

$sql = <<<EOF
INSERT INTO `{$fw->dbNew}menu_adminpanel` (`label`, `child_of`, `order`, `link`, `icon`, `active`, `requires`, `evaluate`) VALUES
('Home', 			NULL,				1, 'home',								'{ICON:home}',				1, 32, NULL),
('Settings',		NULL,				2, 'settings',							'{ICON:settings}',			1, 128, NULL),
('Modules',			NULL,				3, 'modules',							'{ICON:modules}',			0, 128, NULL),
('Members',			NULL,				4, 'members',							'{ICON:member}',			1, 128, NULL),
('Archive',			NULL,				5, 'archive',							'{ICON:archive}',			1, 64, NULL),
('Stories',			NULL,				6, 'stories',							'{ICON:book}',				1, 64, NULL),
('Submission',		'archive',			1, 'archive/submit',					'{ICON:submissions}',		1, 64, NULL),
('Contests',		'archive',			2, 'archive/contests',					'{ICON:contest}',			1, 64, '[\'optional_modules\'][\'contests\']'),
('Characters',		'archive',			3, 'archive/characters',				'{ICON:members}',			1, 64, NULL),
('Tags',			'archive',			4, 'archive/tags',						'{ICON:tags}',				1, 64, NULL),
('Categories',		'archive',			5, 'archive/categories',				'{ICON:categories}',		1, 64, NULL),
('Ratings',			'archive',			6, 'archive/ratings',					'{ICON:rating}',			1, 64, NULL),
('Edit',			'archive/tags',		1, 'archive/tags/edit',					'{ICON:tag}',				1, 64, NULL),
('Taggroups',		'archive/tags',		2, 'archive/tags/groups',				'{ICON:tags}',				1, 128, NULL),
('Tagcloud',		'archive/tags',		3, 'archive/tags/cloud',				'{ICON:cloud}',				1, 128, NULL),
('Manual',			'home',				1, 'home/manual',						'{ICON:manual}',			0, 32, NULL),
('Maintenance',		'home',				2, 'home/maintenance',					'{ICON:waiting}',			1, 64, NULL),
('CustomPages',		'home',				3, 'home/custompages',					'{ICON:text}',				1, 64, NULL),
('News',			'home',				4, 'home/news',							'{ICON:news}',				1, 32, NULL),
('Logs',			'home',				5, 'home/logs',							'{ICON:file}',				1, 64, ''),
('Shoutbox',		'home',				6, 'home/shoutbox',						'{ICON:sbox}',				1, 64, '[\'optional_modules\'][\'shoutbox\']'),
('Polls',			'home',				7, 'home/polls', 						'{ICON:poll}',				1, 64, '[\'optional_modules\'][\'poll\']'),
('Add',				'members',			1, 'members/add',						'{ICON:user-add}',			1, 128, NULL),
('Edit',			'members',			2, 'members/edit',						'{ICON:user-edit}',			1, 128, NULL),
('Pending',			'members',			3, 'members/pending',					'{ICON:waiting}',			1, 128, NULL),
('Groups',			'members',			4, 'members/groups',					'{ICON:members}',			1, 128, NULL),
('Profile',			'members',			5, 'members/profile',					'{ICON:profile}',			1, 128, NULL),
('Team',			'members',			6, 'members/team',						'{ICON:staff}',				1, 128, NULL),
('DateTime',		'settings',			2, 'settings/datetime',					'{ICON:calendar}',			1, 64, NULL),
('Server',			'settings',			3, 'settings/server',					'{ICON:wrench}',			1, 128, NULL),
('Security',		'settings',			4, 'settings/security',					'{ICON:lock}',				1, 128, NULL),
('Screening',		'settings',			5, 'settings/screening',				'{ICON:visible}',			1, 128, NULL),
('Registration',	'settings',			6, 'settings/registration',				'{ICON:register}',			1, 128, NULL),
('Layout',			'settings',			7, 'settings/layout',					'{ICON:layout}',			1, 64, NULL),
('Language',		'settings',			8, 'settings/language',					'{ICON:language}',			1, 64, NULL),
('Themes',			'settings/layout',	1, 'settings/layout/themes',			'{ICON:blank}',				1, 128, NULL),
('Icons',			'settings/layout',	2, 'settings/layout/icons',				'{ICON:blank}',				1, 128, NULL),
('Pending',			'stories',			1, 'stories/pending',					'{ICON:waiting}',			1, 32, NULL),
('Edit',			'stories',			2, 'stories/edit',						'{ICON:edit}',				1, 32, NULL),
('Add',				'stories',			3, 'stories/add',						'{ICON:document-new}',		1, 64, NULL),
('Featured',		'stories',			4, 'stories/featured',					'{ICON:feature}',			1, 64, NULL),
('Series',			'stories',			5, 'stories/series',					'{ICON:numlist}',			1, 32, NULL),
('Collections',		'stories',			6, 'stories/collections',				'{ICON:list}',				1, 32, NULL),
('Recommendations',	'stories',			7, 'stories/recommendations',			'{ICON:thumbs-up}',			1, 32, '[\'optional_modules\'][\'recommendations\']'),
('Current',			'stories/featured', 1, 'stories/featured/select=current',	'{ICON:feature}',			1, 64, NULL),
('Upcoming',		'stories/featured', 2, 'stories/featured/select=upcoming',	'{ICON:feature-upcoming}',	1, 64, NULL),
('Past',			'stories/featured', 3, 'stories/featured/select=past', 		'{ICON:feature-off}',		1, 64, NULL);
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ",
						[
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function menu_user($job, $step)
{
	// User menu
	$fw = \Base::instance();

$sql = <<<EOF
INSERT INTO `{$fw->dbNew}menu_userpanel` (`label`, `order`, `link`, `icon`, `child_of`, `active`, `evaluate`) VALUES
('UserMenu_Start',						1, '',								'{ICON:home}',		NULL, 1, NULL),
('UserMenu_Settings',					2, 'settings',						'{ICON:settings}',	NULL, 1, NULL),
('UserMenu_Message',					3, 'messaging',						'{ICON:mail}',		NULL, 1, NULL),
('UserMenu_Authoring',					4, 'author',						'{ICON:keyboard}',	NULL, 1, NULL),
('UserMenu_MyLibrary',					5, 'library',						'{ICON:book}',		NULL, 1, NULL),
('UserMenu_Feedback',					6, 'feedback',						'{ICON:comments}',	NULL, 1, NULL),
('UserMenu_Shoutbox%%SB%%SB',			7, 'shoutbox',						'{ICON:text}',		NULL, 1, 'shoutbox'),
('UserMenu_Polls%%PL%%PL',				8, 'polls',							'{ICON:poll}',		NULL, 1, NULL),
--
('%%AUTHORS',							1, 'author/uid=%ID%',				'{ICON:member}', 'author', 1, NULL),
('UserMenu_Curator',					2, 'author/curator',				'{ICON:member}', 'author', 1, NULL),
--
('UserMenu_AddStory',					1, 'author/uid=%ID%/add',			'{ICON:following} {ICON:plus}',  'author_sub', 1, NULL),
('Authoring_Finished%%9',				2, 'author/uid=%ID%/finished',		'{ICON:following} {ICON:text}',  'author_sub', 1, NULL),
('Authoring_Unfinished%%6',				3, 'author/uid=%ID%/unfinished',	'{ICON:following} {ICON:text}',  'author_sub', 1, NULL),
('Authoring_Drafts%%1',					4, 'author/uid=%ID%/drafts',		'{ICON:following} {ICON:text}',  'author_sub', 1, NULL),
('Authoring_Deleted%%0',				5, 'author/uid=%ID%/deleted',		'{ICON:following} {ICON:trash}', 'author_sub', 1, NULL),
--
('Library_Bookmarks%%LIB%%BMS',			1, 'library/bookmark',				'{ICON:bookmark}',	'library', 1, NULL),
('Library_Favourites%%LIB%%FAVS',		2, 'library/favourite',				'{ICON:favourite}', 'library', 1, NULL),
('Library_Recommendations%%LIB%%RECS',	3, 'library/recommendations',		'{ICON:thumbs-up}',	'library', 1, 'recommendations'),
('Library_Series%%LIB%%SER', 			4, 'library/series', 				'{ICON:numlist}',	'library', 1, NULL),
('Library_Collections%%LIB%%COLL', 		5, 'library/collections',			'{ICON:list}',		'library', 1, NULL),
--
('UserMenu_PMInbox%%MSG%%UN',			1, 'messaging/inbox',				'{ICON:inbox}',		'messaging', 1, NULL),
('UserMenu_PMWrite',					2, 'messaging/write',				'{ICON:edit}',		'messaging', 1, NULL),
('UserMenu_PMOutbox',					3, 'messaging/outbox',				'{ICON:bars}',		'messaging', 1, NULL),
--
('UserMenu_ReviewsWritten%%FB%%RW',		1, 'feedback/reviews/written',		'{ICON:arrow-right}',	'feedback', 1, NULL),
('UserMenu_ReviewsReceived%%FB%%RR',	2, 'feedback/reviews/received',		'{ICON:arrow-left}',	'feedback', 1, NULL),
('UserMenu_CommentsWritten%%FB%%CW',	3, 'feedback/comments/written',		'{ICON:arrow-right}',	'feedback', 1, NULL),
('UserMenu_CommentsReceived%%FB%%CR',	4, 'feedback/comments/received',	'{ICON:arrow-left}',	'feedback', 1, NULL),
--
('UserMenu_Profile',					1, 'settings/profile',				'{ICON:member}',	'settings', 1, NULL),
('ChangePW',							2, 'settings/changepw',				'{ICON:key}',		'settings', 1, NULL);
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();

	$fw->db5->exec ( "UPDATE `{$fw->dbNew}process`SET `success` = 2, `items` = :items WHERE `id` = :id ",
						[
							':items' => $count,
							':id' => $step['id']
						]
					);
}
?>
