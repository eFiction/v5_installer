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
	// Chapters copy is a 1-pass module, doing the entire chapter relocation
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";

$sql = <<<EOF
INSERT INTO `{$new}menu` (`label`, `order`, `link`) VALUES
('Home', 1, ''),
('Authors', 5, 'authors'),
('Fandoms', 1, 'story/categories'),
('Updates', 3, 'story/updates'),
('Archive', 2, 'story'),
('Search', 6, 'story/search'),
('Challenges', 6, 'story/contests');
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}


function menu_admin($job, $step)
{
	// Chapters copy is a 1-pass module, doing the entire chapter relocation
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";

$sql = <<<EOF
INSERT INTO `{$new}menu_adminpanel` (`label`, `order`, `link`, `icon`, `child_of`, `active`, `requires`, `evaluate`) VALUES
('Tags', 3, 'archive/tags', '{ICON:tag}', 'archive', 1, 64, NULL),
('Featured', 1, 'archive/featured', '{ICON:blank}', 'archive', 1, 64, NULL),
('Groups', 3, 'members/groups', '{ICON:members}', 'members', 1, 128, NULL),
('Pending', 2, 'members/pending', '{ICON:waiting}', 'members', 1, 128, NULL),
('Profile', 4, 'members/profile', '{ICON:blank}', 'members', 1, 128, NULL),
('Language', 6, 'settings/language', '{ICON:language}', 'settings', 1, 128, NULL),
('Icons', 2, 'settings/layout/icons', '{ICON:blank}', 'settings/layout', 1, 128, NULL),
('Themes', 1, 'settings/layout/themes', '{ICON:blank}', 'settings/layout', 1, 128, NULL),
('Layout', 5, 'settings/layout', '{ICON:layout}', 'settings', 1, 64, NULL),
('Registration', 4, 'settings/registration', '{ICON:register}', 'settings', 1, 128, NULL),
('Server', 1, 'settings/server', '{ICON:wrench}', 'settings', 1, 128, NULL),
('Shoutbox', 5, 'home/shoutbox', '{ICON:blank}', 'home', 1, 64, '\$shoutbox == 1;'),
('Modules', 4, 'home/modules', '{ICON:modules}', 'home', 1, 128, NULL),
('News', 3, 'home/news', '{ICON:news}', 'home', 1, 32, NULL),
('CustomPages', 2, 'home/custompages', '{ICON:text}', 'home', 1, 64, NULL),
('Manual', 1, 'home/manual', '{ICON:manual}', 'home', 1, 32, NULL),
('Stories', 5, 'stories', '{ICON:book}', NULL, 1, 64, NULL),
('Archive', 4, 'archive', '{ICON:archive}', NULL, 1, 64, NULL),
('Members', 3, 'members', '{ICON:member}', NULL, 1, 128, NULL),
('Settings', 2, 'settings', '{ICON:settings}', NULL, 1, 128, NULL),
('Home', 1, 'home', '{ICON:home}', NULL, 1, 32, NULL),
('Edit', 1, 'archive/tags/edit', '{ICON:tag}', 'archive/tags', 1, 64, NULL),
('Taggroups', 2, 'archive/tags/groups', '{ICON:tags}', 'archive/tags', 1, 128, NULL),
('Tagcloud', 3, 'archive/tags/cloud', '{ICON:cloud}', 'archive/tags', 1, 128, NULL),
('Categories', 4, 'archive/categories', '{ICON:blank}', 'archive', 1, 64, NULL),
('Pending', 1, 'stories/pending', '{ICON:waiting}', 'stories', 1, 32, NULL),
('Edit', 2, 'stories/edit', '{ICON:edit}', 'stories', 1, 64, NULL),
('Add', 3, 'stories/add', '{ICON:document-new}', 'stories', 1, 64, NULL),
('Settings', 1, 'archive/featured', '{ICON:blank}', 'archive/featured', 1, 64, NULL),
('Current', 2, 'archive/featured/select=current', '{ICON:blank}', 'archive/featured', 1, 64, NULL),
('Past', 4, 'archive/featured/select=past', '{ICON:blank}', 'archive/featured', 1, 64, NULL),
('Future', 3, 'archive/featured/select=future', '{ICON:blank}', 'archive/featured', 1, 64, NULL),
('Characters', 2, 'archive/characters', '{ICON:members}', 'archive', 1, 64, NULL),
('Screening', 3, 'settings/screening', '{ICON:visible}', 'settings', 1, 128, NULL),
('Search', 1, 'members/search', '{ICON:search}', 'members', 1, 128, NULL),
('Team', 5, 'members/team', '{ICON:blank}', 'members', 1, 128, NULL),
('Security', 2, 'settings/security', '{ICON:lock}', 'settings', 1, 128, NULL);
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}

function menu_user($job, $step)
{
	// Chapters copy is a 1-pass module, doing the entire chapter relocation
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";

$sql = <<<EOF
INSERT INTO `{$new}menu_userpanel` (`label`, `order`, `link`, `icon`, `child_of`, `active`, `evaluate`) VALUES
('UserMenu_Settings', 1, 'settings', '{ICON:settings}', NULL, 1, NULL),
('UserMenu_Message', 2, 'messaging', '{ICON:mail}', NULL, 1, NULL),
('UserMenu_Authoring', 3, 'author', '{ICON:keyboard}', NULL, 1, NULL),
('UserMenu_MyLibrary', 4, 'library', '{ICON:book}', NULL, 1, NULL),
('UserMenu_Feedback', 4, 'feedback', '{ICON:comments}', NULL, 1, NULL),
('%%AUTHORS', 1, 'author/uid=%ID%', '{ICON:member}', 'author', 1, NULL),
('UserMenu_Curator', 2, 'author/curator', '{ICON:member}', 'author', 1, NULL),
('UserMenu_AddStory', 1, 'author/uid=%ID%/add', '{ICON:following} {ICON:plus}', 'author_sub', 1, NULL),
('Authoring_Finished%%1', 2, 'author/uid=%ID%/finished', '{ICON:following} {ICON:text}', 'author_sub', 1, NULL),
('Authoring_Unfinished%%0', 3, 'author/uid=%ID%/unfinished', '{ICON:following} {ICON:text}', 'author_sub', 1, NULL),
('Authoring_Drafts%%-1', 4, 'author/uid=%ID%/drafts', '{ICON:following} {ICON:text}', 'author_sub', 1, NULL),
('Library_Bookmarks%%BMS', 1, 'library/bookmark', '{ICON:bookmark}', 'library', 1, NULL),
('Library_Favourites%%FAVS', 2, 'library/favourite', '{ICON:favourite}', 'library', 1, NULL),
('Library_Recommendations%%RECS', 3, 'library/recommendation', '{ICON:star}', 'library', 1, NULL),
('UserMenu_PMInbox', 1, 'messaging/inbox', '{ICON:inbox}', 'messaging', 1, NULL),
('UserMenu_PMWrite', 2, 'messaging/write', '{ICON:edit}', 'messaging', 1, NULL),
('UserMenu_PMOutbox', 3, 'messaging/outbox', '{ICON:bars}', 'messaging', 1, NULL),
('UserMenu_ReviewsWritten%%RW', 1, 'feedback/reviews/written', '{ICON:arrow-right}', 'feedback', 1, NULL),
('UserMenu_ReviewsReceived%%RR', 2, 'feedback/reviews/received', '{ICON:arrow-left}', 'feedback', 1, NULL),
('UserMenu_CommentsWritten%%CW', 3, 'feedback/comments/written', '{ICON:arrow-right}', 'feedback', 1, NULL),
('UserMenu_CommentsReceived%%CR', 4, 'feedback/comments/received', '{ICON:arrow-left}', 'feedback', 1, NULL),
('UserMenu_Shoutbox%%SB', 5, 'feedback/shoutbox', '{ICON:text}', 'feedback', 1, NULL),
('UserMenu_Profile', 1, 'settings/profile', '{ICON:member}', 'settings', 1, NULL),
('ChangePW', 3, 'settings/changepw', '{ICON:key}', 'settings', 1, NULL),
('UserMenu_Preferences', 2, 'settings/preferences', '{ICON:visible}', 'settings', 1, NULL);
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();
	
	$fw->db5->exec ( "UPDATE `{$new}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}
?>