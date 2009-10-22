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

	class CustomTable extends PluginBase
	{
		function GetTitle()
		{
			return 'Custom Table Display Plugin';
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
				"name" => 'Filter',
				"default" => '',
				"inputspec" => 'textarea.60.5',
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
			// echo($this->extra);
			// die();

			$r='';
			switch($this->extra)
			{
				case 'json':
					$r = $this->RenderJSON();
					break;
				default:
					$r = $this->RenderHTML();
					break;
			}
			return $r;
		}

		private function RenderJSON()
		{
			$output = "{\"ResultSet\": { \"totalResultsAvailable\": {0}, \"totalResultsReturned\": {1}, \"firstResultPosition\": {2}, \"Result\": [ {3} ] }}";

			$sql = "SELECT `tname`, `hasdisporder` FROM `zcm_customtable` WHERE `id` = '".
				Zymurgy::$db->escape_string($this->GetConfigValue("Custom Table")).
				"'";
			$table = Zymurgy::$db->get($sql);

			$fields = $this->GetFields();

			$sql = "SELECT `".
				implode("`, `", array_keys($fields)).
				"` FROM `".
				Zymurgy::$db->escape_string($table["tname"]).
				"` ".
				(strlen($this->getConfigValue("Filter")) > 0 ? $this->getConfigValue("Filter") : "").
				" ".
				($table["hasdisporder"] == 1 ? " ORDER BY `disporder`" : "");
//			die($sql);
			$ri = Zymurgy::$db->query($sql)
				or die("Could not retrieve data: ".Zymurgy::$db->error().", $sql");

			$output = str_replace("{0}", Zymurgy::$db->num_rows($ri), $output);
			$output = str_replace("{1}", Zymurgy::$db->num_rows($ri), $output);
			$output = str_replace("{2}", "1", $output);

			$records = array();

			while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
			{
				$recordFields = array();

				foreach($fields as $fieldName => $caption)
				{
					$fieldValue = addslashes($row[$fieldName]);
					$fieldValue = str_replace("\'", "'", $fieldValue);

					$recordFields[] = "\"".
						$fieldName.
						"\": \"".
						$fieldValue.
						"\"";
				}

				$records[] = implode(",", $recordFields);
			}

			Zymurgy::$db->free_result($ri);

			$output = str_replace("{3}", "{".implode("},{", $records)."}", $output);

			return $output;
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

		private function RenderHTML()
		{
			$fields = $this->GetFields();
			$yuiFieldList = array();

			foreach($fields as $fieldName => $caption)
			{
				$yuiFieldList[] = "{ key: \"".
					$fieldName.
					"\", label: \"".
					$caption.
					"\", sortable: true }";
			}

			echo Zymurgy::YUI("fonts/fonts-min.css");
			echo Zymurgy::YUI("paginator/assets/skins/sam/paginator.css");
			echo Zymurgy::YUI("datatable/assets/skins/sam/datatable.css");
			echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
			echo Zymurgy::YUI("connection/connection-min.js");
			echo Zymurgy::YUI("json/json-min.js");
			echo Zymurgy::YUI("element/element-min.js");
			echo Zymurgy::YUI("paginator/paginator-min.js");
			echo Zymurgy::YUI("datasource/datasource-min.js");
			echo Zymurgy::YUI("datatable/datatable-min.js");
?>
	<div class="yui-skin-sam">
		<div id="CustomTable<?= $this->iid ?>"></div>
	</div>
	<script type="text/javascript">
		YAHOO.util.Event.addListener(window, "load", function() {
//			alert("Method start");

			var myColumnDefs = [ <?= implode(", ", $yuiFieldList) ?> ];

//			alert("Columns defined");

			var myDataSource = new YAHOO.util.DataSource("/zymurgy/plugins/CustomTable.php?ViewInstance=<?= $this->InstanceName ?>&");
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
			myDataSource.connXhrMode = "queueRequests";
			myDataSource.responseSchema = {
				resultsList: "ResultSet.Result",
				fields: [ "<?= implode("\", \"", array_keys($fields)) ?>" ]
			};

//			alert("Data Source Defined");

			var tableConfig = {
				paginator: new YAHOO.widget.Paginator({
					rowsPerPage: <?= $this->GetConfigValue("Items per page") ?>
				}),
				initialRequest: ""
			};

			var myDataTable = new YAHOO.widget.DataTable("CustomTable<?= $this->iid ?>", myColumnDefs, myDataSource, tableConfig);

//			alert("DataTable defined");

/*
			var mySuccessHandler = function() {
				alert("Success Handler called");
				this.set("sortedBy", null);
				this.onDataReturnAppendRows.apply(this, arguments);
			};

			var myFailureHandler = function() {
				alert("Failure handler called");
				this.onDataReturnAppendRows.apply(this, arguments);
			};

//			alert("Handlers defined");

			var myCallback = {
				success: mySuccessHandler,
				failure: myFailureHandler,
				scope: myDataTable
			};

//			alert("Method end");

			return {
				oDS: myDataSource,
				oDT: myDataTable
			};
*/
		});
	</script>
<?
		}

		function AdminMenuText()
		{
			return 'CustomTable';
		}

		function RenderAdmin()
		{
			echo "";
		}
	}

	function CustomTableFactory()
	{
		return new CustomTable();
	}

	if (array_key_exists('ViewInstance',$_GET))
	{
		ini_set("display_errors", 1);
		// header("Content-type: application/rss+xml");
		header("Content-type: text/plain");
		$doctype = 'json';

		echo Zymurgy::plugin('CustomTable', $_GET['ViewInstance'], $doctype);
	}

?>