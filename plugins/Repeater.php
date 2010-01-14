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

		function GetDescription()
		{
			return <<<BLOCK
				<h3>Custom Table Repeater Plugin</h3>
				<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
				<p>This plugin allows you to place the contents of a custom
				table on a page, with a greater control over the layout than
				is provided by the Custom Table Display plugin.</p>
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
			$configItems[] = array(
				"name" => 'Items per page',
				"default" => '',
				"inputspec" => 'numeric.5.5',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Fields (comma separated)',
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
				"inputspec" => 'html.600.200',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Repeater Item Layout',
				"default" => '',
				"inputspec" => 'html.600.400',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Repeater Separator Layout',
				"default" => '',
				"inputspec" => 'html.600.200',
				"authlevel" => 0);
			$configItems[] = array(
				"name" => 'Repeater Footer Layout',
				"default" => '',
				"inputspec" => 'html.600.200',
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

			$sql = "SELECT COUNT(*) FROM `".
				Zymurgy::$db->escape_string($table["tname"]).
				"` ".
				(strlen($this->GetConfigValue("Filter")) > 0 ? "WHERE ".$this->GetConfigValue("Filter") : "").
				" ".
				(strlen($this->GetConfigValue("Order By")) > 0 ? " ORDER BY ".$this->GetConfigValue("Order By") : ($table["hasdisporder"] == 1 ? " ORDER BY `disporder`" : ""));
//			die($sql);
			$rowCount = Zymurgy::$db->get($sql);

			$sql = "SELECT `id`, ".
				$this->GetConfigValue("Fields (comma separated)").
				" FROM `".
				Zymurgy::$db->escape_string($table["tname"]).
				"` ".
				(strlen($this->GetConfigValue("Filter")) > 0 ? "WHERE ".$this->GetConfigValue("Filter") : "").
				" ".
				(strlen($this->GetConfigValue("Order By")) > 0 ? " ORDER BY ".$this->GetConfigValue("Order By") : ($table["hasdisporder"] == 1 ? " ORDER BY `disporder`" : "")).
				" LIMIT ".
				(isset($_GET["start".$this->iid]) ? $_GET["start".$this->iid] : 0).
				", ".
				$this->GetConfigValue("Items per page");
			//die($sql);
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve data: ".Zymurgy::$db->error().", $sql");

			if(Zymurgy::$db->num_rows($ri) <= 0)
			{
				echo($this->GetConfigValue("Message for no records"));
			}
			else
			{
				echo($this->AddHeaderValues(
					$this->GetConfigValue("Repeater Header Layout"),
					(isset($_GET["start".$this->iid]) ? $_GET["start".$this->iid] : 0),
					$rowCount));

				include_once(Zymurgy::$root."/zymurgy/InputWidget.php");

				$fieldNames = $this->GetConfigValue("Fields (comma separated)");
				$fieldNames = str_replace("`", "", $fieldNames);
				$fieldNames = str_replace(" ", "", $fieldNames);
				$fieldNames = explode(",", $fieldNames);

				$fieldTypes = $this->GetFields(false);
				$firstRow = true;
				$widget = new InputWidget();

//				print_r($fieldNames);
//				die();

				while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
				{
					if(!$firstRow)
					{
						echo($this->GetConfigValue("Repeater Separator Layout"));
					}
					else
					{
						$firstRow = false;
					}

//					print_r($row);

					$output = $this->GetConfigValue("Repeater Item Layout");

					foreach($fieldNames as $fieldName)
					{
//						$output = str_replace(
//							"{".$fieldName."}",
//							$fieldTypes[$fieldName]["inputspec"],
//							$output);

						$widget->extra["dataset"] = $table["tname"];
						$widget->extra["datacolumn"] = $fieldName;
						$widget->datacolumn = $table["tname"].".".$fieldName;
						$widget->editkey = $row["id"];

//						print_r($widget);

						$output = str_replace(
							"{".$fieldName."}",
							$widget->Display(isset($fieldTypes[$fieldName])
								? $fieldTypes[$fieldName]["inputspec"]
								: "input.10.20", "{0}", $row[$fieldName]),
							$output);
					}

					echo($output);
				}

				echo($this->AddHeaderValues(
					$this->GetConfigValue("Repeater Footer Layout"),
					(isset($_GET["start".$this->iid]) ? $_GET["start".$this->iid] : 0),
					$rowCount));
			}

			Zymurgy::$db->free_result($ri);
		}

		private function AddHeaderValues($text, $startIndex, $numrows)
		{
			$pageNumber = ceil($startIndex / $this->GetConfigValue("Items per page")) + 1;
			$pageCount = ceil($numrows / $this->GetConfigValue("Items per page"));

			$text = str_replace("{pagenumber}", $pageNumber, $text);
			$text = str_replace("{pagecount}", $pageCount, $text);

			if($startIndex > 0)
			{
				$text = str_replace("{previous}", "?start".$this->iid."=".($startIndex - $this->GetConfigValue("Items per page")), $text);
			}
			else
			{
				$text = str_replace("{previous}", "javascript:;\"  style=\"visibility: hidden;\"", $text);
			}

			if($startIndex < $numrows - $this->GetConfigValue("Items per page"))
			{
				$text = str_replace("{next}", "?start".$this->iid."=".($startIndex + $this->GetConfigValue("Items per page")), $text);
			}
			else
			{
				$text = str_replace("{next}", "javascript:;\"  style=\"visibility: hidden;\"", $text);
			}

			return $text;
		}

		private function GetFields(
			$gridHeaderOnly = true)
		{
			$gridHeaderCriteria = "1 = 1";

			if($gridHeaderOnly)
			{
				$gridHeaderCriteria = "LENGTH(`gridheader`) > 0";
			}

			$sql = "SELECT `cname`, `inputspec`, `gridheader`, `caption` FROM `zcm_customfield` WHERE `tableid` = '".
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