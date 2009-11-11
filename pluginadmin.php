<?
/**
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
$pid = 0+$_GET['pid'];
if (array_key_exists('iid',$_GET))
	$iid = 0 +$_GET['iid'];
else
	$iid = 0;

require_once 'cmo.php';

$title = Zymurgy::$db->get("select title from zcm_plugin where id=$pid");
$pluginname = Zymurgy::$db->get("select name from zcm_plugin where id=$pid");

if (array_key_exists('delkey',$_GET))
{
	$delkey = 0 + $_GET['delkey'];
	$instancename = Zymurgy::$db->get("select name from zcm_plugininstance where id=$delkey");
	$pi = Zymurgy::mkplugin($pluginname,$instancename);
	$pi->RemoveInstance();
	Zymurgy::$db->query("delete from zcm_plugininstance where id=$delkey");
}

$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; ";
if ($iid)
{
	$breadcrumbTrail .= "<a href=\"pluginadmin.php?pid=$pid\">$title Instances</a> &gt; {$_GET['name']}";
}
else
	$breadcrumbTrail .= "$title Instances";

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
	$pi->GetDefaultConfig();
	Zymurgy::LoadPluginConfig($pi);
	$pi->RenderAdmin();

//	echo("<pre>");
//	print_r($pi);
//	echo("</pre>");

	if (!$hasdumpeddatagridcss)
	{
		DumpDataGridCSS();
		$hasdumpeddatagridcss = true;
	}
	$pi->RenderCommandMenu();

	// echo "<p><a href=\"pluginconfig.php?plugin=$pid&instance=$iid\">Configuration Options</a></p>";
}
else
{
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		Zymurgy::mkplugin($pluginname, $_POST["instancename"]);
	}

	$sql = "select id,name from zcm_plugininstance where (`private`=0) and (plugin=$pid)";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Error loading instance info: ".Zymurgy::$db->error()."<br>$sql");
	if (Zymurgy::$db->num_rows($ri)==0)
		echo "There are no instances of this plugin yet.";
	else
	{
		if (array_key_exists('autoskip',$_GET) && (Zymurgy::$db->num_rows($ri)==1))
		{
			$row=Zymurgy::$db->fetch_array($ri);
			$redirect = "pluginadmin.php?pid=$pid&iid={$row['id']}&name=".urlencode($row['name']);
			//echo "Redirecting to $redirect";
			header("Location: $redirect");
			exit;
		}
		echo "<h3>Which $title would you like to edit?</h3>";
		while (($row=Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "<a href=\"pluginadmin.php?pid=$pid&iid={$row['id']}&name=".urlencode($row['name'])."\">{$row['name']}</a><br>";
		}
	}

	if($zauth->authinfo['admin'] >= 2)
	{
?>

	<h2>Add Instance</h2>

	<form name="add" method="POST">
		<table>
			<tr>
				<td>Name:</td>
				<td><input type="text" name="instancename" value=""></td>
			</tr>
		</table>

		<p><input type="submit" value="Add Instance"></p>
	</form>
<?
	}
}

require_once('footer.php');
?>
