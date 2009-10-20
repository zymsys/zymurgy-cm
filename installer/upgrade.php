<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Zymurgy:CM - Content Management</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<base href="http://<?=$_SERVER['HTTP_HOST']?>/zymurgy/">
/**
 * 
 * @package Zymurgy
 * @subpackage installer
 */
<style type="text/css">
body {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size:small;
}
</style>
</head>
<body>
<?
// ini_set("display_errors", 1);
ob_start();
require_once("../cmo.php");
require_once('../config/config.php');
include('upgradelib.php');
include('tables.php');

UpdateStatus("Connecting to database...");

// mysql_connect($ZymurgyConfig['mysqlhost'],$ZymurgyConfig['mysqluser'],$ZymurgyConfig['mysqlpass']);
// mysql_select_db($ZymurgyConfig['mysqldb']);

UpdateStatus("-- Connection successful.");
UpdateStatus("");

// ----------

UpdateStatus("Updating table definitions");

RenameOldTables();

ProcessTableDefinitions(
	$baseTableDefinitions);

// ZK: Deprecated
// $newtables = CreateMissingTables();

UpdateStatus("-- Table definitions updated");
UpdateStatus("");

// ----------

UpdateStatus("Configuring Simple Template");

mysql_query("insert ignore into zcm_template (id, name, path) values ('1', 'Simple','/zymurgy/templates/simple.php')");
mysql_query("insert ignore into zcm_template (id, name,path) values ('2', 'URL Link','/zymurgy/templates/link.php')");
mysql_query("insert ignore into zcm_templatetext (id,template,tag,inputspec) values ('1','1','Body','html.600.400')");
mysql_query("insert ignore into zcm_templatetext (id,template,tag,inputspec) values ('2','2','Link URL','input.60.255')");

UpdateStatus("-- Simple template configured");
UpdateStatus("");

// ----------

UpdateStatus("-- Fixing bogus template visibility");
mysql_query("update zcm_sitepage set retire=NULL, golive=NULL, softlaunch=NULL where (retire = golive) and (golive = softlaunch)");
UpdateStatus("-- Bogus template visibility fixed");
UpdateStatus("");

// ----------

UpdateStatus("Configuring Zymurgy:CM Membership Groups");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Zymurgy:CM - User', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - User' )";
mysql_query($sql) or die("Could not add Zymurgy:CM - User membership group: ".mysql_error().", $sql");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Zymurgy:CM - Administrator', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - Administrator' )";
mysql_query($sql) or die("Could not add Zymurgy:CM - Administrator membership group: ".mysql_error().", $sql");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Zymurgy:CM - Webmaster', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - Webmaster' )";
mysql_query($sql) or die("Could not add Zymurgy:CM - Webmaster membership group: ".mysql_error().", $sql");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Registered User', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Registered User' )";
mysql_query($sql) or die("Could not add Registered User membership group: ".mysql_error().", $sql");

if(mysql_affected_rows() > 0)
{
	$sql = "INSERT INTO `zcm_membergroup` ( `memberid`, `groupid` ) SELECT `id`, '".
		mysql_escape_string(mysql_insert_id()).
		"' FROM `zcm_member`";
	mysql_query($sql) or die("Could not add members to Registered User membership group: ".mysql_error().", $sql");
}

UpdateStatus("-- Groups configured.");
UpdateStatus("");

// ----------

UpdateStatus("Migrating users from zcm_password to zcm_member");

$userSQL = "SELECT `username`, `password`, `email`, `fullname`, `admin` ".
	"FROM `zcm_passwd`";
$userRI = mysql_query($userSQL)
	or die("Could not retrieve user list: ".mysql_error().", $userSQL");

UpdateStatus("-- ".mysql_num_rows($userRI)." users found");

while(($userRow = mysql_fetch_array($userRI)) !== FALSE)
{
	UpdateStatus("---- ".$userRow["username"]);

	$memberSQL = "SELECT `id` FROM `zcm_member` WHERE `email` = '".
		mysql_escape_string($userRow["email"]).
		"' OR `username` = '".
		mysql_escape_string($userRow["username"]).
		"'";
	$memberRI = mysql_query($memberSQL)
		or die("Could not verify member information: ".mysql_error().", $memberSQL");

	$memberID = 0;

	if(mysql_num_rows($memberRI) > 0)
	{
		// echo("---- Found existing member (".$memberRow["id"].")<br>");

		$memberRow = mysql_fetch_array($memberRI);
		$memberID = $memberRow["id"];
	}
	else
	{
		// echo("---- Member not found. Adding (new ID: ");

		$insertSQL = "INSERT INTO `zcm_member` ( `username`, `email`, `password`, `fullname` ) VALUES ( '".
			mysql_escape_string($userRow["username"]).
			"', '".
			mysql_escape_string($userRow["email"]).
			"', '".
			mysql_escape_string($userRow["password"]).
			"', '".
			mysql_escape_string($userRow["fullname"]).
			"' )";
		mysql_query($insertSQL)
			or die("Could not migrate user: ".mysql_error().", $insertSQL");
		$memberID = mysql_insert_id();

		// echo($memberID.")<br>");
	}

	if($userRow["admin"] >= 2)
	{
		$insertSQL = "INSERT IGNORE INTO `zcm_membergroup` ( `memberid`, `groupid` ) SELECT '".
			mysql_escape_string($memberID).
			"', `id` FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - Webmaster'";
		mysql_query($insertSQL)
			or die("Could not make ".$memberRow["username"]." a webmaster: ".mysql_error().", $insertSQL");
	}

	if($userRow["admin"] >= 1)
	{
		$insertSQL = "INSERT IGNORE INTO `zcm_membergroup` ( `memberid`, `groupid` ) SELECT '".
			mysql_escape_string($memberID).
			"', `id` FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - Administrator'";
		mysql_query($insertSQL)
			or die("Could not make ".$memberRow["username"]." an administrator: ".mysql_error().", $insertSQL");
	}

	if($userRow["admin"] >= 0)
	{
		$insertSQL = "INSERT IGNORE INTO `zcm_membergroup` ( `memberid`, `groupid` ) SELECT '".
			mysql_escape_string($memberID).
			"', `id` FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - User'";
		mysql_query($insertSQL)
			or die("Could not make ".$memberRow["username"]." a user: ".mysql_error().", $insertSQL");
	}
}

UpdateStatus("-- Users migrated.");
UpdateStatus("");

// ----------

UpdateStatus("Migrating members from e-mair to username-based logins");

$sql = "UPDATE `zcm_member` SET `username` = `email` WHERE `username` = '' OR `username` IS NULL";
mysql_query($sql)
	or die("Could not migrate memberships: ".mysql_error().", $sql");

UpdateStatus("-- ".mysql_affected_rows()." member(s) migrated.");
UpdateStatus("");

// ----------

UpdateStatus("Updating plugin configuration system");

UpdatePluginConfig();

UpdateStatus("-- Done");
UpdateStatus("");

// ----------

UpdateStatus("Updating site text category information");-

//Check if faulty uncategorized content category exists and fix it.
$sql = "select id from zcm_stcategory where name='Uncategorized Content'";
$ri = mysql_query($sql) or die("Unable to find category ($sql): ".mysql_error());
if (mysql_num_rows($ri)>0)
{
	$id = 0 + mysql_result($ri,0,0);
	if ($id > 0)
	{
		$sql = "update zcm_sitetext set category=0 where category=$id";
		mysql_query($sql) or die("Unable to correct default category ($sql): ".mysql_error());
		$sql = "update zcm_stcategory set id=0 where id=$id";
		mysql_query($sql) or die("Unable to reset default category id ($sql): ".mysql_error());
	}
}

//Make sure category 0 exists for uncategorized content
$sql = "select * from zcm_stcategory where id=0";
$ri = mysql_query($sql) or die("Unable to load default category ($sql): ".mysql_error());
if (mysql_num_rows($ri)==0)
{
	$sql = "insert into zcm_stcategory (id,name) values (0,'Uncategorized Content')";
	mysql_query($sql) or die("Unable to create default category ($sql): ".mysql_error());
	$notzero = mysql_insert_id();
	$sql = "update zcm_stcategory set id=0 where id=$notzero";
	mysql_query($sql) or die("Unable to set default category id ($sql): ".mysql_error());
}

UpdateStatus("-- Done");
UpdateStatus("");

// ----------

UpdateStatus("Updating page links");

//Check for no old linkurl in zcm_sitepage, and populate it if required
require_once('../sitenav.php');
$spu = array();
$ri = mysql_query("select id,linktext from zcm_sitepage where linkurl is NULL");
while (($row = mysql_fetch_array($ri))!==false)
{
	$spu[$row['id']] = $row['linktext'];
}
mysql_free_result($ri);
foreach($spu as $id=>$linktext)
{
	mysql_query("update zcm_sitepage set linkurl='".
		mysql_escape_string(ZymurgySiteNav::linktext2linkpart($linktext))."' where id=$id");
}

UpdateStatus("-- Done");
UpdateStatus("");

// ----------

UpdateStatus("Migrating page content from zcm_sitepage to zcm_pagetext");

//Check for old page bodies, and relocate them to new page bodies.
$sitePageBodyRI = mysql_query("show columns from zcm_sitepage like 'body'");
if(mysql_num_rows($sitePageBodyRI) > 0)
{
	mysql_query("insert ignore into zcm_templatetext (id,template,tag) values (1,1,'Body')");

	$sql = "INSERT INTO `zcm_pagetext` ( `sitepage`, `tag`, `body` ) SELECT `id`, 'Body', ".
		"`body` FROM `zcm_sitepage` WHERE NOT EXISTS(SELECT 1 FROM `zcm_pagetext` WHERE ".
		"`zcm_pagetext`.`sitepage` = `zcm_sitepage`.`id` AND `zcm_pagetext`.`tag` = 'Body')";
	mysql_query($sql)
		or die("Could not migrate page content: ".mysql_query($sql).", $sql");

	mysql_query("alter table zcm_sitepage drop body");
}

UpdateStatus("-- Done");
UpdateStatus("");

// ----------

UpdateStatus("Updating Zymurgy:CM navigation");

function SetNavigationFeature($id, $disporder, $label, $url)
{
	$sql = "INSERT INTO `zcm_features` ( `id`, `disporder`, `label`, `url` ) VALUES ( '".
		mysql_escape_string($id).
		"', '".
		mysql_escape_string($disporder).
		"', '".
		mysql_escape_string($label).
		"', '".
		mysql_escape_string($url).
		"') ON DUPLICATE KEY UPDATE `disporder` = '".
		mysql_escape_string($disporder).
		"', `label` = '".
		mysql_escape_string($label).
		"', `url` = '".
		mysql_escape_string($url).
		"'";

	mysql_query($sql)
		or die("Could not update $label feature: ".mysql_error().", $sql");
}

SetNavigationFeature(21,  1, "--- Content ---", "");
SetNavigationFeature(2,   2, "- Pages", "sitepage.php");
SetNavigationFeature(1,   3, "- Simple Content", "sitetext.php");
SetNavigationFeature(3,   4, "- SEO", "headtext.php");
SetNavigationFeature(22,  5, "--- Admin ---", "");
SetNavigationFeature(4,   6, "- Members", "editmember.php");
SetNavigationFeature(5,   7, "- Membership Groups", "editmember.php?action=list_groups");
SetNavigationFeature(26,  8, "- Access Control Lists", "acl.php");
SetNavigationFeature(10,  9, "- Help Editor", "helpeditor.php");
SetNavigationFeature(23, 10, "--- Webmaster ---", "");
SetNavigationFeature(11, 11, "- Navigation", "navigation.php");
SetNavigationFeature(12, 12, "- Appearance Items", "configconfig.php");
SetNavigationFeature(28, 13, "- Flavours", "flavours.php");
SetNavigationFeature(13, 14, "- Plugin Management", "plugin.php");
SetNavigationFeature(14, 15, "- Custom Tables", "customtable.php");
SetNavigationFeature(15, 16, "- Custom Code Generator", "mkcustom.php");
SetNavigationFeature(16, 17, "- Template Manager", "templatemgr.php");
SetNavigationFeature(27, 18, "- Zymurgy:CM Config", "zcmconfig.php");
SetNavigationFeature(24, 19, "--- Media Files ---", "");
SetNavigationFeature(6,  20, "- Media Files", "media.php");
SetNavigationFeature(7,  21, "- Media Packages", "media.php?action=list_media_packages");
SetNavigationFeature(8,  22, "- Media Package Types", "media.php?action=list_media_package_types");
SetNavigationFeature(9,  23, "- Media Relations", "media.php?action=list_relations");
SetNavigationFeature(25, 24, "--- General ---", "");
SetNavigationFeature(17, 25, "- Appearance", "configuration.php");
SetNavigationFeature(18, 26, "- Profile", "profile.php");
SetNavigationFeature(19, 27, "- Help", "help.php");
SetNavigationFeature(20, 28, "- Logout", "logout.php");

UpdateStatus("-- Zymurgy:CM Feature list configured");

//Make sure we start with the default navigation structure
$sql = "select count(*) from zcm_nav";
$ri = mysql_query($sql) or die("Can't get navigation count ($sql): ".mysql_error());
$count = mysql_result($ri,0,0);
if ($count==0)
{
	$sql = "INSERT INTO `zcm_nav` (id,disporder,parent,authlevel,navname,navtype,navto) VALUES
		(1,   1, 0, 0, 'Content',              'Sub-Menu',           ''),
		(3,   3, 0, 1, 'Admin',                'Sub-Menu',           ''),
		(4,   4, 0, 2, 'Webmaster',            'Sub-Menu',           ''),
		(5,   7, 0, 0, 'Profile',              'Zymurgy:CM Feature', '18'),
		(6,  19, 0, 0, 'Help',                 'Zymurgy:CM Feature', '19'),
		(7,  20, 0, 0, 'Logout',               'Zymurgy:CM Feature', '20'),
		(8,   8, 1, 0, 'Simple Content',       'Zymurgy:CM Feature', '1'),
		(9,   9, 1, 0, 'SEO',                  'Zymurgy:CM Feature', '3'),
		(21, 12, 3, 1, 'Members',              'Zymurgy:CM Feature', '4'),
		(22, 13, 3, 1, 'Membership Groups',    'Zymurgy:CM Feature', '5'),
		(26, 14, 3, 1, 'Access Control Lists', 'Zymurgy:CM Feature', '26'),
		(12, 15, 3, 1, 'Help Editor',          'Zymurgy:CM Feature', '10'),
		(13, 13, 4, 2, 'Navigation',           'Zymurgy:CM Feature', '11'),
		(14, 14, 4, 2, 'Appearance Items',     'Zymurgy:CM Feature', '12'),
		(28, 15, 4, 2, 'Flavours',             'Zymurgy:CM Feature', '28'),
		(27, 16, 4, 2, 'Zymurgy:CM Config',    'Zymurgy:CM Feature', '27'),
		(15, 17, 4, 2, 'Plugin Management',    'Zymurgy:CM Feature', '13'),
		(16, 18, 4, 2, 'Custom Tables',        'Zymurgy:CM Feature', '14'),
		(17, 19, 4, 2, 'Custom Code',          'Zymurgy:CM Feature', '15'),
		(18, 20, 4, 2, 'Page Templates',       'Zymurgy:CM Feature', '16'),
		(19,  5, 0, 0, 'Appearance',           'Zymurgy:CM Feature', '17'),
		(20,  6, 1, 0, 'Pages',                'Zymurgy:CM Feature', '2')
		;";
	$ri = mysql_query($sql) or die ("Can't create default navigation ($sql): ".mysql_error());

	UpdateStatus("-- Navigation menu set to initial values");
}

UpdateStatus("-- Done");
UpdateStatus("");

// ----------
// ZK: 2009.03.24
//
// Install/upgrade the media file component.
// ----------

UpdateStatus("Configuring media file support");

require_once("../cmo.php");
require_once("../include/media.php");

// ZK: Because of the new table definition system, the upgrade script for
// the media file components can now be run every time the base upgrade
// script is run.
MediaFileInstaller::Upgrade(0, 1);

// $installedVersion = MediaFileInstaller::InstalledVersion();
// $targetVersion = MediaFileInstaller::Version();

// if($installedVersion < $targetVersion)
// {
// 	echo("-- Installing/upgrading from version $installedVersion to version $targetVersion<br>");
//
//	MediaFileInstaller::Upgrade($installedVersion, $targetVersion);
// }
// else
// {
//	echo("-- No install/upgrade required<br>");
// }

UpdateStatus("-- Done");
UpdateStatus("");

// ----------
// ZK: 2008.11.18
//
// Traverse the plugins folder and install any items in there.
// All plugins are now installed automatically by the upgrader, and it's up to the
// web developer to remove unwanted items from the menu using the Navigation
// configuration system.
// ----------

UpdateStatus("Configuring plugins");

require_once("../cmo.php");
require_once('../PluginBase.php');

$plugins = array();

$di = opendir('../plugins');
while (($entry = readdir($di)) !== false)
{
	$reasons = array();

	if (is_dir("../plugins/$entry")) $reasons[] = "is a directory";
	if (strrpos("../plugins/$entry", ".php") === false) $reasons[] = "not a php file";
	if (strpos("../plugins/$entry", ".#") !== false) $reasons[] = "CVS file";

	if (count($reasons) == 0)	// entry is not a prior version from CVS
	{
		list($name,$extension) = explode('.',$entry);
		$plugins[$name] = 'N'; //Start out as (N)ew plugin.

		UpdateStatus("-- $entry: Including");
		require_once("../plugins/$entry");
	}
	else
	{
		UpdateStatus("-- $entry: Skipping (".implode(', ',$reasons).")");
	}
}
closedir($di);

$ri = Zymurgy::$db->query('select * from zcm_plugin');
while (($row = Zymurgy::$db->fetch_array($ri))!==false)
{
	if (array_key_exists($row['name'],$plugins))
	{
		if ($row['enabled'] == 1)
			$plugins[$row['name']] = 'E'; // (E)nabled
		else
			$plugins[$row['name']] = 'D'; // (D)isabled
	}
	else
	{
		$plugins[$row['name']] = 'R'; // (R)emoved
	}
}
Zymurgy::$db->free_result($ri);

foreach ($plugins as $source=>$state)
{
	switch($state)
	{
		case 'N': // (N)ew
			echo("-- Adding $source<br>");

			ExecuteAdd($source);
			break;
	}
}

UpdateStatus("Done");
UpdateStatus("");

// ==========
// Fail if the hacked version of JSCalendar is not available
// ==========

UpdateStatus("Testing for extended third-party components...");

if(!file_exists("../jscalendar/calendar.php"))
{
	die("-- Extended JSCalendar object not available. Please upgrade through the installer before continuing.<br>");
}
else
{
	require_once("../jscalendar/calendar.php");

	$cal = new DHTML_Calendar(
		"/zymurgy/jscalendar/",
		'en',
		'calendar-win2k-2',
		false);

	if(!method_exists($cal, "SetFieldPrefix"))
	{
		die("-- Extended JSCalendar object not available. Please upgrade through the installer before continuing.<br>");
	}
}

UpdateStatus("Done.");
UpdateStatus("");

// ----------

UpdateStatus("Upgrade complete. Please wait while we forward you to the home page, or <a href=\"index.php\">click here</a>.");

// ----------

if(!isset($_GET["debug"]))
{
	echo("\n\n<script type=\"text/javascript\">\n");
	echo("window.location.href = '/zymurgy/index.php';\n");
	echo("</script>");
	// header('Location: ../index.php');
}

	function  ExecuteAdd($source)
	{
		global $plugins;

		//Get an instance of the plugin class
		echo("---- Getting instance of $source plugin<br>");
		$factory = "{$source}Factory";
		$plugin = $factory();

		//Create a configuration group for this plugin
		Zymurgy::$db->run("insert into zcm_pluginconfiggroup (name) values ('".
			Zymurgy::$db->escape_string($plugin->GetTitle()).": Default')");
		$pcg = Zymurgy::$db->insert_id();
		
		//Add plugin to the plugin table
		echo("---- Adding plugin definition to database<br>");
		Zymurgy::$db->query("insert into zcm_plugin(title,name,uninstallsql,enabled,defaultconfig) values ('".
			Zymurgy::$db->escape_string($plugin->GetTitle())."','".
			Zymurgy::$db->escape_string($source)."','".
			Zymurgy::$db->escape_string($plugin->GetUninstallSQL())."',1,$pcg)");
		$id = Zymurgy::$db->insert_id();
		//	$id = 7;

		//Add default configuration
		echo("---- Retrieving default plugin configuration<br>");
		$defconf = $plugin->GetDefaultConfig();

		//	print_r($defconf);
		//	echo("<br><br><br>");
		//	die();

		foreach ($defconf as $cv)
		{
			//echo("cv: ");
			//print_r($cv);
			//echo("<br>");

			$key = $cv->key;
			$value = $cv->value;

			// echo($key.": ".$value."<br>");
			echo("------ $key<br>");

			$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) values ($pcg, '".
				Zymurgy::$db->escape_string($key)."', '".
				Zymurgy::$db->escape_string($value)."')";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
				die("Error adding plugin config: ".Zymurgy::$db->error()."<br>$sql");

			// echo(htmlentities($sql)."<br>");
		}

		// die();

		echo("---- Initializing plugin<br>");

		$plugin->Initialize();
	}

	function UpdateStatus($msg)
	{
		echo $msg."<br>\n";
		ob_flush();
	}

	function UpdatePluginConfig()
	{
		$sql = "SHOW TABLES LIKE 'zcm_pluginconfig'";
		$systemRI = Zymurgy::$db->query($sql)
			or die("Could not determine plugin configuration system: ".Zymurgy::$db->error().", $sql");

		if(Zymurgy::$db->num_rows($systemRI) > 0)
		{
			UpdateStatus("-- Renaming plugin configuration items");

			RenamePluginKeys('Form',array(
				'Results Email From'=>'Email Form Results To Address',
				'Email From'=>'Email Form Results From Address',
				'Email Subject'=>'Email Form Results Subject'
			));

			$sql = "SELECT `plugin`, '0' as `instance` FROM `zcm_plugininstance` UNION SELECT `plugin`, `id` AS `instance` FROM `zcm_plugininstance`";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not get old plugin config: ".Zymurgy::$db->error().", $sql");

			$previousPlugin = -1;
			$previousInstance = -1;
			$configGroup = -1;

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				if($row["plugin"] <> $previousPlugin || $row["instance"] <> $previousInstance)
				{
					$sql = "INSERT INTO `zcm_pluginconfiggroup` ( `name` ) SELECT CONCAT_WS(': ', `zcm_plugin`.`name`, COALESCE(`zcm_plugininstance`.`name`, 'Default')) FROM `zcm_plugin` LEFT JOIN `zcm_plugininstance` ON `zcm_plugin`.`id` = `zcm_plugininstance`.`plugin` AND `zcm_plugininstance`.`id` = '".
						Zymurgy::$db->escape_string($row["instance"]).
						"' WHERE `zcm_plugin`.`id` = '".
						Zymurgy::$db->escape_string($row["plugin"]).
						"' LIMIT 0, 1";
					Zymurgy::$db->query($sql)
						or die("Could not create new config group: ".Zymurgy::$db->error().", $sql");

					$configGroup = Zymurgy::$db->insert_id();
					$previousPlugin = $row["plugin"];
					$previousInstance = $row["instance"];

					if($row["instance"] <= 0)
					{
						$sql = "UPDATE `zcm_plugin` SET `defaultconfig` = '".
							Zymurgy::$db->escape_string($configGroup).
							"' WHERE `id` = '".
							Zymurgy::$db->escape_string($row["plugin"]).
							"'";
						Zymurgy::$db->query($sql)
							or die("Could not set default config for plugin: ".Zymurgy::$db->error().", $sql");
					}
					else
					{
						$sql = "UPDATE `zcm_plugininstance` SET `config` = '".
							Zymurgy::$db->escape_string($configGroup).
							"' WHERE `id` = '".
							Zymurgy::$db->escape_string($row["instance"]).
							"'";
						Zymurgy::$db->query($sql)
							or die("Could not set default config for instance: ".Zymurgy::$db->error().", $sql");
					}

//					UpdateStatus($sql);
//					UpdateStatus("-- ".$configGroup);
				}

				$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) SELECT '".
					Zymurgy::$db->escape_string($configGroup).
					"', `key`, `value` FROM `zcm_pluginconfig` WHERE `plugin` = '".
					Zymurgy::$db->escape_string($row["plugin"]).
					"' AND `instance` = '".
					Zymurgy::$db->escape_string($row["instance"]).
					"'";
				Zymurgy::$db->query($sql)
					or die("Could not create new config item: ".Zymurgy::$db->error().", $sql");
			}

			$sql = "RENAME TABLE `zcm_pluginconfig` TO `zcm_pluginconfig_old`";
			Zymurgy::$db->query($sql)
				or die("Could not drop old config table: ".Zymurgy::$db->error().", $sql");
		}

		Zymurgy::$db->free_result($systemRI);
	}
?>
</body>
</html>
