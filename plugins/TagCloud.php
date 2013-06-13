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
	/**
	 * Array of all system tags
	 * Keys are tag IDs, values are flavoured text strings
	 * @var array
	 */
	private $alltags;
	
	/**
	 * Array of all system tags
	 * Keys are tag IDs, values are flavour text IDs.
	 * @var array
	 */
	private $alltagsraw;

    private $tagCloudServiceURL;

    function __construct()
    {
        $this->tagCloudServiceURL = Zymurgy::getUrlPath('~plugins/TagCloud.php');
    }
	
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
		$configItems[] = array(
			"name" => "Magic/Hidden Tags",
			"default" => "",
			"inputspec" => "input.50.200",
			"authlevel" => 0);
		return $configItems;
	}

	function VerifyTableDefinitions()
	{
		require_once(Zymurgy::getFilePath('~installer/upgradelib.php'));

		$tableDefinitions = array(
			array(
				"name" => "zcm_tagcloudtag",
				"columns" => array(
					DefineTableField("id", "BIGINT", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "BIGINT", "UNSIGNED NOT NULL"),
					DefineTableField("name", "FLAVOURED", "")
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
	
	function Upgrade()
	{
		parent::Upgrade();
		$this->VerifyTableDefinitions();
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

	function LoadTags()
	{
		if (isset($this->alltags)) return $this->alltags;
		
		//Get ID #'s of tags, and their flavoured text ID #'s.
		$ri = Zymurgy::$db->run("SELECT `id`,`name` FROM `zcm_tagcloudtag` WHERE `instance`=".$this->iid);
		$tags = array();
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$tags[$row['id']] = $row['name'];
		}
		Zymurgy::$db->free_result($ri);
		$this->alltagsraw = $tags;
		
		//Get flavoured text ID #'s and default values for all required flavoured text
		$flavoured = array();
		if ($tags)
		{
			$ri = Zymurgy::$db->run("SELECT `id`,`default` FROM `zcm_flavourtext` WHERE `id` IN (".implode(',', $tags).")");
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$flavoured[$row['id']] = $row['default'];
			}
			Zymurgy::$db->free_result($ri);
		}
		
		//Override default values with flavoured values where possible
		if ($flavoured)
		{
			$flavourcode = array_key_exists('flavour', $_GET) ? $_GET['flavour'] : 'pages';
			if ($flavourcode != 'pages')
			{
				$flavour = Zymurgy::GetFlavourByCode($flavourcode);
				$ri = Zymurgy::$db->run("SELECT `zcm_flavourtext`,`text` FROM `zcm_flavourtextitem` WHERE `flavour`=".
					$flavour['id']." AND `zcm_flavourtext` IN (".
					implode(',', $tags).")");
				while (($row = Zymurgy::$db->fetch_array($ri))!==false)
				{
					$flavoured[$row['zcm_flavourtext']] = $row['text'];
				}
				Zymurgy::$db->free_result($ri);
			}
		}
		//Zymurgy::DbgAndDie($flavoured);
		
		$tagids = array_keys($tags);
		foreach ($tagids as $id)
		{
			if (array_key_exists($tags[$id], $flavoured))
				$tags[$id] = $flavoured[$tags[$id]];
		}
		$this->alltags = $tags;
		return $tags;
	}
	
	function RenderHTML()
	{
		require_once(Zymurgy::getFilePath("~InputWidget.php"));
		$widget = new InputWidget();

		$widget->Render(
			"tagcloud.".$this->GetConfigValue("Name of tag cloud"),
			"cloud".$this->iid,
			$this->extra);
	}
	
	function TagNameToFlavourID($tagname)
	{
		$this->LoadTags();
		$tagid = array_search($tagname, $this->alltags);
		if ($tagid === false) return FALSE;
		return array_key_exists($tagid, $this->alltagsraw) ? $this->alltagsraw[$tagid] : FALSE;
	}

	function BogoRenderXML()
	{
		$this->LoadTags();
		Zymurgy::DbgAndDie($this->alltags,$this->alltagsraw);
	}
	
	//Not sure why this renders XML to do with selected tags, etc.
	//Starting over with flavour aware version, then I'll add stuff as needed, and try to make it easy to understand.
	function RenderXML()
	{
		$this->LoadTags();
		
		$selected = array();
		foreach ($_GET as $key=>$value)
		{
			if ($key[0]=='s')
			{
				if (is_numeric(substr($key,1)))
				{
					$selected[] = $this->TagNameToFlavourID($value);
				}
			}
		}
		
		//Zymurgy::DbgAndDie($this->alltags,$this->alltagsraw,$selected);

		if(count($selected) > 0)
		{
			//$selected contains the flavoured text ID's for each of the keywords selected in the tag cloud.
			//$innersql is the select statement which grabs all of the items which contain any one of the selected tags
			$innersql = "SELECT `relatedrow` FROM `zcm_tagcloudrelatedrow` row2 INNER JOIN `zcm_tagcloudtag` tag2 ON tag2.`id` = row2.`tag` WHERE tag2.`name` IN ( '".
				implode("','", $selected)."' )";
			$sql = "SELECT `zcm_tagcloudtag`.`id`, `name`, COUNT(*) as `count` FROM `zcm_tagcloudtag` INNER JOIN `zcm_tagcloudrelatedrow` row1 ON `zcm_tagcloudtag`.`id` = row1.`tag` WHERE  `zcm_tagcloudtag`.`instance` = '".
				Zymurgy::$db->escape_string($this->iid).
				"' AND `relatedrow` IN ( $innersql ) AND `zcm_tagcloudtag`.`name` NOT IN ( '".
				implode("','", $selected).
				"' ) GROUP BY `zcm_tagcloudtag`.`id`, `name` ORDER BY `zcm_tagcloudtag`.`id`, `name`";
		}
		else
		{
			$hits = array();
			$needle = trim(strtolower($_GET['q']));
			if (!empty($needle))
			{
				foreach ($this->alltags as $tagid=>$tagtext)
				{
					if (strpos(strtolower($tagtext), $needle) !== false)
					{
						$hits[] = $tagid;
					}
				}
				if (!$hits)
				{
					//No hits - make sure no records are found.  An empty array means return all tags for an empty query.
					$hits[] = -1;
				}
			}
			$sql = "SELECT `zcm_tagcloudtag`.`id`, `name`, COUNT(*) AS `count` FROM `zcm_tagcloudtag` INNER JOIN `zcm_tagcloudrelatedrow` ON `zcm_tagcloudtag`.`id` = `zcm_tagcloudrelatedrow`.`tag` WHERE  `zcm_tagcloudtag`.`instance` = '".
				Zymurgy::$db->escape_string($this->iid)."' ";
			if ($hits)
			{
				$sql .= "AND (`zcm_tagcloudtag`.`id` IN (".implode(',', $hits).")) ";
			}
			$sql .= "GROUP BY `zcm_tagcloudtag`.`id`, `name` ORDER BY `zcm_tagcloudtag`.`id`, `name`";
		}
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of related rows: ".Zymurgy::$db->error().", $sql");

		header('Content-type: text/xml');

		echo("<?xml version=\"1.0\"?>\n");
		echo "<results>\r\n";

		$magicTags = explode(",", $this->GetConfigValue("Magic/Hidden Tags"));
		$flavourcode = array_key_exists('flavour', $_GET) ? $_GET['flavour'] : 'pages';
		
		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			if(count($selected) > 0)
			{
				// fancy query here
			}

			if(!in_array($row["name"], $magicTags))
			{
				echo("<tag><id>".
					$row["id"].
					"</id><name>".
					htmlentities(ZIW_Base::GetFlavouredValue($row["name"],$flavourcode)).
					"</name><hits>".
					$row["count"].
					"</hits></tag>\r\n");
			}

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

		echo Zymurgy::RequireOnce(Zymurgy::getUrlPath("~include/tagcloudwidget.js"));

		$output = <<<BLOCK
<input type="hidden" name="{$jsName}" id="{$jsName}" value="{$value}">
<div id="tcw{$jsName}"></div>
<script type="text/javascript">
	ZymurgyTagCloudWidget('{$jsName}','tcw{$jsName}','{$this->tagCloudServiceURL}?DataInstance={$ep[1]}', {0});
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

		echo Zymurgy::RequireOnce(Zymurgy::getUrlPath("~include/tagcloud.js"));

		$startTagsParam = "";
		if (!empty($value))
		{
			$startTags = explode(",", $value);
	
			if(count($startTags) > 0)
			{
				for($cntr = 0; $cntr < count($startTags); $cntr++)
				{
					$startTags[$cntr] = "s".$cntr."=".$startTags[$cntr];
				}
	
				$startTagsParam = implode("&", $startTags);
				$startTagsParam = "&".$startTagsParam;
			}
		}
		
		$output = <<<BLOCK
<input type="hidden" name="{$jsName}" id="{$jsName}" value="{$value}">
<div id="tc{$jsName}"></div>
<script type="text/javascript">
	YAHOO.util.Event.onDOMReady(function() {
		tag{$jsName} = new ZymurgyTagCloud(
			'tc{$jsName}',
			'{$this->tagCloudServiceURL}?DataInstance={$ep[1]}{$startTagsParam}',
			'{$jsName}');

		if(document.getElementById('tcr{$jsName}')) {
			datafor{$jsName}.startRequest("");
		}
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