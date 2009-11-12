<?php
/**
 *
 * @package Zymurgy_Plugins
 */
	if (!class_exists('PluginBase'))
	{
		require_once('../cmo.php');
		require_once('../PluginBase.php');
	}

	class CustomTableRSS extends PluginBase
	{
		function GetTitle()
		{
			return "Custom Table RSS Feed";
		}

		function GetUninstallSQL()
		{
			return "";
		}

		function GetConfigItems()
		{
			$configItems = array();

			$configItems[] = array(
				"name" => "Custom Table",
				"default" => "",
				"inputspec" => "lookup.zcm_customtable.id.tname.tname",
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Items in Feed",
				"default" => "20",
				"inputspec" => "numeric.5.5",
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Channel Title",
				"default" => "",
				"inputspec" => "input.50.200",
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Channel Description",
				"default" => "",
				"inputspec" => "textarea.40.5",
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Language",
				"default" => "",
				"inputspec" => "drop.en-us,en-ca,fr-ca",
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Managing Editor",
				"default" => "webmaster@".Zymurgy::$config['sitehome'],
				"inputspec" => "input.50.200",
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Webmaster",
				"default" => "webmaster@".Zymurgy::$config['sitehome'],
				"inputspec" => "input.50.200",
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
			// do nothing
		}

		function Render()
		{
			// echo($this->extra);
			// die();

			$r='';
			switch($this->extra)
			{
				case 'xml':
					$r = $this->RenderXML();
					break;
				default:
					$r = $this->RenderHTML();
					break;
			}
			return $r;
		}

		private function RenderHTML()
		{
			return "<link href=\"/zymurgy/plugins/CustomTableRSS.php?FeedInstance=".
				$this->iid.
				"\" type=\"application/rss+xml\" rel=\"alternate\" title=\"".
				$this->GetConfigValue("Channel Title").
				"\">\n";
		}

		private function RenderXML()
		{
			$template = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n".
				"<rss version=\"2.0\">\n".
				"<channel>\n".
				"<title>{0}</title>\n".
				"<link>{1}</link>\n".
				"<description>{2}</description>\n".
				"<language>{3}</language>\n".
				"<generator>".Zymurgy::GetLocaleString("Common.ProductName")."</generator>\n".
				"<managingEditor>{4}</managingEditor>\n".
				"<webMaster>{5}</webMaster>\n".
				"<ttl>120</ttl>\n".
				"{6}\n".
				"</channel>\n".
				"</rss>\n";
			$itemTemplate = "<item>\n".
				"<title>{0}</title>\n".
				"<link>{1}</link>\n".
				"<description>{2}</description>\n".
				"<pubDate>{3}</pubDate>\n".
				"<guid>{4}</guid>\n".
				"</item>";

			$xml = str_replace("{0}", $this->GetConfigValue("Channel Title"), $template);
			$xml = str_replace("{1}", "http://".Zymurgy::$config["sitehome"]."/zymurgy/plugins/CustomTableRSS.php?FeedInstance=".$this->iid, $xml);
			$xml = str_replace("{2}", $this->GetConfigValue("Channel Description"), $xml);
			$xml = str_replace("{3}", $this->GetConfigValue("Language"), $xml);
			$xml = str_replace("{4}", $this->GetConfigValue("Managing Editor"), $xml);
			$xml = str_replace("{5}", $this->GetConfigValue("Webmaster"), $xml);

			$items = array();

			$sql = "SELECT `tname` FROM `zcm_customtable` WHERE `id` = '".
				Zymurgy::$db->escape_string($this->GetConfigValue("Custom Table")).
				"'";
			$tableName = Zymurgy::$db->get($sql);

			$sql = "SELECT `id`, `".
				Zymurgy::$db->escape_string($this->GetConfigValue("Item Title Field")).
				"` AS `title`, `".
				Zymurgy::$db->escape_string($this->GetConfigValue("Item Description Field")).
				"` AS `description`, `".
				Zymurgy::$db->escape_string($this->GetConfigValue("Item Publication Date Field")).
				"` AS `pubdate` FROM `".
				Zymurgy::$db->escape_string($tableName).
				"` ORDER BY `".
				Zymurgy::$db->escape_string($this->GetConfigValue("Item Publication Date Field")).
				"` DESC LIMIT 0, ".
				Zymurgy::$db->escape_string($this->GetConfigValue("Items in Feed"));
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve items to include in feed: ".Zymurgy::$db->error().", $sql");

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$item = str_replace("{0}", $row["title"], $itemTemplate);
				$item = str_replace(
					"{1}",
					str_replace("{0}", $row["id"], $this->GetConfigValue("Item Link")),
					$item);
				$item = str_replace("{2}", $row["description"], $item);

				if(is_numeric($row["pubdate"]))
				{
					$item = str_replace("{3}", date("D, d M Y H:i:s T", $row["pubdate"]), $item);
				}
				else
				{
					$date = strtotime($row["pubdate"]);

					if($date > 0)
					{
						$item = str_replace("{3}", $row["pubdate"], $item);
					}
					else
					{
						$item = str_replace("{3}", date("D, d M Y H:i:s T"), $item);
					}
				}

				$item = str_replace(
					"{4}",
					str_replace("{0}", $row["id"], $this->GetConfigValue("Item Link")),
					$item);

				$items[] = $item;
			}

			Zymurgy::$db->free_result($ri);

			$xml = str_replace("{6}", implode("\n", $items), $xml);

			return $xml;
		}

		function RenderAdmin()
		{
			echo "Content pending";
		}

		function GetExtensions()
		{
			$extensions = array();

			$extensions[] = new CustomTableRSSItems();
			$extensions[] = new CustomTableRSSCategories();

			if(file_exists(Zymurgy::$root."/zymurgy/custom/plugins/CustomTableRSS.php"))
			{
				include_once(Zymurgy::$root."/zymurgy/custom/plugins/CustomTableRSS.php");

				$extensions = array_merge(
					$extensions,
					CustomTableRSSExtensions::GetExtensions());
			}

			return $extensions;
		}
	}

	function CustomTableRSSFactory()
	{
		return new CustomTableRSS();
	}

	class CustomTableRSSItems implements PluginExtension
	{
		// ZK: This "extension" only exists to streamline the config,
		// and to avoid having to extend the config screen to allow for
		// javascript updates based on selections on the General config
		// screen.

		public function GetExtensionName()
		{
			return "Items";
		}

		public function GetDescription()
		{
			return "";
		}

		public function GetConfigItems($plugin = NULL)
		{
			// Since this cannot be enforced through the interface without
			// breaking older installs, enforce this via contract instead.
			if($plugin == NULL)
			{
				die("Plugin must be passed to GetConfigItems().");
			}

			$configItems = array();

			$sql = "SELECT `cname` FROM `zcm_customfield` WHERE `tableid` = '".
				$plugin->GetConfigValue("Custom Table").
				"' ORDER BY `disporder`";
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve list of columns for custom table: ".Zymurgy::$db->error().", $sql");

//			echo("<pre>");
//			print_r($plugin);
//			echo("</pre>");

			$columns = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$columns[] = $row["cname"];
			}

			Zymurgy::$db->free_result($ri);

			$configItems[] = array(
				"name" => "Item Title Field",
				"default" => "",
				"inputspec" => "drop.".implode(",", $columns),
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Item Description Field",
				"default" => "",
				"inputspec" => "drop.".implode(",", $columns),
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Item Publication Date Field",
				"default" => "",
				"inputspec" => "drop.".implode(",", $columns),
				"authlevel" => 0);
			$configItems[] = array(
				"name" => "Item Link",
				"default" => "",
				"inputspec" => "input.50.200",
				"authlevel" => 0);

			return $configItems;
		}

		public function GetCommands()
		{
			return array();
		}

		public function IsEnabled($plugin)
		{
			return true;
		}
	}

	class CustomTableRSSCategories implements PluginExtension
	{
		// ZK: This "extension" only exists to streamline the config,
		// and to avoid having to extend the config screen to allow for
		// javascript updates based on selections on the General and
		// Items config screens.

		public function GetExtensionName()
		{
			return "Categories";
		}

		public function GetDescription()
		{
			return "";
		}

		public function GetConfigItems($plugin = NULL)
		{
			// Since this cannot be enforced through the interface without
			// breaking older installs, enforce this via contract instead.
			if($plugin == NULL)
			{
				die("Plugin must be passed to GetConfigItems().");
			}

			$configItems = $this->GetParentConfigItem(
				$plugin->GetConfigValue("Custom Table"),
				1);

			return $configItems;
		}

		private function GetParentConfigItem($tableID, $index)
		{
			$configItems = array();

			$sql = "SELECT `detailfor` FROM `zcm_customtable` WHERE `id` = '".
				Zymurgy::$db->escape_string($tableID).
				"'";
			$parentTableID = Zymurgy::$db->get($sql);

			if($parentTableID > 0)
			{
				$sql = "SELECT `cname` FROM `zcm_customfield` WHERE `tableid` = '".
					Zymurgy::$db->escape_string($parentTableID).
					"'";
				$ri = Zymurgy::$db->query($sql)
					or die("Could not retrieve list of columns for parent table: ".Zymurgy::$db->error().", $sql");
				$columns = array();

				while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
				{
					$columns[] = $row["cname"];
				}

				Zymurgy::$db->free_result($ri);

				$configItems[] = array(
					"name" => "Parent ".$index." Category Label",
					"default" => "",
					"inputspec" => "drop.".implode(",", $columns),
					"authlevel" => 0);

				$configItems = array_merge(
					$configItems,
					$this->GetParentConfigItem($parentTableID, $index + 1));
			}

			return $configItems;
		}

		public function GetCommands()
		{
			return array();
		}

		public function IsEnabled($plugin)
		{
			return true;
		}
	}

	if (array_key_exists('FeedInstance',$_GET))
	{
		ini_set("display_errors", 1);
		header("Content-type: application/rss+xml");
		// header("Content-type: text/plain");
		$doctype = 'xml';

		echo Zymurgy::plugin('CustomTableRSS', $_GET['FeedInstance'], $doctype);
	}
?>
