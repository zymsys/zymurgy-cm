<?
$id = 0 + $_GET['plugin'];
$instance = 0 + $_GET['instance'];

// Default configs may only be set by the webmaster
if($instance == 0)
	$adminlevel = 2;

require_once 'cmo.php';
$title = Zymurgy::$db->get("select title from zcm_plugin where id=$id");
$instancename = Zymurgy::$db->get("select name from zcm_plugininstance where id=$instance");
$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; <a href=\"pluginadmin.php?pid=$id\">$title Instances</a> &gt; ";
if ($instance)
	$breadcrumbTrail .= "<a href=\"pluginadmin.php?pid=$id&iid=$instance&name=".urlencode($instancename)."\">$instancename</a> &gt; Settings";
else 
	$breadcrumbTrail .= "Default Settings";

require_once('header.php');
require_once('PluginBase.php');

//Load plugin info
$ri = Zymurgy::$db->query("select * from zcm_plugin where id=$id");
$plugin = Zymurgy::$db->fetch_array($ri);
require_once("plugins/{$plugin['name']}.php");
$factory = "{$plugin['name']}Factory";
$po = $factory();
$po->config = $po->GetDefaultConfig();
//Load instance data
$ri = Zymurgy::$db->query("select * from zcm_plugininstance where id=$instance");
$instancerow = Zymurgy::$db->fetch_array($ri);
$po->InstanceName = $instancerow['name'];
//Load instance values to override this config
$ri = Zymurgy::$db->query("select * from zcm_pluginconfig where plugin=$id and instance=$instance");
while (($row = Zymurgy::$db->fetch_array($ri))!== false)
{
	$po->SetConfigValue($row['key'],$row['value']);
}
$config = $po->config;

$widget = new InputWidget();
$widget->fckeditorcss = '';
if ($_SERVER['REQUEST_METHOD']=='POST')
{
	if (get_magic_quotes_gpc()) {
		function stripslashes_deep($value)
		{
			$value = is_array($value) ?
				array_map('stripslashes_deep', $value) :
				stripslashes($value);				
			return $value;
		}
		
		$_POST = array_map('stripslashes_deep', $_POST);
		$_GET = array_map('stripslashes_deep', $_GET);
		$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	}

	foreach ($config as $cv)
	{
		if ($zauth->authinfo['admin'] < $cv->authlevel)
			continue; //We don't have auth for this config item.
		$key = str_replace('_',' ',$cv->key);
		$value = $cv->value;
		$dbvalue = Zymurgy::$db->escape_string($widget->PostValue($cv->inputspec,$key));
		$sql = "update zcm_pluginconfig set `value`='$dbvalue' where (`key`='".
			Zymurgy::$db->escape_string($key).
			"') and (`plugin`={$plugin['id']}) and (`instance`=$instance)";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri)
		{
			die("Error updating plugin config: ".Zymurgy::$db->error()."<br>$sql");
		}
		if (Zymurgy::$db->affected_rows()==0)
		{
			//Key doesn't exist yet.  Create it.
			$sql = "insert into zcm_pluginconfig (`plugin`,`instance`,`key`,`value`) values ({$plugin['id']},$instance,'".
				Zymurgy::$db->escape_string($key).
				"','$dbvalue')";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
			{
				if (Zymurgy::$db->errno() != 1062) //1062 means the user hit submit bit didn't change anything, so no rows affected and can't re-insert.  Just ignore it.
					die("Error (".Zymurgy::$db->errno().") adding plugin config: ".Zymurgy::$db->error()."<br>$sql");
			}
		}
	}
	if ($issuper)
		header('Location: plugin.php');
	else 
		header("Location: pluginadmin.php?pid=$id&iid=$instance&name=".urlencode($po->InstanceName));
}
else 
{
	//print_r($config);
	// die();
	
	//Render data entry form.
	echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">";
	echo "<table>";
	foreach ($config as $cv)
	{
		// ZK: authlevel no longer returned
		// if ($zauth->authinfo['admin'] < $cv->authlevel)
		// 	continue; //We don't have auth for this config item.
		
		$key = $cv->key;
		$value = $cv->value;
		
		echo "<tr><td align=\"right\">$key:</td><td>";
		//echo "[{$cv->inputspec}]";
		$widget->Render($cv->inputspec,str_replace(' ','_',$key),$value);
		echo "</td></tr>\r\n";
	}
	echo "<tr><td colspan=\"2\"><input value=\"Save Plugin Config\" type=\"submit\"></td></tr></table>";
	echo "</form>";
	include('footer.php');
}
?>
