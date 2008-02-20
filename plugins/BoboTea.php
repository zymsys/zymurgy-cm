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
	
	function GetDefaultConfig()
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
		mysql_query('create table bobotea(int id)');
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