<?php
/**
 * Management screen for the settings of a given plugin instance.
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
	ini_set("display_errors", 1);

	require_once('cmo.php');
	require_once('PluginBase.php');

	$descriptor = "";

	$plugin = GetPlugin(
		$_GET["plugin"],
		$_GET["instance"]);
	$configItems = GetConfigItems(
		$plugin,
		isset($_GET["ext"]) ? $_GET["ext"] : "",
		$descriptor);
	$configValues = PopulateConfiguration(
		$plugin);
	$breadcrumbTrail = GetBreadcrumbTrail(
		$plugin);
	$wikiArticleName = "Plugin_Management";
	$message = "";

	require_once('header.php');

	if ($_SERVER['REQUEST_METHOD']=='POST')
	{
		if (get_magic_quotes_gpc()) {
			$_POST = array_map('stripslashes_deep', $_POST);
			$_GET = array_map('stripslashes_deep', $_GET);
			$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
		}

		// $fieldLog = "";
//Zymurgy::Dbg($configItems);
//Zymurgy::DbgAndDie($_POST);

		foreach ($configItems as $configItem)
		{
			$inputField = str_replace(' ','_',$configItem["name"]);

//			$fieldLog .= "<br>-- $inputField: ".
//				(isset($_POST[$inputField]) ? $_POST[$inputField] : "(not set)");

			if(isset($_POST[$inputField]))
			{
				$dbvalue = Zymurgy::$db->escape_string($_POST[$inputField]);

//				$sql = "update zcm_pluginconfig set `value`='$dbvalue' where (`key`='".
//					Zymurgy::$db->escape_string($configItem["name"]).
//					"') and (`plugin`={$plugin->pid}) and (`instance`={$plugin->iid})";
				$sql = "UPDATE `zcm_pluginconfigitem` SET `value` = '".
					$dbvalue.
					"' WHERE `key` = '".
					Zymurgy::$db->escape_string($configItem["name"]).
					"' AND `config` = '".
					Zymurgy::$db->escape_string($plugin->configid).
					"'";
				$ri = Zymurgy::$db->query($sql);

				if (!$ri)
				{
					die("Error updating plugin config: ".Zymurgy::$db->error()."<br>$sql");
				}
				if (Zymurgy::$db->affected_rows()==0)
				{
					//Key doesn't exist yet.  Create it.
//					$sql = "insert into zcm_pluginconfig (`plugin`,`instance`,`key`,`value`) values ({$plugin->pid},{$plugin->iid},'".
//						Zymurgy::$db->escape_string($configItem["name"]).
//						"','$dbvalue')";

					$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) VALUES ( '".
						Zymurgy::$db->escape_string($plugin->configid).
						"', '".
						Zymurgy::$db->escape_string($configItem["name"]).
						"', '".
						$dbvalue.
						"' )";

					$ri = Zymurgy::$db->query($sql);

					if (!$ri)
					{
						if (Zymurgy::$db->errno() != 1062) //1062 means the user hit submit bit didn't change anything, so no rows affected and can't re-insert.  Just ignore it.
							die("Error (".Zymurgy::$db->errno().") adding plugin config: ".Zymurgy::$db->error()."<br>$sql");
					}
				}

				$configValues[$configItem["name"]] = $_POST[$inputField];
			}
			else
			{
				$sql = "UPDATE `zcm_pluginconfigitem` SET `value` = NULL WHERE (`key` = '".
					Zymurgy::$db->escape_string($configItem["name"]).
					"') AND (`config` = '".
					Zymurgy::$db->escape_string($plugin->configid).
					"')";

				Zymurgy::$db->query($sql)
					or die("Could not clear config item: ".Zymurgy::$db->error().", $sql");

				$configValues[$configItem["name"]] = "";
			}
		}

		$extensions = $plugin->GetExtensions();

		if(count($extensions) > 0)
		{
			$message .= "Settings saved.";
			// $message .= "<br>$fieldLog";
		}
		else
		{
			header("Location: pluginadmin.php?pid=".
				$plugin->pid.
				"&iid=".
				$plugin->iid.
				"&name=".urlencode($plugin->InstanceName));
		}


	}

	echo("<table>");
	echo("<tr><td valign=\"top\" style=\"border-right: 1px solid black;\"><div style=\"width: 170px; margin-right: 10px;\">");
	DisplayMenu(
		$plugin);
	echo("</div></td><td valign=\"top\" style=\"padding-left: 10px;\">");
	DisplayConfigurationPage(
		$plugin,
		$configItems,
		$configValues,
		$descriptor,
		$message);
	echo("</td></tr></table>");

	require_once("footer.php");

	/**
	 * Get an instance of the plugin class, based on the ID of the plugin and
	 * the instance, as stored in the database.
	 *
	 * @param int $pluginID
	 * @param int $instanceID
	 * @return PluginBase
	 */
	function GetPlugin(
		$pluginID,
		$instanceID)
	{
		$sql = "SELECT `zcm_plugin`.`name`, COALESCE(`config`, `defaultconfig`) AS `config` FROM `zcm_plugin` LEFT JOIN `zcm_plugininstance` ON `zcm_plugininstance`.`plugin` = `zcm_plugin`.`id` AND `zcm_plugininstance`.`id` = '".
			Zymurgy::$db->escape_string($instanceID).
			"' WHERE `zcm_plugin`.`id` = '".
			Zymurgy::$db->escape_string($pluginID).
			"'";
		$plugin = Zymurgy::$db->get($sql)
			or die("Could not get plugin information: ".mysql_error().", $sql");

		if (file_exists("plugins/{$plugin['name']}.php"))
			require_once("plugins/{$plugin['name']}.php");
		else
			require_once("custom/plugins/{$plugin['name']}.php"); 
		$factory = "{$plugin["name"]}Factory";
		$po = $factory();

		$po->pid = $pluginID;
		$po->iid = $instanceID;
		$po->configid = $plugin["config"];
		$po->InstanceName = GetInstanceName($instanceID);
		Zymurgy::LoadPluginConfig($po);

		return $po;
	}

	/**
	 * Get the name of the instance, given its ID in the database.
	 *
	 * @param int $instanceID
	 * @return string
	 */
	function GetInstanceName(
		$instanceID)
	{
		$defaultInstance = "Defaults";

		$sql = "SELECT `name` FROM zcm_plugininstance WHERE `id` = '".
			Zymurgy::$db->escape_string($instanceID).
			"'";
		$instanceName = Zymurgy::$db->get($sql);
		//	or die("Could not get instance information: ".mysql_error().", $sql");

		return strlen($instanceName) > 0 ? $instanceName : $defaultInstance;
	}

	/**
	 * Get the list of settings for the given plugin/extension combination.
	 *
	 * @param PluginBase $plugin
	 * @param string $extensionName
	 * @param string $descriptor
	 * @return mixed
	 */
	function GetConfigItems(
		$plugin,
		$extensionName,
		&$descriptor)
	{
		$objectWithConfig = $plugin;

		if(strlen($extensionName) > 0)
		{
			$extensions = $plugin->GetExtensions();

			foreach($extensions as $extension)
			{
				if($extension instanceof $extensionName)
				{
					$objectWithConfig = $extension;
					$descriptor = $extension->GetDescription();

					break;
				}
			}
		}

		return $objectWithConfig->GetConfigItems($plugin);
	}

	/**
	 * Retrieve the plugin instance's configuration.
	 *
	 * @param PluginBase $plugin
	 * @return mixed
	 */
	function PopulateConfiguration(
		$plugin)
	{
		$configValues = array();

		// print_r($plugin);

		$sql = "SELECT `key`, `value` FROM `zcm_pluginconfigitem` WHERE `config` = '".
			Zymurgy::$db->escape_string($plugin->configid).
			"'";
		// die($sql);
		$ri = Zymurgy::$db->query($sql)
			or die("Could not get current configuration: ".mysql_error().", $sql");

		while (($row = Zymurgy::$db->fetch_array($ri))!== false)
		{
			$configValues[$row['key']] = $row['value'];
		}

		return $configValues;
	}

	/**
	 * Get the breadcrumb trail for the Edit Settings screen.
	 *
	 * @param string $plugin
	 * @return string
	 */
	function GetBreadcrumbTrail(
		$plugin)
	{
		$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; <a href=\"pluginadmin.php?pid=".
			$plugin->pid.
			"\">".
			$plugin->GetTitle().
			" Instances</a> &gt; ";

		if (isset($_GET["instance"]))
			$breadcrumbTrail .= "<a href=\"pluginadmin.php?pid=".
				$plugin->pid.
				"&amp;iid=".
				$plugin->iid.
				"&amp;name=".
				urlencode($plugin->InstanceName).
				"\">".
				$plugin->InstanceName.
				"</a> &gt; Settings";
		else
			$breadcrumbTrail .= "Default Settings";

		return $breadcrumbTrail;
	}

	/**
	 * Display the Edit Settings menu.
	 *
	 * @param PluginBase $plugin
	 */
	function DisplayMenu(
		$plugin)
	{
		if(isset($_GET["ext"]))
		{
			echo("<a href=\"pluginconfig.php?plugin=".
				$plugin->pid.
				"&amp;instance=".
				$plugin->iid.
				"\">General</a><br>");
		}
		else
		{
			echo("<b>General</b><br>");
		}

		$extensions = $plugin->GetExtensions();

		foreach($extensions as $extension)
		{
			if(isset($_GET["ext"]) && $extension instanceof $_GET["ext"])
			{
				echo("<b>".$extension->GetExtensionName()."</b><br>");
			}
			else
			{
			echo("<a href=\"pluginconfig.php?plugin=".
				$plugin->pid.
				"&amp;instance=".
				$plugin->iid.
				"&amp;ext=".
				get_class($extension).
				"\">".
				$extension->GetExtensionName().
				"</a><br>");
			}
		}
	}

	/**
	 * Display the settings for the given plugin instance.
	 *
	 * @param PluginBase $plugin
	 * @param mixed $configItems
	 * @param mixed $configValues
	 * @param string $descriptor
	 * @param string $message
	 */
	function DisplayConfigurationPage(
		$plugin,
		$configItems,
		$configValues,
		$descriptor,
		$message)
	{
		$widget = new InputWidget();
		$widget->fckeditorcss = '';

		// print_r($configValues);

		echo("<style>\n");
		echo(".pluginButton { width: 80px; margin-right: 10px; }\n");
		echo(".message { background: lightyellow; border: 2px solid black; ".
			"padding: 10px; margin-bottom: 10px; }\n");
		echo("</style>");

		echo($descriptor);

		if(strlen($message) > 0)
		{
			echo("<div class=\"message\">$message</div>\n");
		}

		echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">";
		echo "<table>";

		foreach ($configItems as $configItem)
		{
			echo "<tr><td align=\"right\">".$configItem["name"]."</td><td>";
			$widget->Render(
				$configItem["inputspec"],
				str_replace(' ', '_', $configItem["name"]),
				isset($configValues[$configItem["name"]])
					? $configValues[$configItem["name"]]
					: $configItem["default"]);
			echo "</td></tr>\r\n";
		}

		echo("<tr><td colspan=\"2\">&nbsp;</td></tr>");

		echo "<tr><td colspan=\"2\">";
		echo "<input class=\"pluginButton\" type=\"submit\" value=\"Save\">";
		echo "<input class=\"pluginButton\" type=\"button\" value=\"Cancel\" onclick=\"window.reload();\">";
		echo "</td></tr></table>";
		echo "</form>";
	}

	/**
	 * Strip the slashes from both strings and arrays.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	function stripslashes_deep($value)
	{
		$value = is_array($value) ?
			array_map('stripslashes_deep', $value) :
			stripslashes($value);
		return $value;
	}
?>