<?
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
	
	// ZK 2008.11.18
	// Removed the ability to add/remove plugins from the GUI.
	// All plugins are now installed automatically by the upgrader, and it's up to the 
	// web developer to remove unwanted items from the menu using the Navigation 
	// configuration system.
	
	/*
	require_once('PluginBase.php');

	$plugins = array();
	$actions = array();

	//Scan and include plugins
	$di = opendir('plugins');
	while (($entry = readdir($di)) !== false)
	{
		if (!is_dir("plugins/$entry"))
		{
			list($name,$extension) = explode('.',$entry);
			$plugins[$name] = 'N'; //Start out as (N)ew plugin.
			require_once("plugins/$entry");
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
	
	*/
	// Leave the TakeAction() call in.
	// This gives us the ability to add/remove plugins outside of the upgrader,
	// which may come in handy during development/debugging.
	TakeAction();
	/*
	CreatePluginActions();
	DisplayActions();
	*/
	
	if(!isset($_GET['sortcolumn']))
		$_GET['sortcolumn'] = "zcm_plugin.title";
	
	$ds = new DataSet('zcm_plugin','id');
	$ds->AddColumns('id','title','name','enabled');
	$dg = new DataGrid($ds);
	$dg->AddColumn('Plugin Name','title');
	$dg->AddColumn('Invocation','name',"&lt;?php echo Zymurgy::plugin('{0}','Instance Name'); ?&gt;");
	$dg->AddColumn('','id',"<a href='pluginconfig.php?plugin={0}&instance=0'>Default Settings</a>");
	//$dg->AddColumn('','id',"<a href='plugininstance.php?plugin={0}'>Instances</a>");
	$dg->AddColumn('','id',"<a href='pluginadmin.php?pid={0}'>Instances</a>");
	//$dg->AddColumn('Enabled','enabled');
	//$dg->AddEditColumn();
	//$dg->AddRadioEditor('enabled','Enabled:',array(1=>'Yes',0=>'No'));
	$dg->insertlabel='';
	$dg->Render();

	include('footer.php');

	// ZK 2008.11.18
	// Removed the ability to add/remove plugins from the GUI.
	// All plugins are now installed automatically by the upgrader, and it's up to the 
	// web developer to remove unwanted items from the menu using the Navigation 
	// configuration system.

	/*	
	function CreatePluginActions()
	{
		global $plugins, $actions;
		foreach ($plugins as $source=>$state)
		{
			switch($state)
			{
				case 'R': // (R)emoved
					$actions['executeremove='.urlencode($source)] = "The plugin provided by $source was not found.  Click this link to remove it from the site's configuration.";
					break;
				case 'N': // (N)ew
					$actions['executeadd='.urlencode($source)] = "A new plugin provided by $source was found.  Click this link to add it to the site configuration.";
					break;
			}
		}
	}
	
	function DisplayActions()
	{
		global $actions;
		
		foreach ($actions as $getparam=>$linktext)
		{
			echo "<a href=\"plugin.php?$getparam\">$linktext</a><br>\r\n";
		}
	}
	*/
	
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
			
			echo(htmlentities($sql)."<br>");
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


