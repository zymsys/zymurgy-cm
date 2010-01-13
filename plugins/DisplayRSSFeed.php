<?
	ini_set("display_errors", 1);

	if (!class_exists('PluginBase'))
	{
		require_once('../cmo.php');
		require_once('../PluginBase.php');
	}

/**
 *
 * @package Zymurgy_Plugins
 */

class DisplayRSSFeed extends PluginBase
{
	/**
	 * Return the user-friendly name of the plugin to display on the Plugin
	 * Management screen.
	 *
	 * @return string
	 */
	public function GetTitle()
	{
		return 'Display RSS Feed Plugin';
	}

	/**
	 * Return the user-friendly description of the plugin to display on the
	 * Plugin Details and Add Plugin screens.
	 *
	 * @return string
	 */
	public function GetDescription()
	{
		return <<<BLOCK
			<h3>Display RSS Feed Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
BLOCK;
	}

	/**
	 * Return the SQL scripts to run when uninstalling the plugin.
	 *
	 * If your plugin creates tables to store data specific to the plugin,
	 * include the "DROP TABLE" statements for those tables here.
	 *
	 */
	public function GetUninstallSQL()
	{
//		return 'drop table bobotea';
		return "";
	}

	/**
	 * Return the list of settings for the plugin to display when the user
	 * clicks on "Default Settings" on the Plugin Management screen, the
	 * "Edit Settings" menu item for a plugin instance, or the "Edit Gadget"
	 * link in the pages system.
	 *
	 * @return mixed
	 */
	public function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => 'Feed URL',
			"default" => '',
			"inputspec" => 'input.50.200',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Output Template',
			"default" => '',
			"inputspec" => 'textarea.60.5',
			"authlevel" => 0);

		return $configItems;
	}

	/**
	 * Return the list of default configuration settings for this plugin that
	 * must be set before attempting to use an otherwise empty instance. Most
	 * plugins do not need to return anything.
	 *
	 * @return mixed
	 */
	function GetDefaultConfig()
	{
		return array();
	}

	/**
	 * Return the list of menu items to display at the bottom of the plugin's
	 * instance screen.
	 *
	 * @return mixed
	 */
	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

//		$this->BuildMenuItem(
//			$r,
//			"View form details",
//			"pluginadmin.php?pid={pid}&iid={iid}&name={name}",
//			0);

		return $r;
	}

	/**
	 * Perform any tasks that need to be performed when the plugin is first
	 * installed. This includes, but is not limited to, creating database
	 * tables
	 *
	 */
	function Initialize()
	{
//		$tableDefinitions = array(
//			array(
//				"name" => "bobotea",
//				"columns" => array(
//					DefineTableField("id", "INTEGER", "UNSIGNED NOT NULL")
//				),
//				"indexes" => array(),
//				"primarykey" => "id",
//				"engine" => "InnoDB"
//			)
//		);
//
//		ProcessTableDefinitions($tableDefinitions);
	}

	/**
	 * Render the contents of the plugin, as it should appear on the front-end
	 * web site.
	 *
	 * @return string
	 */
	function Render()
	{
		if(isset($_GET["TemplateInstance"]))
		{
//			return "Hi";

			echo $this->GetConfigValue("Output Template");
		}
		else
		{
			$_GET["XMLFILE"] = $this->GetConfigValue("Feed URL");
			$_GET["TEMPLATE"] = "http://".$_SERVER['SERVER_NAME']."/zymurgy/plugins/DisplayRSSFeed.php?TemplateInstance=".urlencode($this->InstanceName);
			$_GET["MAXITEMS"] = 4;

//			die($_GET["TEMPLATE"]);
			include(Zymurgy::$root."/zymurgy/include/rss2html.php");
		}
	}

	/**
	 * Render the screen displayed when the user selects the instance in the
	 * Plugin Management section of Zymurgy:CM, or when they select
	 * "Edit Gadget" in the pages section of Zymurgy:CM.
	 *
	 */
	function RenderAdmin()
	{
		echo "There are no settings for this plugin.";
	}
}

/**
 * Each plugin also requires a corresponding Factory function. The Factory
 * function is used by the base Zymurgy:CM system to instantiate a new
 * instance of the class for use.
 *
 * @return BoboTea
 */
function DisplayRSSFeedFactory()
{
	return new DisplayRSSFeed();
}

	if (array_key_exists('TemplateInstance',$_GET))
	{
		ini_set("display_errors", 1);
		// header("Content-type: application/rss+xml");
		header("Content-type: text/plain");
		$doctype = 'json';

		echo Zymurgy::plugin('DisplayRSSFeed', $_GET['TemplateInstance'], $doctype);
	}


?>