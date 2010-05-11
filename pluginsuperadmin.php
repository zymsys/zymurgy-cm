<?
/**
 * Management screen for administrative functionality of plugins that is only 
 * supposed to be accessible by the Webmaster.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */

require_once('cmo.php');

$pid = 0+$_GET['plugin'];
$iid = 0+$_GET['instance'];

$adminlevel = 2;

$title = Zymurgy::$db->get("select title from zcm_plugin where id=$pid");
$pluginname = Zymurgy::$db->get("select name from zcm_plugin where id=$pid");

$wikiArticleName = "Plugin_Management";

$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; ";
if ($iid)
{
	$breadcrumbTrail .= "<a href=\"pluginadmin.php?pid=$pid\">$title</a> &gt; Advanced Settings";
}
else
	$breadcrumbTrail .= "$title Instances";


require_once('header.php');
require_once('datagrid.php');

$sql = "select name,title,`release` from zcm_plugin where id=$pid";
$ri = Zymurgy::$db->query($sql);
if (!$ri) die("Error loading plugin info: ".Zymurgy::$db->error()."<br>$sql");
$plugin=Zymurgy::$db->fetch_array($ri);
if ($plugin===false) die("No plugin with that pid available.");

require_once('PluginBase.php');
require_once("plugins/{$plugin['name']}.php");
$fn = "{$plugin['name']}Factory";
$pi = $fn();
$pi->pid = $pid;
$pi->iid = $iid;
Zymurgy::LoadPluginConfig($pi);
if (@method_exists($pi,'RenderSuperAdmin'))
	$pi->RenderSuperAdmin();
else
	echo "This plugin has no super admin configuration.";

require_once('footer.php');
?>