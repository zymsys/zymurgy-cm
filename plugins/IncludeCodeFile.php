<?
class IncludeCodeFile extends PluginBase
{
	function GetTitle()
	{
		return 'Include Code File Plugin';
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
		// mysql_query('create table bobotea(int id)');
	}

	function Render()
	{
		include(Zymurgy::$root.$this->GetConfigValue("URL"));
	}

	function AdminMenuText()
	{
		return 'Include Code File';
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