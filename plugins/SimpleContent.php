<?
class SimpleContent extends PluginBase
{
	function GetTitle()
	{
		return 'Simple Content Plugin';
	}

	function GetUninstallSQL()
	{
		return '';
	}

	function GetDefaultConfig()
	{
		return array();
	}
	
	function GetDescription()
	{
		return "Allows you to drop a content block within a list of gadgets.";
	}

	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function GetConfigItems()
	{
		$configItems = array();
		$configItems[] = array(
			"name" => 'Show as pop-up when page loads?',
			"default" => 'No',
			"inputspec" => 'drop.Yes,No',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Content Type',
			"default" => '',
			"inputspec" => 'inputspec',
			"authlevel" => 0);
		return $configItems;
	}

	function Initialize()
	{
	}

	function Render()
	{
		return $this->GetConfigValue('Content Type');
	}

	function AdminMenuText()
	{
		return 'SimpleContent Admin menu text?';
	}

	function RenderAdmin()
	{
		echo "Choose 'Edit Settings' below to insert your embed code.";
	}
}

function SimpleContentFactory()
{
	return new SimpleContent();
}
?>