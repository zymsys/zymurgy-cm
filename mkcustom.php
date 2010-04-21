<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
// Code generation may only be performed by a webmaster
$adminlevel = 2;

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"mkcustom.php\">Custom Code</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "Generate Custom Code";	
}

include('header.php');

$dsevents = array(
	'OnDelete'=>"//The values array contains tablename.columnname keys with values from the row to be deleted.\r\nfunction OnDelete(\$values)\r\n{\r\n\treturn true; //Return false to override delete.\r\n}",
	'OnBeforeUpdate'=>"//The values array contains tablename.columnname keys with the proposed new values for the updated row.\r\nfunction OnBeforeUpdate(\$values)\r\n{\r\n\treturn \$values; // Change values you want to alter before the update occurs.\r\n}",
	'OnBeforeInsert'=>"//The values array contains tablename.columnname keys with the proposed new values for the new row.\r\nfunction OnBeforeInsert(\$values)\r\n{\r\n\treturn \$values; // Change values you want to alter before the insert occurs.\r\n}",
	'OnUpdate'=>"//The values array contains tablename.columnname keys with the new values for the updated row.\r\nfunction OnUpdate(\$values)\r\n{\r\n}",
	'OnInsert'=>"//The values array contains tablename.columnname keys with the values for the new row.\r\nfunction OnInsert(\$values)\r\n{\r\n}"
	//'OnPreRenderEdit' Leave this undocumented.  I don't know why I ever needed it, and it uses the old dsr convention which breaks on newer versions of php.
);
	
$noshow = array('id','disporder');

function GetTable()
{
	include_once("datagrid.php");
	DumpDataGridCSS();

?>
	<p>You can use the Generate Custom Code utility to:</p>
	<ul>
		<li>Add the table to the list of Custom Tables managed by Zymurgy:CM. This allows you to use the built-in Custom Table management tools against an existing table.</li>
		<li>Generate PHP code for managing the table in Zymurgy:CM. This allows you to create highly customized edit screens for the table, in cases where the built-in Custom Table management tools are not appropriate.</li>
	</ul>
	<p>To begin, please select the table from the list below:</p>
	<table class="DataGrid" cellspacing="0" cellpadding="3" bordercolor="#999999" border="1" rules="cols">
		<tr class="DataGridHeader">
			<td>Table</td>
			<td>Custom Table</td>
			<td>Generate Code</td>
		</tr>
<?php
	$sql = "show tables";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve table list: ".Zymurgy::$db->error().", $sql");
	$tables = array();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$table = $row[0];
		if (substr($table,0,4) == 'zcm_') continue;
		if (substr($table,0,4) == 'kfm_') continue;
		$tables[] = $table;
	}
	Zymurgy::$db->free_result($ri);

	$sql = "SELECT `tname` FROM `zcm_customtable`";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve list of custom tables: ".Zymurgy::$db->error().", $sql");
	$customtables = array();
	while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
	{
		$customtables[] = $row["tname"];
	}
	Zymurgy::$db->free_result($ri);

	$cntr = 1;
	foreach($tables as $table)
	{
		$inCustomTable = in_array($table, $customtables);
		$customTableLink = $inCustomTable
			? "<span style=\"color: #999999;\">Already custom table</span>"
			: "<a href=\"mkcustom.php?t={$table}&amp;adding=1\">Add to Custom Tables</a>";
?>
		<tr class="DataGridRow<?= $cntr % 2 == 0 ? "Alternate" : "" ?>">
			<td><?= $table ?></td>
			<td><?= $customTableLink ?></td>
			<td><a href="mkcustom.php?t=<?= $table ?>&amp;adding=0">Generate PHP Code</a></td>
		</tr>
<?php
		$cntr++;
	}
?>
	</table>
<?php
}

function GetCustomTableOptions()
{
	include_once("datagrid.php");
	DumpDataGridCSS();

	$table = $_GET['t'];
	$tblname = strtoupper(substr($table,0,1)).substr($table,1);
	$sql = "show columns from $table";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve columns: ".Zymurgy::$db->error.", $sql");

?>
	<p><b><?= $table ?></b></p>

	<p>To add this table to the list of custom tables in Zymurgy:CM, define the Field Type for each of the fields in the table. Zymurgy:CM uses the field type to determine which edit control to use on the built-in management screens.</p>

	<p><b>Lookups</b></p>

	<p>To specify a look-up field, provide a dot-separated string with the following items:</p>
	<ul>
		<li>The name of the table the value is coming from (the table does not have to be specified as a Custom Table in Zymurgy:CM)</li>
		<li>The name of the field the value is coming from</li>
		<li>The name of the field containing the value to display in the drop-down list</li>
		<li>The name of the field used to sort the values in the drop-down list</li>
	</ul>
	<p>For example, given a reference table named <b>issuestatus</b> with the following fields:</p>
	<ul>
		<li><b>id</b> - int(10) unsigned, auto_increment, primary key</li>
		<li><b>name</b> - varchar(50)</li>
		<li><b>disporder</b> - int(10) unsigned</li>
	</ul>
	<p>You would enter the following string for the lookup:</p>
	<blockquote>issuestatus.id.name.disporder</blockquote>

	<form method="GET">
		<input type="hidden" name="t" value="<?= $table ?>">
		<input type="hidden" name="adding" value="1">

		<table class="DataGrid" cellspacing="0" cellpadding="3" bordercolor="#999999" border="1" rules="cols">
			<tr class="DataGridHeader">
				<td>Column</td>
				<td>Data Type</td>
				<td>Field Type</td>
			</tr>
<?php
	$cntr = 1;

	while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
	{
		$fld = $row["Field"];
		list($type,$length) = ParseType($row['Type']);
		switch ($type)
		{
			case('char'):
			case('varchar'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"input.$length.$length\" checked>Text ".
					"<input type=\"radio\" name=\"f$fld\" value=\"attachment\">File ".
					"<input type=\"radio\" name=\"f$fld\" value=\"image.600.400\">Image";
				break;
			case('longtext'):
			case('text'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"textarea.40.5\">Text ".
					"<input type=\"radio\" name=\"f$fld\" value=\"html.600.400\" checked>HTML";
				break;
			case('bigint'):
			case('smallint'):
			case('tinyint'):
			case('int'):
				if($row["Key"] == "PRI")
				{
					$opts = "<input type=\"radio\" name=\"f$fld\" value=\"pk\" CHECKED>Primary Key";
				}
				else
				{
					$opts = "<input type=\"radio\" name=\"f$fld\" value=\"numeric.5.5\" checked>Number".
						"<input type=\"radio\" name=\"f$fld\" value=\"unixdate\">UNIX Date ".
						"<input type=\"radio\" name=\"f$fld\" value=\"currency\">Currency ".
						"<input type=\"radio\" name=\"f$fld\" value=\"lookup\">Lookup:  ".
						"<input type=\"text\" name=\"x$fld\" size=\"10\" value=\"\">";
				}
				break;
			case('smallint'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"bool\" checked>Yes/No ".
					"<input type=\"radio\" name=\"f$fld\" value=\"numeric.5.5\">Number";
				break;
			case('enum'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"input.$length.$length\" checked>Drop List ".
					"<input type=\"radio\" name=\"f$fld\" value=\"radio\">Radio";
				break;
			case('datetime'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"datetime\" checked>MySQL Date/Time";
				break;
			case('date'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"date\" checked>MySQL Date";
				break;
			case('time'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"time\" checked>MySQL Time";
				break;
			default:
				$opts = "Unknown type: $type";
				break;
		}
?>
			<tr class="DataGridRow<?= $cntr % 2 == 0 ? "Alternate" : "" ?>">
				<td><?= $row["Field"] ?></td>
				<td><?= $row["Type"] ?></td>
				<td><?= $opts ?></td>
			</tr>
<?php
		$cntr++;
	}
?>
		</table>

		<p>
			<input type="submit" value="Add to Custom Tables">
			<input type="button" value="Cancel" onclick="history.go(-1);">
		</p>
	</form>
<?php
	Zymurgy::$db->free_result($ri);
}

function GetOptions()
{
	include_once("datagrid.php");
	DumpDataGridCSS();

	global $dsevents,$noshow;
	
	$table = $_GET['t'];
	$tblname = strtoupper(substr($table,0,1)).substr($table,1);
	$sql = "show columns from $table";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Couldn't load columns from $table.");
?>
	<p><b><?= $table ?></b></p>

	<p>To generate PHP code for this table, define the Field Type for each of the fields in the table. Zymurgy:CM uses the field type to determine which edit control to use.</p>

	<form>
		<input type="hidden" name="t" value="<?= $table ?>">
		<input type="hidden" name="adding" value="0">

		<table class="DataGrid" cellspacing="0" cellpadding="3" bordercolor="#999999" border="1" rules="cols">
			<tr class="DataGridHeader">
				<td>Column</td>
				<td>Data Type</td>
				<td>Field Type</td>
			</tr>
<?php
	$cntr = 1;

	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$fld = $row['Field'];
		if (in_array($fld,$noshow)) continue;
		list($type,$length) = ParseType($row['Type']);
		switch ($type)
		{
			case('char'):
			case('varchar'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"text\" checked>Text ".
					"<input type=\"radio\" name=\"f$fld\" value=\"file\">File ".
					"<input type=\"radio\" name=\"f$fld\" value=\"thumb\">Thumb";
				break;
			case('longtext'):
			case('text'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"text\">Text ".
					"<input type=\"radio\" name=\"f$fld\" value=\"html\" checked>HTML";
				break;
			case('bigint'):
			case('smallint'):
			case('tinyint'):
			case('int'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"date\" checked>UNIX Date ".
					"<input type=\"radio\" name=\"f$fld\" value=\"currency\">Currency ".
					"<input type=\"radio\" name=\"f$fld\" value=\"lookup\">Lookup ".
					"<input type=\"radio\" name=\"f$fld\" value=\"number\">Number";
				break;
			case('smallint'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"bool\" checked>Yes/No ".
					"<input type=\"radio\" name=\"f$fld\" value=\"number\">Number";
				break;
			case('enum'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"droplist\" checked>Drop List ".
					"<input type=\"radio\" name=\"f$fld\" value=\"radio\">Radio";
				break;
			case('datetime'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"datetime\" checked>MySQL Date/Time";
				break;
			case('date'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"date\" checked>MySQL Date";
				break;
			case('time'):
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"time\" checked>MySQL Time";
				break;
			default:
				$opts = "Unknown type: $type";
				break;
		}
?>
		<tr class="DataGridRow<?= $cntr % 2 == 0 ? "Alternate" : "" ?>">
			<td><?= $row["Field"] ?></td>
			<td><?= $row["Type"] ?></td>
			<td><?= $opts ?></td>
		</tr>
<?php

		$cntr++;
	}
	echo "</table><br /><b>Generate data event stubs:</b><table>";
	foreach ($dsevents as $event=>$code)
	{
		echo "<tr><td><input type=\"checkbox\" name=\"e$event\"> $event</td></tr>\r\n";
	}
	echo "</table><p><input type=\"submit\" value=\"Generate Code\"> <input type=\"button\" value=\"Cancel\" onclick=\"history.go(-1);\"></p></form>";
}

/**
 * Shamelessly taken from http://www.php.net/manual/en/function.split.php, used here to parse enum lists.
 * Create a 2D array from a CSV string
 *
 * @param mixed $data 2D array
 * @param string $delimiter Field delimiter
 * @param string $enclosure Field enclosure
 * @param string $newline Line seperator
 * @return
 */
function parsecsv($data, $delimiter = ',', $enclosure = '"', $newline = "\n"){
    $pos = $last_pos = -1;
    $end = strlen($data);
    $row = 0;
    $quote_open = false;
    $trim_quote = false;

    $return = array();

    // Create a continuous loop
    for ($i = -1;; ++$i){
        ++$pos;
        // Get the positions
        $comma_pos = strpos($data, $delimiter, $pos);
        $quote_pos = strpos($data, $enclosure, $pos);
        $newline_pos = strpos($data, $newline, $pos);

        // Which one comes first?
        $pos = min(($comma_pos === false) ? $end : $comma_pos, ($quote_pos === false) ? $end : $quote_pos, ($newline_pos === false) ? $end : $newline_pos);

        // Cache it
        $char = (isset($data[$pos])) ? $data[$pos] : null;
        $done = ($pos == $end);

        // It it a special character?
        if ($done || $char == $delimiter || $char == $newline){

            // Ignore it as we're still in a quote
            if ($quote_open && !$done){
                continue;
            }

            $length = $pos - ++$last_pos;

            // Is the last thing a quote?
            if ($trim_quote){
                // Well then get rid of it
                --$length;
            }

            // Get all the contents of this column
            $return[$row][] = ($length > 0) ? str_replace($enclosure . $enclosure, $enclosure, substr($data, $last_pos, $length)) : '';

            // And we're done
            if ($done){
                break;
            }

            // Save the last position
            $last_pos = $pos;

            // Next row?
            if ($char == $newline){
                ++$row;
            }

            $trim_quote = false;
        }
        // Our quote?
        else if ($char == $enclosure){

            // Toggle it
            if ($quote_open == false){
                // It's an opening quote
                $quote_open = true;
                $trim_quote = false;

                // Trim this opening quote?
                if ($last_pos + 1 == $pos){
                    ++$last_pos;
                }

            }
            else {
                // It's a closing quote
                $quote_open = false;

                // Trim the last quote?
                $trim_quote = true;
            }

        }

    }

    return $return;
}

function ParseType($t)
{
	$typeinfo = explode('(',$t,2);
	$type = $typeinfo[0];
	if (count($typeinfo)>1)
	{
		if ($type=='enum')
		{
			$typedata = parsecsv(substr($typeinfo[1],0,-1),',',"'");
			$typedata = $typedata[0];
		}
		else 
		{
			$tp = explode(')',$typeinfo[1]);
			$typedata = $tp[0];
		}
	}
	else 
		$typedata = 0;
	return array($type,$typedata);
}

function ShowCode()
{
	global $noshow, $dsevents;
	
	$table = $_GET['t'];
	$tblname = strtoupper(substr($table,0,1)).substr($table,1);
	$sql = "show columns from $table";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Couldn't load columns from $table.");
	$dg = $dgc = $dsc = $dse = $efunc = array();
	$noshowtype = array('text');
	foreach ($dsevents as $event=>$code)
	{
		if (array_key_exists("e$event",$_GET))
		{
			$dse[] = "\$ds->$event = '$event';";
			$efunc[] = $code;
		}
	}
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$fld = $row['Field'];
		$name = strtoupper(substr($fld,0,1)).substr($fld,1);
		list($type,$length) = ParseType($row['Type']);
		$dsc[] = $fld;
		if (!in_array($fld,$noshow) && !in_array($type,$noshowtype))
			$dgc[] = "\$dg->AddColumn('$name','$fld');";
		if ($fld == 'disporder')
			$dg[] = "\$dg->AddUpDownColumn('$fld');";
		else 
		{
			if (in_array($fld,$noshow)) continue;
			$optname = "f$fld";
			if (array_key_exists($optname,$_GET))
				$opts = $_GET[$optname];
			else 
				$opts = '';
			switch ($type)
			{
				case('char'):
				case('varchar'):
					switch ($opts)
					{
						case('file'): $dg[] = "\$dg->AddAttachmentEditor('$fld','$name:');"; break;
						case('thumb'): 
							$dg[] = "\$dg->AddAttachmentEditor('$fld','$name:');"; 
							$dgc[count($dgc)-1] = "\$dg->AddThumbColumn('$name','$fld',100,100);";
							break;
						default: $dg[] = "\$dg->AddInput('$fld','$name:',$length,$length);"; break;
					}
					break;
				case('text'):
					switch ($opts)
					{
						case('text'): $dg[] = "\$dg->AddTextArea('$fld','$name:');"; break;
						default: $dg[] = "\$dg->AddHtmlEditor('$fld','$name:');"; break;
					}
					break;
				case('bigint'):
				case('smallint'):
				case('tinyint'):
				case('int'):
					switch ($opts)
					{
						case('currency'): $dg[] = "\$dg->AddMoneyEditor('$fld','$name');"; break;
						case('lookup'): $dg[] = "\$dg->AddLookup('$fld','$name','lookuptable','id','name','disporder');"; break;
						case('number'): $dg[] = "\$dg->AddInput('$fld','$name',3,3);"; break;//TODO:  Numeric validator
						default: $dg[] = "\$dg->AddUnixDateEditor('$fld','$name');"; break;
					}
					break;
				case('datetime'):
					switch($opts)
					{
						default: $dg[] = "\$dg->AddEditor('$fld','$name','datetime');"; break;
					}
					break;
				case('date'):
					switch($opts)
					{
						default: $dg[] = "\$dg->AddEditor('$fld','$name','date');"; break;
					}
					break;
				case('time'):
					switch($opts)
					{
						default: $dg[] = "\$dg->AddEditor('$fld','$name','time');"; break;
					}
					break;
				case('smallint'):
					switch ($opts)
					{
						case('number'): $dg[] = "\$dg->AddInput('$fld','$name',3,3);"; break;//TODO:  Numeric validator
						default: $dg[] = "\$dg->AddRadioEditor('$fld','$name',array('0'=>'No','1'=>'Yes'));";
					}
					break;
				case('enum'):
					$enumarray = array();
					foreach($length as $enumvalue)
					{
						$enumarray[] = "'$enumvalue'=>'$enumvalue'";
					}
					switch ($opts)
					{
						case('droplist'): $dg[] = "\$dg->AddDropListEditorLookup('$fld','$name',array(".implode(',',$enumarray)."));"; break;
						default: $dg[] = "\$dg->AddRadioEditor('$fld','$name',array(".implode(',',$enumarray)."));"; break;
					}
					break;
					break;
			}
		}
	}
	$custom = "<?
include '../header.php';
include '../datagrid.php';

".implode("\r\n\r\n",$efunc)."

\$ds = new DataSet('$table','id');
\$ds->AddColumns('".implode("','",$dsc)."');
".implode("\r\n",$dse)."

\$dg = new DataGrid(\$ds);
".implode("\r\n",$dgc)."\r\n".
implode("\r\n",$dg)."
\$dg->AddEditColumn();
\$dg->AddDeleteColumn();
\$dg->insertlabel = 'Add New $tblname';
\$dg->Render();

include('../footer.php');
?>";
	echo "<form action=\"{$_SERVER['REQUEST_URI']}\"><textarea rows=\"20\" cols=\"70\">$custom</textarea></form>";
}

function AddToCustomTable()
{
	$idfieldname = array_search("pk", $_GET);

	$sql = "INSERT INTO `zcm_customtable` ( `tname`, `detailfor`, `hasdisporder`, `ismember`, `navname`, `selfref`, `idfieldname` ) VALUES ( '".
		Zymurgy::$db->escape_string($_GET["t"]).
		"', '', 0, 0, '".
		Zymurgy::$db->escape_string($_GET["t"]).
		"', '', '".
		Zymurgy::$db->escape_string(substr($idfieldname, 1)).
		"' )";
	Zymurgy::$db->query($sql)
		or die("Could not add to list of custom tables: ".Zymurgy::$db->error().", $sql");
	$tableID = Zymurgy::$db->insert_id();

	foreach($_GET as $key => $value)
	{
		if(strpos($key, "f") === 0 && $key != $idfieldname)
		{
			$fieldName = substr($key, 1);
			if(array_key_exists("x".$fieldName, $_GET))
			{
				$value = $value.".".$_GET["x".$fieldName];
			}

			$sql = "INSERT INTO `zcm_customfield` ( `tableid`, `cname`, `inputspec`, `caption`, `indexed`, `gridheader` ) VALUES ( '".
				Zymurgy::$db->escape_string($tableID).
				"', '".
				Zymurgy::$db->escape_string($fieldName).
				"', '".
				Zymurgy::$db->escape_string($value).
				"', '".
				Zymurgy::$db->escape_string($fieldName).
				"', 0, '".
				Zymurgy::$db->escape_string($fieldName).
				"' )";
			Zymurgy::$db->query($sql)
				or die("Could not add field definition: ".Zymurgy::$db->error().", $sql");
		}
	}
}

if (!array_key_exists('t',$_GET))
	GetTable();
else
{
	if (count($_GET) > 2)
	{
		if($_GET["adding"] > 0)
		{
			AddToCustomTable();
		}
		else
		{
			ShowCode();
		}
	}
	else
	{
		if($_GET["adding"] > 0)
		{
			GetCustomTableOptions();
		}
		else
		{
			GetOptions();
		}
	}
}
include("footer.php");
?>
