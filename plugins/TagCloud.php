<?
/**
 *
 * @package Zymurgy_Plugins
 */

ini_set("display_errors", 1);

if (!class_exists('PluginBase'))
{
	require_once('../cms.php');
	require_once('../PluginBase.php');
	require_once('../include/Thumb.php');
}

class TagCloud extends PluginBase
{
	function GetTitle()
	{
		return 'Tag Cloud Plugin';
	}

	function GetDescription()
	{
		return <<<BLOCK
			<h3>Tag Cloud Plugin</h3>
			<p><b>Provider:</b> Zymurgy Systems, Inc.</p>
			<p>This plugin provides:</p>
			<ul>
				<li>A front end component for selecting tags from a tag
				cloud</li>
				<li>A custom inputspec (taglist) for associating records in
				custom tables to tags</li>
			</ul>
			<p>This plugin is best used in association with the TagCloudResult
			plugin, which displays the records associated with a given tag
			in a table.</p>
BLOCK;
	}

	function GetUninstallSQL()
	{
		return
			'drop table zcm_tagcloud;'.
			'drop table zcm_tagcloudtag;'.
			'drop table zcm_tagcloudrelatedtag;'.
			'drop table zcm_tagcloudrelatedrow';
	}

	function GetConfigItems()
	{
		$configItems = array();
		$configItems["Cloud"] = array(
			"name" => "Name of tag cloud",
			"default" => "",
			"inputspec" => "input.20.50",
			"authlevel" => 0);
		return $configItems;
	}

	function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');

		$tableDefinitions = array(
			array(
				"name" => "zcm_tagcloudtag",
				"columns" => array(
					DefineTableField("id", "BIGINT", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "BIGINT", "UNSIGNED NOT NULL"),
					DefineTableField("name", "VARCHAR(100)", "DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => "false", "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_tagcloudrelatedtag",
				"columns" => array(
					DefineTableField("id", "BIGINT", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "BIGINT", "UNSIGNED NOT NULL"),
					DefineTableField("taga", "bigint", "UNSIGNED NOT NULL"),
					DefineTableField("tagb", "bigint", "UNSIGNED NOT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => "false", "type" => ""),
					array("columns" => "taga", "unique" => "false", "type" => ""),
					array("columns" => "tagb", "unique" => "false", "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			),
			array(
				"name" => "zcm_tagcloudrelatedrow",
				"columns" => array(
					DefineTableField("id", "BIGINT", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "BIGINT", "UNSIGNED NOT NULL"),
					DefineTableField("tag", "bigint", "UNSIGNED NOT NULL"),
					DefineTableField("relatedrow", "VARCHAR(200)", "NOT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => "false", "type" => ""),
					array("columns" => "tag", "unique" => "false", "type" => ""),
					array("columns" => "relatedrow", "unique" => "false", "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
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
		require_once(Zymurgy::$root."/zymurgy/InputWidget.php");
		$widget = new InputWidget();

		$widget->Render(
			"tagcloud.".$this->GetConfigValue("Name of tag cloud"),
			"cloud".$this->iid,
			"");
	}

	function RenderXML()
	{
		$selected = array();
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
			$sql = "SELECT `zcm_tagcloudtag`.`id`, `name`, COUNT(*) as `count` FROM `zcm_tagcloudtag` INNER JOIN `zcm_tagcloudrelatedrow` row1 ON `zcm_tagcloudtag`.`id` = row1.`tag` WHERE  `zcm_tagcloudtag`.`instance` = '".
				Zymurgy::$db->escape_string($this->iid).
				"' AND `relatedrow` IN ( SELECT `relatedrow` FROM `zcm_tagcloudrelatedrow` row2 INNER JOIN `zcm_tagcloudtag` tag2 ON tag2.`id` = row2.`tag` WHERE tag2.`name` IN ( '".
				implode("','", $selected).
				"' ) ) AND `zcm_tagcloudtag`.`name` NOT IN ( '".
				implode("','", $selected).
				"' ) GROUP BY `zcm_tagcloudtag`.`id`, `name` ORDER BY `zcm_tagcloudtag`.`id`, `name`";
		}
		else
		{
			$sql = "SELECT `zcm_tagcloudtag`.`id`, `name`, COUNT(*) AS `count` FROM `zcm_tagcloudtag` INNER JOIN `zcm_tagcloudrelatedrow` ON `zcm_tagcloudtag`.`id` = `zcm_tagcloudrelatedrow`.`tag` WHERE  `zcm_tagcloudtag`.`instance` = '".
				Zymurgy::$db->escape_string($this->iid).
				"' AND `name` LIKE '%".
				Zymurgy::$db->escape_string(isset($_GET["q"]) ? $_GET["q"] : "").
				"%' GROUP BY `zcm_tagcloudtag`.`id`, `name` ORDER BY `zcm_tagcloudtag`.`id`, `name`";
		}
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of related rows: ".Zymurgy::$db->error().", $sql");

		header('Content-type: text/xml');

		echo("<?xml version=\"1.0\"?>\n");
		echo "<results>\r\n";

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			if(count($selected) > 0)
			{
				// fancy query here
			}

			echo("<tag><id>".
				$row["id"].
				"</id><name>".
				htmlentities($row["name"]).
				"</name><hits>".
				$row["count"].
				"</hits></tag>\r\n");

			$firstRow = false;
		}

		echo "</results>\r\n";

		Zymurgy::$db->free_result($ri);
	}

	function RenderAdmin()
	{
		echo "This is the admin for the TagCloud plugin.";
	}
}

function TagCloudFactory()
{
	return new TagCloud();
}

if (array_key_exists('DataInstance',$_GET))
{
	echo plugin('TagCloud',$_GET['DataInstance'], "xml");
}

/**
 * Provides an input widget for selecting an available database table.
 *
 * @package Zymurgy
 * @subpackage inputwidgets
 *
 */
class PIW_CloudTagInput extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$jsName = Zymurgy::getsitenav()->linktext2linkpart($name);

		echo Zymurgy::YUI('fonts/fonts-min.css');
		echo Zymurgy::YUI('autocomplete/assets/skins/sam/autocomplete.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI('connection/connection-min.js');
		echo Zymurgy::YUI('animation/animation-min.js');
		echo Zymurgy::YUI('datasource/datasource-min.js');
		echo Zymurgy::YUI('autocomplete/autocomplete-min.js');

		echo Zymurgy::RequireOnce("/zymurgy/include/tagcloudwidget.js");

		$output = <<<BLOCK
<input type="hidden" name="{$jsName}" id="{$jsName}" value="{$value}">
<div id="tcw{$jsName}"></div>
<script type="text/javascript">
	ZymurgyTagCloudWidget('{$jsName}','tcw{$jsName}','/zymurgy/plugins/TagCloud.php?DataInstance={$ep[1]}', {0});
</script>
BLOCK;

		if(is_array($value) && count($value) > 0)
		{
			$output = str_replace("{0}", "\"".implode("\",\"", $value)."\"", $output);
		}
		else if(strlen($value) > 0)
		{
			$output = str_replace("{0}", "\"$value\"", $output);
		}
		else
		{
			$output = str_replace("{0}", "\"\"", $output);
		}

		echo $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return 'VARCHAR(200)';
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_PIW_CloudTagInput(inputspecName) {\n";
		$output .= " var description = \"Cloud Tags\"\n";

		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = description;\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"For Tag Cloud Named\", 30, 200, \"\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}
}

class PIW_CloudTagCloud extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$jsName = Zymurgy::getsitenav()->linktext2linkpart($name);

		echo Zymurgy::YUI('fonts/fonts-min.css');
		echo Zymurgy::YUI('autocomplete/assets/skins/sam/autocomplete.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI("dragdrop/dragdrop.js");
		echo Zymurgy::YUI("element/element.js");
		echo Zymurgy::YUI('connection/connection-min.js');
		echo Zymurgy::YUI('animation/animation-min.js');
		echo Zymurgy::YUI('datasource/datasource-min.js');
		echo Zymurgy::YUI("datatable/datatable.js");
		echo Zymurgy::YUI('autocomplete/autocomplete-min.js');

		echo Zymurgy::RequireOnce("/zymurgy/include/tagcloud.js");

		$output = <<<BLOCK
<input type="hidden" name="{$jsName}" id="{$jsName}" value="{$value}">
<div id="tc{$jsName}"></div>
<script type="text/javascript">
	YAHOO.util.Event.onDOMReady(function() {
		tag{$jsName} = ZymurgyTagCloud(
			'tc{$jsName}',
			'/zymurgy/plugins/TagCloud.php?DataInstance={$ep[1]}',
			'{$jsName}');

//		alert("Checking for results table.");

		if(document.getElementById('tcr{$jsName}')) {
//			alert("Found results table.");
//			alert(datafor{$jsName});
			datafor{$jsName}.startRequest("");
//			alert("Request for results sent.");
		}

//		alert("onDOMReady fin");
	});
</script>
BLOCK;

		echo $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		die("Not to be used to store content. Only used for display.");
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_PIW_CloudTagCloud(inputspecName) {\n";
		$output .= " var description = \"Cloud Tags\"\n";

		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = description;\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"For Tag Cloud Named\", 30, 200, \"\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}
}
?>