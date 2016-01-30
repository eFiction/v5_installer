<?php

$sql['init']['chapters'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}chapters` (
`chapid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `chaptertext` mediumtext,
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

if ( $path =="upgrade")
{
	$steps[] = array
	(
		"info"	=>	"Chapters",
		"steps" => array (
									array ( "chapters", 0, "Copy chapter data (except for chapter text, this happens later)" ),
										),
	);

	if($fw['installerCFG.chapters']=="filebase")
	{
	$sql['data']['chapters'] = <<<EOF
	INSERT INTO `{$new}chapters`
		( `chapid`, `title`, `inorder`, `notes`, `chaptertext`, `endnotes`, `validated`, `wordcount`, `rating`, `reviews`, `sid`, `count` )
		SELECT
			C.chapid, C.title, C.inorder, C.notes, NULL, C.endnotes, C.validated, C.wordcount, (C.rating*10), C.reviews, C.sid, C.count
		FROM `{$old}chapters`C ORDER BY C.chapid ASC;
EOF;
	}
	else
	{
	$sql['data']['chapters'] = <<<EOF
	INSERT INTO `{$new}chapters`
		( `chapid`, `title`, `inorder`, `notes`, `chaptertext`, `endnotes`, `validated`, `wordcount`, `rating`, `reviews`, `sid`, `count` )
		SELECT
			C.chapid, C.title, C.inorder, C.notes, C.storytext, C.endnotes, C.validated, C.wordcount, (C.rating*10), C.reviews, C.sid, C.count
		FROM `{$old}chapters`C ORDER BY C.chapid ASC;
EOF;
	}

}
// No data step required for fresh install

?>