<?
/**
 *
 * @package Zymurgy_Plugins
 */
class MemberProfile extends PluginBase
{
	function GetTitle()
	{
		return 'Member Profile Plugin';
	}

	function GetUninstallSQL()
	{
		return '';
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

	function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "Profile Form Plugin Instance",
			"default" => "",
			"inputspec" => "lookup.zcm_plugininstance.name.name.name",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Username Field Name",
			"default" => "username",
			"inputspec" => "input.20.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Password Field Name",
			"default" => "password",
			"inputspec" => "input.20.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Confirm Password Field Name",
			"default" => "confirm",
			"inputspec" => "input.20.50",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Forward URL",
			"default" => "/pages/Members/Home",
			"inputspec" => "input.50.200",
			"authlevel" => 0);

		return $configItems;
	}

	function Initialize()
	{
	}

	function Render()
	{
		return Zymurgy::membersignup(
			$this->GetConfigValue("Profile Form Plugin Instance"),
			$this->GetConfigValue("Username Field Name"),
			$this->GetConfigValue("Password Field Name"),
			$this->GetConfigValue("Confirm Password Field Name"),
			$this->GetConfigValue("Forward URL"));
	}

	function RenderAdmin()
	{
		// echo "This plugin has no settings.";
	}
}

function MemberProfileFactory()
{
	return new MemberProfile();
}
?>