<?
/**
 *
 * @package Zymurgy_Plugins
 */
class IncludeCodeFile extends PluginBase
{
	function GetTitle()
	{
		return 'Include Code File Plugin';
	}

	function GetDescription()
	{
		return <<<BLOCK
			<h3>Include Code File Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>This plug-in allows you to run a PHP file inside the page using
			a standard PHP include. Using this plugin is the simplest way of
			providing custom functionality within your web site, while still
			maintaining your content within the Pages system.</p>
BLOCK;
	}

	function GetUninstallSQL()
	{
		// return 'drop table zcm_';
	}

	function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "URL",
			"default" => "",
			"inputspec" => "input.50.200",
			"authlevel" => 2);

		return $configItems;
	}

	function GetDefaultConfig()
	{
		$r = array();

		$configItems = $this->GetConfigItems();

		foreach($configItems as $configItem)
		{
			$this->BuildConfig(
				$r,
				$configItem["name"],
				$configItem["default"],
				$configItem["inputspec"],
				$configItem["authlevel"]);
		}

		$this->BuildExtensionConfig($r);

		return $r;
	}

	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function Initialize()
	{
		// mysql_query('create table bobotea(int id)');
	}

	function Render()
	{
		include(Zymurgy::$root.$this->GetConfigValue("URL"));
	}

	function RenderAdmin()
	{
		echo "<p>This plug-in allows you to run a PHP file inside the page using a ".
			"standard PHP include.</p>".
			"<p>This is an advanced operation, and as such, this plugin should only ".
			"be added to the page by your Web Developer when adding functionality ".
			"to the site.</p>";
	}
}

function IncludeCodeFileFactory()
{
	return new IncludeCodeFile();
}
?>