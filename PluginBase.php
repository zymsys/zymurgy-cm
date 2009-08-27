<?
if (isset($ZymurgyRoot))
	require_once("$ZymurgyRoot/zymurgy/InputWidget.php");
else
	require_once(Zymurgy::$root."/zymurgy/InputWidget.php");

class PluginBase
{
	var $pid; //Plugin ID# from the database
	var $iid; //Instance ID# from the database
	var $configid; // Config ID from the zcm_pluginconfiggroup table

	var $dbrelease; //Release known to the database
	var $config = array();

	function PluginBase()
	{
		//Stub so that ancestors can call parent and when we need one the wiring is in place.
	}

	function CompleteUpgrade()
	{
		$this->dbrelease = $this->GetRelease();
		Zymurgy::$db->query("update zcm_plugin set `release`={$this->dbrelease} where id={$this->pid}");
	}

	function GetTitle()
	{
		die("GetTitle must be implemented by plugins.");
	}

	function GetRelease()
	{
		return 1; //Default release number.
	}

	function GetUninstallSQL()
	{
	}

	/**
	 * Remove related records and files for this instance.  Base method removes the plugin's configuration.
	 *
	 */
	function RemoveInstance()
	{
		$sql = "delete from zcm_pluginconfig where plugin={$this->pid} and instance={$this->iid}";
		Zymurgy::$db->query($sql) or die ("Unable to remove plugin configuration ($sql): ".Zymurgy::$db->error());
	}

	function GetDefaultConfig()
	{
		return array();
	}

	function GetUserConfigKeys()
	{
		return array();
	}

	function GetConfigItemTypes()
	{
		//Data types are in the format:
		//Implemented:
		//Not Implemented:
//		"input.$size.$maxlength"
//		"textarea.$width.$height"
//		"html.$widthpx.$heightpx"
//		"radio.".serialize($optionarray)
//		"drop.".serialize($optionarray)
//		"attachment"
//		"money"
//		"unixdate"
//		"lookup.$table"
		return array();
	}

	function Initialize()
	{
	}

	function Upgrade()
	{
	}

	function Render()
	{
		die("Render must be implemented by plugins.");
	}

	function RenderAdmin()
	{
		die("If a plugin implements AdminMenuText() it must also implement RenderAdmin().");
	}

	function RenderCommandMenu()
	{
		global $zauth;

		$menuItems = $this->GetCommandMenuItems();

		// echo("menuItems: [ ");
		// print_r($menuItems);
		// echo(" ] ");
		// die();

		if(sizeof($menuItems) > 0)
		{
			echo("<br><br><table class=\"DataGrid\">");
			echo("<tr class=\"DataGridHeader\"><td>Commands</td></tr>");

			for($commandIndex = 0; $commandIndex < sizeof($menuItems); $commandIndex++)
			{
				$menuItem = $menuItems[$commandIndex];

				if($menuItem->authlevel <= $zauth->authinfo['admin'])
				{
					$rowClass = $commandIndex % 2 == 0 ? "DataGridRow" : "DataGridRowAlternate";
					$url = str_replace(
						"{pid}",
						$this->pid,
						$menuItem->url);
					$url = str_replace(
						"{iid}",
						$this->iid,
						 $url);
					$url = str_replace(
						"{name}",
						urlencode($this->InstanceName),
						$url);
					$text = str_replace(
						"{pluginName}",
						str_replace("Plugin", "", $this->GetTitle()),
						$menuItem->text);

					echo "<tr class=\"{$rowClass}\"><td><a ";
					if (!is_null($menuItem->confirmation))
					{
						echo "onClick=\"return confirm('{$menuItem->confirmation}')\" ";
					}
					echo "href=\"{$url}\">{$text}</a></td></tr>";
				}
			}

			echo("</table>");
		}
	}

	function AdminMenuText()
	{
		return '';
	}

	function BuildConfig(&$cfg,$key,$default,$inputspec='input.40.60',$authlevel=0)
	{
		$cfg[$key]=new PluginConfig($key,$default,$inputspec,$authlevel);
	}

	function SetConfigValue($key, $value)
	{
//		echo("Setting $key to $value");

		if (array_key_exists($key,$this->config))
			$this->config[$key]->value = $value;
		else
			$this->config[$key] = new PluginConfig($key,$value);
	}

	function GetConfigValue($key,$default='')
	{
//		print_r($this->config);

		if (array_key_exists($key,$this->config))
			return $this->config[$key]->value;
		else
			return $default;
	}

	function GetCommandMenuItems()
	{
		die("GetCommandMenuItems must be implemented by plugins.");
	}

	function BuildMenuItem(&$array, $text, $url, $authlevel = 0, $confirmation = NULL)
	{
		$array[] = new PluginMenuItem(
			$text,
			$url,
			$authlevel,
			$confirmation);
	}

	function BuildSettingsMenuItem(&$r)
	{
		$this->BuildMenuItem(
			$r,
			"Edit settings",
			"pluginconfig.php?plugin={pid}&amp;instance={iid}",
			0);
	}

	function BuildDeleteMenuItem(&$r)
	{
		$this->BuildMenuItem(
			$r,
			"Delete this {pluginName}",
			"pluginadmin.php?pid={pid}&delkey={iid}",
			0,
			"Are you sure you want to delete this instance?  This action is not reversible.");
	}

	function GetExtensions()
	{
		return array();
	}

	function CallExtensionMethod($methodName)
	{
		$extensions = $this->GetExtensions();
		$returnValue = null;

		foreach($extensions as $extension)
		{
			if($extension->IsEnabled($this) && method_exists($extension, $methodName))
			{
				//call_user_func($methodName, $extension, $this);
				$result = call_user_method($methodName, $extension, $this);

				if($result !== null)
				{
					$returnValue = $result;
				}
			}
		}

		return $returnValue;
	}

	function BuildExtensionConfig(&$r)
	{
		$extensions = $this->GetExtensions();

		foreach($extensions as $extension)
		{
			$configItems = $extension->GetConfigItems($this);

			foreach($configItems as $configItem)
			{
				$this->BuildConfig(
					$r,
					$configItem["name"],
					$configItem["default"],
					$configItem["inputspec"],
					$configItem["authlevel"]);
			}
		}
	}

	function BuildExtensionMenu(&$r)
	{
		$extensions = $this->GetExtensions();

		foreach($extensions as $extension)
		{
			if($extension->IsEnabled($this))
			{
				$menuItems = $extension->GetCommands();

				foreach($menuItems as $menuItem)
				{
					$this->BuildMenuItem(
						$r,
						$menuItem["caption"],
						$menuItem["url"],
						$menuItem["authlevel"],
						key_exists("confirmation", $menuItem) ? $menuItem["confirmation"] : NULL);
				}
			}
		}
	}
}

class PluginConfig
{
	var $key;
	var $value;
	var $inputspec;
	var $authlevel;

	function PluginConfig($key, $value, $inputspec='input.40.60', $authlevel=0)
	{
		$this->key = $key;
		$this->value = $value;
		$this->inputspec = $inputspec;
		$this->authlevel = $authlevel;
	}
}

class PluginMenuItem
{
	var $text;
	var $url;
	var $authlevel;
	var $confirmation;

	function PluginMenuItem($text, $url, $authlevel = 0, $confirmation = NULL)
	{
		$this->text = $text;
		$this->url = $url;
		$this->authlevel = $authlevel;
		$this->confirmation = $confirmation;
	}
}

interface PluginExtension
{
	public function GetExtensionName();
	public function GetDescription();
	public function IsEnabled($plugin);
	public function GetConfigItems($plugin);
	public function GetCommands();
}
?>
