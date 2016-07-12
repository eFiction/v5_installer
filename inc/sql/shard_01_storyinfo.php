<?php

/* --------------------------------------------------------------------------------------------
																																								 * TAGS GROUPS*
requires: -
-------------------------------------------------------------------------------------------- */

$sql['init']['characters'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}characters` (
  `charid` int(11) NOT NULL,
  `catid` int(11) NOT NULL DEFAULT '0',
  `charname` tinytext NOT NULL,
  `biography` mediumtext NOT NULL,
  `image` tinytext NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`charid`), UNIQUE KEY `charname` (`charname`(64))
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='(eFI5): new table';
EOF;

/* --------------------------------------------------------------------------------------------
																																								 * TAGS GROUPS*
requires: -
-------------------------------------------------------------------------------------------- */

$sql['init']['tag_groups'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}tag_groups` (
  `tgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `description` mediumtext,
  PRIMARY KEY (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET={$characterset} COMMENT='New table, eFiction 5';
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

/* --------------------------------------------------------------------------------------------
																																									* CATEGORIES *
requires: -
-------------------------------------------------------------------------------------------- */

$sql['init']['categories'] = <<<EOF
CREATE TABLE IF NOT EXISTS `{$new}categories` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `parent_cid` int(11) NOT NULL DEFAULT '0',
  `category` varchar(60) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `leveldown` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `inorder` int(11) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL DEFAULT '0',
  `stats` text NOT NULL,
  PRIMARY KEY (`cid`), KEY `byparent` (`parent_cid`,`inorder`)
) ENGINE=MyISAM DEFAULT CHARSET={$characterset} COMMENT='(eFI5): derived from _categories';
EOF;

?>