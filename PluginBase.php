<?
if (isset($ZymurgyRoot))
	require_once("$ZymurgyRoot/zymurgy/InputWidget.php");
else
	require_once(Zymurgy::$root."/zymurgy/InputWidget.php");

class PluginBase
{
	var $pid; //Plugin ID# from the database
	var $iid; //Instance ID# from the database
	var $dbrelease; //Release known to the database
	var $config = array();

	function PluginBase()
	{
		//Stub so that ancestors can call parent and when we need one the wiring is in place.
	}
	
	function CompleteUpgrade()
	{
		$this->dbrelease = $this->GetRelease();
		Zymurgy::$db->query("update zcm_plugin set `release`={$this->dbrelease} where id={$this->pid}");
	}
	
	function GetTitle()
	{
		die("GetTitle must be implemented by plugins.");
	}
	
	function GetRelease()
	{
		return 1; //Default release number.
	}
	
	function GetUninstallSQL()
	{
	}
	
	/**
	 * Remove related records and files for this instance.  Base method removes the plugin's configuration.
	 *
	 */
	function RemoveInstance()
	{
		$sql = "delete from zcm_pluginconfig where plugin={$this->pid} and instance={$this->iid}";
		Zymurgy::$db->query($sql) or die ("Unable to remove plugin configuration ($sql): ".Zymurgy::$db->error());
	}
	
	function GetDefaultConfig()
	{
		return array();
	}
	
	function GetUserConfigKeys()
	{
		return array();
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
	}
	
	function Upgrade()
	{
	}
	
	function Render()
	{
		die("Render must be implemented by plugins.");
	}
	
	function RenderAdmin()
	{
		die("If a plugin implements AdminMenuText() it must also implement RenderAdmin().");
	}
	
	function AdminMenuText()
	{
		return '';
	}
	
	function BuildConfig(&$cfg,$key,$default,$inputspec='input.40.60',$authlevel=0)
	{
		$cfg[$key]=new PluginConfig($key,$default,$inputspec,$authlevel);
	}
	
	function SetConfigValue($key, $value)
	{
		if (array_key_exists($key,$this->config))
			$this->config[$key]->value = $value;
		else 
			$this->config[$key] = new PluginConfig($key,$value);
	}
	
	function GetConfigValue($key,$default='')
	{
		if (array_key_exists($key,$this->config))
			return $this->config[$key]->value;
		else 
			return $default;
	}
}

class PluginConfig
{
	var $key;
	var $value;
	var $inputspec;
	var $authlevel;
	
	function PluginConfig($key, $value, $inputspec='input.40.60', $authlevel=0)
	{
		$this->key = $key;
		$this->value = $value;
		$this->inputspec = $inputspec;
		$this->authlevel = $authlevel;
	}
}
?>
