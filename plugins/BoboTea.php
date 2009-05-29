<?
class BoboTea extends PluginBase
{
	function GetTitle()
	{
		return 'BoboTea Plugin';
	}

	function GetUninstallSQL()
	{
		return 'drop table bobotea';
	}

	function GetConfigItems()
	{
		return array();
	}

	function GetDefaultConfig()
	{
		return array();
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
		$tableDefinitions = array(
			array(
				"name" => "bobotea",
				"columns" => array(
					DefineTableField("id", "INTEGER", "UNSIGNED NOT NULL")
				),
				"indexes" => array(),
				"primarykey" => "id",
				"engine" => "InnoDB"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
	}

	function Render()
	{
		return "I am the BoboTea Plugin.  I represent {$this->config['bobo']}.";
	}

	function AdminMenuText()
	{
		return 'BoboTea';
	}

	function RenderAdmin()
	{
		echo "This is the admin for the BoboTea plugin.";
	}
}

function BoboTeaFactory()
{
	return new BoboTea();
}
?>