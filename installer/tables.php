<?
/**
 *
 * @package Zymurgy
 * @subpackage installer
 */
	/* array("columns" => "export", "unique" => false, "type" => "") */

	$baseTableDefinitions = array();
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetFlavourDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetAuthenticationTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetConfigurationTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetSimpleContentTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetPluginTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetMembershipTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetCustomTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetHelpTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetNavigationTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetVersionControlTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetVisitTrackingTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetACLTableDefinitions());
	$baseTableDefinitions = array_merge($baseTableDefinitions, GetPageTableDefinitions());

	function GetFlavourDefinitions()
	{
		return array(
			array(
				"name" => "zcm_flavour",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "INT(11)", "NOT NULL"),
					DefineTableField("code", "VARCHAR(50)", "NOT NULL"),
					DefineTableField("label", "VARCHAR(200)", "NOT NULL"),
					DefineTableField("templateprovider","BIGINT","Default '0'"),
					DefineTableField("contentprovider","BIGINT","Default '0'")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => FALSE, "type" => ""),
					array("columns" => "code", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_flavourtext",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("default", "LONGTEXT", "NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_flavourtextitem",
				"columns" => array(
					DefineTableField("zcm_flavourtext", "BIGINT", "NOT NULL"),
					DefineTableField("flavour", "BIGINT", "NOT NULL"),
					DefineTableField("text", "LONGTEXT", "NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "zcm_flavourtext, flavour",
				"engine" => "MyISAM"
			)
		);
	}

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
					DefineTableField("value", "LONGTEXT", "NOT NULL DEFAULT ''"),
					DefineTableField("disporder", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("inputspec", "TEXT", "NOT NULL")
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
					DefineTableField("category", "BIGINT(20)", "DEFAULT '0'"),
					DefineTableField("acl", "BIGINT(20)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "tag", "unique" => FALSE, "type" => ""),
					array("columns" => "category", "unique" => FALSE, "type" => ""),
					array("columns" => "plainbody", "unique" => FALSE, "type" => "FULLTEXT")
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
					DefineTableField("uninstallsql", "TEXT", "NOT NULL"),
					DefineTableField("defaultconfig", "INT(11)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "enabled", "unique" => FALSE, "type" => ""),
					array("columns" => "name", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_pluginconfiggroup",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(50)", "NOT NULL DEFAULT ''")
				),
				"indexes" => array(),
				"primarykey" => "`id`",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_pluginconfigitem",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("config", "INT(11)", "NOT NULL"),
					DefineTableField("key", "VARCHAR(50)", "NOT NULL DEFAULT ''"),
					DefineTableField("value", "TEXT", "NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "`id`",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_plugininstance",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("plugin", "INT(11)", "NOT NULL DEFAULT '0'"),
					DefineTableField("name", "VARCHAR(50)", "NOT NULL DEFAULT ''"),
					DefineTableField("private", "TINYINT(4)", "DEFAULT NULL"),
					DefineTableField("config", "INT(11)", "DEFAULT NULL")
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
					DefineTableField("username", "VARCHAR(80)", "DEFAULT NULL"),
					DefineTableField("email", "VARCHAR(80)", "DEFAULT NULL"),
					DefineTableField("password", "VARCHAR(32)", "DEFAULT NULL"),
					DefineTableField("fullname", "VARCHAR(100)", "NOT NULL DEFAULT ''"),
					DefineTableField("regtime", "DATETIME", "DEFAULT NULL"),
					DefineTableField("lastauth", "DATETIME", "DEFAULT NULL"),
					DefineTableField("formdata", "INT(11)", "DEFAULT NULL"),
					DefineTableField("authkey", "VARCHAR(32)", "DEFAULT NULL"),
					DefineTableField("orgunit", "INT(11)", "DEFAULT NULL"),
					DefineTableField("mpkey", "VARCHAR(40)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "username", "unique" => TRUE, "type" => ""),
					array("columns" => "username, password", "unique" => FALSE, "type" => ""),
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
					DefineTableField("name", "VARCHAR(50)", "NOT NULL"),
					DefineTableField("builtin", "INTEGER", "NOT NULL DEFAULT '0'")
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
					DefineTableField("detailforfield", "VARCHAR(30)", "DEFAULT ''"),
					DefineTableField("hasdisporder", "TINYINT(4)", "DEFAULT NULL"),
					DefineTableField("ismember", "TINYINT", "DEFAULT NULL"),
					DefineTableField("navname", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("selfref", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("idfieldname", "VARCHAR(30)", "DEFAULT 'id'")
				),
				"indexes" => array(
					array("columns" => "detailfor", "unique" => FALSE, "type" => ""),
					array("columns" => "disporder", "unique" => FALSE, "type" => ""),
					array("columns" => "navname", "unique" => FALSE, "type" => "")
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
			),
			array(
				"name" => "zcm_helpalso",
				"columns" => array(
					DefineTableField("help", "BIGINT(20)", "NOT NULL DEFAULT '0'"),
					DefineTableField("seealso", "BIGINT(20)", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(),
				"primarykey" => "help, seealso",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_helpindex",
				"columns" => array(
					DefineTableField("phrase", "BIGINT(20)", "NOT NULL DEFAULT '0'"),
					DefineTableField("help", "BIGINT(20)", "NOT NULL DEFAULT '0'")
				),
				"indexes" => array(),
				"primarykey" => "phrase, help",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_helpindexphrase",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("phrase", "VARCHAR(200)", "DEFAULT NULL")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetNavigationTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_nav",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("parent", "BIGINT(20)", "DEFAULT '0'"),
					DefineTableField("navname", "VARCHAR(60)", "DEFAULT NULL"),
					DefineTableField("navtype", "ENUM('URL', 'Custom Table', 'Plugin', 'Sub-Menu', 'Zymurgy:CM Feature')", "DEFAULT NULL"),
					DefineTableField("navto", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("authlevel", "INT(11)", "NOT NULL DEFAULT 0")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => FALSE, "type" => ""),
					array("columns" => "parent", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_features",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "BIGINT(20)", "NOT NULL"),
					DefineTableField("label", "VARCHAR(200)", "NOT NULL"),
					DefineTableField("url", "VARCHAR(200)", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetVersionControlTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_draft",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("saved", "DATETIME", "DEFAULT NULL"),
					DefineTableField("form", "VARCHAR(80)", "DEFAULT NULL"),
					DefineTableField("json", "LONGTEXT", ""),
					DefineTableField("keeper", "TINYINT(4)", "DEFAULT '0'")
				),
				"indexes" => array(
					array("columns" => "form", "unique" => FALSE, "type" => ""),
					array("columns" => "saved", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetVisitTrackingTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_tracking",
				"columns" => array(
					DefineTableField("id", "VARCHAR(23)", "DEFAULT NULL"),
					DefineTableField("created", "DATETIME", "DEFAULT NULL"),
					DefineTableField("lastload", "DATETIME", "DEFAULT NULL"),
					DefineTableField("member", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("addr", "VARCHAR(15)", "DEFAULT NULL"),
					DefineTableField("tag", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("referrer", "TEXT", ""),
					DefineTableField("ua", "TEXT", "")
				),
				"indexes" => array(
					array("columns" => "member", "unique" => FALSE, "type" => ""),
					array("columns" => "tag", "unique" => FALSE, "type" => ""),
					array("columns" => "created", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_pageview",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("trackingid", "VARCHAR(23)", "DEFAULT NULL"),
					DefineTableField("pageid", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("orphan", "TINYINT(4)", "DEFAULT NULL"),
					DefineTableField("viewtime", "DATETIME", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "pageid", "unique" => FALSE, "type" => ""),
					array("columns" => "orphan", "unique" => FALSE, "type" => ""),
					array("columns" => "trackingid", "unique" => FALSE, "type" => ""),
					array("columns" => "viewtime", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_sitepageview",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("trackingid", "VARCHAR(23)", "DEFAULT NULL"),
					DefineTableField("sitepageid", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("path", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("orphan", "TINYINT(4)", "DEFAULT NULL"),
					DefineTableField("viewtime", "DATETIME", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "sitepageid", "unique" => FALSE, "type" => ""),
					array("columns" => "orphan", "unique" => FALSE, "type" => ""),
					array("columns" => "trackingid", "unique" => FALSE, "type" => ""),
					array("columns" => "viewtime", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetACLTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_acl",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(50)", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "name", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_aclitem",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("zcm_acl", "BIGINT(20)", "NOT NULL"),
					DefineTableField("disporder", "BIGINT(20)", "NOT NULL"),
					DefineTableField("group", "BIGINT(20)", "NOT NULL"),
					DefineTableField("permission", "VARCHAR(30)", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "zcm_acl", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}

	function GetPageTableDefinitions()
	{
		return array(
			array(
				"name" => "zcm_sitepage",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("linktext", "FLAVOURED", ""),
					DefineTableField("linkurl", "FLAVOURED", ""),
					DefineTableField("parent", "BIGINT(20)", "DEFAULT '0'"),
					DefineTableField("retire", "DATETIME", "DEFAULT NULL"),
					DefineTableField("golive", "DATETIME", "DEFAULT NULL"),
					DefineTableField("softlaunch", "DATETIME", "DEFAULT NULL"),
					DefineTableField("template", "BIGINT", "DEFAULT 1"),
					DefineTableField("acl", "BIGINT", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "disporder", "unique" => FALSE, "type" => ""),
					array("columns" => "parent", "unique" => FALSE, "type" => ""),
					array("columns" => "retire", "unique" => FALSE, "type" => ""),
					array("columns" => "parent, linktext", "unique" => FALSE, "type" => ""),
					array("columns" => "template", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_sitepageseo",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("zcm_sitepage", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("description", "FLAVOURED", ""),
					DefineTableField("keywords", "FLAVOURED", ""),
					DefineTableField("title", "FLAVOURED", ""),
					DefineTableField("mtime", "BIGINT(20)", "NOT NULL DEFAULT '0'"),
					DefineTableField("changefreq", "VARCHAR(10)", "DEFAULT 'monthly'"),
					DefineTableField("priority", "TINYINT(4)", "DEFAULT '5'")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_sitepageplugin",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("zcm_sitepage", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("disporder", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("plugin", "TEXT", ""),
					DefineTableField("align", "VARCHAR(6)", "DEFAULT NULL"),
					DefineTableField("acl", "BIGINT(20)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "zcm_sitepage", "unique" => FALSE, "type" => ""),
					array("columns" => "disporder", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_pagetext",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("sitepage", "BIGINT(20)", "DEFAULT '0'"),
					DefineTableField("tag", "VARCHAR(35)", "NOT NULL DEFAULT ''"),
					DefineTableField("body", "LONGTEXT", ""),
					DefineTableField("acl", "BIGINT(20)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "sitepage", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_template",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(30)", "DEFAULT NULL"),
					DefineTableField("path", "FLAVOURED", "")
				),
				"indexes" => array(
					array("columns" => "name", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_templatetext",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("template", "BIGINT(20)", "NOT NULL DEFAULT 1"),
					DefineTableField("tag", "VARCHAR(35)", "NOT NULL DEFAULT ''"),
					DefineTableField("inputspec", "VARCHAR(100)", "NOT NULL DEFAULT 'html.600.400'")
				),
				"indexes" => array(
					array("columns" => "template", "unique" => FALSE, "type" => ""),
					array("columns" => "tag", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_sitepageredirect",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("sitepage", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("parent", "BIGINT(20)", "DEFAULT NULL"),
					DefineTableField("flavour", "BIGINT(20)", "DEFAULT '0'"),
					DefineTableField("linkurl", "VARCHAR(40)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "sitepage", "unique" => FALSE, "type" => ""),
					array("columns" => "parent", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			),
			array(
				"name" => "zcm_quicklink",
				"columns" => array(
					DefineTableField("id", "BIGINT(20)", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("name", "VARCHAR(100)", "DEFAULT NULL"),
					DefineTableField("targeturl", "VARCHAR(100)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "name", "unique" => FALSE, "type" => "")
				),
				"primarykey" => "id",
				"engine" => "MyISAM"
			)
		);
	}
?>
