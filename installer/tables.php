<?
	/* array("columns" => "export", "unique" => false, "type" => "") */

	$baseTableDefinitions = array();
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetAuthenticationTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetConfigurationTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetSimpleContentTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetPluginTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetMembershipTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetCustomTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetHelpTableDefinitions());

	function GetAuthenticationTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_passwd",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("username", "VARCHAR(20)", "NOT NULL DEFAULT ''"),
					DefineTableField("password", "VARCHAR(20)", "NOT NULL DEFAULT ''"),
					DefineTableField("email", "VARCHAR(80)", "NOT NULL DEFAULT ''"),
					DefineTableField("fullname", "VARCHAR(60)", "NOT NULL DEFAULT ''"),
					DefineTableField("admin", "TINYINT(4)", "NOT NULL DEFAULT '0'"),
					DefineTableField("eula", "TINYINT(4)", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(
					array("columns" => "username, password", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetConfigurationTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_config",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(40)", "NOT NULL DEFAULT ''"),
					DefineTableField("value", "VARCHAR(100)", "NOT NULL DEFAULT ''"),
					DefineTableField("disporder", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("inputspec", "VARCHAR(100)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetSimpleContentTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_meta",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("document", "VARCHAR(80)", "NOT NULL DEFAULT ''"),
					DefineTableField("description", "TEXT", "NOT NULL"),
					DefineTableField("keywords", "TEXT", "NOT NULL"),
					DefineTableField("title", "VARCHAR(80)", "NOT NULL DEFAULT ''"),
					DefineTableField("mtime", "BIGINT(20)", "NOT NULL DEFAULT '0'"),
					DefineTableField("changefreq", "VARCHAR(10)", "DEFAULT 'monthly'"),
					DefineTableField("priority", "TINYINT(4)", "DEFAULT '5'")
				),
				"indexes" => array(
					array("columns" => "document", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_sitetext",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("tag", "VARCHAR(35)", "NOT NULL DEFAULT ''"),
					DefineTableField("body", "LONGTEXT", "NOT NULL"),
					DefineTableField("plainbody", "LONGTEXT", "NOT NULL"),
					DefineTableField("inputspec", "VARCHAR(100)", "NOT NULL DEFAULT 'html.600.400'"),
					DefineTableField("category", "BIGINT(20)", "DEFAULT '0'")
				),
				"indexes" => array(
					array("columns" => "tag", "unique" => FALSE, "type" => ""),
					array("columns" => "category", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_stcategory",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(60)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "name", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_textpage",
				"columns" => array(
					DefineTableField("metaid", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("sitetextid", "INT(11)", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(
					array("columns" => "metaid", "unique" => FALSE, "type" => ""),
					array("columns" => "sitetextid", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "metaid, sitetextid",
				"engine" => "MyISAM"
			)
		);
	}

	function GetPluginTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_plugin",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("release", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("title", "VARCHAR(50)", "NOT NULL DEFAULT ''"),
					DefineTableField("name", "VARCHAR(50)", "NOT NULL DEFAULT ''"),
					DefineTableField("enabled", "SMALLINT(6)", "NOT NULL DEFAULT '0'"),
					DefineTableField("uninstallsql", "TEXT", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "enabled", "unique" => FALSE, "type" => ""),
					array("columns" => "name", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_pluginconfig",
				"columns" => array(
					DefineTableField("plugin", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("instance", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("key", "VARCHAR(50)", "NOT NULL DEFAULT ''"),
					DefineTableField("value", "TEXT", "NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "`plugin`, `instance`, `key`",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_plugininstance",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("plugin", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("name", "VARCHAR(50)", "NOT NULL DEFAULT ''"),
					DefineTableField("private", "TINYINT(4)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "plugin", "unique" => FALSE, "type" => ""),
					array("columns" => "private", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetMembershipTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_member",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("email", "VARCHAR(80)", "DEFAULT NULL"),
					DefineTableField("password", "VARCHAR(32)", "DEFAULT NULL"),
					DefineTableField("regtime", "DATETIME", "DEFAULT NULL"),
					DefineTableField("lastauth", "DATETIME", "DEFAULT NULL"),
					DefineTableField("formdata", "INT(11)", "DEFAULT NULL"),
					DefineTableField("authkey", "VARCHAR(32)", "DEFAULT NULL"),
					DefineTableField("orgunit", "INT(11)", "DEFAULT NULL"),
					DefineTableField("mpkey", "VARCHAR(40)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "email", "unique" => TRUE, "type" => ""),
					array("columns" => "mpkey", "unique" => TRUE, "type" => ""),
					array("columns" => "email, password", "unique" => FALSE, "type" => ""),
					array("columns" => "regtime", "unique" => FALSE, "type" => ""),
					array("columns" => "lastauth", "unique" => FALSE, "type" => ""),
					array("columns" => "authkey", "unique" => FALSE, "type" => ""),
					array("columns" => "orgunit", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_memberaudit",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("member", "INT(11)", "DEFAULT NULL"),
					DefineTableField("audittime", "DATETIME", "DEFAULT NULL"),
					DefineTableField("remoteip", "VARCHAR(15)", "DEFAULT NULL"),
					DefineTableField("realip", "VARCHAR(15)", "DEFAULT NULL"),
					DefineTableField("audit", "TEXT", "")
				),
				"indexes" => array(
					array("columns" => "member, audittime", "unique" => FALSE, "type" => ""),
					array("columns" => "remoteip", "unique" => FALSE, "type" => ""),
					array("columns" => "realip", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_groups",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(50)", "DEFAULT NULL")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_membergroup",
				"columns" => array(
					DefineTableField("memberid", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("groupid", "INT(11)", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(
					array("columns" => "memberid", "unique" => FALSE, "type" => ""),
					array("columns" => "groupid", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "memberid, groupid",
				"engine" => "MyISAM"
			)
		);
	}

	function GetCustomTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_customtable",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("tname", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("detailfor", "BIGINT(20)", "DEFAULT '0'"),
					DefineTableField("hasdisporder", "TINYINT(4)", "DEFAULT NULL"),
					DefineTableField("navname", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("selfref", "VARCHAR(30)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "detailfor", "unique" => FALSE, "type" => ""),
					array("columns" => "disporder", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_customfield",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("tableid", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("cname", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("inputspec", "TEXT", ""),
					DefineTableField("caption", "TEXT", ""),
					DefineTableField("indexed", "VARCHAR(1)", "DEFAULT NULL"),
					DefineTableField("gridheader", "VARCHAR(30)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetHelpTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_help",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("parent", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("authlevel", "TINYINT(4)", "DEFAULT NULL"),
					DefineTableField("title", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("body", "TEXT", ""),
					DefineTableField("plain", "TEXT", "")
				),
				"indexes" => array(
					array("columns" => "authlevel", "unique" => FALSE, "type" => ""),
					array("columns" => "title", "unique" => FALSE, "type" => ""),
					array("columns" => "title, plain", "unique" => FALSE, "type" => "FULLTEXT")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

$tables = array(
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
  KEY `parent` (`parent`))",
	'zcm_draft'=>"CREATE TABLE `zcm_draft` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `saved` datetime default NULL,
  `form` varchar(80) default NULL,
  `json` longtext,
  `keeper` tinyint(4) default '0',
  UNIQUE KEY `id` (`id`),
  KEY `form` (`form`),
  KEY `saved` (`saved`))",
	'zcm_tracking'=>"CREATE TABLE `zcm_tracking` (
  `id` varchar(23) default NULL,
  `created` datetime default NULL,
  `lastload` datetime default NULL,
  `member` bigint(20) default NULL,
  `addr` varchar(15) default NULL,
  `tag` varchar(30) default NULL,
  `referrer` text,
  `ua` text,
  UNIQUE KEY `id` (`id`),
  KEY `member` (`member`),
  KEY `tag` (`tag`),
  KEY `created` (`created`))",
	'zcm_pageview'=>"CREATE TABLE `zcm_pageview` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `trackingid` varchar(23) default NULL,
  `pageid` bigint(20) default NULL,
  `orphan` tinyint(4) default NULL,
  `viewtime` datetime default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `pageid` (`pageid`),
  KEY `orphan` (`orphan`),
  KEY `trackingid` (`trackingid`),
  KEY `viewtime` (`viewtime`))",
	'zcm_sitepage'=>"CREATE TABLE `zcm_sitepage` (
  `id` bigint(20) NOT NULL auto_increment,
  `disporder` bigint(20) default NULL,
  `linktext` varchar(40) default NULL,
  `parent` bigint(20) default '0',
  `retire` datetime default NULL,
  `golive` datetime default NULL,
  `softlaunch` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `disporder` (`disporder`),
  KEY `parent` (`parent`),
  KEY `retire` (`retire`),
  KEY `parent_2` (`parent`,`linktext`))",
	'zcm_sitepageplugin'=>"CREATE TABLE `zcm_sitepageplugin` (
  `id` bigint(20) NOT NULL auto_increment,
  `zcm_sitepage` bigint(20) default NULL,
  `disporder` bigint(20) default NULL,
  `plugin` text,
  `align` varchar(6) default NULL,
  PRIMARY KEY  (`id`),
  KEY `zcm_sitepage` (`zcm_sitepage`),
  KEY `disporder` (`disporder`))",
	'zcm_template'=>"CREATE TABLE `zcm_template` (
  `id` bigint(20) NOT NULL auto_increment,
  `name` varchar(30) default NULL,
  `path` varchar(200) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`))",
	'zcm_pagetext'=>"CREATE TABLE `zcm_pagetext` (
  `id` bigint(20) NOT NULL auto_increment,
  `sitepage` bigint(20) NOT NULL default '0',
  `tag` varchar(35) NOT NULL default '',
  `body` longtext,
  PRIMARY KEY  (`id`),
  KEY `sitepage` (`sitepage`))",
	'zcm_templatetext'=>"CREATE TABLE `zcm_templatetext` (
  `id` bigint(20) NOT NULL auto_increment,
  `template` bigint(20) NOT NULL default 1,
  `tag` varchar(35) NOT NULL default '',
  `inputspec` varchar(100) NOT NULL default 'html.600.400',
  KEY `template` (`template`),
  KEY `tag` (`tag`),
  PRIMARY KEY  (`id`))",
	'zcm_sitepageredirect'=>"CREATE TABLE `zcm_sitepageredirect` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `sitepage` bigint(20) default NULL,
  `parent` bigint(20) default NULL,
  `linkurl` varchar(40) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `sitepage` (`sitepage`),
  KEY `parent` (`parent`))"
  );
?>
