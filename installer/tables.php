<?
$tables = array(
		'zcm_passwd'=>"CREATE TABLE `zcm_passwd` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(20) NOT NULL default '',
  `password` varchar(20) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  `fullname` varchar(60) NOT NULL default '',
  `admin` tinyint(4) NOT NULL default '0',
  `eula` tinyint(4) default '0',
  PRIMARY KEY  (`id`),
  KEY `userpass` (`username`,`password`))",
		'zcm_meta'=>"CREATE TABLE `zcm_meta` (
  `id` int(11) NOT NULL auto_increment,
  `document` varchar(80) NOT NULL default '',
  `description` text NOT NULL,
  `keywords` text NOT NULL,
  `title` varchar(80) NOT NULL default '',
  `mtime` bigint(20) NOT NULL default '0',
  `changefreq` varchar(10) default 'monthly',
  `priority` tinyint(4) default '5',
  PRIMARY KEY  (`id`),
  KEY `document` (`document`))",
		'zcm_sitetext'=>"CREATE TABLE `zcm_sitetext` (
  `id` int(11) NOT NULL auto_increment,
  `tag` varchar(35) NOT NULL default '',
  `body` longtext NOT NULL,
  `plainbody` longtext NOT NULL,
  `inputspec` VARCHAR( 100 ) DEFAULT 'html.600.400' NOT NULL,
  `category` bigint(20) default '0',
  PRIMARY KEY  (`id`),
  KEY `tag` (`tag`),
  KEY `category` (`category`))",
		'zcm_stcategory'=>"CREATE TABLE `zcm_stcategory` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(60) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`))",
  		'zcm_textpage'=>"CREATE TABLE `zcm_textpage` (
  `metaid` int(11) NOT NULL default '0',
  `sitetextid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`metaid`,`sitetextid`),
  KEY `metaid` (`metaid`),
  KEY `sitetextid` (`sitetextid`))",
		'zcm_config'=>"CREATE TABLE `zcm_config` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(40) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  `disporder` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `disporder` (`disporder`))",
		'zcm_plugin'=>"CREATE TABLE `zcm_plugin` (
  `id` int(11) NOT NULL auto_increment,
  `release` int(11) NOT NULL default '0',
  `title` varchar(50) NOT NULL default '',
  `name` varchar(50) NOT NULL default '',
  `enabled` smallint(6) NOT NULL default '0',
  `uninstallsql` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `enabled` (`enabled`),
  KEY `source` (`name`))",
		'zcm_pluginconfig'=>"CREATE TABLE `zcm_pluginconfig` (
  `plugin` int(11) NOT NULL default '0',
  `instance` int(11) NOT NULL default '0',
  `key` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`plugin`,`instance`,`key`))",
		'zcm_plugininstance'=>"CREATE TABLE `zcm_plugininstance` (
  `id` int(11) NOT NULL auto_increment,
  `plugin` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `private` tinyint(4) default NULL,
  PRIMARY KEY  (`id`),
  KEY `plugin` (`plugin`),
  KEY `private` (`private`))",
		'zcm_member'=>"CREATE TABLE `zcm_member` (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(80) default NULL,
  `password` varchar(32) default NULL,
  `regtime` datetime default NULL,
  `lastauth` datetime default NULL,
  `formdata` int(11) default NULL,
  `authkey` varchar(32) default NULL,
  `orgunit` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email_3` (`email`),
  KEY `email` (`email`,`password`),
  KEY `regtime` (`regtime`),
  KEY `lastauth` (`lastauth`),
  KEY `authkey` (`authkey`),
  KEY `orgunit` (`orgunit`))",
		'zcm_memberaudit'=>"CREATE TABLE `zcm_memberaudit` (
  `id` int(11) NOT NULL auto_increment,
  `member` int(11) default NULL,
  `audittime` datetime default NULL,
  `remoteip` varchar(15) default NULL,
  `realip` varchar(15) default NULL,
  `audit` text,
  PRIMARY KEY  (`id`),
  KEY `member` (`member`,`audittime`),
  KEY `remoteip` (`remoteip`),
  KEY `realip` (`realip`))",
		'zcm_membergroup'=>"CREATE TABLE `zcm_membergroup` (
  `memberid` int(11) NOT NULL default '0',
  `groupid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`memberid`,`groupid`),
  KEY `memberid` (`memberid`),
  KEY `groupid` (`groupid`))",
		'zcm_groups'=>"CREATE TABLE `zcm_groups` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`))",
		'zcm_customtable'=>"CREATE TABLE `zcm_customtable` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `disporder` bigint(20) default NULL,
  `tname` varchar(30) default NULL,
  `detailfor` bigint(20) default '0',
  `hasdisporder` tinyint(4) default NULL,
  `navname` varchar(30) default NULL,
  `selfref` varchar(30) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `detailfor` (`detailfor`),
  KEY `disporder` (`disporder`))",
		'zcm_customfield'=>"CREATE TABLE `zcm_customfield` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `disporder` bigint(20) default NULL,
  `tableid` bigint(20) default NULL,
  `cname` varchar(30) default NULL,
  `inputspec` text,
  `caption` text,
  `indexed` varchar(1) default NULL,
  `gridheader` varchar(30) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `disporder` (`disporder`))",
		'zcm_help'=>"CREATE TABLE `zcm_help` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `parent` bigint(20) default NULL,
  `disporder` bigint(20) default NULL,
  `authlevel` tinyint(4) default NULL,
  `title` varchar(200) default NULL,
  `body` text,
  `plain` text,
  UNIQUE KEY `id` (`id`),
  KEY `authlevel` (`authlevel`),
  KEY `title` (`title`),
  FULLTEXT KEY `title_2` (`title`,`plain`))",
	'zcm_helpalso'=>"CREATE TABLE `zcm_helpalso` (
  `help` bigint(20) NOT NULL default '0',
  `seealso` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`help`,`seealso`))",
	'zcm_helpindex'=>"CREATE TABLE `zcm_helpindex` (
  `phrase` bigint(20) NOT NULL default '0',
  `help` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`phrase`,`help`))",
	'zcm_helpindexphrase'=>"CREATE TABLE `zcm_helpindexphrase` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `phrase` varchar(200) default NULL,
  UNIQUE KEY `id` (`id`))",
	'zcm_nav'=>"CREATE TABLE `zcm_nav` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `disporder` bigint(20) default NULL,
  `parent` bigint(20) default '0',
  `navname` varchar(60) default NULL,
  `navtype` enum('URL','Custom Table','Plugin','Sub-Menu') default NULL,
  `navto` varchar(200) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `disporder` (`disporder`),
  KEY `parent` (`parent`))"
	);
?>