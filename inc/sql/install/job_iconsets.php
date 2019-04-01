<?php
/*
	Job definition for 'chapters'
	eFiction upgrade from version 3.5.x
*/

$fw->jobSteps = array(
		"create"	=> "Create config table",
	);


function iconsets_create($job, $step)
{
	// Chapters copy is a 1-pass module, doing the entire chapter relocation
	$fw = \Base::instance();

$sql = <<<EOF
INSERT INTO `{$fw->dbNew}iconsets` (`set_id`, `name`, `value`) VALUES
(1, '#author', 'Rainer @ eFiction.org'),
(1, '#directory', NULL),
(1, '#name', 'Font Awesome CSS Icons'),
(1, '#notes', 'requires \'@import url(https://use.fontawesome.com/releases/v5.3.1/css/all.css);\' in styles.css (See http://fortawesome.github.io/Font-Awesome/get-started/ )'),
(1, '#pattern', '<span class=\"@1@\"@T@></span>'),
(1, 'alert', 'fas fa-exclamation-triangle'),
(1, 'angle-double-left', 'fas fa-angle-double-left'),
(1, 'angle-double-right', 'fas fa-angle-double-right'),
(1, 'angle-left', 'fas fa-angle-left'),
(1, 'angle-right', 'fas fa-angle-right'),
(1, 'archive', 'fas fa-university'),
(1, 'arrow-down', 'fas fa-arrow-down'),
(1, 'arrow-left', 'fas fa-arrow-left'),
(1, 'arrow-right', 'fas fa-arrow-right'),
(1, 'arrow-up', 'fas fa-arrow-up'),
(1, 'arrow-vert', 'fas fa-arrows-alt-v'),
(1, 'bars', 'fas fa-bars'),
(1, 'blank', 'far fa-square'),
(1, 'book', 'fas fa-book'),
(1, 'bookmark', 'fas fa-bookmark'),
(1, 'bookmark-off', 'far fa-bookmark'),
(1, 'calendar', 'far fa-calendar-times'),
(1, 'categories', 'fas fa-folder'),
(1, 'check', 'fas fa-check'),
(1, 'close', 'fas fa-times'),
(1, 'cloud', 'fas fa-cloud'),
(1, 'comment', 'far fa-comment'),
(1, 'comments', 'far fa-comments'),
(1, 'comment_dark', 'fas fa-comment'),
(1, 'document-new', 'far fa-file'),
(1, 'download', 'fas fa-download'),
(1, 'edit', 'fas fa-edit'),
(1, 'external-link', 'fas fa-external-link-alt'),
(1, 'favourite,heart', 'fas fa-heart'),
(1, 'favourite-off', 'far fa-heart'),
(1, 'features', 'fas fa-star-half-alt'),
(1, 'file,text', 'far fa-file-alt'),
(1, 'flag-off,report-off', 'far fa-flag'),
(1, 'flag-on, report-on', 'fas fa-flag'),
(1, 'folder', 'fas fa-folder-open'),
(1, 'following', 'fas fa-reply fa-rotate-180'),
(1, 'home', 'fas fa-home'),
(1, 'inbox', 'fas fa-inbox'),
(1, 'info', 'fas fa-info-circle'),
(1, 'invisible', 'far fa-eye-slash'),
(1, 'key', 'fas fa-key'),
(1, 'keyboard', 'far fa-keyboard'),
(1, 'language', 'fas fa-language'),
(1, 'layout', 'fas fa-desktop'),
(1, 'list', 'fas fa-list'),
(1, 'lock', 'fas fa-lock'),
(1, 'mail', 'far fa-envelope'),
(1, 'mail-read', 'far fa-envelope-open'),
(1, 'manual', 'fab fa-wikipedia-w'),
(1, 'member', 'fas fa-user'),
(1, 'members', 'fas fa-users'),
(1, 'minus', 'fas fa-minus-square'),
(1, 'modules', 'fas fa-cubes'),
(1, 'news', 'far fa-newspaper'),
(1, 'numlist', 'fas fa-list-ol'),
(1, 'plus', 'fas fa-plus-square'),
(1, 'print', 'fas fa-print'),
(1, 'profile', 'fas fa-user-cog'),
(1, 'rating', 'fas fa-registered'),
(1, 'reader', 'fas fa-book-reader'),
(1, 'recommendation-off', 'far fa-star'),
(1, 'register', 'fas fa-sign-in-alt'),
(1, 'remove', 'fas fa-times'),
(1, 'sbox', 'far fa-comment-alt'),
(1, 'search', 'fas fa-search'),
(1, 'settings', 'fas fa-cogs'),
(1, 'sign-in', 'fas fa-sign-in-alt'),
(1, 'sign-out', 'fas fa-sign-out-alt'),
(1, 'sort', 'fas fa-sort'),
(1, 'sort-alpha-asc', 'fas fa-sort-alpha-up'),
(1, 'sort-alpha-desc', 'fas fa-sort-alpha-down'),
(1, 'sort-numeric-asc', 'fas fa-sort-numeric-up'),
(1, 'sort-numeric-desc', 'fas fa-sort-numeric-down'),
(1, 'sort-size-asc', 'fas fa-sort-amount-up'),
(1, 'sort-size-desc', 'fas fa-sort-amount-down'),
(1, 'square-down', 'fas fa-caret-square-down'),
(1, 'square-up', 'fas fa-caret-square-up'),
(1, 'staff', 'fas fa-user-shield'),
(1, 'star,recommendation', 'fas fa-star'),
(1, 'submissions', 'fas fa-cog'),
(1, 'tag', 'fas fa-tag'),
(1, 'tags', 'fas fa-tags'),
(1, 'time', 'far fa-clock'),
(1, 'trash', 'fas fa-trash'),
(1, 'twitter', 'fab fa-twitter'),
(1, 'unlock', 'fas fa-unlock'),
(1, 'user-edit', 'fas fa-user-edit'),
(1, 'user-friend', 'fas fa-user-plus'),
(1, 'visible', 'far fa-eye'),
(1, 'waiting', 'fas fa-spinner fa-spin'),
(1, 'wrench', 'fas fa-wrench');
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