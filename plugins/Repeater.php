<?
	/**
	 *
	 * @package Zymurgy_Plugins
	 */
	ini_set("display_errors", 1);

	if (!class_exists('PluginBase'))
	{
		require_once('../cmo.php');
		require_once('../PluginBase.php');
	}

	class Repeater extends PluginBase
	{
		function GetTitle()
		{
			return 'Custom Table Repeater Plugin';
		}

		function GetUninstallSQL()
		{
			return "";
		}

		function GetConfigItems()
		{
			$configItems = array();

			$configItems[] = array(
				"name" => 'Custom Table',
				"default" => '',
				"inputspec" => 'lookup.zcm_customtable.id.tname.tname',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Fields (comma seperated)',
				"default" => '',
				"inputspec" => 'textarea.60.2',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Filter',
				"default" => '',
				"inputspec" => 'textarea.60.5',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Order By',
				"default" => '',
				"inputspec" => 'input.50.200',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Repeater Header Layout',
				"default" => '',
				"inputspec" => 'html.600.400',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Repeater Item Layout',
				"default" => '',
				"inputspec" => 'html.600.400',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Repeater Footer Layout',
				"default" => '',
				"inputspec" => 'html.600.400',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Message for no records',
				"default" => '<p>There are no records to display.</p>',
				"inputspec" => 'html.600.200',
				"authlevel" => 0);

			return $configItems;
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
			return array();
		}

		function Initialize()
		{
		}

		function Render()
		{
			$sql = "SELECT `tname`, `hasdisporder` FROM `zcm_customtable` WHERE `id` = '".
				Zymurgy::$db->escape_string($this->GetConfigValue("Custom Table")).
				"'";
			$table = Zymurgy::$db->get($sql);

//			die("Length: ".strlen($this->GetConfigValue("Order By")));

			$sql = "SELECT ".
				$this->GetConfigValue("Fields (comma seperated)").
				" FROM `".
				Zymurgy::$db->escape_string($table["tname"]).
				"` ".
				(strlen($this->GetConfigValue("Filter")) > 0 ? $this->GetConfigValue("Filter") : "").
				" ".
				(strlen($this->GetConfigValue("Order By")) > 0 ? " ORDER BY ".$this->GetConfigValue("Order By") : ($table["hasdisporder"] == 1 ? " ORDER BY `disporder`" : ""));
//			die($sql);
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve data: ".Zymurgy::$db->error().", $sql");

			if(Zymurgy::$db->num_rows($ri) <= 0)
			{
				echo($this->GetConfigValue("Message for no records"));
			}
			else
			{
				echo($this->GetConfigValue("Repeater Header Layout"));

				while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
				{
					$output = $this->GetConfigValue("Repeater Item Layout");

					foreach($row as $fieldName => $fieldValue)
					{
						$output = str_replace("{".$fieldName."}", $fieldValue, $output);
					}

					echo($output);
				}

				echo($this->GetConfigValue("Repeater Footer Layout"));
			}

			Zymurgy::$db->free_result($ri);
		}

		private function GetFields()
		{
			$sql = "SELECT `cname`, `gridheader` AS `caption` FROM `zcm_customfield` WHERE `tableid` = '".
				Zymurgy::$db->escape_string($this->GetConfigValue("Custom Table")).
				"' AND LENGTH(`gridheader`) > 0 ORDER BY `disporder`";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve field list: ".Zymurgy::$db->error().", $sql");

			$fields = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$fields[$row["cname"]] = $row["caption"];
			}

			Zymurgy::$db->free_result($ri);

			return $fields;
		}

		function AdminMenuText()
		{
			return 'Repeater';
		}

		function RenderAdmin()
		{
			echo "";
		}
	}

	function RepeaterFactory()
	{
		return new Repeater();
	}
?>