<?php

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
('adjacent_paginations', '2', 'Contiguous page links to display@SMALL@"1" to display: 1 ... 4 [5] 6 ... 9<br>\n"2" to display: 1 ... 3 4 [5] 6 7 ... 9<br>"0" to display all links', 'settings_general', 6, 'text//numeric', 1, 1),
('admin_list_elements', '20', NULL, '', 0, 'text//numeric', 1, 1),
('allow_guest_comment_news', 'FALSE', NULL, '', 0, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('allow_guest_reviews', 'FALSE', 'Allow guests to write reviews', 'archive_general', 2, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('allow_registration', 'FALSE', 'Allow registration?', 'settings_registration', 0, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('author_self', 'TRUE', 'Every member can post stories@SMALL@If set to no, members must be added to group Authors to allow them to post stories', 'archive_general', 3, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('chapter_data_location', '{$chapterLocation}', 'Where to store chapters (Database Server or local file storage)@SMALL@Local file is being handled by SQLite', 'archive_general', 0, 'select//Database=db//Local Storage=local', 1, 2),
('date_format_long', 'd.m.Y H:i (T)', 'Default long date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'settings_datetime', 2, 'text//small', 1, 1),
('date_format_short', 'd.m.Y', 'Default short date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)', 'settings_datetime', 1, 'text//small', 1, 1),
('debug', '5', 'Debug level', 'settings_server', 1, 'select//disabled=0//low=1//2=2//3=3//4=4//5=5', 1, 1),
('epub_domain', '', 'Used to calculate your epub UUID v5. Leave blank for default (Archive URL)', '', 0, '', 0, 1),
('epub_namespace', '', NULL, '', 0, '', 1, 0),
('iconset_default', '1', NULL, '', 0, '', 1, 0),
('language_available', 'en_GB', 'List all languages that are available to common members.', '', 0, '', 0, 0),
('language_default', 'en', NULL, '', 0, '', 1, 0),
('language_forced', '0', 'Disable custom language selection:@SMALL@Default is <b>no</b>', 'settings_language', 0, 'select//__yes=1//__no=0', 1, 1),
('layout_available', 'default', NULL, '', 0, '', 1, 0),
('layout_default', 'default', NULL, '', 0, '', 1, 0),
('layout_forced', '0', 'Disable custom layout selection:@SMALL@Default is <b>no</b>', 'settings_layout', 1, 'select//__yes=1//__no=0', 1, 1),
('monday_first_day', '1', 'Weeks in calendar start with ...', 'settings_datetime', 3, 'select//__Monday=1//__Sunday=0', 1, 1),
('optional_modules', '', NULL, '', 0, '', 1, 0),
('page_default', 'about', NULL, '', 0, '', 1, 1),
('page_mail', "{$fw['installerCFG.data.siteemail']}", 'Webmaster e-mail address', 'settings_general', 2, 'text//', 1, 1),
('page_slogan', "{$fw['installerCFG.data.slogan']}", 'Site slogan', 'settings_general', 3, 'text//', 1, 1),
('page_title', "{$fw['installerCFG.data.sitename']}", 'Website title', 'settings_general', 1, 'text//', 1, 1),
('page_title_add', 'path', 'Show page path or slogan in title', 'settings_general', 4, 'select//__path=path//__slogan=slogan', 1, 1),
('page_title_reverse', 'FALSE', 'Reverse sort order of page title elements.@SMALL@(Default is <b>no</b>)', 'settings_general', 5, 'select//__yes=TRUE//__no=FALSE', 1, 1),
('page_title_separator', ' | ', 'Separator for page title elements', 'settings_general', 4, 'text//small', 1, 1),
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
('shoutbox_entries', '5', 'Number of shoutbox items to display', 'settings_general', 7, 'text//numeric', 1, 1),
('shoutbox_guest', 'TRUE', 'Allow guest posts in shoutbox', 'settings_general', 8, 'select//__yes=TRUE//__no=FALSE', 1, 1),
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
																																										 * BAD BEHAVIOR *
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
--SPLIT--
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
--SPLIT--
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

$sql['data']['menu'] = <<<EOF
INSERT INTO `{$new}menu` (`label`, `order`, `link`) VALUES
('Home', 1, ''),
('Authors', 5, 'authors'),
('Fandoms', 1, 'story/categories'),
('Updates', 3, 'story/updates'),
('Archive', 2, 'story'),
('Search', 6, 'story/search'),
('Challenges', 6, 'story/contests');--NOTEPage menu
--SPLIT--
INSERT INTO `{$new}menu_adminpanel` (`label`, `order`, `link`, `icon`, `child_of`, `active`, `requires`, `evaluate`) VALUES
('LN__AdminMenu_Tags', 2, 'archive/tags,tag', '{ICON:tag}', 'archive', 1, 1, NULL),
('LN__AdminMenu_Featured', 1, 'archive/featured', '{ICON:blank}', 'archive', 1, 1, NULL),
('LN__AdminMenu_Groups', 3, 'members/groups', '{ICON:members}', 'members', 1, 2, NULL),
('LN__AdminMenu_Pending', 2, 'members/pending', '{ICON:waiting}', 'members', 1, 2, NULL),
('LN__AdminMenu_Search', 1, 'members/search', '{ICON:search}', 'members', 1, 2, NULL),
('LN__AdminMenu_Language', 4, 'settings/language', '{ICON:language}', 'settings', 1, 2, NULL),
('LN__AdminMenu_Icons', 2, 'settings/layout/icons', '{ICON:blank}', 'settings/layout', 1, 2, NULL),
('LN__AdminMenu_Themes', 1, 'settings/layout/themes', '{ICON:blank}', 'settings/layout', 1, 2, NULL),
('LN__AdminMenu_Layout', 3, 'settings/layout', '{ICON:layout}', 'settings', 1, 2, NULL),
('LN__AdminMenu_Registration', 2, 'settings/registration', '{ICON:register}', 'settings', 1, 2, NULL),
('LN__AdminMenu_Server', 1, 'settings/server', '{ICON:wrench}', 'settings', 1, 2, NULL),
('LN__AdminMenu_Shoutbox', 5, 'home/shoutbox', '{ICON:blank}', 'home', 1, 1, '\$shoutbox == 1;'),
('LN__AdminMenu_Modules', 4, 'home/modules', '{ICON:modules}', 'home', 1, 2, NULL),
('LN__AdminMenu_News', 3, 'home/news', '{ICON:news}', 'home', 1, 1, NULL),
('LN__AdminMenu_CustomPages', 2, 'home/custompages', '{ICON:text}', 'home', 1, 1, NULL),
('LN__AdminMenu_Manual', 1, 'home/manual', '{ICON:manual}', 'home', 1, 1, NULL),
('LN__AdminMenu_Stories', 5, 'stories', '{ICON:book}', NULL, 1, 1, NULL),
('LN__AdminMenu_Archive', 4, 'archive', '{ICON:archive}', NULL, 1, 1, NULL),
('LN__AdminMenu_Members', 3, 'members', '{ICON:member}', NULL, 1, 2, NULL),
('LN__AdminMenu_Settings', 2, 'settings', '{ICON:settings}', NULL, 1, 2, NULL),
('LN__AdminMenu_Home', 1, 'home', '{ICON:home}', NULL, 1, 1, NULL),
('LN__AdminMenu_Edit', 1, 'archive/tags/tag', '{ICON:tag}', 'archive/tags', 1, 1, NULL),
('LN__AdminMenu_Taggroups', 2, 'archive/tags/groups', '{ICON:tags}', 'archive/tags', 1, 2, NULL),
('LN__AdminMenu_Tagcloud', 3, 'archive/tags/cloud', '{ICON:cloud}', 'archive/tags', 1, 2, NULL),
('LN__AdminMenu_Categories', 3, 'archive/categories', '{ICON:blank}', 'archive', 1, 1, NULL),
('LN__AdminMenu_Pending', 1, 'stories/pending', '{ICON:waiting}', 'stories', 1, 2, NULL),
('LN__AdminMenu_Edit', 2, 'stories/edit', '{ICON:edit}', 'stories', 1, 1, NULL),
('LN__AdminMenu_Add', 3, 'stories/add', '{ICON:document-new}', 'stories', 1, 1, NULL);--NOTEAdmin panel menu
--SPLIT--
INSERT INTO `{$new}menu_userpanel` (`label`, `order`, `link`, `icon`, `child_of`, `active`, `evaluate`) VALUES
('LN__UserMenu_Profile', 1, 'profile', '{ICON:member}', NULL, 1, NULL),
('LN__UserMenu_Message', 2, 'messaging', '{ICON:mail}', NULL, 1, NULL),
('LN__UserMenu_Authoring', 3, 'author', '{ICON:keyboard}', NULL, 1, NULL),
('LN__UserMenu_MyLibrary', 4, 'library', '{ICON:book}', NULL, 1, NULL),
('LN__UserMenu_Reviews', 4, 'reviews', '{ICON:comments}', NULL, 1, NULL),
('LN__UserMenu_Preferences', 6, 'preferences', '{ICON:settings}', NULL, 1, NULL),
('%AUTHORS%', 1, 'author&amp;uid=%UID%', '{ICON:member}', 'story', 1, NULL),
('%FINISHED%', 1, 'author/%UID/finished', NULL, 'authoring', 1, NULL),
('%UNFINISHED%', 2, 'author/%UID%/unfinished', NULL, 'authoring', 1, NULL),
('%DRAFTS%', 3, 'author/%UID%/drafts', '{ICON:folder}', 'authoring', 1, NULL),
('LN__UserMenu_AddStory', 4, 'author%UID%/add', '{ICON:text}', 'authoring', 1, NULL),
('__Bookmarks%BMS%', 1, 'library/bm', '{ICON:bookmark}', '4', 1, NULL),
('__Favourites%FAVS%', 2, 'library/fav', '{ICON:heart}', '4', 1, NULL),
('__Recommendations%RECS%', 3, 'library/rec', '{ICON:star}', '4', 1, NULL),
('LN__UserMenu_PMInbox', 1, 'messaging/inbox', '{ICON:inbox}', 'messaging', 1, NULL),
('LN__UserMenu_PMWrite', 2, 'messaging/write', '{ICON:edit}', 'messaging', 1, NULL),
('LN__UserMenu_PMOutbox', 3, 'messaging/outbox', '{ICON:bars}', 'messaging', 1, NULL),
('__aboutMe', 1, 'profile/about', '{ICON:text}', 'profile', 1, NULL),
('__changePW', 3, 'profile/changepw', '{ICON:key}', 'profile', 1, NULL),
('__Authors', 1, 'library/fav/AU', NULL, '13', 1, NULL),
('__Stories', 2, 'library/fav/ST', NULL, '13', 1, NULL),
('LN__UserMenu_Curator', 2, 'author/curator', NULL, 'story', 1, NULL);--NOTEUser panel menu
EOF;

?>