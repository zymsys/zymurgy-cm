<?php
/**
 *
 * @package Zymurgy_Plugins
 */

if (!class_exists('PluginBase'))
{
	require_once('../cms.php');
	require_once('../PluginBase.php');
	require_once('../include/Thumb.php');
}

class TagCloudResult extends PluginBase
{
	function GetTitle()
	{
		return 'Tag Cloud Results Plugin';
	}

	function GetUninstallSQL()
	{
		return '';
	}

	function GetConfigItems()
	{
		$configItems = array();
		$configItems[] = array(
			"name" => "Input plugin instance",
			"default" => "",
			"inputspec" => "lookup.zcm_plugininstance.id.name.name",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Content Link",
			"default" => "",
			"inputspec" => "input.50.200",
			"authlevel" => 0);
		$configItems[] = array(
			"name" => "Table Header",
			"default" => "",
			"inputspec" => "textarea.60.5",
			"authlevel" => 0);
		return $configItems;
	}

	function VerifyTableDefinitions()
	{
	}

	function Initialize()
	{
		$this->VerifyTableDefinitions();
	}

	function Render()
	{
		$r = "n/a";

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

	function RenderHTML()
	{
		$sql = "SELECT `name` FROM `zcm_plugininstance` WHERE `id` = '".
			Zymurgy::$db->escape_string($this->iid).
			"'";
		$instanceName = Zymurgy::$db->get($sql);

		$output = <<<BLOCK
<div id="tcrcloud{0}"></div>
<script type="text/javascript">
	var dataforcloud{0} = {
		url: "/zymurgy/plugins/TagCloudResult.php?ResultInstance={1}&",
		targetElement: "tcrcloud{0}",
		handleSuccess: function(o) {
			document.getElementById(this.targetElement).innerHTML = o.responseText;
		},
		handleFailure: function(o) {
			document.getElementById(this.targetElement).innerHTML = o.status +
				": " + o.responseText;
		},
		startRequest: function() {
//			alert("StartRequest start");
			document.getElementById(this.targetElement).innerHTML = "Updating...";

			url = this.url + arguments[0];
//			alert(url);

			var callback = {
				success: this.handleSuccess,
				failure: this.handleFailure,
				scope: this
			};

			YAHOO.util.Connect.asyncRequest(
				"GET",
				this.url + arguments[0],
				callback,
				null);

//			alert("StartRequest fin");
		}
	};
</script>
BLOCK;

		$output = str_replace("{0}", $this->GetConfigValue("Input plugin instance"), $output);
		$output = str_replace("{1}", $instanceName, $output);

		echo $output;
	}

	function RenderXML()
	{
		$selected = array();
		$selectedFilter = "1 = 1";

		foreach ($_GET as $key=>$value)
		{
			if ($key[0]=='s')
			{
				if (is_numeric(substr($key,1)))
				{
					$selected[] = $value;
				}
			}
		}

		if(count($selected) > 0)
		{
			$selectedFilter = "`zcm_tagcloudtag`.`name` IN ( '".
				implode("','", $selected).
				"' )";
		}

		$sql = "SELECT DISTINCT `relatedrow` FROM `zcm_tagcloudrelatedrow` INNER JOIN `zcm_tagcloudtag` ON `zcm_tagcloudtag`.`id` = `zcm_tagcloudrelatedrow`.`tag` WHERE `zcm_tagcloudtag`.`instance` = '".
			Zymurgy::$db->escape_string($this->GetConfigValue("Input plugin instance")).
			"' AND $selectedFilter";
//		die($sql);

		$riRelatedRows = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of related rows: ".Zymurgy::$db->error().", $sql");

//		header('Content-type: text/xml');

/*		echo("<?xml version=\"1.0\"?>\n"); */
//		echo "<results>\r\n";

		echo("<table class=\"ZymurgyTagCloudResult\">");

		if(strlen($this->GetConfigValue("Table Header")) > 0)
		{
			echo($this->GetConfigValue("Table Header"));
		}

		while(($relatedRow = Zymurgy::$db->fetch_array($riRelatedRows)) !== FALSE)
		{
//			echo("<hit>");
			echo("<tr class=\"ZymurgyTagCloudResultRow\">");

			$relatedRowList = explode(".", $relatedRow["relatedrow"]);

			$fields = $this->GetFields($relatedRowList[0]);

//			die("<pre>".print_r($fields, true)."</pre>");

			$sql = "SELECT `id`, `".
				implode("`, `", array_keys($fields)).
				"` FROM `".
				Zymurgy::$db->escape_string($relatedRowList[0]).
				"` WHERE `id` = '".
				Zymurgy::$db->escape_string($relatedRowList[1]).
				"'";
//			die($sql);
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve data: ".Zymurgy::$db->error().", $sql");

			include_once(Zymurgy::$root."/zymurgy/InputWidget.php");

//			$records = array();
			$widget = new InputWidget();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
//				$recordFields = array();

//				echo("<table>".$relatedRowList[0]."</table>");
//				echo("<id>".$row["id"]."</id>");

				foreach($fields as $fieldName => $field)
				{
					$widget->extra["dataset"] = $relatedRowList[0];
					$widget->extra["datacolumn"] = $fieldName;
					$widget->datacolumn = $relatedRowList[0].".".$fieldName;
					$widget->editkey = $row["id"];

					$fieldValue = $widget->Display($field["inputspec"], "{0}", $row[$fieldName]);
					$fieldValue = addslashes($fieldValue);
					$fieldValue = str_replace("\'", "'", $fieldValue);

//					echo("<".$fieldName.">".htmlentities($fieldValue)."</".$fieldName.">");
//					echo("<".$fieldName.">".$field["inputspec"]."</".$fieldName.">");

					if(strlen($this->GetConfigValue("Content Link")) > 0)
					{
						echo("<td class=\"ZymurgyTagCloudResultField_".$fieldName."\"><a href=\"".
							str_replace("{0}", $row["id"],
							str_replace("{1}", $relatedRowList[0], $this->GetConfigValue("Content Link"))).
							"\">".
							htmlentities($fieldValue)."</a></td>");
					}
					else
					{
						echo("<td class=\"ZymurgyTagCloudResultField_".$fieldName."\">".htmlentities($fieldValue)."</td>");
					}
				}

//				$records[] = implode(",", $recordFields);
			}

			Zymurgy::$db->free_result($ri);

			$sql = "SELECT `zcm_tagcloudtag`.`name` FROM `zcm_tagcloudtag` WHERE `instance` = '".
				Zymurgy::$db->escape_string($this->GetConfigValue("Input plugin instance")).
				"' AND `name` NOT IN('".
				implode("','", $selected).
				"') AND EXISTS(SELECT 1 FROM `zcm_tagcloudrelatedrow` WHERE `zcm_tagcloudrelatedrow`.`tag` = `zcm_tagcloudtag`.`id` AND `relatedrow` = '".
				Zymurgy::$db->escape_string($relatedRow["relatedrow"]).
				"')";
//			die($sql);
			$ri = Zymurgy::$db->query($sql)
				or die("Could not related tags: ".Zymurgy::$db->error().", $sql");

			echo("<td class=\"ZymurgyRelatedTagList\">");
			$tags = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
//				echo("<tag>".
//					htmlentities($row["name"]).
//					"</tag>");
				$tags[] = "<a class=\"ZymurgyRelatedTag\" href=\"javascript:;\" onclick=\"tagcloud".
					$this->GetConfigValue("Input plugin instance").
					".onTagClick({ currentTarget: document.getElementById('cloud".
					$this->GetConfigValue("Input plugin instance").
					"_".
					htmlentities($row["name"]).
					"') }, tagcloud".
					$this->GetConfigValue("Input plugin instance").
					");\">".
					htmlentities($row["name"]).
					"</a>";
			}

			echo(implode(", ", $tags));
			echo("</td>");

			Zymurgy::$db->free_result($ri);

//			echo("</hit>");
			echo("</tr>");
		}

//		echo "</results>\r\n";
		echo("</table>");

		Zymurgy::$db->free_result($riRelatedRows);
	}

	private function GetFields(
		$tableName,
		$gridHeaderOnly = true)
	{
		$gridHeaderCriteria = "1 = 1";

		if($gridHeaderOnly)
		{
			$gridHeaderCriteria = "LENGTH(`gridheader`) > 0";
		}

		$sql = "SELECT `cname`, `inputspec`, `gridheader`, `caption` FROM `zcm_customfield` WHERE EXISTS(SELECT 1 FROM `zcm_customtable` WHERE `zcm_customtable`.`id` = `zcm_customfield`.`tableid` AND `zcm_customtable`.`tname` = '".
			Zymurgy::$db->escape_string($tableName).
			"') AND $gridHeaderCriteria ORDER BY `disporder`";
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

	function AdminMenuText()
	{
		return 'TagCloud';
	}

	function RenderAdmin()
	{
		echo "This is the admin for the TagCloud plugin.";
	}
}

function TagCloudResultFactory()
{
	return new TagCloudResult();
}

if (array_key_exists('ResultInstance',$_GET))
{
	$suppressDatagridJavascript = true;
//	die("Suppress: ".$suppressDatagridJavascript);

	echo plugin('TagCloudResult',$_GET['ResultInstance'], "xml");
}
?>