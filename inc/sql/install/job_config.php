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

$sql = <<<EOF
INSERT INTO `{$new}config` (`name`, `value`, `comment`, `admin_module`, `section_order`, `form_type`, `to_config_file`, `can_edit`) VALUES
('adjacent_paginations', '2', 'Contiguous page links to display@SMALL@"1" to display: 1 ... 4 [5] 6 ... 9<br>\n"2" to display: 1 ... 3 4 [5] 6 7 ... 9<br>"0" to display all links', 'settings_general', 6, 'text//numeric', 1, 1),
('admin_list_elements', '20', NULL, '', 0, 'text//numeric', 1, 1),
('allow_guest_comment_news', 'FALSE', NULL, '', 0, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('allow_guest_reviews', 'FALSE', 'Allow guests to write reviews', 'archive_general', 2, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('allow_registration', 'FALSE', 'Allow registration?', 'settings_registration', 0, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('author_self', 'TRUE', 'Every member can post stories@SMALL@If set to no, members must be added to group Authors to allow them to post stories', 'archive_general', 3, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2_enabled', 'TRUE', 'Screen access\n<a href="http://bad-behavior.ioerror.us/support/configuration/" target="_blank">Bad Behaviour manual</a>@SMALL@(default <b>"{{@LN__yes}}"</b>)', 'bad_behaviour', 1, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__display_stats', 'TRUE', 'Display Statistics@SMALL@(default <b>"{{@LN__yes}}"</b>) (this causes extra load, turn off to save power)', 'bad_behaviour', 2, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__logging', 'TRUE', 'Logging@SMALL@(default <b>"{{@LN__yes}}"</b>)', 'bad_behaviour', 3, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__strict', 'FALSE', 'Strict Mode@SMALL@(default <b>"{{@LN__no}}"</b>)', 'bad_behaviour', 4, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__verbose', 'FALSE', 'Verbose Logging@SMALL@(default <b>"{{@LN__no}}"</b>)', 'bad_behaviour_ext', 1, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__offsite_forms', 'FALSE', 'Allow Offsite Forms@SMALL@(default <b>"{{@LN__no}}"</b>)', 'bad_behaviour_ext', 2, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__eu_cookie', 'FALSE', 'EU Cookie@SMALL@(default <b>"{{@LN__no}}"</b>)', 'bad_behaviour_ext', 3, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__httpbl_key', '', 'http:BL API Key@SMALL@Screen requests through Project Honey Pot.\r\nLeave empty to disable.', 'bad_behaviour_ext', 4, 'text//small', 1, 1),
('bb2__httpbl_threat', '25', 'http:BL Threat Level@SMALL@(default <b>"25"</b>)', 'bad_behaviour_ext', 5, 'text//numeric', 1, 1),
('bb2__httpbl_maxage', '30', 'http:BL Maximum Age@SMALL@(default <b>"30"</b>)', 'bad_behaviour_ext', 6, 'text//numeric', 1, 1),
('bb2__reverse_proxy', 'FALSE', 'Reverse Proxy@SMALL@(default <b>"{{@LN__no}}"</b>)', 'bad_behaviour_rev', 1, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('bb2__reverse_proxy_header', 'X-Forwarded-For', 'Reverse Proxy Header@SMALL@(default “X-Forwarded-For”)\r\nOnly required when using reverse proxy!', 'bad_behaviour_rev', 2, 'text//small', 1, 1),
('bb2__reverse_proxy_addresses', '', 'Reverse Proxy Addresses@SMALL@(no default)\r\nOnly required when using reverse proxy!', 'bad_behaviour_rev', 3, 'text//', 1, 1),
('chapter_data_location', '{$chapterLocation}', 'Where to store chapters (Database Server or local file storage)@SMALL@Local file is being handled by SQLite', 'archive_general', 0, 'select//Database=db//Local Storage=local', 1, 2),
('date_format_long', 'd.m.Y H:i (T)', 'Default long date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'settings_datetime', 2, 'text//small', 1, 1),
('date_format_short', 'd.m.Y', 'Default short date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'settings_datetime', 1, 'text//small', 1, 1),
('debug', '5', 'Debug level', 'settings_server', 1, 'select//disabled=0//low=1//2=2//3=3//4=4//5=5', 1, 1),
('epub_domain', '', 'Used to calculate your epub UUID v5. Leave blank for default (Archive URL)', '', 0, '', 0, 1),
('epub_namespace', '', NULL, '', 0, '', 1, 0),
('iconset_default', '1', NULL, '', 0, '', 1, 0),
('language_available', '{\"en_GB\":\"English\"}', 'List all languages that are available to common members.', 'settings_language_file', 0, '', 1, 0),
('language_default', 'en_GB', NULL, 'settings_language_file', 0, '', 1, 0),
('language_forced', '0', 'Disable custom language selection:@SMALL@Default is <b>no</b>', 'settings_language', 0, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('layout_available', '{\"default\":\"eFiction 5 default\"}', NULL, 'settings_layout_file', 0, '', 1, 0),
('layout_default', 'default', NULL, 'settings_layout_file', 0, '', 1, 0),
('layout_forced', '0', 'Disable custom layout selection:@SMALL@Default is <b>no</b>', 'settings_layout', 1, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('monday_first_day', '1', 'Weeks in calendar start with ...', 'settings_datetime', 3, 'select//{{ @LN__Weekday, strtotime(''2016/02/01'') | format }}=1//{{ @LN__Weekday, strtotime(''2016/05/01'') | format }}=0', 1, 1),
('optional_modules', '{$fw['installerCFG.modulesDB']}', NULL, '', 0, '', 0, 0),
('page_default', 'about', NULL, '', 0, '', 1, 1),
('page_mail', "{$fw['installerCFG.data.siteemail']}", 'Webmaster e-mail address', 'settings_general', 2, 'text//', 1, 1),
('page_slogan', "{$fw['installerCFG.data.slogan']}", 'Site slogan', 'settings_general', 3, 'text//', 1, 1),
('page_title', "{$fw['installerCFG.data.sitename']}", 'Website title', 'settings_general', 1, 'text//', 1, 1),
('page_title_add', 'path', 'Show page path or slogan in title', 'settings_general', 4, 'select//__path=path//__slogan=slogan', 1, 1),
('page_title_reverse', 'FALSE', 'Reverse sort order of page title elements.@SMALL@(Default is <b>no</b>)', 'settings_general', 5, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('page_title_separator', ' | ', 'Separator for page title elements', 'settings_general', 4, 'text//small', 1, 1),
('reg_min_password', '6', 'Minimum characters for passwords', 'settings_registration', 3, '', 0, 1),
('reg_min_username', '0', 'Minimum characters for usernames', 'settings_registration', 2, 'text//numeric', 0, 1),
('reg_password_complexity', '1', 'Password complexity:@SMALL@none - anything goes (not advised)<br>light - cannot be same as username<br>medium - requires one number, capital or special character<br>heavy - requires at least 2 non-letter characters', 'settings_registration', 4, 'select//__none=0//__light=1//__medium=2//__heavy=3', 0, 1),
('reg_require_email', 'TRUE', 'User must activate their account via eMail link.', 'settings_registration', 1, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 0, 1),
('reg_sfs_api_key', '', 'Your API key (optional)', 'settings_registration_sfs', 8, 'text//small', 0, 1),
('reg_sfs_check_advice', '', 'You may turn off username checking if you encounter false positives.<br>Turning off IP and mail check is not advised, however.', 'settings_registration_sfs', 5, 'note', 0, 1),
('reg_sfs_check_ip', 'TRUE', 'Check IP', 'settings_registration_sfs', 2, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 0, 1),
('reg_sfs_check_mail', 'TRUE', 'Check mail address', 'settings_registration_sfs', 3, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 0, 1),
('reg_sfs_check_username', 'FALSE', 'Check username', 'settings_registration_sfs', 4, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 0, 1),
('reg_sfs_explain_api', '', '__AdminRegExplainSFSApi', 'settings_registration_sfs', 7, 'note', 0, 1),
('reg_sfs_failsafe', '0', 'How to behave if the SFS Service cannot be reached upon registration@SMALL@Default is to hold.', 'settings_registration_sfs', 6, 'select//__AdminRegSFSReject=-1//__AdminRegSFSHold=0//__AdminRegSFSAllow=1', 0, 1),
('reg_sfs_usage', 'TRUE', 'Use the "Stop Forumspam" Service.@SMALL@<a href="http://www.stopforumspam.com/faq" target="_blank">FAQ @ http://www.stopforumspam.com</a>', 'settings_registration_sfs', 1, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 0, 1),
('reg_use_captcha', '0', 'Select CAPTCHA to be used@SMALL@Configure under <a href=''{{@BASE}}/adminCP/settings/security''>Settings - Security</a>', 'settings_registration', 5, '', 0, 1),
('shoutbox_entries', '5', 'Number of shoutbox items to display', 'settings_general', 7, 'text//numeric', 1, 1),
('shoutbox_guest', 'TRUE', 'Allow guest posts in shoutbox', 'settings_general', 8, 'select//{{@LN__yes}}=TRUE//{{@LN__no}}=FALSE', 1, 1),
('sidebar_modules', 'quickpanel,tags,calendar', NULL, '', 0, '', 1, 1),
('stories_per_page', "{$fw['installerCFG.data.itemsperpage']}", 'Stories per page in the Archive', 'archive_general', 1, 'text//numeric', 1, 1),
('story_intro_items', '5', 'Stories to show on the archive entry page.', 'archive_intro', 1, 'text//numeric', 1, 1),
('story_intro_order', 'modified', 'Order in which stories appear on the archive entry page.', 'archive_intro', 2, 'select//__modified=modified//__published=published', 1, 1),
('tagcloud_basesize', '70', 'Base size in percent relative to normal font size.', 'archive_tags_cloud', 1, 'text//numeric', 1, 1),
('tagcloud_elements', '20', 'Maximum number of elements in the tag cloud@SMALL@Elements are ordered by count.', 'archive_tags_cloud', 2, 'text//numeric', 1, 1),
('tagcloud_minimum_elements', '10', 'Minimum amount of elements required to show tag cloud@SMALL@0 = always show', 'archive_tags_cloud', 3, 'text//numeric', 1, 1),
('tagcloud_spread', '4', 'Maximum size spread:@SMALL@spread*100 is the maximum percentage for the most used tag.<br>2.5 would convert to 250%.<br>(Realistic values are somewhere between 3 and 5)', 'archive_tags_cloud', 4, 'text//numeric', 1, 1),
('time_format', 'H:i', 'Default time format.', 'settings_datetime', 4, 'select//23:30=H:i//11:30 pm=h:i a', 1, 1),
('version', '5.0.0', NULL, '', '0', '', '0', NULL);
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