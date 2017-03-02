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
(1, '#author', 'eFiction.org'),
(1, '#directory', NULL),
(1, '#name', 'Font Awesome CSS Icons'),
(1, '#notes', 'requires ''@import url(//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css);'' in styles.css (See http://fortawesome.github.io/Font-Awesome/get-started/ )'),
(1, '#pattern', '<span class="fa @1@"></span>'),
(1, 'archive', 'fa-university'),
(1, 'arrow-down', 'fa-arrow-down'),
(1, 'arrow-left', 'fa-arrow-left'),
(1, 'arrow-right', 'fa-arrow-right'),
(1, 'arrow-up', 'fa-arrow-up'),
(1, 'bars', 'fa-bars'),
(1, 'blank', 'fa-square-o'),
(1, 'book', 'fa-book'),
(1, 'bookmark', 'fa-bookmark'),
(1, 'bookmark-off', 'fa-bookmark-o'),
(1, 'calendar', 'fa-calendar'),
(1, 'check', 'fa-check'),
(1, 'cloud', 'fa-cloud'),
(1, 'close', 'fa-times'),
(1, 'comment', 'fa-comment-o'),
(1, 'comments', 'fa-comments-o'),
(1, 'comment_dark', 'fa-comment'),
(1, 'document-new', 'fa-file-o'),
(1, 'edit', 'fa-pencil-square-o'),
(1, 'external-link', 'fa-external-link'),
(1, 'favourite,heart', 'fa-heart'),
(1, 'favourite-off', 'fa-heart-o'),
(1, 'folder', 'fa-folder-open'),
(1, 'following', 'fa-reply fa-rotate-180'),
(1, 'home', 'fa-home'),
(1, 'inbox', 'fa-inbox'),
(1, 'info', 'fa-info-circle'),
(1, 'invisible', 'fa-eye-slash'),
(1, 'key', 'fa-key'),
(1, 'keyboard', 'fa-keyboard-o'),
(1, 'language', 'fa-language'),
(1, 'layout,visible', 'fa-eye'),
(1, 'lock', 'fa-lock'),
(1, 'mail', 'fa-envelope'),
(1, 'mail-read', 'fa-envelope-open-o'),
(1, 'manual', 'fa-info'),
(1, 'member', 'fa-user'),
(1, 'members', 'fa-users'),
(1, 'minus', 'fa-minus-square'),
(1, 'modules', 'fa-cubes'),
(1, 'news', 'fa-rss'),
(1, 'plus', 'fa-plus-square'),
(1, 'recommendation-off', 'fa-star-o'),
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
(1, 'star,recommendation', 'fa-star'),
(1, 'tag', 'fa-tag'),
(1, 'tags', 'fa-tags'),
(1, 'text', 'fa-file-text-o'),
(1, 'time', 'fa-clock-o'),
(1, 'trash', 'fa-trash-o'),
(1, 'unlock', 'fa-unlock'),
(1, 'waiting', 'fa-spin fa-spinner'),
(1, 'wrench', 'fa-wrench');
EOF;

	$fw->db5->exec($sql);
	$count = $fw->db5->count();
	
	$fw->db5->exec ( "UPDATE `{$fw->dbNew}convert`SET `success` = 2, `items` = :items WHERE `id` = :id ", 
						[ 
							':items' => $count,
							':id' => $step['id']
						]
					);
}
?>