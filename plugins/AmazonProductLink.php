<?
/**
 * Creates an Amazon Associate Product Link, allowing users to link products on
 * Amazon using their affiliate account.
 *
 * @package Zymurgy_Plugins
 */
class AmazonProductLink extends PluginBase
{
	/**
	 * Return the user-friendly name of the plugin to display on the Plugin
	 * Management screen.
	 *
	 * @return string
	 */
	public function GetTitle()
	{
		return 'AmazonProductLink Plugin';
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
			<h3>Amazon Product Link Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>Creates an Amazon Associate Product Link, allowing users to link
			products on Amazon using their affiliate account.</p>
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
			"name" => 'Amazon Affiliate ID',
			"default" => '',
			"inputspec" => 'input.20.50',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Product ASINs (comma seperated)',
			"default" => '',
			"inputspec" => 'textarea.40.10',
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
//		return "<h1>BoboTea!</h1>";

		$output = <<<HTML
<p align="center">
	<iframe src="http://rcm.amazon.com/e/cm?t={0}&o=1&p=8&l=as1&asins={1}&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0">
</iframe>
</p>
HTML;

		$output = str_replace("{0}", $this->GetConfigValue("Amazon Affiliate ID"), $output);
//		$output = str_replace("{1}", $this->GetConfigValue("Product ASIN"), $output);

		$asins = explode(",", $this->GetConfigValue("Product ASINs (comma seperated)"));
		$asin = trim($asins[rand(0, count($asins) - 1)]);
		$output = str_replace("{1}", $asin, $output);

		return $output;
	}

	/**
	 * Render the screen displayed when the user selects the instance in the
	 * Plugin Management section of Zymurgy:CM, or when they select
	 * "Edit Gadget" in the pages section of Zymurgy:CM.
	 *
	 */
	function RenderAdmin()
	{
		echo $this->GetDescription();
	}
}

/**
 * Each plugin also requires a corresponding Factory function. The Factory
 * function is used by the base Zymurgy:CM system to instantiate a new
 * instance of the class for use.
 *
 * @return AmazonProductLink
 */
function AmazonProductLinkFactory()
{
	return new AmazonProductLink();
}
?>