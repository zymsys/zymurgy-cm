<?
class EmbeddedContent extends PluginBase
{
	function GetTitle()
	{
		return 'Embedded Content Plugin';
	}

	function GetUninstallSQL()
	{
		return '';
		//return 'drop table zcm_embeddedcontent';
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

	function GetConfigItems()
	{
		$configItems = array();
		$configItems[] = array(
			"name" => 'Show as pop-up when page loads?',
			"default" => 'No',
			"inputspec" => 'drop.Yes,No',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Embed Code',
			"default" => '',
			"inputspec" => 'textarea.60.10',
			"authlevel" => 0);
		return $configItems;
	}

	function Initialize()
	{
	}

	function Render()
	{
		return $this->GetConfigValue('Embed Code');
	}

	function AdminMenuText()
	{
		return 'EmbeddedContent Admin menu text?';
	}

	function RenderAdmin()
	{
		echo "Choose 'Edit Settings' below to insert your embed code.";
	}
}

function EmbeddedContentFactory()
{
	return new EmbeddedContent();
}
?>