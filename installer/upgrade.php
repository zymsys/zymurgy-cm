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

CheckColumns('sitetext',$newsitetext);
CheckColumns('passwd',$newpasswd);
CheckColumns('plugininstance',$newplugininstance);
CheckColumns('customtable',$newcustomtable);

RenamePluginKeys('Form',array(
	'Results Email From'=>'Email Form Results To Address',
	'Email From'=>'Email Form Results From Address',
	'Email Subject'=>'Email Form Results Subject'
));

//Check if faulty uncategorized content category exists and fix it.
$sql = "select id from stcategory where name='Uncategorized Content'";
$ri = mysql_query($sql) or die("Unable to find category ($sql): ".mysql_error());
$id = 0 + mysql_result($ri,0,0);
if ($id > 0)
{
	$sql = "update sitetext set category=0 where category=$id";
	mysql_query($sql) or die("Unable to correct default category ($sql): ".mysql_error());
	$sql = "update stcategory set id=0 where id=$id";
	mysql_query($sql) or die("Unable to reset default category id ($sql): ".mysql_error());
}
//Make sure category 0 exists for uncategorized content
$sql = "select * from stcategory where id=0";
$ri = mysql_query($sql) or die("Unable to load default category ($sql): ".mysql_error());
if (mysql_num_rows($ri)==0)
{
	$sql = "insert into stcategory (id,name) values (0,'Uncategorized Content')";
	mysql_query($sql) or die("Unable to create default categor ($sql): ".mysql_error());
	$notzero = mysql_insert_id();
	$sql = "update stcategory set id=0 where id=$notzero";
	mysql_query($sql) or die("Unable to set default category id ($sql): ".mysql_error());
}

//Run random column type updates
mysql_query("alter table sitetext change body body longtext");
mysql_query("alter table sitetext change plainbody plainbody longtext");

header('Location: ../login.php');
?>