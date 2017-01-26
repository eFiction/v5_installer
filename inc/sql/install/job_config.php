<?php
/*
	Job definition for 'chapters'
	eFiction upgrade from version 3.5.x
*/

$fw->jobSteps = array(
		"create"	=> "Create config table",
	);


function config_create($job, $step)
{
	// Chapters copy is a 1-pass module, doing the entire chapter relocation
	$fw = \Base::instance();
	$new = "{$fw['installerCFG.db5.dbname']}`.`{$fw['installerCFG.db5.prefix']}";

	$chapterLocation =   ( $fw['installerCFG.chapters']=="filebase" ) ? "local" : "db";
	/*
	$modules = [];
	foreach ( $fw['installerCFG.optional'] as $moduleName => $moduleOpt )
	{
		if ( $moduleOpt[0]!="-" ) $modules[$moduleName] = 1;
	}
	$modules_enabled = json_encode($modules);
	*/
//	if ( sizeof($modules)>0 ) $mapper->modules_enabled = $modules;

/*
  `imageupload` tinyint(1) NOT NULL DEFAULT '0',
  `imageheight` int(11) NOT NULL DEFAULT '200',
  `imagewidth` int(11) NOT NULL DEFAULT '200',

  `tinyMCE` tinyint(1) NOT NULL DEFAULT '0',
  `allowed_tags` varchar(200) NOT NULL DEFAULT '<b><i><u><center><hr><p><br /><br><blockquote><ol><ul><li><img><strong><em>',
  `captcha` tinyint(1) NOT NULL DEFAULT '0',
  `recentdays` tinyint(2) NOT NULL DEFAULT '7', Begrenzung der Tage für zuletzt erschienene Geschichten
  
  `ratings` tinyint(1) NOT NULL DEFAULT '0', Bewertungssystem

  `disablepopups` tinyint(1) NOT NULL DEFAULT '0', Warn-Popups nur einmal anzeigen
  `agestatement` tinyint(1) NOT NULL DEFAULT '0', Altersstellungnahme im Benutzerprofil


--------  `extendcats` tinyint(1) NOT NULL DEFAULT '0', ???????
--------  `words` text,  ????????
--------  `favorites` tinyint(1) NOT NULL DEFAULT '0',
--------  `logging` tinyint(1) NOT NULL DEFAULT '0',
--------  `debug` tinyint(1) NOT NULL DEFAULT '0',
--------  `displaycolumns` tinyint(1) NOT NULL DEFAULT '1', -> TPL
--------  `linkstyle` tinyint(1) NOT NULL DEFAULT '0', -> Paginations (TPL)
--------  `linkrange` tinyint(2) NOT NULL DEFAULT '5', -> Paginations
--------  `multiplecats` tinyint(1) NOT NULL DEFAULT '0', Anzahl Kategorien ????? Auto-detect
--------  `displayprofile` tinyint(1) NOT NULL DEFAULT '0', Profil anzeigen, anders gelöst in eFi5

---- ('modules_enabled',				'', 0, 							'{$modules_enabled}', NULL, '', 0),

*/
$sql = <<<EOF
INSERT INTO `{$new}config` (`name`, `admin_module`, `section_order`, `value`, `comment`, `form_type`, `can_edit`) VALUES
('stories_per_page',			'archive_general', 1,			"{$fw['installerCFG.data.itemsperpage']}", 'Stories per page in the Archive', 'text//numeric', 1),
('stories_default_order',		'archive_general', 2,			"{$fw['installerCFG.data.defaultsort']}", 'Default sorting for stories', 'select//__sort_date=date//__sort_name=title', 1),
('story_toc_default',			'archive_general', 3,			"{$fw['installerCFG.data.displayindex']}", 'Default to table of contents on stories with multiple chapters.', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('epub_domain',					'archive_general', 9,			'', 'ePub Domain@SMALL@Used to calculate your epub UUID v5. Leave blank for default (Archive URL)', 'text//small', 1),
('story_intro_items',			'archive_intro', 1,				'5', 'Stories to show on the archive entry page.', 'text//numeric', 1),
('story_intro_order',			'archive_intro', 2,				'modified', 'Order in which stories appear on the archive entry page.', 'select//__modified=modified//__published=published', 1),
('author_self', 				'archive_submit', 1,			"{$fw['installerCFG.data.author_self']}", 'Every member can post stories@SMALL@If set to no, members must be added to group Authors to allow them to post stories', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('story_validation', 			'archive_submit', 2,			"{$fw['installerCFG.data.story_validation']}", 'Stories require validation@SMALL@This does not apply to trusted authors.', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('stories_min_words',			'archive_submit', 3,			"{$fw['installerCFG.data.minwords']}", 'Minimum amount of words for a chapter', 'text//numeric', 1),
('stories_max_words',			'archive_submit', 4,			"{$fw['installerCFG.data.maxwords']}", 'Maximum amount of words for a chapter@SMALL@(0 = unlimited)', 'text//numeric', 1),
('allow_co_author', 			'archive_submit', 5,			"{$fw['installerCFG.data.coauthallowed']}", 'Allow addition of other authors to stories', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_series', 				'archive_submit', 6,			"{$fw['installerCFG.data.allowseries']}", 'Allow authors to create series@SMALL@Member series are now collections', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_roundrobin', 			'archive_submit', 7,			"{$fw['installerCFG.data.roundrobins']}", 'Allow guests to write reviews', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_reviews', 				'archive_reviews', 1,			"{$fw['installerCFG.data.reviewsallowed']}", 'Allow reviews', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_guest_reviews', 		'archive_reviews', 2,			"{$fw['installerCFG.data.anonreviews']}", 'Allow guests to write reviews', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_review_delete', 		'archive_reviews', 3,			"{$fw['installerCFG.data.revdelete']}", 'Authors can delete reviews', 'select//__all=2//__anonymous=1//{{@LN__no}}=0', 1),
('allow_rateonly', 				'archive_reviews', 4,			"{$fw['installerCFG.data.rateonly']}", 'Allow ratings without review (including kudos)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('tagcloud_basesize',			'archive_tags_cloud', 1,		'70', 'Base size in percent relative to normal font size.', 'text//numeric', 1),
('tagcloud_elements',			'archive_tags_cloud', 2,		'20', 'Maximum number of elements in the tag cloud@SMALL@Elements are ordered by count.', 'text//numeric', 1),
('tagcloud_minimum_elements',	'archive_tags_cloud', 3,		'10', 'Minimum amount of elements required to show tag cloud@SMALL@0 = always show', 'text//numeric', 1),
('tagcloud_spread',				'archive_tags_cloud', 4,		'4', 'Maximum size spread:@SMALL@spread*100 is the maximum percentage for the most used tag.<br>2.5 would convert to 250%.<br>(Realistic values are somewhere between 3 and 5)', 'text//numeric', 1),
('bb2_enabled', 				'bad_behaviour', 1,				'TRUE', 'Screen access\n<a href="http://bad-behavior.ioerror.us/support/configuration/" target="_blank">Bad Behaviour manual</a>@SMALL@(default <b>"{{@LN__yes}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__display_stats', 			'bad_behaviour', 2,				'TRUE', 'Display Statistics@SMALL@(default <b>"{{@LN__yes}}"</b>) (this causes extra load, turn off to save power)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__logging', 				'bad_behaviour', 3,				'TRUE', 'Logging@SMALL@(default <b>"{{@LN__yes}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__strict', 				'bad_behaviour', 4,				'FALSE', 'Strict Mode@SMALL@(default <b>"{{@LN__no}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__verbose', 				'bad_behaviour_ext', 1,			'FALSE', 'Verbose Logging@SMALL@(default <b>"{{@LN__no}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__offsite_forms', 			'bad_behaviour_ext', 2,			'FALSE', 'Allow Offsite Forms@SMALL@(default <b>"{{@LN__no}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__eu_cookie', 				'bad_behaviour_ext', 3,			'FALSE', 'EU Cookie@SMALL@(default <b>"{{@LN__no}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__httpbl_key', 			'bad_behaviour_ext', 4,			'', 'http:BL API Key@SMALL@Screen requests through Project Honey Pot.\r\nLeave empty to disable.', 'text//small', 1),
('bb2__httpbl_threat', 			'bad_behaviour_ext', 5,			'25', 'http:BL Threat Level@SMALL@(default <b>"25"</b>)', 'text//numeric', 1),
('bb2__httpbl_maxage', 			'bad_behaviour_ext', 6,			'30', 'http:BL Maximum Age@SMALL@(default <b>"30"</b>)', 'text//numeric', 1),
('bb2__reverse_proxy', 			'bad_behaviour_rev', 1,			'FALSE', 'Reverse Proxy@SMALL@(default <b>"{{@LN__no}}"</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__reverse_proxy_header', 	'bad_behaviour_rev', 2,			'X-Forwarded-For', 'Reverse Proxy Header@SMALL@(default “X-Forwarded-For”)\r\nOnly required when using reverse proxy!', 'text//small', 1),
('bb2__reverse_proxy_addresses','bad_behaviour_rev', 3,			'', 'Reverse Proxy Addresses@SMALL@(no default)\r\nOnly required when using reverse proxy!', 'text//', 1),
('date_format_short',			'settings_datetime', 1,			"{$fw['installerCFG.data.dateformat']}", 'Default short date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'text//small', 1),
('date_format_long',			'settings_datetime', 2,			"{$fw['installerCFG.data.dateformat']} {$fw['installerCFG.data.timeformat']}", 'Default long date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'text//small', 1),
('time_format',					'settings_datetime', 3,			"{$fw['installerCFG.data.timeformat']}", 'Default time format.', 'select//23:30=H:i//11:30 pm=h:i a', 1),
('monday_first_day',			'settings_datetime', 4,			'1', 'Weeks in calendar start with ...', 'select//{{ @LN__Weekday, strtotime(''2016/02/01'') | format }}=1//{{ @LN__Weekday, strtotime(''2016/05/01'') | format }}=0', 1),
('language_forced',				'settings_language', 0, 		'FALSE', 'Disable custom language selection:@SMALL@Default is <b>no</b>', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('language_available',			'settings_language_file', 0, 	'{\"en_GB\":\"English\"}', 'List all languages that are available to common members.', '', 0),
('language_default',			'settings_language_file', 0, 	'en_GB', NULL, '', 0),
('layout_forced',				'settings_layout', 1, 			'FALSE', 'Disable custom layout selection:@SMALL@Default is <b>no</b>', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('layout_available',			'settings_layout_file', 0, 		'{\"default\":\"eFiction 5 default\"}', NULL, '', 0),
('layout_default',				'settings_layout_file', 0, 		'default', NULL, '', 0),
('optional_modules',			'', 0, 							'{$fw['installerCFG.modulesDB']}', NULL, '', 0),
('sidebar_modules',				'', 0,							'quickpanel,tags,calendar', NULL, '', 1),
('page_title',					'settings_general', 1, 			"{$fw['installerCFG.data.sitename']}", 'Website title', 'text//', 1),
('page_mail',					'settings_general', 2, 			"{$fw['installerCFG.data.siteemail']}", 'Webmaster e-mail address', 'text//', 1),
('page_slogan',					'settings_general', 3, 			"{$fw['installerCFG.data.slogan']}", 'Site slogan', 'text//', 1),
('page_title_add',				'settings_general', 4, 			'path', 'Show page path or slogan in title', 'select//__path=path//__slogan=slogan', 1),
('page_title_reverse',			'settings_general', 5, 			'FALSE', 'Reverse sort order of page title elements.@SMALL@(Default is <b>no</b>)', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('page_title_separator',		'settings_general', 6, 			' | ', 'Separator for page title elements', 'text//small', 1),
('adjacent_paginations', 		'settings_general',	7,			'2', 'Contiguous page links to display@SMALL@"1" to display: 1 ... 4 [5] 6 ... 9<br>\n"2" to display: 1 ... 3 4 [5] 6 7 ... 9<br>"0" to display all links', 'text//numeric', 1),
('shoutbox_entries',			'settings_general', 8,			'5', 'Number of shoutbox items to display', 'text//numeric', 1),
('shoutbox_guest',				'settings_general', 9,			'FALSE', 'Allow guest posts in shoutbox', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_comment_news',			'settings_general', 10,			"{$fw['installerCFG.data.newscomments']}", 'Allow news comments', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_guest_comment_news',	'settings_general', 11,			'FALSE', 'Allow guest news comments', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_registration', 			'settings_registration', 0,		'FALSE', 'Allow registration?', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_require_email',			'settings_registration', 1, 	'TRUE', 'User must activate their account via eMail link.', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_require_mod',				'settings_registration', 2, 	'TRUE', 'User registrations require moderation.', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_min_username',			'settings_registration', 3, 	'0', 'Minimum characters for usernames', 'text//numeric', 1),
('reg_min_password',			'settings_registration', 4, 	'6', 'Minimum characters for passwords', 'text//numeric', 1),
('reg_password_complexity',		'settings_registration', 5, 	'2', 'Password complexity:@SMALL@see wiki', 'select//__none=1//__light=2//__medium=3//__heavy=4', 1),
('reg_use_captcha',				'settings_registration', 6,		'1', 'Select CAPTCHA to be used@SMALL@Configure under <a href=''{{@BASE}}/adminCP/settings/security''>Settings - Security</a>', '', 1),
('reg_sfs_usage',				'settings_registration_sfs', 1, 'TRUE', 'Use the "Stop Forumspam" Service.@SMALL@<a href="http://www.stopforumspam.com/faq" target="_blank">FAQ @ http://www.stopforumspam.com</a>', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_ip',			'settings_registration_sfs', 2, 'TRUE', 'Check IP', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_mail',			'settings_registration_sfs', 3, 'TRUE', 'Check mail address', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_username',		'settings_registration_sfs', 4, 'FALSE', 'Check username', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_advice',		'settings_registration_sfs', 5, '', 'You may turn off username checking if you encounter false positives.<br>Turning off IP and mail check is not advised, however.', 'note', 1),
('reg_sfs_failsafe',			'settings_registration_sfs', 6, '0', 'How to behave if the SFS Service cannot be reached upon registration@SMALL@Default is to hold.', 'select//__AdminRegSFSReject=-1//__AdminRegSFSHold=0//__AdminRegSFSAllow=1', 1),
('reg_sfs_explain_api',			'settings_registration_sfs', 7, '', '__AdminRegExplainSFSApi', 'note', 1),
('reg_sfs_api_key',				'settings_registration_sfs', 8,	'', 'Your API key (optional)', 'text//small', 1),
('mail_notifications',			'settings_mail', 1,				"{$fw['installerCFG.data.alertson']}", 'Members can opt-in to receive mail notifications.', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('smtp_advice',					'settings_mail', 2,				'', 'Leave SMTP server fields empty to send through PHP and sendmail.@SMALL@<a href="http://efiction.org/wiki/Server#Working_settings_for_common_mail_providers" target="_blank">Documentation in the wiki. {ICON:external-link}</a>', 'note', 1),
('smtp_server',					'settings_mail', 3,				"{$fw['installerCFG.data.smtp_host']}", 'SMTP server@SMALL@See WIKI for GMail!', 'text//small', 1),
('smtp_scheme', 				'settings_mail', 4,				'tls', 'SMTP security scheme', 'select//(START)TLS=tls//SMTPS=ssl//none=', 1),
('smtp_port',					'settings_mail', 5,				'', 'Port number (if not using default)', 'text//numeric', 1),
('smtp_username',				'settings_mail', 6,				"{$fw['installerCFG.data.smtp_username']}", 'SMTP username', 'text//small', 1),
('smtp_password',				'settings_mail', 7,				"{$fw['installerCFG.data.smtp_password']}", 'SMTP password', 'text//password', 1),
('chapter_data_location', 		'settings_maintenance', 1,		'{$chapterLocation}', 'Where to store chapters (Database Server or local file storage)@SMALL@Local file is being handled by SQLite', 'select//Database=db//Local Storage=local', 2),
('debug',						'settings_maintenance', 2,		'5', 'Debug level', 'select//disabled=0//low=1//2=2//3=3//4=4//5=5', 1),
('maintenance',					'settings_maintenance', 3,		'TRUE', 'Archive closed for maintenance', 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('admin_list_elements', 		'', 0, '20', NULL, 'text//numeric', 1),
('iconset_default',				'', 0, '1', NULL, '', 0),
('version',						'', '0', '5.0.0', NULL, '', '0');
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