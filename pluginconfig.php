<?php
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

		foreach ($configItems as $configItem)
		{
			$inputField = str_replace(' ','_',$configItem["name"]);

//			$fieldLog .= "<br>-- $inputField: ".
//				(isset($_POST[$inputField]) ? $_POST[$inputField] : "(not set)");

			if(isset($_POST[$inputField]))
			{
				$dbvalue = Zymurgy::$db->escape_string($_POST[$inputField]);
				$sql = "update zcm_pluginconfig set `value`='$dbvalue' where (`key`='".
					Zymurgy::$db->escape_string($configItem["name"]).
					"') and (`plugin`={$plugin->pid}) and (`instance`={$plugin->iid})";
				$ri = Zymurgy::$db->query($sql);
				if (!$ri)
				{
					die("Error updating plugin config: ".Zymurgy::$db->error()."<br>$sql");
				}
				if (Zymurgy::$db->affected_rows()==0)
				{
					//Key doesn't exist yet.  Create it.
					$sql = "insert into zcm_pluginconfig (`plugin`,`instance`,`key`,`value`) values ({$plugin->pid},{$plugin->iid},'".
						Zymurgy::$db->escape_string($configItem["name"]).
						"','$dbvalue')";
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
				$sql = "UPDATE `zcm_pluginconfig` SET `value` = NULL WHERE (`key` = '".
					Zymurgy::$db->escape_string($configItem["name"]).
					"') AND (`plugin` = '".
					Zymurgy::$db->escape_string($plugin->pid).
					"') AND (`instance` = '".
					Zymurgy::$db->escape_string($plugin->iid).
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

	function GetPlugin(
		$pluginID,
		$instanceID)
	{
		$sql = "SELECT `name` FROM `zcm_plugin` WHERE `id` = '".
			Zymurgy::$db->escape_string($pluginID).
			"'";
		$pluginName = Zymurgy::$db->get($sql)
			or die("Could not get plugin information: ".mysql_error().", $sql");

		require_once("plugins/{$pluginName}.php");
		$factory = "{$pluginName}Factory";
		$po = $factory();

		$po->pid = $pluginID;
		$po->iid = $instanceID;
		$po->InstanceName = GetInstanceName($instanceID);

		return $po;
	}

	function GetInstanceName(
		$instanceID)
	{
		$sql = "SELECT `name` FROM zcm_plugininstance WHERE `id` = '".
			Zymurgy::$db->escape_string($instanceID).
			"'";
		$instanceName = Zymurgy::$db->get($sql)
			or die("Could not get instance information: ".mysql_error().", $sql");

		return $instanceName;
	}

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

		return $objectWithConfig->GetConfigItems();
	}

	function PopulateConfiguration(
		$plugin)
	{
		$configValues = array();

		// print_r($plugin);

		$sql = "SELECT `key`, `value` FROM `zcm_pluginconfig` WHERE `plugin` = '".
			Zymurgy::$db->escape_string($plugin->pid).
			"' AND `instance` = '".
			Zymurgy::$db->escape_string($plugin->iid).
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

	function stripslashes_deep($value)
	{
		$value = is_array($value) ?
			array_map('stripslashes_deep', $value) :
			stripslashes($value);
		return $value;
	}
?>