<?
/**
 * The BoboTea plugin is a sample class that you can use to create your own
 * plugins for Zymurgy:CM. It contains the minimum necessary methods required,
 * as well as commented-out sample code.
 *
 * @package Zymurgy_Plugins
 */
class BoboTea extends PluginBase
{
	/**
	 * Return the user-friendly name of the plugin to display on the Plugin
	 * Management screen.
	 *
	 * @return string
	 */
	public function GetTitle()
	{
		return 'BoboTea Plugin';
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
//		$configItems = array();
//
//		$configItems[] = array(
//			"name" => 'Sample Setting',
//			"default" => '',
//			"inputspec" => 'input.50.200',
//			"authlevel" => 0);
//
//		return $configItems;

		return array();
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
//		return "<h1>BoboTea!</h1>";

		return "";
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
function BoboTeaFactory()
{
	return new BoboTea();
}
?>