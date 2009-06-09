<?
// Custom field definitions may only be set by a webmaster
$adminlevel = 2;

ob_start();

$t = 0 + $_GET['t'];

require_once('cmo.php');
include 'datagrid.php';
include 'customlib.php';

$crumbs = array("customtable.php"=>"Custom Tables");
tablecrumbs($t);
$crumbs["customfield.php?t=$t"] = 'Fields';
if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$crumbs[''] = 'Edit';
}

include 'header.php';

echo Zymurgy::YUI('yahoo/yahoo-min.js');
echo Zymurgy::YUI('event/event-min.js');
echo Zymurgy::YUI('connection/connection-min.js');

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	global $t;
	$tbl = gettable($t);
	$sql = "alter table `{$tbl['tname']}` drop `{$values['zcm_customfield.cname']}`";
	mysql_query($sql) or die("Unabel to remove column ($sql): ".mysql_error());
	return true; //Return false to override delete.
}

//The values array contains tablename.columnname keys with the proposed new values for the updated row.
function OnBeforeUpdate($values)
{
	$okname = okname($values['zcm_customfield.cname']);
	if ($okname!==true)
	{
		return $okname;
	}
	global $t;
	$tbl = gettable($t);
	$sqltype = inputspec2sqltype($values['zcm_customfield.inputspec']);
	$sql = "select * from zcm_customfield where id={$values['zcm_customfield.id']}";
	$ri = mysql_query($sql) or die("Unable to get old field info ($sql): ".mysql_error());
	$old = mysql_fetch_array($ri) or die ("No such field ($sql)");
	if (($old['cname']!=$values['zcm_customfield.cname']) || ($old['inputspec']!=$values['zcm_customfield.inputspec']))
	{
		//Column name or type has changed, update the db.
		$sql = "alter table `{$tbl['tname']}` change `{$old['cname']}` `{$values['zcm_customfield.cname']}` $sqltype";
		mysql_query($sql) or die("Unable to change field ($sql): ".mysql_error());
	}
	if ($old['indexed']!=$values['zcm_customfield.indexed'])
	{
		//Add or remove an index
		if ($values['zcm_customfield.indexed']=='Y')
		{
			$sql = "alter table `{$tbl['tname']}` add index(`{$values['zcm_customfield.cname']}`)";
		}
		else
		{
			$sql = "alter table `{$tbl['tname']}` drop index(`{$values['zcm_customfield.cname']}`)";
		}
		mysql_query($sql) or die("Can't change index ($sql): ".mysql_error());
	}
	return $values; // Change values you want to alter before the update occurs.
}

//The values array contains tablename.columnname keys with the proposed new values for the new row.
function OnBeforeInsert($values)
{
	$okname = okname($values['zcm_customfield.cname']);
	if ($okname!==true)
	{
		return $okname;
	}
	global $t;
	$tbl = gettable($t);
	$sqltype = inputspec2sqltype($values['zcm_customfield.inputspec']);
	$sql = "alter table `{$tbl['tname']}` add `{$values['zcm_customfield.cname']}` $sqltype";
	mysql_query($sql) or die("Unable to add column ($sql): ".mysql_error());
	if ($values['zcm_customfield.indexed']=='Y')
	{
		$sql = "alter table `{$tbl['tname']}` add index(`{$values['zcm_customfield.cname']}`)";
	}
	return $values; // Change values you want to alter before the insert occurs.
}

$ds = new DataSet('zcm_customfield','id');
$ds->AddColumns('id','disporder','tableid','cname','inputspec','caption','indexed','gridheader');
$ds->AddDataFilter('tableid',$t);
$ds->OnBeforeUpdate = 'OnBeforeUpdate';
$ds->OnBeforeInsert = 'OnBeforeInsert';
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddConstant('tableid',$t);
$dg->AddColumn('Field Name','cname');
$dg->AddColumn('Indexed','indexed');
$dg->AddColumn('Grid Header','gridheader');
$dg->AddUpDownColumn('disporder');
$dg->AddInput('cname','Field Name:',30,30);
$dg->AddDropListEditor('indexed','Indexed?',array('N'=>'No','Y'=>'Yes'));
$dg->AddInput('gridheader','Grid Header:',30,30);
$dg->AddEditor('inputspec','Input Spec:','inputspec');
$dg->AddInput('caption','Caption:',4096,40);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Field';
$dg->Render();
?>
<script language="javascript" type="text/javascript">
	var tableNameAjaxObject = {
		handleSuccess:function(tableXML) {
		 	var items = tableXML.responseXML.getElementsByTagName("table");
			var list = document.getElementById("param_0");
			var listDefault = document.getElementById("param_0_default").value;

			list.options.length = 0;

			for(var cntr = 0; cntr < items.length; cntr++)
			{
				var tableName = items[cntr].firstChild.nodeValue;

				list.options[cntr] = new Option(tableName, tableName);

				if(listDefault == tableName)
					list.selectedIndex = cntr;
			}

			var valueColumn = document.getElementById("param_2");
			var sortColumn = document.getElementById("param_3");

			// clear the existing options
			valueColumn.options.length = 0;
			sortColumn.options.length = 0;

			valueColumn.options[0] = new Option("Loading...", "Loading...");
			sortColumn.options[0] = new Option("Loading...", "Loading...");

			setTimeout("getColumnNames();", 100);
		},

		handleFailure:function(tableXML) {
			alert("tableNameAjaxObject: Cannot retrieve XML data. " + tableXML.statusText);
		},

		startRequest:function() {
			YAHOO.util.Connect.asyncRequest(
				'GET',
				'gettables.php',
				tableNameCallback,
				"");
		}
	};

	var tableNameCallback = {
		success:tableNameAjaxObject.handleSuccess,
		failure:tableNameAjaxObject.handleFailure,
		scope:tableNameAjaxObject
	};

	function getTableNames()
	{
		var tableNames = new Array();

		tableNames.push("Loading...");

		// setTimeout("loadTableNames()", 100);
		setTimeout("tableNameAjaxObject.startRequest();", 100);

		return tableNames;
	}

	var columnNameAjaxObject = {
		handleSuccess:function(columnXML) {
		 	var items = columnXML.responseXML.getElementsByTagName("field");

			var valueColumn = document.getElementById("param_2");
			var sortColumn = document.getElementById("param_3");

			var valueDefault = document.getElementById("param_2_default").value;
			var sortDefault = document.getElementById("param_3_default").value;

			// clear the existing options
			valueColumn.options.length = 0;
			sortColumn.options.length = 0;

			sortColumn.options[0] = new Option("(none)", "");

			var hasDispOrder = items[0].firstChild.nodeValue;
			var startIndex = 0;

			if(hasDispOrder == "disporder")
			{
				sortColumn.options[1] = new Option("disporder", "disporder");
				startIndex = 1;
			}

			for(var cntr = startIndex; cntr < items.length; cntr++)
			{
				var columnName = items[cntr].firstChild.nodeValue;

	 			valueColumn.options[cntr - startIndex] = new Option(columnName, columnName);
				sortColumn.options[cntr + 1] = new Option(columnName, columnName);

				if(valueDefault == columnName)
					valueColumn.selectedIndex = cntr - startIndex;

				if(sortDefault == columnName)
					sortColumn.selectedIndex = cntr + 1;
			}
		},

		handleFailure:function(columnXML) {
			alert("columnNameAjaxObject: Cannot retrieve XML data. " + columnXML.statusText);
		},

		startRequest:function() {
			selectedTable = document.getElementById("param_0").options[
			document.getElementById("param_0").selectedIndex].value;

			YAHOO.util.Connect.asyncRequest(
				'GET',
				'getfields.php?tname=' + selectedTable,
				columnNameCallback,
				"");
		}
	};

	var columnNameCallback = {
		success:columnNameAjaxObject.handleSuccess,
		failure:columnNameAjaxObject.handleFailure,
		scope:columnNameAjaxObject
	};

	function getColumnNames()
	{
		setTimeout("columnNameAjaxObject.startRequest();", 100);
	}
</script>
<?php
include('footer.php');
?>
