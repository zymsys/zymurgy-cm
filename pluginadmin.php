<?
$breadcrumbTrail = "Plugin Administration";

$pid = 0+$_GET['pid'];
if (array_key_exists('iid',$_GET))
	$iid = 0 +$_GET['iid'];
else 
	$iid = 0;

require_once('header.php');
require_once('datagrid.php');

if (($pid > 0) && ($iid > 0))
{ // We have both a plugin and an instance, load its config and render it.
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
	$pi->InstanceName = $_GET['name'];
	$pi->dbrelease = $plugin['release'];
	if ($pi->GetRelease() > $pi->dbrelease) $pi->Upgrade();
	$pi->GetDefaultConfig();
	Zymurgy::LoadPluginConfig($pi);
	$pi->RenderAdmin();
	
	$pi->RenderCommandMenu();
		
	// echo "<p><a href=\"pluginconfig.php?plugin=$pid&instance=$iid\">Configuration Options</a></p>";
}
else 
{
	$sql = "select id,name from zcm_plugininstance where (`private`=0) and (plugin=$pid)";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Error loading instance info: ".Zymurgy::$db->error()."<br>$sql");
	if (Zymurgy::$db->num_rows($ri)==0)
		echo "There are no instances of this plugin yet.";
	else 
	{
		if (Zymurgy::$db->num_rows($ri)==1)
		{
			$row=Zymurgy::$db->fetch_array($ri);
			$redirect = "pluginadmin.php?pid=$pid&iid={$row['id']}&name=".urlencode($row['name']);
			//echo "Redirecting to $redirect";
			header("Location: $redirect");
			exit;
		}
		echo "<h3>Which one would you like to edit?</h3>";
		while (($row=Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "<a href=\"pluginadmin.php?pid=$pid&iid={$row['id']}&name=".urlencode($row['name'])."\">{$row['name']}</a><br>";
		}
	}
}
require_once('footer.php');
?>
