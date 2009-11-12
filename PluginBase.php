<?
/**
 * Provides basic functionality for Zymurgy:CM plugin classes.
 *
 * @package Zymurgy
 * @subpackage frontend
 */

if (isset($ZymurgyRoot))
	require_once("$ZymurgyRoot/zymurgy/InputWidget.php");
else
	require_once(Zymurgy::$root."/zymurgy/InputWidget.php");

interface ZymurgyPlugin
{
	public function GetTitle();
	public function Render();
	public function RenderAdmin();
}

abstract class PluginBase implements ZymurgyPlugin
{
	/**
	 * The ID of the plugin, as set in the database when the plugin was
	 * installed.
	 *
	 * @var int
	 */
	var $pid;

	/**
	 * The ID of the plugin instance, as set in the database when the instance
	 * is created.
	 *
	 * @var int
	 */
	var $iid;

	/**
	 * The ID of the configuration set used by the plugin instance, as set in
	 * the zcm_pluginconfiggroup table.
	 *
	 * @var int
	 */
	var $configid;

	/**
	 * The plugin instance configuration. This is loaded when the plugin is
	 * instantiated
	 *
	 * @var mixed
	 */
	var $config = array();

	/**
	 * Constructor
	 *
	 * @return PluginBase
	 */
	public function PluginBase()
	{
		//Stub so that ancestors can call parent and when we need one the wiring is in place.
	}

	/**
	 * Return the user-friendly name of the plugin to display on the Plugin
	 * Management screen.
	 *
	 */
//	public function GetTitle()
//	{
//		die("GetTitle must be implemented by plugins.");
//	}

	/**
	 * The SQL scripts to run when uninstalling the plugin.
	 *
	 */
	public function GetUninstallSQL()
	{
	}

	/**
	 * Create a new instance of the plugin in the database, and assign a copy
	 * of the default configuration set to it.
	 *
	 * @param PluginBase $pi The instance of the plugin to create in the database
	 * @param string $plugin The name of the plugin
	 * @param string $instance The name of the instance
	 * @param boolean $private If true, do not list the instance in the instance
	 * list within the Plugin Management section of Zymurgy:CM
	 */
	public function CreateInstance(&$pi, $plugin, $instance, $private)
	{
		$sql = "select id,enabled from zcm_plugin where name='".
			Zymurgy::$db->escape_string($plugin)."'";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri)
		{
			die ("Error creating plugin instance for [$plugin]: ".Zymurgy::$db->error()."<br>$sql");
		}
		$row = Zymurgy::$db->fetch_array($ri);
		if ($row === false)
		{
			die ("Plugin [$plugin] isn't installed.");
		}
		if ($row['enabled']==0)
			die ("The plugin [$plugin] is not enabled.");
		$pi->pid = $row['id'];
		$ri = Zymurgy::$db->query("insert into zcm_plugininstance (plugin,name,`private`) values ({$pi->pid},'".
			Zymurgy::$db->escape_string($instance)."',$private)");
		$iid = Zymurgy::$db->insert_id();
		Zymurgy::LoadPluginConfig($pi); //Load default config for new instance
		$pi->pii = $pi->iid = $iid;

		$sql = "INSERT INTO `zcm_pluginconfiggroup` ( `name` ) VALUES ( '".
			Zymurgy::$db->escape_string($pi->GetTitle()).
			": ".
			Zymurgy::$db->escape_string($instance).
			"')";
		Zymurgy::$db->query($sql)
			or die("Could not save new plugin config group: ".Zymurgy::$db->error().", $sql");
		$pi->configid = Zymurgy::$db->insert_id();
		Zymurgy::$db->run("update zcm_plugininstance set config={$pi->configid} where id={$pi->pii}");
		foreach($pi->config as $cv)
		{
			$key = $cv->key;
			$value = $cv->value;
			$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) VALUES ( '".
				Zymurgy::$db->escape_string($pi->configid).
				"', '".
				Zymurgy::$db->escape_string($key).
				"', '".
				Zymurgy::$db->escape_string($value).
				"' )";
			Zymurgy::$db->query($sql)
				or die("Could not save new plugin config item: ".Zymurgy::$db->error().", $sql");
		}
	}

	/**
	 * Remove related records and files for this instance.  Base method removes
	 * the plugin's configuration.
	 *
	 */
	function RemoveInstance()
	{
//		$sql = "delete from zcm_pluginconfig where plugin={$this->pid} and instance={$this->iid}";
//		Zymurgy::$db->query($sql) or die ("Unable to remove plugin configuration ($sql): ".Zymurgy::$db->error());
	}

	/**
	 * Get the default configuration for the plugin.
	 *
	 * @return unknown
	 * @deprecated
	 */
	function GetDefaultConfig()
	{
		return array();
	}

	/**
	 * Return the list of keys used in the configuration for the plugin.
	 *
	 * @deprecated
	 * @return unknown
	 */
	function GetUserConfigKeys()
	{
		return array();
	}

	/**
	 * Get the list of supported data types for the config items for this plugin.
	 *
	 * @deprecated
	 * @return unknown
	 */
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

	/**
	 * Render the plugin, as it should appear on the front-end web site.
	 *
	 */
//	function Render()
//	{
//		die("Render must be implemented by plugins.");
//	}

	/**
	 * Render the screen displayed when the user selects the instance in the
	 * Plugin Management section of Zymurgy:CM, or when they select
	 * "Edit Gadget" in the pages section of Zymurgy:CM.
	 *
	 */
//	function RenderAdmin()
//	{
//		die("If a plugin implements AdminMenuText() it must also implement RenderAdmin().");
//	}

	/**
	 * Render the menu of commands shown on the bottom of the screen displayed
	 * when the user select the instance in the Plugin Management section of
	 * Zymurgy:CM, or when they select "Edit Gadget" in the pages section of
	 * Zymurgy:CM.
	 *
	 */
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

	/**
	 * Build the config item.
	 *
	 * @deprecated ?
	 * @param unknown_type $cfg
	 * @param unknown_type $key
	 * @param unknown_type $default
	 * @param unknown_type $inputspec
	 * @param unknown_type $authlevel
	 */
	function BuildConfig(&$cfg,$key,$default,$inputspec='input.40.60',$authlevel=0)
	{
		$cfg[$key]=new PluginConfig($key,$default,$inputspec,$authlevel);
	}

	/**
	 * Set the given config item to the given value for this plugin instance.
	 *
	 * @param unknown_type $key
	 * @param unknown_type $value
	 */
	function SetConfigValue($key, $value)
	{
//		echo("Setting $key to $value");

		if (array_key_exists($key,$this->config))
			$this->config[$key]->value = $value;
		else
			$this->config[$key] = new PluginConfig($key,$value);
	}

	/**
	 * Retrieve the value for the given config item for this plugin instance.
	 *
	 * @param string $key
	 * @param string $default Value to return if the config item does not
	 * actually exist.
	 * @return mixed
	 */
	function GetConfigValue($key,$default='')
	{
//		print_r($this->config);

		if (array_key_exists($key,$this->config))
			return $this->config[$key]->value;
		else
			return $default;
	}

	/**
	 * Build the provided menu item for use in the RenderCommandItems() method.
	 *
	 * @param unknown_type $array
	 * @param unknown_type $text
	 * @param unknown_type $url
	 * @param unknown_type $authlevel
	 * @param unknown_type $confirmation
	 */
	function BuildMenuItem(&$array, $text, $url, $authlevel = 0, $confirmation = NULL)
	{
		$array[] = new PluginMenuItem(
			$text,
			$url,
			$authlevel,
			$confirmation);
	}

	/**
	 * Build the "Edit Settings" menu item for use in the RenderCommandItems()
	 * method.
	 *
	 * @param mixed $r The command items array to append the "Edit Settings"
	 * menu item to.
	 */
	function BuildSettingsMenuItem(&$r)
	{
		$this->BuildMenuItem(
			$r,
			"Edit settings",
			"pluginconfig.php?plugin={pid}&amp;instance={iid}",
			0);
	}

	/**
	 * Build the "Delete this plugin" menu item for use in the RenderCommandItems()
	 * method
	 *
	 * @param mixed $r The command items array to append the "Delete this
	 * plugin" menu item to.
	 */
	function BuildDeleteMenuItem(&$r)
	{
		$this->BuildMenuItem(
			$r,
			"Delete this {pluginName}",
			"pluginadmin.php?pid={pid}&delkey={iid}",
			0,
			"Are you sure you want to delete this instance?  This action is not reversible.");
	}

	/**
	 * Retrieve the default command menu for use in the RenderCommandItems()
	 * method.
	 *
	 * Plugins that have additional commands are to re-implement this method.
	 *
	 * @return mixed
	 */
	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	/**
	 * Get the list of extension classes supported by the plugin.
	 *
	 * @return mixed
	 */
	function GetExtensions()
	{
		return array();
	}

	/**
	 * Call the extension method implemented by one or more extension classes.
	 * If more than one extension implements the method, the methods may be
	 * called in the same order they were listed in the GetExtensions() method,
	 * but this is not guaranteed.
	 *
	 * @param unknown_type $methodName
	 * @return unknown
	 */
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

	/**
	 * Build the list of configuration items specific to plugin extension
	 * classes.
	 *
	 * @param unknown_type $r
	 */
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

	/**
	 * Build the list of command items specific to plugin extension classes.
	 *
	 * @param unknown_type $r
	 */
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

/**
 * Plugin Configuration Item
 *
 */
class PluginConfig
{
	/**
	 * The plugin configuration item's key
	 *
	 * @var string
	 */
	var $key;

	/**
	 * The plugin configuration item's value
	 *
	 * @var mixed
	 */
	var $value;

	/**
	 * The inputspec to use when rendering the plugin configuration screen.
	 *
	 * @var string
	 */
	var $inputspec;

	/**
	 * The authlevel required to modify the plugin configuration item.
	 *
	 * @var int
	 */
	var $authlevel;

	/**
	 * Constructor
	 *
	 * @param string $key The plugin configuration item's key
	 * @param mixed $value The plugin configuration item's value
	 * @param string $inputspec The inputspec to use when rendering the plugin
	 * configuration screen.
	 * @param int $authlevel The authlevel required to modify the plugin
	 * configuration item
	 * @return PluginConfig
	 */
	function PluginConfig($key, $value, $inputspec='input.40.60', $authlevel=0)
	{
		$this->key = $key;
		$this->value = $value;
		$this->inputspec = $inputspec;
		$this->authlevel = $authlevel;
	}
}

/**
 * Represents a single item on the menu displayed on the bottom of the
 * Plugin screen.
 *
 */
class PluginMenuItem
{
	/**
	 * The text of the plugin's menu item.
	 *
	 * @var string
	 */
	var $text;

	/**
	 * The URL to display when the user clicks on the plugin's menu item.
	 *
	 * @var string
	 */
	var $url;

	/**
	 * The authlevel required to see the plugin's menu item.
	 *
	 * @var int
	 */
	var $authlevel;

	/**
	 * When set, a confirmation dialog is displayed using the value of the
	 * property as a message. If null, no dialog is displayed.
	 *
	 * @var string
	 */
	var $confirmation;

	/**
	 * Constructor.
	 *
	 * @param unknown_type $text The text of the plugin's menu item.
	 * @param unknown_type $url The URL to display when the user clicks on the
	 * plugin's menu item.
	 * @param unknown_type $authlevel The authlevel required to see the
	 * plugin's menu item.
	 * @param unknown_type $confirmation When set, a confirmation dialog is
	 * displayed using the value of the property as a message. If null, no
	 * dialog is displayed.
	 * @return PluginMenuItem
	 */
	function PluginMenuItem($text, $url, $authlevel = 0, $confirmation = NULL)
	{
		$this->text = $text;
		$this->url = $url;
		$this->authlevel = $authlevel;
		$this->confirmation = $confirmation;
	}
}

/**
 * Interface describing the methods that must be provided by a plugin
 * extension class.
 *
 */
interface PluginExtension
{
	/**
	 * Returns the name of the extension displayed in the left-hand menu
	 * when the user is on the "Edit Settings" screen.
	 *
	 * @return string
	 */
	public function GetExtensionName();

	/**
	 * Returns the description of the extension to display before the actual
	 * extension settings when the user is on the "Edit Settings" screen.
	 *
	 * @return string
	 */
	public function GetDescription();

	/**
	 * Determine if the extension is enabled, and return the result.
	 *
	 * @param PluginBase $plugin
	 * @return boolean If true, the plugin is enabled.
	 */
	public function IsEnabled($plugin);

	/**
	 * Returns an array of the configuration items required by the plugin
	 * extension.
	 *
	 * @return mixed
	 */
	public function GetConfigItems();

	/**
	 * Returns an array of menu items to append to the plugin's list of menu
	 * items.
	 *
	 * @return mixed
	 */
	public function GetCommands();
}
?>
