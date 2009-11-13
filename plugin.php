<?
/**
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
	$adminlevel = 2;

	if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
	{
		$breadcrumbTrail = "<a href=\"plugins.php\">Plugin Management</a> &gt; Edit";
	}
	else
	{
		$breadcrumbTrail = "Plugin Management";
	}

	require_once('header.php');
	require_once('datagrid.php');

	// Leave the TakeAction() call in.
	// This gives us the ability to add/remove plugins outside of the upgrader,
	// which may come in handy during development/debugging.
	TakeAction();

	if(!isset($_GET['sortcolumn']))
		$_GET['sortcolumn'] = "zcm_plugin.title";

	$ds = new DataSet('zcm_plugin','id');
	$ds->AddColumns('id','title','name','enabled');
	$dg = new DataGrid($ds);
	$dg->AddColumn('Plugin Name','title');
	$dg->AddColumn('Invocation','name',"&lt;?php echo Zymurgy::plugin('{0}','Instance Name'); ?&gt;");
	$dg->AddColumn('','id',"<a href='pluginconfig.php?plugin={0}&instance=0'>Default Settings</a>");
	$dg->AddColumn('','id',"<a href='pluginadmin.php?pid={0}'>Instances</a>");
	$dg->insertlabel='';
	$dg->Render();
?>
	<table class="DataGrid">
		<tr class="DataGridHeader">
			<td>Commands</td>
		</tr>
		<tr class="DataGridRow">
			<td><a href="pluginlist.php?action=addlist">Add/Enable Plugins</a></td>
		</tr>
		<tr class="DataGridRowAlternate">
			<td><a href="pluginlist.php?action=removelist">Remove/Disable Plugins</a></td>
		</tr>
	</table>
<?php
	include('footer.php');

	// To remove a plug-in outside the updater, use the following URL template:
	// http://{website}/zymurgy/plugin.php?executeremove={plugin_name}
	function ExecuteRemove($source)
	{
		global $plugins;

		$ri = Zymurgy::$db->query("select * from zcm_plugin where name='$source'");
		$row = Zymurgy::$db->fetch_array($ri);
		$id = $row['id'];
		$uninstallsql = explode(';',$row['uninstallsql']);
		foreach ($uninstallsql as $query)
		{
			Zymurgy::$db->query($query);
		}
		Zymurgy::$db->query("delete from zcm_pluginconfig where plugin=$id");
		Zymurgy::$db->query("delete from zcm_plugininstance where plugin=$id");
		Zymurgy::$db->query("delete from zcm_plugin where id=$id");
		unset($plugins[$source]);
		header('Location: plugin.php');
		exit;
	}

	// To add a plug-in outside the updater, use the following URL template:
	// http://{website}/zymurgy/plugin.php?executeadd={plugin_name}
	function  ExecuteAdd($source)
	{
		global $plugins;

		require_once('PluginBase.php');
		require_once("plugins/".$source.".php");

		//Get an instance of the plugin class
		$factory = "{$source}Factory";
		$plugin = $factory();
		//Add plugin to the plugin table
		Zymurgy::$db->query("insert into zcm_plugin(title,name,uninstallsql,enabled) values ('".
			Zymurgy::$db->escape_string($plugin->GetTitle())."','".
			Zymurgy::$db->escape_string($source)."','".
			Zymurgy::$db->escape_string($plugin->GetUninstallSQL())."',1)");
		$id = Zymurgy::$db->insert_id();
		//	$id = 7;
		//Add default configuration
		$defconf = $plugin->GetDefaultConfig();

		//	print_r($defconf);
		//	echo("<br><br><br>");
		//	die();

		foreach ($defconf as $key=>$value)
		{
			//echo("cv: ");
			//print_r($cv);
			//echo("<br>");

			//$key = $cv->key;
			//$value = $cv->value;

			// echo($key.": ".$value."<br>");

			$sql = "insert into zcm_pluginconfig (plugin,instance,`key`,value) values ($id,0,'".
				Zymurgy::$db->escape_string($key)."','".Zymurgy::$db->escape_string($value)."')";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
				die("Error adding plugin config: ".Zymurgy::$db->error()."<br>$sql");

			echo(htmlspecialchars($sql)."<br>");
		}

		// die();

		$plugin->Initialize();
		$plugins[$source] = 'E'; // (E)nabled
		header('Location: plugin.php');
		exit;
	}

	function TakeAction()
	{
		if (array_key_exists('executeremove',$_GET))
		{
			ExecuteRemove($_GET['executeremove']);
		}
		if (array_key_exists('executeadd',$_GET))
		{
			ExecuteAdd($_GET['executeadd']);
		}
	}
?>


