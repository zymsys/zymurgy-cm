<?
/**
 *
 * @package Zymurgy_Plugins
 */
class MemberLogin extends PluginBase
{
	function GetTitle()
	{
		return 'Member Login Plugin';
	}

	function GetDescription()
	{
		return <<<BLOCK
			<h3>Member Login Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>This plugin allows you to easily place a member login form onto
			you pages.</p>
BLOCK;
	}

	function GetUninstallSQL()
	{
		return '';
	}

	function GetDefaultConfig()
	{
		return array();
	}

	function GetCommandMenuItems()
	{
		$r = array();

		//$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function GetConfigItems()
	{
		$configItems = array();
		/*$configItems[] = array(
			"name" => 'Show as pop-up when page loads?',
			"default" => 'No',
			"inputspec" => 'drop.Yes,No',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Embed Code',
			"default" => '',
			"inputspec" => 'textarea.60.10',
			"authlevel" => 0);*/
		return $configItems;
	}

	function Initialize()
	{
	}

	function Render()
	{
		$r = Zymurgy::memberlogin();
		if (!array_key_exists('reg',$_GET))
			$r .= "<a href=\"{$_SERVER['REQUEST_URI']}?reg=username\">Not a member?  Sign-up.</a>";
		return $r;
	}

	function RenderAdmin()
	{
		echo "This plugin has no settings.";
	}
}

function MemberLoginFactory()
{
	return new MemberLogin();
}
?>