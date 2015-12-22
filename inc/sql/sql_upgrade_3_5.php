<?php
/*
SQL convert from eFiction 3.5.x to 5.0.0
*/

$old = "{$fw['installerCFG.dbname']}`.`{$fw['installerCFG.pre_old']}fanfiction_";
$new = "{$fw['installerCFG.db_new']}`.`{$fw['installerCFG.pre_new']}";
$characterset = $fw['installerCFG.charset'];
$review_split = "<br><br><i>";

$init = array
(
	"info"	=>	"Create empty tables",
	"steps" => array (
								array ( "bad_behavior", 0, "Bad Behavior 2" ),
								array ( "chapters", 0, "Chapters" ),
								array ( "config", 0, "Page config" ),
								array ( "convert", 0, "eFiction conversion table" ),
								array ( "layout", 0, "Layout" ),
								array ( "iconsets", 0, "Layout: Iconset" ),
								array ( "menu", 0, "Page menu" ),
								array ( "menu_adminpanel", 0, "Admin panel menu" ),
								array ( "menu_userpanel", 0, "User panel menu" ),
								array ( "textblocks", 0, "Textblocks (former: messages)" ),
								array ( "tag_groups", 0, "Tag groups" ),
								array ( "tags", 0, "Tags" ),
								array ( "categories", 0, "Categories" ),
								array ( "stories_authors", 0, "Story relation table: authors" ),
								array ( "stories_categories", 0, "Story relation table: categories" ),
								array ( "stories_tags", 0, "Story relation table: tags" ),
								array ( "stories", 0, "Stories" ),
								array ( "ratings", 0, "Ratings" ),
								array ( "series", 0, "Series" ),
								array ( "series_stories", 0, "Stories in Series" ),
								array ( "users", 0, "Users" ),
								array ( "user_fields", 0, "User fields" ),
								array ( "user_info", 0, "User info" ),
								array ( "user_favourites", 0, "User favourites" ),
								array ( "user_friends", 0, "User friends" ),
								array ( "messaging", 0, "Messaging (new feature)" ),
								array ( "news", 0, "News" ),
								array ( "shoutbox", 0, "Shoutbox" ),
								array ( "sessions", 0, "Session" ),
								array ( "feedback", 0, "Feedback (former: reviews & comments)" ),
								array ( "poll", 0, "Polls" ),
								array ( "poll_votes", 0, "Poll votes" ),
								array ( "tracker", 0, "Story tracker (new feature)" ),
								array ( "stories_blockcache", 0, "Story cache" ),
								array ( "series_blockcache", 0, "Series cache" ),
								array ( "stats_cache", 0, "Page stats cache" ),
									),
);
$_SESSION['skipped'] = array();

/* --------------------------------------------------------------------------------------------
																																										* CONVERT *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['convert'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}convert` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `job` tinytext NOT NULL,
  `joborder` tinyint(4) NOT NULL,
  `step` tinyint(4) NOT NULL,
  `job_description` tinytext NOT NULL,
  `step_description` tinytext NOT NULL,
  `code` mediumtext NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `items` smallint(5) unsigned NOT NULL DEFAULT '0',
  `error` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} ;
EOF;

/* --------------------------------------------------------------------------------------------
																																										 * CONFIG *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['config'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}config` (
  `name` varchar(32) NOT NULL,
  `value` varchar(256) NOT NULL,
  `comment` tinytext,
  `admin_module` varchar(64) NOT NULL,
  `section_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `form_type` text NOT NULL,
  `to_config_file` tinyint(1) NOT NULL DEFAULT '1',
  `can_edit` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
EOF;

$sql['data']['config'] = <<<EOF
INSERT INTO `{$new}config` (`name`, `value`, `comment`, `admin_module`, `section_order`, `form_type`, `to_config_file`, `can_edit`) VALUES
('adjacent_paginations', '2', 'Contiguous page links to display@SMALL@"1" to display: 1 ... 4 [5] 6 ... 9<br>\n"2" to display: 1 ... 3 4 [5] 6 7 ... 9<br>"0" to display all links', 'general_settings_site', 6, 'text//numeric', 1, 1),
('admin_list_elements', '20', NULL, '', 0, 'text//numeric', 1, 1),
('allow_guest_comment_news', 'FALSE', NULL, '', 0, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('allow_guest_reviews', 'FALSE', 'Allow guests to write reviews', 'archive_general', 2, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('allow_registration', 'FALSE', 'Allow registration?', 'settings_registration', 0, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('author_self', 'TRUE', 'Every member can post stories@SMALL@If set to no, members must be added to group Authors to allow them to post stories', 'archive_general', 3, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('chapter_data_location', 'local', 'Where to store chapters (Database Server or local file storage)@SMALL@Local file is being handled by SQLite', 'archive_general', 0, 'select//Database=db//Local Storage=local', 1, 2),
('date_format_long', 'd.m.Y H:i (T)', 'Default long date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'settings_datetime', 2, 'text//small', 1, 1),
('date_format_short', 'd.m.Y', 'Default short date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'settings_datetime', 1, 'text//small', 1, 1),
('epub_domain', '', 'Used to calculate your epub UUID v5. Leave blank for default (Archive URL)', '', 0, '', 0, 1),
('epub_domain_uuid', '', NULL, '', 0, '', 0, 0),
('language_available', 'en_GB', 'List all languages that are available to common members.', '', 0, '', 0, 0),
('language_default', 'en_GB', NULL, '', 0, '', 1, 0),
('language_forced', '0', 'Disable custom language selection:@SMALL@Default is <b>no</b>', 'settings_language', 0, 'select//__yes=1//__no=0', 1, 1),
('layout_default', '47bugs', NULL, '', 0, '', 1, 0),
('layout_forced', '0', 'Disable custom layout selection:@SMALL@Default is <b>no</b>', 'settings_layout', 1, 'select//__yes=1//__no=0', 1, 1),
('monday_first_day', '1', 'Weeks in calendar start with ...', 'settings_datetime', 3, 'select//__Monday=1//__Sunday=0', 1, 1),
('page_default', 'about', NULL, '', 0, '', 1, 1),
('page_mail', "{$fw['installCFG.data.siteemail']}", 'Webmaster e-mail address', 'general_settings_site', 2, 'text//', 1, 1),
('page_slogan', "{$fw['installCFG.data.slogan']}", 'Site slogan', 'general_settings_site', 3, 'text//', 1, 1),
('page_title', "{$fw['installCFG.data.sitename']}", 'Website title', 'general_settings_site', 1, 'text//', 1, 1),
('page_title_reverse', 'FALSE', 'Reverse sort order of page title elements.@SMALL@(Default is <b>no</b>)', 'general_settings_site', 5, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('page_title_separator', ' | ', 'Separator for page title elements', 'general_settings_site', 4, 'text//small', 1, 1),
('reg_min_password', '6', 'Minimum characters for passwords', 'settings_registration', 3, '', 0, 1),
('reg_min_username', '0', 'Minimum characters for usernames', 'settings_registration', 2, 'text//numeric', 0, 1),
('reg_password_complexity', '1', 'Password complexity:@SMALL@none - anything goes (not advised)<br>light - cannot be same as username<br>medium - requires one number, capital or special character<br>heavy - requires at least 2 non-letter characters', 'settings_registration', 4, 'select//__none=0//__light=1//__medium=2//__heavy=3', 0, 1),
('reg_require_email', 'TRUE', 'User must activate their account via eMail link.', 'settings_registration', 1, 'select//__yes=TRUE//__no=FALSE', 0, 1),
('reg_sfs_api_key', '', 'You API key (optional)', 'settings_registration_sfs', 8, 'text//small', 0, 1),
('reg_sfs_check_advice', '', 'You may turn off username checking if you encounter false positives.<br>Turning off IP and mail check is not advised, however.', 'settings_registration_sfs', 5, 'note', 0, 1),
('reg_sfs_check_ip', 'TRUE', 'Check IP', 'settings_registration_sfs', 2, 'select//__yes=TRUE//__no=FALSE', 0, 1),
('reg_sfs_check_mail', 'TRUE', 'Check mail address', 'settings_registration_sfs', 3, 'select//__yes=TRUE//__no=FALSE', 0, 1),
('reg_sfs_check_username', 'FALSE', 'Check username', 'settings_registration_sfs', 4, 'select//__yes=TRUE//__no=FALSE', 0, 1),
('reg_sfs_explain_api', '', '__AdminRegExplainSFSApi', 'settings_registration_sfs', 7, 'note', 0, 1),
('reg_sfs_failsafe', '0', 'How to behave if the SFS Service cannot be reached upon registration@SMALL@Default is to hold.', 'settings_registration_sfs', 6, 'select//__AdminRegSFSReject=-1//__AdminRegSFSHold=0//__AdminRegSFSAllow=1', 0, 1),
('reg_sfs_usage', 'TRUE', 'Use the "Stop Forumspam" Service.@SMALL@<a href="http://www.stopforumspam.com/faq" target="_blank">FAQ @ http://www.stopforumspam.com</a>', 'settings_registration_sfs', 1, 'select//__yes=TRUE//__no=FALSE', 0, 1),
('reg_use_captcha_level', '2', 'Level of CAPTCHA to be used@SMALL@"0" - disabled<br>"1" - light<br>"2" - medium"<br>"3" - heavy', 'settings_registration', 5, 'select//__diabled=0//__light=1//__medium=2//__heavy=3', 0, 1),
('show_debug', '5', 'Debug level', 'settings_server', 1, 'select//disabled=0//low=1//2=2//3=3//4=4//5=5', 1, 1),
('sidebar_modules', 'quickpanel,tags,calendar', NULL, '', 0, '', 1, 1),
('stories_per_page', '10', 'Stories per page in the Archive', 'archive_general', 1, 'text//numeric', 1, 1),
('story_intro_items', '5', 'Stories to show on the archive entry page.', 'archive_intro', 1, 'text//numeric', 1, 1),
('story_intro_order', 'modified', 'Order in which stories appear on the archive entry page.', 'archive_intro', 2, 'select//__modified=modified//__published=published', 1, 1),
('tagcloud_basesize', '70', 'Base size in percent relative to normal font size.', 'archive_tags_cloud', 1, 'text//numeric', 1, 1),
('tagcloud_elements', '20', 'Maximum number of elements in the tag cloud@SMALL@Elements are ordered by count.', 'archive_tags_cloud', 2, 'text//numeric', 1, 1),
('tagcloud_minimum_elements', '10', 'Minimum amount of elements required to show tag cloud@SMALL@0 = always show', 'archive_tags_cloud', 3, 'text//numeric', 1, 1),
('tagcloud_spread', '4', 'Maximum size spread:@SMALL@spread*100 is the maximum percentage for the most used tag.<br>2.5 would convert to 250%.<br>(Realistic values are somewhere between 3 and 5)', 'archive_tags_cloud', 4, 'text//numeric', 1, 1),
('time_format', 'H:i', 'Default time format.', 'settings_datetime', 4, 'select//23:30=H:i//11:30 pm=h:i a', 1, 1),
('version', '5.0.0', NULL, '', '0', '', '0', NULL);
EOF;

/* --------------------------------------------------------------------------------------------
																																										 * LAYOUT *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['layout'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}layout` (
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `layout` tinyint(3) unsigned NOT NULL,
  `setting` varchar(64) NOT NULL,
  `value` varchar(256) NOT NULL,
  PRIMARY KEY (`uid`,`layout`,`setting`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																										 * LAYOUT *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['bad_behavior'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}bad_behavior` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` text NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `request_method` text NOT NULL,
  `request_uri` text NOT NULL,
  `server_protocol` text NOT NULL,
  `http_headers` text NOT NULL,
  `user_agent` text NOT NULL,
  `request_entity` text NOT NULL,
  `key` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`(15)),
  KEY `user_agent` (`user_agent`(10))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																										* ICONSET *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['iconsets'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}iconsets` (
  `set_id` tinyint(4) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` text,
  PRIMARY KEY (`set_id`,`name`(30))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['iconsets'] = <<<EOF
INSERT INTO `{$new}iconsets` (`set_id`, `name`, `value`) VALUES
(1, '#author', 'eFiction.org'),
(1, '#directory', NULL),
(1, '#name', 'Font Awesome CSS Icons'),
(1, '#notes', 'requires ''@import url(//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css);'' in styles.css (See http://fortawesome.github.io/Font-Awesome/get-started/ )'),
(1, '#pattern', '<span class="fa @1@"></span>'),
(1, 'archive', 'fa-university'),
(1, 'bars', 'fa-bars'),
(1, 'blank', 'fa-square-o'),
(1, 'book', 'fa-book'),
(1, 'bookmark', 'fa-bookmark'),
(1, 'check', 'fa-check'),
(1, 'cloud', 'fa-cloud'),
(1, 'comment', 'fa-comment-o'),
(1, 'comments', 'fa-comments-o'),
(1, 'comment_dark', 'fa-comment'),
(1, 'document-new', 'fa-file-o'),
(1, 'edit', 'fa-pencil-square-o'),
(1, 'favourite', 'fa-heart'),
(1, 'folder', 'fa-folder-open'),
(1, 'heart', 'fa-heart'),
(1, 'home', 'fa-home'),
(1, 'inbox', 'fa-inbox'),
(1, 'invisible', 'fa-eye-slash'),
(1, 'key', 'fa-key'),
(1, 'keyboard', 'fa-keyboard-o'),
(1, 'language', 'fa-language'),
(1, 'layout', 'fa-eye'),
(1, 'mail', 'fa-envelope'),
(1, 'manual', 'fa-info'),
(1, 'member', 'fa-user'),
(1, 'members', 'fa-users'),
(1, 'minus', 'fa-minus-square'),
(1, 'modules', 'fa-cubes'),
(1, 'news', 'fa-rss'),
(1, 'plus', 'fa-plus-square'),
(1, 'register', 'fa-sign-in'),
(1, 'remove', 'fa-remove'),
(1, 'search', 'fa-search'),
(1, 'settings', 'fa-cogs'),
(1, 'sort', 'fa-sort'),
(1, 'sort-alpha-asc', 'fa-sort-alpha-asc'),
(1, 'sort-alpha-desc', 'fa-sort-alpha-desc'),
(1, 'sort-numeric-asc', 'fa-sort-numeric-asc'),
(1, 'sort-numeric-desc', 'fa-sort-numeric-desc'),
(1, 'sort-size-asc', 'fa-sort-amount-asc'),
(1, 'sort-size-desc', 'fa-sort-amount-desc'),
(1, 'star', 'fa-star'),
(1, 'tag', 'fa-tag'),
(1, 'tags', 'fa-tags'),
(1, 'text', 'fa-file-text-o'),
(1, 'time', 'fa-clock-o'),
(1, 'trash', 'fa-trash-o'),
(1, 'visible', 'fa-eye'),
(1, 'waiting', 'fa-spin fa-spinner'),
(1, 'wrench', 'fa-wrench');
EOF;

/* --------------------------------------------------------------------------------------------
																																											 * MENU *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['menu'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}menu` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` int(2) NOT NULL,
  `link` varchar(256) DEFAULT NULL,
  `meta` varchar(128) DEFAULT NULL,
  `child_of` int(2) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
EOF;

$sql['data']['menu'] = <<<EOF
INSERT INTO `{$new}menu` (`id`, `label`, `order`, `link`, `meta`, `child_of`, `active`) VALUES
(1, 'Home', 1, '', NULL, NULL, 1),
(2, 'Authors', 5, 'story,authors', NULL, 6, 1),
(3, 'Fandoms', 1, 'story,categories', NULL, 6, 1),
(4, 'FAQ', 4, '', NULL, 6, 1),
(5, 'Updates', 3, 'story,updates', NULL, 6, 1),
(6, 'Archive', 2, 'story', NULL, NULL, 1),
(7, 'Browse', 6, 'story,browse', NULL, 6, 1),
(8, 'Search', 6, 'story,search', NULL, 6, 1),
(9, 'Forum', 3, '', NULL, NULL, 1),
(10, 'Website', 4, NULL, NULL, NULL, 1),
(11, 'Challenges', 6, 'story,contests', NULL, 6, 1);
EOF;

/* --------------------------------------------------------------------------------------------
																																								 * ADMIN MENU *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['menu_adminpanel'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}menu_adminpanel` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `label` tinytext NOT NULL,
  `order` int(2) NOT NULL,
  `link` tinytext,
  `icon` varchar(64) DEFAULT '{ICON:blank}',
  `child_of` varchar(64) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `requires` tinyint(1) unsigned NOT NULL DEFAULT '2',
	`evaluate` tinytext,
  PRIMARY KEY (`id`),
  KEY `child_of` (`child_of`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
EOF;

$sql['data']['menu_adminpanel'] = <<<EOF
INSERT INTO `{$new}menu_adminpanel` (`label`, `order`, `link`, `icon`, `child_of`, `active`, `requires`, `evaluate`) VALUES
('__AdminMenuHome', 1, 'home', '{ICON:home}', NULL, 1, 1, NULL),
('__AdminMenuSettings', 2, 'settings', '{ICON:settings}', NULL, 1, 2, NULL),
('__AdminMenuMembers', 3, 'members', '{ICON:member}', NULL, 1, 2, NULL),
('__AdminMenuArchive', 4, 'archive', '{ICON:archive}', NULL, 1, 1, NULL),
('__AdminMenuStories', 5, 'stories', '{ICON:book}', NULL, 1, 1, NULL),
('__AdminMenuManual', 1, 'home,manual', '{ICON:manual}', 'home', 1, 1, NULL),
('__AdminMenuCustomPages', 2, 'home,custompages', '{ICON:text}', 'home', 1, 1, NULL),
('__AdminMenuNews', 3, 'home,news', '{ICON:news}', 'home', 1, 1, NULL),
('__AdminMenuModules', 4, 'home,modules', '{ICON:modules}', 'home', 1, 2, NULL),
('__AdminMenuShoutbox', 5, 'home,shoutbox', '{ICON:blank}', 'home', 1, 1, '\$shoutbox == 1;'),
('__AdminMenuServer', 1, 'settings,server', '{ICON:wrench}', 'settings', 1, 2, NULL),
('__AdminMenuRegistration', 2, 'settings,registration', '{ICON:register}', 'settings', 1, 2, NULL),
('__AdminMenuLayout', 3, 'settings,layout', '{ICON:layout}', 'settings', 1, 2, NULL),
('__AdminMenuThemes', 1, 'settings,layout,themes', '{ICON:blank}', 'settings,layout', 1, 2, NULL),
('__AdminMenuIcons', 2, 'settings,layout,icons', '{ICON:blank}', 'settings,layout', 1, 2, NULL),
('__AdminMenuLanguage', 4, 'settings,language', '{ICON:language}', 'settings', 1, 2, NULL),
('__AdminMenuSearch', 1, 'members,search', '{ICON:search}', 'members', 1, 2, NULL),
('__AdminMenuPending', 2, 'members,pending', '{ICON:waiting}', 'members', 1, 2, NULL),
('__AdminMenuGroups', 3, 'members,groups', '{ICON:members}', 'members', 1, 2, NULL),
('__AdminMenuFeatured', 1, 'archive,featured', '{ICON:blank}', 'archive', 1, 1, NULL),
('__AdminMenuTags', 2, 'archive,tags,tag', '{ICON:tag}', 'archive', 1, 1, NULL),
('__AdminMenuEdit', 1, 'archive,tags,tag', '{ICON:tag}', 'archive,tags', 1, 1, NULL),
('__AdminMenuTaggroups', 2, 'archive,tags,groups', '{ICON:tags}', 'archive,tags', 1, 2, NULL),
('__AdminMenuTagcloud', 3, 'archive,tags,cloud', '{ICON:cloud}', 'archive,tags', 1, 2, NULL),
('__AdminMenuGroupings', 3, 'archive,groupings', '{ICON:blank}', 'archive', 1, 1, NULL),
('__AdminMenuPending', 1, 'stories,pending', '{ICON:waiting}', 'stories', 1, 2, NULL),
('__AdminMenuEdit', 2, 'stories,edit', '{ICON:edit}', 'stories', 1, 1, NULL),
('__AdminMenuAdd', 3, 'stories,add', '{ICON:document-new}', 'stories', 1, 1, NULL);
EOF;
/* --------------------------------------------------------------------------------------------
																																									* USER MENU *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['menu_userpanel'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}menu_userpanel` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `label` tinytext NOT NULL,
  `order` int(2) NOT NULL,
  `link` tinytext,
  `icon` tinytext,
  `child_of` varchar(16) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `evaluate` tinytext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu` (`child_of`,`order`),
  KEY `child_of` (`child_of`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset} COMMENT='(eFI5): New table';
EOF;

$sql['data']['menu_userpanel'] = <<<EOF
INSERT INTO `{$new}menu_userpanel` (`id`, `label`, `order`, `link`, `icon`, `child_of`, `active`, `evaluate`) VALUES
(1, 'Profile', 1, 'profile', '{ICON:member}', NULL, 1, NULL),
(2, 'Message', 2, 'messaging', '{ICON:mail}', NULL, 1, NULL),
(3, 'Authoring', 3, 'author', '{ICON:keyboard}', NULL, 1, NULL),
(4, 'My Library', 4, 'library', '{ICON:book}', NULL, 1, NULL),
(5, 'Reviews', 4, 'reviews', '{ICON:comments}', NULL, 1, NULL),
(6, '__preferences', 6, 'preferences', '{ICON:settings}', NULL, 1, NULL),
(7, '%AUTHORS%', 1, 'author&amp;uid=%UID%', '{ICON:member}', 'story', 1, NULL),
(8, '%FINISHED%', 1, 'author,story,finished&amp;uid=%UID%', NULL, 'authoring', 1, NULL),
(9, '%UNFINISHED%', 2, 'author,story,unfinished&amp;uid=%UID%', NULL, 'authoring', 1, NULL),
(10, '%DRAFTS%', 3, 'author,story,drafts&amp;uid=%UID%', '{ICON:folder}', 'authoring', 1, NULL),
(11, '__storyAdd', 4, 'author,story,add&amp;uid=%UID%', '{ICON:text}', 'authoring', 1, NULL),
(12, '__Bookmarks%BMS%', 1, 'library,bm', '{ICON:bookmark}', '4', 1, NULL),
(13, '__Favourites%FAVS%', 2, 'library,fav', '{ICON:heart}', '4', 1, NULL),
(14, '__Recommendations%RECS%', 3, 'library,rec', '{ICON:star}', '4', 1, NULL),
(15, 'Inbox', 1, 'messaging/inbox', '{ICON:inbox}', 'messaging', 1, NULL),
(16, 'Write', 2, 'messaging/write', '{ICON:edit}', 'messaging', 1, NULL),
(17, '__aboutMe', 1, 'profile,about', '{ICON:text}', 'profile', 1, NULL),
(19, '__changePW', 3, 'profile,changepw', '{ICON:key}', 'profile', 1, NULL),
(20, '__Authors', 1, 'library,fav&amp;sub=AU', NULL, '13', 1, NULL),
(21, '__Stories', 2, 'library,fav&amp;sub=ST', NULL, '13', 1, NULL),
(22, 'Outbox', 3, 'messaging/outbox', '{ICON:bars}', 'messaging', 1, NULL),
(23, '__Curator', 2, 'author,curator', NULL, 'story', 1, NULL);
EOF;

/* --------------------------------------------------------------------------------------------
																																									* TEXTBLOCKS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Copy messages, new table 'textblocks'",
	"steps" => array (
								array ( "textblocks", 0, "Copy data" ),
								array ( "textblocks", 1, "Mark the old admin block area as block_only" ),
								array ( "textblocks", 2, "Add a registration page" ),
									),
);

$sql['init']['textblocks'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}textblocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `as_page` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `label` (`label`)
) ENGINE=InnoDB  DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['textblocks'] = <<<EOF
INSERT INTO `{$new}textblocks` ( `id`, `label`, `title`, `content`, `as_page` )
	SELECT message_id, message_name, message_title, message_text, 1
	FROM `{$old}messages`;
--SPLIT--
UPDATE `{$new}textblocks`T SET T.as_page=0 WHERE T.id IN(1,2,4,5,7,9);
--SPLIT--
INSERT INTO `{$new}textblocks` (`label`, `title`, `content`, `as_page`) VALUES
('registration', '__Registration', 'By registering, you consent to the following rules: No BS-ing!', 0);
EOF;

/* --------------------------------------------------------------------------------------------
																																								 * TAGS GROUPS*
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Tag groups and tags",
	"steps" => array (
								array ( "tag_groups", 0, "Import tag groups from class types" ),
								array ( "tag_groups", 1, "Add tag group 'character'" ),
								array ( "tags", 0, "Import tags from classes" ),
								array ( "tags", 1, "Import tags from characters" ),
									),
);

$sql['init']['tag_groups'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tag_groups` (
  `tgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `description` mediumtext,
  PRIMARY KEY (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table, eFiction 5';
EOF;

$sql['data']['tag_groups'] = <<<EOF
INSERT INTO `{$new}tag_groups` ( tgid, label, description )
	SELECT T.classtype_id, T.classtype_name, T.classtype_title
	FROM `{$old}classtypes` T;
--SPLIT--
INSERT INTO `{$new}tag_groups` ( label, description )
	VALUES ('characters', 'Character'); 
EOF;

/* --------------------------------------------------------------------------------------------
																																											 * TAGS *
requires: tag_groups
-------------------------------------------------------------------------------------------- */
$sql['init']['tags'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tags` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oldid` int(11) NOT NULL,
  `tgid` int(10) unsigned NOT NULL,
  `label` tinytext NOT NULL,
  `description` mediumtext NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`tid`), UNIQUE KEY `label` (`label`(64)), KEY `tgid` (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table';
EOF;

$sql['data']['tags'] = <<<EOF
INSERT INTO `{$new}tags` ( tid, tgid, label )
 SELECT C.class_id, C.class_type, C.class_name
   FROM  `{$old}classes` C;
 --SPLIT--
INSERT INTO `{$new}tags` ( oldid, tgid, label )
	SELECT C.charid, TG.tgid, C.charname
		FROM  `{$old}characters` C
		LEFT JOIN  `{$new}tag_groups` TG ON ( TG.label =  'characters' );
EOF;

/* --------------------------------------------------------------------------------------------
																																									* CATEGORIES *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story categories (e.g. fandoms, season)",
	"steps" => array (
								array ( "categories", 0, "Copy categories" ),
									),
);

$sql['init']['categories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `parent_cid` int(11) NOT NULL DEFAULT '-1',
  `category` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
  `locked` char(1) NOT NULL DEFAULT '0',
  `leveldown` tinyint(4) NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`), KEY `byparent` (`parent_cid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFI5): derived from _categories';
EOF;

$sql['data']['categories'] = <<<EOF
INSERT INTO `{$new}categories`
	( `cid`, `parent_cid`, `category`, `description`, `image`, `locked`, `leveldown`, `inorder`, `count` )
	SELECT C.catid, C.parentcatid, C.category, C.description, C.image, C.locked, C.leveldown, C.displayorder, C.numitems 
	FROM `{$old}categories`C;
EOF;

/* --------------------------------------------------------------------------------------------
																																						* STORIES AUTHORS *
requires: tags, categories
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story relation table",
	"steps" => array (
								array ( "stories_authors", 0, "Story <-> Author relations" ),
								array ( "stories_authors", 1, "Story <-> Co-Author relations" ),
								array ( "stories_authors", 2, "Cleanup" ),
									),
);

$sql['init']['stories_authors'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_authors` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `aid` int(10) unsigned NOT NULL,
  `ca` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`lid`), KEY `relation` (`sid`,`aid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for story-author relations';
EOF;

$sql['data']['stories_authors'] = <<<EOF
INSERT INTO `{$new}stories_authors` (`sid`, `aid`)
	SELECT S.sid,S.uid
		FROM `{$old}stories`S ORDER BY S.sid ASC;
--SPLIT--
INSERT INTO `{$new}stories_authors` (`sid`, `aid`, `ca`)
	SELECT Ca.sid,Ca.uid, 1 as 'ca'
		FROM `{$old}coauthors`Ca ORDER BY Ca.sid ASC;
--SPLIT--
DELETE S1 
FROM `{$new}stories_authors`S1 
INNER JOIN `{$new}stories_authors`S2 ON ( S1.sid=S2.sid AND S1.aid = S2.aid AND S1.lid < S2.lid )
EOF;

/* --------------------------------------------------------------------------------------------
																																					* STORIES CATEGORIES *
requires: tags, categories
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story relation table",
	"steps" => array (
								array ( "stories_categories", 1, "Story <-> category relations" ),
									),
);

$sql['init']['stories_categories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_categories` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lid`), UNIQUE KEY `relation` (`sid`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for story-category relations';
EOF;

$sql['data']['stories_categories'] = <<<EOF
INSERT INTO `{$new}stories_categories` ( `sid`,`cid` )
	SELECT S.sid,C.cid
		FROM `{$old}stories`S
		INNER JOIN `{$new}categories`C ON (FIND_IN_SET(C.cid,S.catid)>0);
EOF;

/* --------------------------------------------------------------------------------------------
																																							 * STORIES TAGS *
requires: tags, categories
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story relation table",
	"steps" => array (
								array ( "stories_tags", 1, "Story <-> Tags relations (from classes)" ),
								array ( "stories_tags", 2, "Story <-> Tags relations (from characters)" ),
								array ( "stories_tags", 3, "Recount tags" ),
									),
);

$sql['init']['stories_tags'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_tags` (
  `lid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lid`), UNIQUE KEY `relation` (`sid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for story-tag relations';
EOF;

$sql['data']['stories_tags'] = <<<EOF
INSERT INTO `{$new}stories_tags` ( `sid`,`tid` )
	SELECT S.sid,T.tid
		FROM `{$old}stories`S
		INNER JOIN `{$new}tags` T ON (FIND_IN_SET(T.tid,S.classes)>0);
--SPLIT--
INSERT INTO `{$new}stories_tags` ( `sid`,`tid` )
	SELECT S.sid,T.tid
		FROM `{$old}stories`S
		INNER JOIN `{$new}tags`T ON (FIND_IN_SET(T.oldid,S.charid)>0);
--SPLIT--
--LOOP
UPDATE `{$new}tags` T1 
LEFT JOIN
(
	SELECT T.tid, COUNT( DISTINCT RT.sid ) AS counter 
	FROM `{$new}tags`T 
	LEFT JOIN `{$new}stories_tags`RT ON (RT.tid = T.tid )
		WHERE T.count IS NULL
		GROUP BY T.tid
		LIMIT 0,25
) AS T2 ON T1.tid = T2.tid
SET T1.count = T2.counter WHERE T1.tid = T2.tid
EOF;

/* --------------------------------------------------------------------------------------------
																																										* STORIES *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story table",
	"steps" => array (
								array ( "stories", 0, "Import story information" ),
									),
);

$sql['init']['stories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT 'Untitled',
  `summary` text,
  `storynotes` text,
  `rid` varchar(25) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `print_cache` tinyint(1) NOT NULL DEFAULT '0',
  `featured` char(1) NOT NULL DEFAULT '0',
  `validated` char(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '-2 deleted, -1 draft, 0 w.i.p., 1 all done',
  `rr` char(1) NOT NULL DEFAULT '0',
  `wordcount` int(11) NOT NULL DEFAULT '0',
	`ranking` tinyint(3) DEFAULT NULL COMMENT 'user rating, but name was ambigious with the age rating',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`), KEY `title` (`title`), KEY `rid` (`rid`), KEY `featured` (`featured`), KEY `completed` (`completed`), KEY `rr` (`rr`), KEY `validateduid` (`validated`), KEY `recent` (`updated`,`validated`)
 ) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['stories'] = <<<EOF
INSERT INTO `{$new}stories`
	( `sid`, `title`, `summary`, `storynotes`, `rid`, `date`, `updated`, `featured`, `validated`, `completed`, `rr`, `wordcount`, `ranking`, 														`reviews`, `count` )
	SELECT
		S.sid, S.title, S.summary, S.storynotes, S.rid, S.date, S.updated, S.featured, S.validated, S.completed, S.rr, S.wordcount, (10*SUM(R.rating)/COUNT(R.reviewid)), S.reviews, S.count
	FROM `{$old}stories`S
		LEFT JOIN `{$old}reviews`R ON ( S.sid = R.item AND R.rating > 0 )
	GROUP BY S.sid
	ORDER BY S.sid ASC
EOF;

/* --------------------------------------------------------------------------------------------
																																										* RATINGS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story ratings",
	"steps" => array (
								array ( "ratings", 0, "Copy data" ),
									),
);

$sql['init']['ratings'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}ratings` (
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `rating` varchar(60) NOT NULL DEFAULT '',
  `rating_image` varchar(50) NULL DEFAULT NULL,
  `ratingwarning` tinyint(1) NOT NULL DEFAULT '0',
  `warningtext` text NOT NULL,
  PRIMARY KEY (`rid`),
  KEY `rating` (`rating`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['ratings'] = <<<EOF
INSERT INTO `{$new}ratings` ( `rid`, `rating`, `ratingwarning`, `warningtext` )
	SELECT `rid`, `rating`, `ratingwarning`, `warningtext`
	FROM `{$old}ratings`;
EOF;

/* --------------------------------------------------------------------------------------------
																																									 * CHAPTERS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Chapters",
	"steps" => array (
								array ( "chapters", 0, "Copy data (option to import from file later)" ),
									),
);

$sql['init']['chapters'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}chapters` (
`chapid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `storytext` mediumtext,
  `workingtext` mediumtext,
  `workingdate` timestamp NULL DEFAULT NULL,
  `endnotes` text,
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `wordcount` int(11) NOT NULL DEFAULT '0',
  `rating` tinyint(3) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`chapid`), KEY `sid` (`sid`), KEY `inorder` (`inorder`), KEY `title` (`title`), KEY `validated` (`validated`), KEY `forstoryblock` (`sid`,`validated`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['chapters'] = <<<EOF
INSERT INTO `{$new}chapters`
	( `chapid`, `title`, `inorder`, `notes`, `storytext`, `endnotes`, `validated`, `wordcount`, `rating`, `reviews`, `sid`, `count` )
	SELECT
		C.chapid, C.title, C.inorder, C.notes, C.storytext, C.endnotes, C.validated, C.wordcount, (C.rating*10), C.reviews, C.sid, C.count
	FROM `{$old}chapters`C ORDER BY C.chapid ASC;
EOF;

/* --------------------------------------------------------------------------------------------
																																										 * SERIES *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Series",
	"steps" => array (
								array ( "series", 0, "Copy data" ),
								array ( "series_stories", 0, "Copy story <-> series relations" ),
									),
);

$sql['init']['series'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series` (
  `seriesid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '',
  `summary` text NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `isopen` tinyint(4) NOT NULL DEFAULT '0',
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `reviews` smallint(6) NOT NULL DEFAULT '0',
  `challenges` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`seriesid`),
  KEY `owner` (`uid`,`title`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['series'] = <<<EOF
INSERT INTO `{$new}series`
	(`seriesid`, `title`, `summary`, `uid`, `isopen`, `rating`, `reviews`, `challenges` )
	SELECT
	`seriesid`, `title`, `summary`, `uid`, `isopen`, `rating`, `reviews`, `challenges`
	FROM `{$old}series`;
EOF;

/* --------------------------------------------------------------------------------------------
																																						* SERIES RELATION *
requires: SERIES
-------------------------------------------------------------------------------------------- */
$sql['init']['series_stories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series_stories` (
  `seriesid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `confirmed` int(11) NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`,`seriesid`),
  KEY `seriesid` (`seriesid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['series_stories'] = <<<EOF
INSERT INTO `{$new}series_stories`
	( `seriesid`, `sid`, `confirmed`, `inorder` )
	SELECT
	`seriesid`, `sid`, `confirmed`, `inorder`
	FROM `{$old}inseries`;
EOF;



/* --------------------------------------------------------------------------------------------
																																											 * USER *
requires: STORIES RELATION
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Users",
	"steps" => array (
								array ( "users", 0, "Create guest entry" ),
								array ( "users", 1, "Copy users, mark admins" ),
								array ( "users", 2, "Mark validated users" ),
								array ( "users", 3, "Mark authors" ),
								array ( "users", 4, "Set remaining users to active" ),
									),
);

$sql['init']['users'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}users` (
  `uid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(128) CHARACTER SET utf8 NOT NULL,
  `nickname` varchar(128) CHARACTER SET utf8 NOT NULL,
  `realname` text CHARACTER SET utf8 NOT NULL,
  `password` varchar(140) CHARACTER SET utf8 NOT NULL,
  `email` varchar(256) CHARACTER SET utf8 NOT NULL,
  `registered` datetime NOT NULL,
  `groups` int(10) unsigned DEFAULT NULL,
  `curator` mediumint(8) unsigned DEFAULT NULL,
  `resettoken` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
	`about` mediumtext CHARACTER SET utf8 NULL,
  PRIMARY KEY (`uid`), UNIQUE KEY `name1` (`login`), KEY `pass1` (`password`), KEY `curator` (`curator`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table for users';
EOF;

$sql['data']['users'] = <<<EOF
INSERT INTO `{$new}users` (`login`, `nickname`, `realname`, `password`, `email`, `registered` ) VALUES
('Guest', 'Guest', '', '', '', '0000-00-00 00:00:00');--NOTEAdd guest entry
--SPLIT--
UPDATE `{$new}users` SET `uid` = '0';--NOTEAdjust Guest ID
--SPLIT--
INSERT INTO `{$new}users`
	( uid, login, nickname, realname, password, email, registered, groups, `about` )
	SELECT
		A.uid, A.penname, A.penname, A.realname, IF(CAST(A.password AS CHAR(32))='0',NULL,A.password), replace(A.email , ' ',''), A.date, 
		(SELECT 255 FROM `{$old}authorprefs` P where P.level = 1 AND P.uid = A.uid), A.bio
	FROM `{$old}authors`A ORDER BY A.date ASC;--NOTECopy users (formerly called authors)
--SPLIT--
UPDATE `{$new}users`U
	INNER JOIN `{$old}authorprefs`AP ON ( U.uid=AP.uid ) 
	SET U.groups = 13 
	WHERE U.groups IS NULL AND AP.validated=1 AND U.uid > 0;--NOTEAssign `trusted author` group to validated authors
--SPLIT--
UPDATE `{$new}users`U 
	INNER JOIN `{$new}stories_authors` R1 ON ( R1.aid = U.uid  ) 
	SET U.groups = 5
	WHERE U.groups IS NULL AND U.uid > 0;--NOTEAssign `author`to all users with at least one story
--SPLIT--
UPDATE `{$new}users`U
	SET groups = 1 WHERE U.groups IS NULL AND U.uid > 0;--NOTESet remaining users as `active`
EOF;

/* --------------------------------------------------------------------------------------------
																																								* USER FIELDS *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"User details",
	"steps" => array (
								array ( "user_fields", 0, "Copy user fields" ),
								array ( "user_info", 0, "Copy user info" ),
								array ( "user_favourites", 0, "Copy favourites" ),
									),
);

$sql['init']['user_fields'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_fields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_type` tinyint(4) NOT NULL DEFAULT '0',
  `field_name` varchar(30) NOT NULL DEFAULT ' ',
  `field_title` varchar(255) NOT NULL DEFAULT ' ',
  `field_options` text,
  `field_code_in` text,
  `field_code_out` text,
  `field_on` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['user_fields'] = <<<EOF
INSERT INTO `{$new}user_fields`
	SELECT * 
	FROM `{$old}authorfields`;
--SPLIT--
INSERT INTO `{$new}user_fields` ( field_type, field_name, field_title ) VALUES ( 2, 'avatar', 'Avatar' );
EOF;

/* --------------------------------------------------------------------------------------------
																																									* USER INFO *
requires: user_fields
-------------------------------------------------------------------------------------------- */
$sql['init']['user_info'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_info` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `field` int(11) NOT NULL DEFAULT '0',
  `info` varchar(255) NOT NULL DEFAULT ' ',
  PRIMARY KEY (`uid`,`field`), KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;
 
$sql['data']['user_info'] = <<<EOF
INSERT INTO `{$new}user_info`
	SELECT Ai.uid, Ai.field, Ai.info
	FROM `{$old}authorinfo`Ai
		INNER JOIN `{$new}user_fields`Uf ON (Ai.field=Uf.field_id)
	ORDER BY Ai.uid, Ai.field ASC;
--SPLIT--
INSERT INTO `{$new}user_info`
	SELECT A.uid, F.field_id, A.image 
		FROM `{$old}authors`A
			LEFT JOIN `{$new}user_fields`F ON ( F.field_name = 'avatar' )
	WHERE A.image NOT LIKE '';
EOF;

/* --------------------------------------------------------------------------------------------
																																						* USER_FAVOURITES *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['user_favourites'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_favourites` (
  `fid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `item` int(11) NOT NULL DEFAULT '0',
  `type` char(2) NOT NULL DEFAULT '',
  `notify` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` tinyint(1) NOT NULL DEFAULT '2',
  `comments` text NOT NULL,
  PRIMARY KEY (`fid`), UNIQUE KEY `byitem` (`item`,`type`,`uid`), UNIQUE KEY `byuid` (`uid`,`type`,`item`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['user_favourites'] = <<<EOF
INSERT INTO `{$new}user_favourites`
	( `uid`, `item`, `type`, `comments` )
	SELECT * FROM `{$old}favorites`;
EOF;

/* --------------------------------------------------------------------------------------------
																																							 * USER_FRIENDS *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['user_friends'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}user_friends` (
  `link` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `friend` int(10) unsigned NOT NULL,
  `comment` varchar(512) NOT NULL,
  PRIMARY KEY (`link`), UNIQUE KEY `relation` (`uid`,`friend`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																									* MESSAGING *
requires: users
-------------------------------------------------------------------------------------------- */
$sql['init']['messaging'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}messaging` (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender` int(10) unsigned NOT NULL,
  `recipient` int(10) unsigned NOT NULL,
  `date_sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_read` timestamp NULL DEFAULT NULL,
  `subject` tinytext NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFiction 5): new table for user messages';
EOF;

/* --------------------------------------------------------------------------------------------
																																											 * NEWS *
requires: users
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Page news",
	"steps" => array (
								array ( "news", 0, "Copy data" ),
									),
);

$sql['init']['news'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}news` (
  `nid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `headline` varchar(255) NOT NULL DEFAULT '',
  `newstext` text NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `comments` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nid`), KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['news'] = <<<EOF
INSERT INTO `{$new}news`
	( `nid`, `uid`, `headline`, `newstext`, `datetime`, `comments` )
	SELECT
		N.nid, U.uid, N.title, N.story, N.time, N.comments
	FROM `{$old}news`N
		LEFT JOIN `{$new}users`U ON ( N.author = U.login )
	ORDER BY N.time ASC;
EOF;

/* --------------------------------------------------------------------------------------------
																																									 * SHOUTBOX *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Shoutbox",
	"steps" => array (
								array ( "shoutbox", 0, "Copy data" ),
									),
);

$sql['init']['shoutbox'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}shoutbox` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(5) NOT NULL DEFAULT 0,
  `guest_name` tinytext,
  `message` varchar(200) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;


/* --------------------------------------------------------------------------------------------
																																										* SESSION *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['sessions'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}sessions` (
  `session` char(32) NOT NULL,
  `user` int(8) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastvisited` timestamp NOT NULL,
  `ip` int(12) unsigned NOT NULL DEFAULT '0',
  `admin` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session`),
  KEY `user` (`user`),
  KEY `session` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table for sessions';
EOF;


/* --------------------------------------------------------------------------------------------
																																									 * FEEDBACK *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Feedback (reviews and comments)",
	"steps" => array (
								array ( "feedback", 0, "Create temporary table" ),
								array ( "feedback", 1, "Copy data to temporary table" ),
								array ( "feedback", 2, "Get reviews" ),
								array ( "feedback", 3, "Get the replies to above reviews, link to story author" ),
								array ( "feedback", 4, "Get news comments" ),
								array ( "feedback", 5, "Drop temporary table" ),
									),
);

$sql['init']['feedback'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}feedback` (
  `fid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reference` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `reference_sub` mediumint(9) unsigned DEFAULT NULL,
  `writer_name` varchar(60) DEFAULT NULL,
  `writer_uid` mediumint(9) unsigned NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `datetime` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `type` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`fid`), KEY `sub_ref` (`reference_sub`), KEY `by_uid` (`writer_uid`,`reference`,`type`), KEY `alias_story_chapter` (`reference`,`reference_sub`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['feedback'] = <<<EOF
INSERT INTO `{$new}feedback`
	(`fid`, `reference`, `reference_sub`, `writer_name`, `writer_uid`, `text`, `datetime`, `rating`, `type`)
	SELECT
		reviewid, 
		item, 
		chapid, 
		IF(uid=0,reviewer,NULL) as reviewer, 
		uid, 
		SUBSTRING_INDEX(review, '{$review_split}', 1), 
		date, 
		rating, 
		type 
	FROM `{$old}reviews` Rv ORDER BY Rv.reviewid;--NOTECopy Reviews
--SPLIT--
INSERT INTO `{$new}feedback`
	(`reference`, `reference_sub`, `writer_uid`, `text`, `type`)
	SELECT
		item, 
		reviewid, 
		U.uid,
		TRIM(TRAILING '</i>' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(review, '{$review_split}', -1), ': ', -1) ),
		'C'
	FROM `{$old}reviews` Rv 
		LEFT JOIN `{$new}stories_authors`SR ON ( Rv.item = SR.sid AND SR.ca=0 )
			LEFT JOIN `{$new}users`U ON ( SR.aid = U.uid )
	WHERE LOCATE('{$review_split}', Rv.review) > 0 ORDER BY Rv.date;--NOTECopy replies to reviews
--SPLIT--
INSERT INTO `{$new}feedback`
	( `reference`, `writer_uid`, `text`, `datetime`, `type` )
	SELECT
		C.nid, C.uid, C.comment, C.time, 'N'
	FROM `{$old}comments`C;--NOTECopy news comments
EOF;

/* --------------------------------------------------------------------------------------------
																																											 * POLL *
requires: -
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Polls and votes",
	"steps" => array (
								array ( "poll", 0, "Copy poll data" ),
								array ( "poll_votes", 0, "Copy votes" ),
									),
);

$sql['init']['poll'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}poll` (
  `poll_id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_question` varchar(250) NOT NULL,
  `poll_opts` text NOT NULL,
  `poll_start` datetime NOT NULL,
  `poll_end` datetime DEFAULT NULL,
  `poll_results` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`poll_id`)
) ENGINE=MyISAM  DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																								 * POLL VOTES *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['poll_votes'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}poll_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `vote_user` int(11) NOT NULL DEFAULT '0',
  `vote_opt` int(11) NOT NULL DEFAULT '0',
  `vote_poll` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`),
  KEY `vote_user` (`vote_user`,`vote_poll`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

/* --------------------------------------------------------------------------------------------
																																										* TRACKER *
requires: -
-------------------------------------------------------------------------------------------- */
$sql['init']['tracker'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tracker` (
  `sid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `last_chapter` smallint(5) unsigned NOT NULL,
  `last_read` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`uid`), KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
EOF;

/* --------------------------------------------------------------------------------------------
																																											* CACHE *
requires: *
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Story cache",
	"steps" => array (
								array ( "stories_blockcache", 0, "Caching stats" ),
									),
);

$sql['init']['stories_blockcache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stories_blockcache` (
  `sid` int(11) unsigned NOT NULL,
  `tagblock` text,
  `authorblock` text,
  `categoryblock` text,
  `rating` tinytext NOT NULL,
  `reviews` smallint(5) unsigned NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['stories_blockcache'] = "SELECT 1;--NOTEStory Cache";

$sql['probe']['stories_blockcache'] = "SELECT S.sid,C.sid from `{$new}stories`S LEFT JOIN `{$new}stories_blockcache`C ON S.sid = C.sid WHERE C.sid IS NULL LIMIT 0,1";

$sql['proc']['stories_blockcache'] = <<<EOF
SELECT SELECT_OUTER.sid,
GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
GROUP_CONCAT(DISTINCT uid,',',nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
GROUP_CONCAT(DISTINCT rid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating,
COUNT(DISTINCT fid) AS reviews,
COUNT(DISTINCT chapid) AS chapters
FROM
(
	SELECT S.sid,C.chapid,UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
		F.fid,
		S.rid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
		U.uid, U.nickname,
		Cat.cid, Cat.category,
		TG.description,TG.order,TG.tgid,T.label as tag,T.tid 
		FROM
		(
			SELECT S1.*
			FROM `{$new}stories` S1
			LEFT JOIN `{$new}stories_blockcache` B ON ( B.sid = S1.sid )
			WHERE B.sid IS NULL
			LIMIT 0,50
		) AS S
		LEFT JOIN `{$new}ratings` Ra ON ( Ra.rid = S.rid )
		LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
			LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
		LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
			LEFT JOIN `{$new}tags` T ON ( T.tid = rST.tid )
				LEFT JOIN `{$new}tag_groups` TG ON ( TG.tgid = T.tgid )
		LEFT JOIN `{$new}stories_categories`rSC ON ( rSC.sid = S.sid )
			LEFT JOIN `{$new}categories` Cat ON ( rSC.cid = Cat.cid )
		LEFT JOIN `{$new}chapters` C ON ( C.sid = S.sid )
		LEFT JOIN `{$new}feedback` F ON ( F.reference = S.sid AND F.type='ST' )
)AS SELECT_OUTER
GROUP BY sid ORDER BY sid ASC
EOF;

$sql['init']['series_blockcache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}series_blockcache` (
  `seriesid` mediumint(8) unsigned NOT NULL,
  `tagblock` text,
  `authorblock` text,
  `categoryblock` text,
  `max_rating` tinytext NOT NULL,
  `chapters` smallint(5) unsigned NOT NULL,
  `words` int(10) unsigned NOT NULL,
  PRIMARY KEY (`seriesid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['series_blockcache'] = "SELECT 1;--NOTESeries Cache";

$sql['probe']['series_blockcache'] = "SELECT S.seriesid,C.seriesid from `{$new}series`S LEFT JOIN `{$new}series_blockcache`C ON S.seriesid = C.seriesid WHERE C.seriesid IS NULL LIMIT 0,1";

$sql['proc']['series_blockcache'] = <<<EOF
SELECT 
	SERIES.seriesid, 
	SERIES.tagblock, 
	SERIES.authorblock, 
	SERIES.categoryblock, 
	CONCAT(rating,'||',max_rating_id) as max_rating, 
	chapter_count, 
	word_count
FROM
(
SELECT 
Ser.seriesid,
MAX(Ra.rid) as max_rating_id,
			GROUP_CONCAT(DISTINCT U.uid,',',U.nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
			GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
			GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock,
			COUNT(DISTINCT Ch.chapid) as chapter_count, SUM(Ch.wordcount) as word_count
		FROM 
		(
			SELECT Ser1.seriesid
				FROM `{$new}series`Ser1
				LEFT JOIN `{$new}series_blockcache`B ON ( B.seriesid = Ser1.seriesid )
				WHERE B.seriesid IS NULL
				LIMIT 0,5
		) AS Ser
			LEFT JOIN `{$new}series_stories`TrS ON ( Ser.seriesid = TrS.seriesid )
				LEFT JOIN `{$new}stories`S ON ( TrS.sid = S.sid )
					LEFT JOIN `{$new}chapters`Ch ON ( Ch.sid = S.sid )
					LEFT JOIN `{$new}stories_authors`rSA ON ( rSA.sid = S.sid )
						LEFT JOIN `{$new}users` U ON ( rSA.aid = U.uid )
			LEFT JOIN `{$new}ratings`Ra ON ( Ra.rid = S.rid )
			LEFT JOIN `{$new}stories_tags`rST ON ( rST.sid = S.sid )
				LEFT JOIN `{$new}tags`T ON ( T.tid = rST.tid )
					LEFT JOIN `{$new}tag_groups`TG ON ( TG.tgid = T.tgid )
			LEFT JOIN `{$new}stories_categories`rSC ON ( rSC.sid = S.sid )
				LEFT JOIN `{$new}categories`C ON ( rSC.cid = C.cid )
		GROUP BY Ser.seriesid
) AS SERIES
LEFT JOIN `{$new}ratings`R ON (R.rid = max_rating_id);
EOF;

/* --------------------------------------------------------------------------------------------
																																								* STATS CACHE *
requires: *
-------------------------------------------------------------------------------------------- */
$steps[] = array
(
	"info"	=>	"Page stats cache table",
	"steps" => array (
								array ( "stats_cache", 0, "Users" ),
								array ( "stats_cache", 1, "Authors" ),
								array ( "stats_cache", 2, "Reviews" ),
								array ( "stats_cache", 3, "Stories" ),
								array ( "stats_cache", 4, "Chapters" ),
								array ( "stats_cache", 5, "Words" ),
									),
);

// numeric-only table, so CHARSET doesnt't matter much
$sql['init']['stats_cache'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}stats_cache` (
  `field` varchar(32) NOT NULL,
  `value` int(10) unsigned NOT NULL,
  `name` tinytext DEFAULT NULL,
  UNIQUE KEY `field` (`field`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset};
EOF;

$sql['data']['stats_cache'] = <<<EOF
INSERT INTO `{$new}stats_cache`
	SELECT 'users', COUNT(1), NULL FROM `{$new}users`U WHERE U.groups > 0;
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'authors', COUNT(1), NULL FROM `{$new}users`U WHERE ( U.groups & 4 );
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'reviews', COUNT(1), NULL FROM `{$new}feedback`F WHERE F.type='ST';
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'stories', COUNT(DISTINCT sid), NULL FROM `{$new}stories`S WHERE S.validated > 0;
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'chapters', COUNT(DISTINCT chapid), NULL FROM `{$new}chapters`C INNER JOIN `{$new}stories`S ON ( C.sid=S.sid AND S.validated > 0 );
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'words', SUM(C.wordcount), NULL FROM `{$new}chapters`C INNER JOIN `{$new}stories`S ON ( C.sid=S.sid AND S.validated > 0 );
--SPLIT--
INSERT INTO `{$new}stats_cache`
	SELECT 'newmember', 0, CONCAT_WS(',', U.uid, U.nickname) FROM `{$new}users`U WHERE U.groups>0 ORDER BY U.registered DESC LIMIT 1;
EOF;

?>