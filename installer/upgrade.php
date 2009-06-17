<?
// ini_set("display_errors", 1);
ob_start();
require_once("../cmo.php");
require_once('../config/config.php');
include('upgradelib.php');
include('tables.php');

echo("Connecting to database...");

// mysql_connect($ZymurgyConfig['mysqlhost'],$ZymurgyConfig['mysqluser'],$ZymurgyConfig['mysqlpass']);
// mysql_select_db($ZymurgyConfig['mysqldb']);

echo("done.<br>");
echo("Updating table definitions...<br>");

RenameOldTables();

ProcessTableDefinitions(
	$baseTableDefinitions);

// ZK: Deprecated
// $newtables = CreateMissingTables();

echo("done.<br>");

//print_r($newtables); exit;
// if (array_search('zcm_template',$newtables)!==false)
//{
	//Create default templates
	mysql_query("insert ignore into zcm_template (id, name, path) values ('1', 'Simple','/zymurgy/templates/simple.php')");
	//$stid = mysql_insert_id();
	mysql_query("insert ignore into zcm_template (id, name,path) values ('2', 'URL Link','/zymurgy/templates/link.php')");
	//$ulid = mysql_insert_id();
	mysql_query("insert ignore into zcm_templatetext (id,template,tag,inputspec) values ('1','1','Body','html.600.400')");
	mysql_query("insert ignore into zcm_templatetext (id,template,tag,inputspec) values ('2','2','Link URL','input.60.255')");
//}

// ----------

echo("Reconciling Zymurgy:CM membership groups...<br>");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Zymurgy:CM - User', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - User' )";
mysql_query($sql) or die("Could not add Zymurgy:CM - User membership group: ".mysql_error().", $sql");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Zymurgy:CM - Administrator', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - Administrator' )";
mysql_query($sql) or die("Could not add Zymurgy:CM - Administrator membership group: ".mysql_error().", $sql");

$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) SELECT 'Zymurgy:CM - Webmaster', 1 FROM DUAL ".
	"WHERE NOT EXISTS( SELECT 1 FROM `zcm_groups` WHERE `name` = 'Zymurgy:CM - Webmaster' )";
mysql_query($sql) or die("Could not add Zymurgy:CM - Webmaster membership group: ".mysql_error().", $sql");

echo("done.<br>");

// ----------

echo("Migrating users in zcm_passwd to zcm_member...<br>");

$userSQL = "SELECT `username`, `password`, `email`, `fullname`, `admin` ".
	"FROM `zcm_passwd`";
$userRI = mysql_query($userSQL)
	or die("Could not retrieve user list: ".mysql_error().", $userSQL");

echo(mysql_num_rows($userRI)." users found<br>");

while(($userRow = mysql_fetch_array($userRI)) !== FALSE)
{
	echo("-- ".$userRow["username"]."<br>");

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

echo("done<br>");

// ----------

echo("Migrating members from e-mail based logins to username-based logins...<br>");

$sql = "UPDATE `zcm_member` SET `username` = `email` WHERE `username` = '' OR `username` IS NULL";
mysql_query($sql)
	or die("Could not migrate memberships: ".mysql_error().", $sql");

echo("done<br>");

// ----------

echo("Renaming plugin configuration items...<br>");

RenamePluginKeys('Form',array(
	'Results Email From'=>'Email Form Results To Address',
	'Email From'=>'Email Form Results From Address',
	'Email Subject'=>'Email Form Results Subject'
));

echo("done.<br>");
echo("Updating site text category information...<br>");

//Check for old page bodies, and relocate them to new page bodies.
$sitePageBodyRI = mysql_query("show columns from zcm_sitepage like 'body'");
if(mysql_num_rows($sitePageBodyRI) > 0)
if (array_key_exists('body',$sitepagecols))
{
	mysql_query("insert ignore into zcm_templatetext (id,template,tag) values (1,1,'Body')");
	$ri = mysql_query("select * from zcm_sitepage where not exists(select 1 from zcm_pagetext ".
		"where zcm_pagetext.sitepage = zcm_sitepage.id and zcm_pagetext.tag = 'Body')");
	while (($row = mysql_fetch_array($ri))!==false)
	{
		mysql_query("insert into zcm_pagetext (sitepage,tag,body) values ({$row['id']},'Body','".
			mysql_escape_string($row['body'])."')");
	}
	mysql_query("alter table zcm_sitepage drop body");
}

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

echo("done.<br>");
echo("Updating Zymurgy:CM navigation...");

//Make sure we start with the default navigation structure
$sql = "select count(*) from zcm_nav";
$ri = mysql_query($sql) or die("Can't get navigation count ($sql): ".mysql_error());
$count = mysql_result($ri,0,0);
if ($count==0)
{
	$sql = "INSERT INTO `zcm_nav` VALUES
		(1,1,0,'Content','Sub-Menu',''),
		(3,3,0,'Admin','Sub-Menu',''),
		(4,4,0,'Webmaster','Sub-Menu',''),
		(5,7,0,'Profile','URL','profile.php'),
		(6,19,0,'Help','URL','help.php'),
		(7,20,0,'Logout','URL','logout.php'),
		(8,8,1,'Simple Content','URL','sitetext.php'),
		(9,9,1,'SEO','URL','headtext.php'),
		(10,10,3,'User Management','URL','usermng.php'),
		(11,11,3,'User Activity','URL','useractivity.php'),
		(12,12,3,'Help Editor','URL','helpeditor.php'),
		(13,13,4,'Navigation','URL','navigation.php'),
		(14,14,4,'Master Config','URL','configconfig.php'),
		(15,15,4,'Plugin Management','URL','plugin.php'),
		(16,16,4,'Custom Tables','URL','customtable.php'),
		(17,17,4,'Custom Code','URL','mkcustom.php'),
		(18,18,4,'Page Templates','URL','templatemgr.php'),
		(19,5,0,'Appearance','URL','configuration.php'),
		(20,6,0,'Pages','URL','sitepage.php')
		;";
	$ri = mysql_query($sql) or die ("Can't create default navigation ($sql): ".mysql_error());
}

echo("done.<br>");
echo("Updating table definitions...");

//Run random column type updates
mysql_query("alter table zcm_sitetext change body body longtext");
mysql_query("alter table zcm_sitetext change plainbody plainbody longtext");
mysql_query("alter table zcm_config change value value longtext");
mysql_query("alter table zcm_config change inputspec inputspec text");

echo("done.<br>");

// ----------
// ZK: 2009.03.24
//
// Install/upgrade the media file component.
// ----------

echo("Checking media file support...<br>");

require_once("../cmo.php");
require_once("../include/media.php");

$installedVersion = MediaFileInstaller::InstalledVersion();
$targetVersion = MediaFileInstaller::Version();

if($installedVersion < $targetVersion)
{
	echo("-- Installing/upgrading from version $installedVersion to version $targetVersion");

	MediaFileInstaller::Upgrade($installedVersion, $targetVersion);
}
else
{
	echo("-- No install/upgrade required<br>");
}

// ----------
// ZK: 2008.11.18
//
// Traverse the plugins folder and install any items in there.
// All plugins are now installed automatically by the upgrader, and it's up to the
// web developer to remove unwanted items from the menu using the Navigation
// configuration system.
// ----------

echo("Installing plugins...<br>");

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

		echo("-- Including $entry<br>");
		require_once("../plugins/$entry");
	}
	else
	{
		echo "-- Skipping $entry (".implode(', ',$reasons).")<br>";
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

// ==========
// Fail if the hacked version of JSCalendar is not available
// ==========

echo("Testing for extended third-party components...<br>");

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

echo("Done.<br>");

// ----------
// END - Install plugins
// ----------

if(!isset($_GET["debug"]))
{
	header('Location: ../index.php');
}

	function  ExecuteAdd($source)
	{
		global $plugins;

		//Get an instance of the plugin class
		echo("---- Getting instance of $source plugin<br>");
		$factory = "{$source}Factory";
		$plugin = $factory();

		//Add plugin to the plugin table
		echo("---- Adding plugin definition to database<br>");
		Zymurgy::$db->query("insert into zcm_plugin(title,name,uninstallsql,enabled) values ('".
			Zymurgy::$db->escape_string($plugin->GetTitle())."','".
			Zymurgy::$db->escape_string($source)."','".
			Zymurgy::$db->escape_string($plugin->GetUninstallSQL())."',1)");
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

			$sql = "insert into zcm_pluginconfig (plugin,instance,`key`,value) values ($id,0,'".
				Zymurgy::$db->escape_string($key)."','".Zymurgy::$db->escape_string($value)."')";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
				die("Error adding plugin config: ".Zymurgy::$db->error()."<br>$sql");

			// echo(htmlentities($sql)."<br>");
		}

		// die();

		echo("---- Initializing plugin<br>");

		$plugin->Initialize();
	}
?>
