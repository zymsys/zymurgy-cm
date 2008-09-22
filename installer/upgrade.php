<?
require_once('../config/config.php');
include('tables.php');
mysql_connect($ZymurgyConfig['mysqlhost'],$ZymurgyConfig['mysqluser'],$ZymurgyConfig['mysqlpass']);
mysql_select_db($ZymurgyConfig['mysqldb']);

$newsitetext = array(
	'inputspec'=>"ALTER TABLE `sitetext` ADD `inputspec` VARCHAR( 100 ) DEFAULT 'html.600.400' NOT NULL ;",
	'category'=>array("ALTER TABLE `sitetext` ADD `category` bigint(20) default '0'",
		"ALTER TABLE `sitetext` ADD INDEX (`category`)"),
	'plainbody'=>array("alter table sitetext add plainbody text after body",
		"alter table sitetext add fulltext(plainbody)")
);

$newplugininstance = array(
	'private'=>"alter table plugininstance  add `private` tinyint default 0"
);

$newpasswd = array(
	'eula'=>"alter table passwd add `eula` tinyint default 0"
);

$newcustomtable = array(
	'selfref'=>"alter table customtable add selfref varchar(30)"
);

include('upgradelib.php');

RenameOldTables();
CreateMissingTables();
CheckColumns('zcm_sitetext',$newsitetext);
CheckColumns('zcm_passwd',$newpasswd);
CheckColumns('zcm_plugininstance',$newplugininstance);
CheckColumns('zcm_customtable',$newcustomtable);

RenamePluginKeys('Form',array(
	'Results Email From'=>'Email Form Results To Address',
	'Email From'=>'Email Form Results From Address',
	'Email Subject'=>'Email Form Results Subject'
));

//Check if faulty uncategorized content category exists and fix it.
$sql = "select id from zcm_stcategory where name='Uncategorized Content'";
$ri = mysql_query($sql) or die("Unable to find category ($sql): ".mysql_error());
$id = 0 + mysql_result($ri,0,0);
if ($id > 0)
{
	$sql = "update zcm_sitetext set category=0 where category=$id";
	mysql_query($sql) or die("Unable to correct default category ($sql): ".mysql_error());
	$sql = "update zcm_stcategory set id=0 where id=$id";
	mysql_query($sql) or die("Unable to reset default category id ($sql): ".mysql_error());
}
//Make sure category 0 exists for uncategorized content
$sql = "select * from zcm_stcategory where id=0";
$ri = mysql_query($sql) or die("Unable to load default category ($sql): ".mysql_error());
if (mysql_num_rows($ri)==0)
{
	$sql = "insert into zcm_stcategory (id,name) values (0,'Uncategorized Content')";
	mysql_query($sql) or die("Unable to create default categor ($sql): ".mysql_error());
	$notzero = mysql_insert_id();
	$sql = "update zcm_stcategory set id=0 where id=$notzero";
	mysql_query($sql) or die("Unable to set default category id ($sql): ".mysql_error());
}

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
		(5,5,0,'Profile','URL','profile.php'),
		(6,6,0,'Help','URL','help.php'),
		(7,7,0,'Logout','URL','logout.php'),
		(8,8,1,'Simple Content','URL','sitetext.php'),
		(9,9,1,'SEO','URL','headtext.php'),
		(10,10,3,'User Management','URL','usermng.php'),
		(11,11,3,'User Activity','URL','useractivity.php'),
		(12,12,3,'Help Editor','URL','helpeditor.php'),
		(13,13,4,'Navigation','URL','navigation.php'),
		(14,14,4,'Master Config','URL','configconfig.php'),
		(15,15,4,'Plugin Management','URL','plugin.php'),
		(16,16,4,'Custom Tables','URL','customtable.php'),
		(17,17,4,'Custom Code','URL','mkcustom.php');";
	$ri = mysql_query($sql) or die ("Can't create default navigation ($sql): ".mysql_error());
}

//Run random column type updates
mysql_query("alter table zcm_sitetext change body body longtext");
mysql_query("alter table zcm_sitetext change plainbody plainbody longtext");

header('Location: ../login.php');
?>