<?
	/**
	 *
	 * @package Zymurgy_Plugins
	 */
	if (!class_exists('PluginBase'))
	{
		require_once('../cmo.php');
		require_once('../PluginBase.php');
	}

	class CustomTableEditor extends PluginBase
	{
		function GetTitle()
		{
			return 'Custom Table Editor Plugin';
		}

		function GetDescription()
		{
			return <<<BLOCK
				<h3>Custom Table Editor Plugin</h3>
				<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
				<p>This plugin renders a form for editing @author vic
				custom table.</p>
BLOCK;
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

		function Initialize()
		{
		}

		function Render()
		{
			$r='';
			switch($this->extra)
			{//Here in case we need it
				default:
					$r = $this->RenderHTML();
					break;
			}
			return $r;
		}

		private function GetFields(
			$gridHeaderOnly = true)
		{
			$gridHeaderCriteria = "1 = 1";

			if($gridHeaderOnly)
			{
				$gridHeaderCriteria = "LENGTH(`caption`) > 0";
			}

			$sql = "SELECT `cname`, `inputspec`, `caption` FROM `zcm_customfield` WHERE `tableid` = '".
				Zymurgy::$db->escape_string($this->GetConfigValue("Custom Table")).
				"' AND $gridHeaderCriteria ORDER BY `disporder`";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve field list: ".Zymurgy::$db->error().", $sql");

			$fields = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$fields[$row["cname"]] = $row;
			}

			Zymurgy::$db->free_result($ri);

			return $fields;
		}

		private function RenderHTML()
		{
			if (array_key_exists('validationerrors',$GLOBALS))
			{
				echo $GLOBALS['validationerrors'];
			}
			$tname = Zymurgy::$db->get("SELECT `tname` FROM `zcm_customtable` WHERE `id`=".$this->GetConfigValue("Custom Table"));
			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$formvalues = $_POST;
			}
			else if (array_key_exists('id', $_GET))
			{
				$formvalues = Zymurgy::$db->get("SELECT * FROM `".$tname.
					"` WHERE `id` = ".intval($_GET['id']));
			}
			else 
			{
				$formvalues = array();
			}
			$fields = $this->GetFields();
			$uriparts = explode('?', $_SERVER['REQUEST_URI']);
			$uri = array_shift($uriparts);
			echo "<form bobo=\"tea\" method=\"post\" enctype=\"multipart/form-data\" action=\"".
				htmlspecialchars($uri)."\">";
			foreach ($_GET as $key=>$value)
			{
				echo "<input type=\"hidden\" name=\"".
					htmlspecialchars($key)."\" value=\"".
					htmlspecialchars($value)."\">";
			}
			echo "<table class=\"CustomTableEditor CustomTableEditor".$tname."\">";
			$iw = new InputWidget();
			foreach ($fields as $fieldname=>$fielddata)
			{
				echo "<tr><th>{$fielddata['caption']}</th><td>";
				$value = array_key_exists($fieldname, $formvalues) ? $formvalues[$fieldname] : '';
				$iw->Render($fielddata['inputspec'], $fieldname, $value);
				echo "</td></tr>";
			}
			echo "<tr><td class=\"FormSubmit\"><input type=\"submit\" value=\"Save\"></td></tr>";
			echo "</table></form>";
		}

		function RenderAdmin()
		{
			echo "";
		}
	}

	function CustomTableEditorFactory()
	{
		return new CustomTableEditor();
	}
?>