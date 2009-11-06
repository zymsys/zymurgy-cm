<?
/**
 *
 * @package Zymurgy_Plugins
 */
class TagCloud extends PluginBase
{
	function GetTitle()
	{
		return 'Tag Cloud Plugin';
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
		$configItems["Table"] = array(
			"name" => "Table",
			"default" => "",
			"inputspec" => "databasetable.false",
			"authlevel" => 0);
		$configItems["Random"] = array(
			"name" => "Randomize Tags",
			"default" => "yes",
			"inputspec" => "drop.yes,no",
			"authlevel" => 0);
		$configItems["SearchBox"] = array(
			"name" => "Search Box",
			"default" => "yes",
			"inputspec" => "drop.yes,no",
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
					DefineTableField("relatedrow", "bigint", "UNSIGNED NOT NULL")
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
		$myname = "ZymurgyTagCloud_".$this->iid;
		$r =
			Zymurgy::YUI('fonts/fonts-min.css').
			Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js').
			Zymurgy::YUI('dragdrop/dragdrop.js').
			Zymurgy::YUI('element/element.js').
			Zymurgy::YUI('datasource/datasource.js').
			Zymurgy::YUI('connection/connection.js').
			Zymurgy::RequireOnce('/zymurgy/include/tagcloud.css').
			Zymurgy::RequireOnce('/zymurgy/include/tagcloud.js');
		$r .= <<<ENDBLOCK
<div id=\"$myname\"></div>
<script type="text/javascript">
/* <![CDATA[ */
YAHOO.util.Event.onDOMReady(function () {
        //Create and render tag cloud
        var zymurgyTagCloud{$this->iid} = new ZymurgyTagCloud("$myname","/zymurgy/include/tagcloud.php?iid={$this->iid}");
});
/* ]]> */
</script>
ENDBLOCK;
		return $r;
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

function TagCloudFactory()
{
	return new TagCloud();
}

/**
 * Provides an input widget for selecting an available database table.
 *
 * @package Zymurgy
 * @subpackage inputwidgets
 *
 */
class PIW_CloudTags extends ZIW_Base
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
	ZymurgyTagCloudWidget('{$jsName}','tcw{$jsName}','/tags.php', {0});
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
			$output = str_replace("{0}", "", $output);
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

		$output .= "function GetSpecifier_PIW_CloudTags(inputspecName) {\n";
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