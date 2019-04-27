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
	$chapterLocation =   ( $fw['installerCFG.chapters']=="filebase" ) ? "local" : "db";

	
	// Upon fresh installation, deliver sane and safe default variables
	if ( empty($fw['installerCFG.data.itemsperpage']) )
	{
		$fw['installerCFG.data.itemsperpage']		= 10;
		$fw['installerCFG.data.recentdays']			= 14;
		$fw['installerCFG.data.defaultsort']		= 'date';
		$fw['installerCFG.data.displayindex'] 		= 'TRUE';
		$fw['installerCFG.data.author_self']		= 'FALSE';		
		$fw['installerCFG.data.story_validation']	= 'TRUE';
		$fw['installerCFG.data.minwords']			= 100;
		$fw['installerCFG.data.maxwords']			= 0;
		$fw['installerCFG.data.tinyMCE']			= 'TRUE';
		$fw['installerCFG.data.coauthallowed']		= 'TRUE';
		$fw['installerCFG.data.allowseries']		= 'FALSE';
		$fw['installerCFG.data.allowcollections']	= 'FALSE';
		$fw['installerCFG.data.roundrobins']		= 'FALSE';
		$fw['installerCFG.data.imageupload']		= 'FALSE';
		$fw['installerCFG.data.imageheight']		= 200;
		$fw['installerCFG.data.imagewidth']			= 200;
		$fw['installerCFG.data.reviewsallowed']		= 'TRUE';
		$fw['installerCFG.data.anonreviews']		= 'FALSE';
		$fw['installerCFG.data.revdelete']			= 0;
		$fw['installerCFG.data.rateonly']			= 'FALSE';
		$fw['installerCFG.data.agestatement']		= 'FALSE';
		$fw['installerCFG.data.dateformat']			= 'd.m.Y';
		$fw['installerCFG.data.timeformat']			= 'H:i';
		$fw['installerCFG.data.sitename']			= 'New archive';
		$fw['installerCFG.data.siteemail']			= $fw['installerCFG.admin.mail'];
		$fw['installerCFG.data.slogan']				= 'powered by eFiction 5';
		$fw['installerCFG.data.linkrange']			= 2;
		$fw['installerCFG.data.newscomments']		= 'TRUE';
		$fw['installerCFG.data.alertson']			= 'FALSE';
		$fw['installerCFG.data.smtp_host'] 			= '';
		$fw['installerCFG.data.smtp_username']		= '';
		$fw['installerCFG.data.smtp_password']		= '';
		$fw['installerCFG.data.debug']				= 0;
		$fw['installerCFG.data.logging']			= 'TRUE';
	}
/*
Remaining config variables:
  
  `ratings` tinyint(1) NOT NULL DEFAULT '0', 		Use rating(ranking)
  `disablepopups` tinyint(1) NOT NULL DEFAULT '0', 	warning popups only once
  `words` text, 									Bad word filter
  `favorites` tinyint(1) NOT NULL DEFAULT '0',		Use of favourites
  `extendcats` tinyint(1) NOT NULL DEFAULT '0', 	Show category path (?)

Obsolete config variables:

  `linkstyle` tinyint(1) NOT NULL DEFAULT '0', 		-> Paginations (TPL)
  `multiplecats` tinyint(1) NOT NULL DEFAULT '0', 	Category auto detect
  `displaycolumns` tinyint(1) NOT NULL DEFAULT '1', -> TPL
  `displayprofile` tinyint(1) NOT NULL DEFAULT '0', Show profile on user page - different way now
  `allowed_tags` varchar(200) NOT NULL DEFAULT '<b><i><u><center><hr><p><br /><br><blockquote><ol><ul><li><img><strong><em>',
				editor settings happen in editor.js file
  `captcha` tinyint(1) NOT NULL DEFAULT '0',		Defaults to '1' now for security reasons

*/
$sql = <<<EOF
INSERT INTO `{$fw->dbNew}config` (`name`, `admin_module`, `section_order`, `value`, `form_type`, `can_edit`) VALUES
('stories_per_page',			'archive_general', 1,			"{$fw['installerCFG.data.itemsperpage']}", 		'text//numeric', 1),
('stories_recent',				'archive_general', 2,			"{$fw['installerCFG.data.recentdays']}", 		'text//numeric', 1),
('stories_default_order',		'archive_general', 3,			"{$fw['installerCFG.data.defaultsort']}", 		'select//__sort_date=date//__sort_name=title', 1),
('story_toc_default',			'archive_general', 3,			"{$fw['installerCFG.data.displayindex']}", 		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('epub_domain',					'archive_general', 9,			'', 											'text//small', 1),
('story_intro_items',			'archive_intro', 1,				'5', 											'text//numeric', 1),
('story_intro_order',			'archive_intro', 2,				'modified', 									'select//__modified=modified//__published=published', 1),
('author_overview_columns', 	'archive_authors', 1,			'5',											'text//numeric', 1),
('author_letter_columns', 		'archive_authors', 2,			'3',											'text//numeric', 1),
('author_self', 				'archive_submit', 1,			"{$fw['installerCFG.data.author_self']}", 		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('story_validation', 			'archive_submit', 2,			"{$fw['installerCFG.data.story_validation']}",	'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('stories_min_words',			'archive_submit', 3,			"{$fw['installerCFG.data.minwords']}", 			'text//numeric', 1),
('stories_max_words',			'archive_submit', 4,			"{$fw['installerCFG.data.maxwords']}",			'text//numeric', 1),
('advanced_editor', 			'archive_submit', 5,			"{$fw['installerCFG.data.tinyMCE']}",			'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_co_author', 			'archive_submit', 6,			"{$fw['installerCFG.data.coauthallowed']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('stories_min_tags',			'archive_submit', 7,			'0', 											'text//numeric', 1),
('allow_collections', 			'archive_submit', 8,			"{$fw['installerCFG.data.allowseries']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_series', 				'archive_submit', 9,			"{$fw['installerCFG.data.allowseries']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_roundrobin', 			'archive_submit', 10,			"{$fw['installerCFG.data.roundrobins']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('images_allowed', 				'archive_images', 1,			"{$fw['installerCFG.data.imageupload']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('images_height',				'archive_images', 2,			"{$fw['installerCFG.data.imageheight']}",		'text//numeric', 1),
('images_width',				'archive_images', 3,			"{$fw['installerCFG.data.imagewidth']}",		'text//numeric', 1),
('allow_reviews', 				'archive_reviews', 1,			"{$fw['installerCFG.data.reviewsallowed']}",	'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_guest_reviews', 		'archive_reviews', 2,			"{$fw['installerCFG.data.anonreviews']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_review_delete', 		'archive_reviews', 3,			"{$fw['installerCFG.data.revdelete']}",			'select//__all=2//__anonymous=1//{{@LN__no}}=0', 1),
('allow_rateonly', 				'archive_reviews', 4,			"{$fw['installerCFG.data.rateonly']}",			'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('tagcloud_basesize',			'archive_tags_cloud', 1,		'70',											'text//numeric', 1),
('tagcloud_elements',			'archive_tags_cloud', 2,		'20',											'text//numeric', 1),
('tagcloud_minimum_elements',	'archive_tags_cloud', 3,		'10',											'text//numeric', 1),
('tagcloud_spread',				'archive_tags_cloud', 4,		'4',											'text//numeric', 1),
('bb2_enabled', 				'bad_behaviour', 1,				'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__display_stats', 			'bad_behaviour', 2,				'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__logging', 				'bad_behaviour', 3,				'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__strict', 				'bad_behaviour', 4,				'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__verbose', 				'bad_behaviour_ext', 1,			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__offsite_forms', 			'bad_behaviour_ext', 2,			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__eu_cookie', 				'bad_behaviour_ext', 3,			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__httpbl_key', 			'bad_behaviour_ext', 4,			'',												'text//small', 1),
('bb2__httpbl_threat', 			'bad_behaviour_ext', 5,			'25',											'text//numeric', 1),
('bb2__httpbl_maxage', 			'bad_behaviour_ext', 6,			'30', 											'text//numeric', 1),
('bb2__reverse_proxy', 			'bad_behaviour_rev', 1,			'FALSE', 										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('bb2__reverse_proxy_header', 	'bad_behaviour_rev', 2,			'X-Forwarded-For', 								'text//small', 1),
('bb2__reverse_proxy_addresses','bad_behaviour_rev', 3,			'',												'text//', 1),
('agestatement',				'members_general',	 1,			"{$fw['installerCFG.data.agestatement']}", 		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('datetime_preset_explain',		'settings_datetime', 1,			'', 											'note', 1),
('date_preset',					'settings_datetime', 2,			'd.m.Y',										'select//24.12.1997 [d.m.Y]=d.m.Y//1997/24/12 [Y/d/m]=Y/d/m', 1),
('time_preset',					'settings_datetime', 3,			"H:i", 											'select//23:30 [H:i]=H:i//11:30 pm [h:i a]=h:i a', 1),
('monday_first_day',			'settings_datetime', 4,			'1',											'select//{{ @LN__Weekday, strtotime(''2016/02/01'') | format }}=1//{{ @LN__Weekday, strtotime(''2016/05/01'') | format }}=0', 1),
('datetime_custom_explain',		'settings_datetime', 5,			'', 											'note', 1),
('date_format',					'settings_datetime', 6,			"{$fw['installerCFG.data.dateformat']}", 		'text//small', 1),
('time_format',					'settings_datetime', 7,			"{$fw['installerCFG.data.timeformat']}", 		'text//small', 1),
('datetime_format',				'settings_datetime', 8,			"{$fw['installerCFG.data.dateformat']} {$fw['installerCFG.data.timeformat']}", 
																												'text//small', 1),
('language_forced',				'settings_language', 1, 		'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('language_available',			'settings_language_file', 0, 	'{\"en\":\"English\"}', 						'', 0),
('language_default',			'settings_language_file', 0, 	'en',											'', 0),
('layout_forced',				'settings_layout', 1, 			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('layout_available',			'settings_layout_file', 0, 		'{\"default\":\"eFiction 5 default\"}', 		'', 0),
('layout_default',				'settings_layout_file', 0, 		'default', 										'', 0),
('page_title',					'settings_general', 1, 			"{$fw['installerCFG.data.sitename']}",			'text//', 1),
('page_mail',					'settings_general', 2, 			"{$fw['installerCFG.data.siteemail']}",			'text//', 1),
('page_slogan',					'settings_general', 3, 			"{$fw['installerCFG.data.slogan']}",			'text//', 1),
('page_title_add',				'settings_general', 4, 			'path',											'select//__path=path//__slogan=slogan', 1),
('page_title_reverse',			'settings_general', 5, 			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('page_title_separator',		'settings_general', 6, 			' | ',											'text//small', 1),
('adjacent_paginations', 		'settings_general',	7,			"{$fw['installerCFG.data.linkrange']}",			'text//numeric', 1),
('shoutbox_entries',			'settings_general', 8,			'5',											'text//numeric', 1),
('shoutbox_guest',				'settings_general', 9,			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_comment_news',			'settings_general', 10,			"{$fw['installerCFG.data.newscomments']}",		'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_guest_comment_news',	'settings_general', 11,			'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('allow_registration', 			'settings_registration', 0,		'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_require_email',			'settings_registration', 1, 	'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_require_mod',				'settings_registration', 2, 	'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_min_username',			'settings_registration', 3, 	'0',											'text//numeric', 1),
('reg_min_password',			'settings_registration', 4, 	'6',											'text//numeric', 1),
('reg_password_complexity',		'settings_registration', 5, 	'2',											'select//__none=1//__light=2//__medium=3//__heavy=4', 1),
('reg_use_captcha',				'settings_registration', 6,		'1',											'', 1),
('reg_sfs_usage',				'settings_registration_sfs', 1, 'TRUE', 										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_ip',			'settings_registration_sfs', 2, 'TRUE', 										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_mail',			'settings_registration_sfs', 3, 'TRUE', 										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_username',		'settings_registration_sfs', 4, 'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('reg_sfs_check_advice',		'settings_registration_sfs', 5, '', 											'note', 1),
('reg_sfs_failsafe',			'settings_registration_sfs', 6, '0',											'select//__AdminRegSFSReject=-1//__AdminRegSFSHold=0//__AdminRegSFSAllow=1', 1),
('reg_sfs_explain_api',			'settings_registration_sfs', 7, '', 											'note', 1),
('reg_sfs_api_key',				'settings_registration_sfs', 8,	'', 											'text//small', 1),
('server_report', 				'settings_report',			 1, '0', 											'select//{{@LN__monthly}}=2//{{@LN__weekly}}=1//{{@LN__disabled}}=0', 1),
('server_report_anon', 			'settings_report',			 2, 'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('server_report_publish', 		'settings_report',			 3, 'FALSE',										'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('mail_notifications',			'settings_mail', 1,				"{$fw['installerCFG.data.alertson']}", 			'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('smtp_advice',					'settings_mail', 2,				'', 											'note', 1),
('smtp_server',					'settings_mail', 3,				"{$fw['installerCFG.data.smtp_host']}", 		'text//small', 1),
('smtp_scheme', 				'settings_mail', 4,				'tls', 											'select//(START)TLS=tls//SMTPS=ssl//none=', 1),
('smtp_port',					'settings_mail', 5,				'', 											'text//numeric', 1),
('smtp_username',				'settings_mail', 6,				"{$fw['installerCFG.data.smtp_username']}", 	'text//small', 1),
('smtp_password',				'settings_mail', 7,				"{$fw['installerCFG.data.smtp_password']}",		'text//password', 1),
('chapter_data_location', 		'settings_maintenance', 1,		'{$chapterLocation}',							 'select//Database=db//Local Storage=local', 2),
('debug',						'settings_maintenance', 2,		"{$fw['installerCFG.data.debug']}",				'select//disabled=0//low=1//2=2//3=3//4=4//5=5', 1),
('maintenance',					'settings_maintenance', 3,		'TRUE',											'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('logging',						'settings_maintenance', 4,		"{$fw['installerCFG.data.logging']}", 			'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1),
('admin_list_elements', 		'', 0, '20', 								'text//numeric', 1),
('optional_modules',			'', 0, '{$fw['installerCFG.modulesDB']}', 	'', 0),
('sidebar_modules',				'', 0,	'quickpanel,tags,calendar', 		'', 1),
('iconset_default',				'', 0, '1',									'', 0),
('version',						'', '0', '5.0.0',							'', '0');
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