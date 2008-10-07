<?
// Code generation may only be performed by a webmaster
$adminlevel = 2;

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"mkcustom.php\">Custom Code</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "Custom Code";	
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
	echo "Select the table you want to generate custom code for:<br><br>";
	$sql = "show tables";
	$ri = Zymurgy::$db->query($sql);
	$tables = array();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$table = $row[0];
		if (substr($table,0,4) == 'zcm_') continue;
		if (substr($table,0,4) == 'kfm_') continue;
		$tables[] = $table;
	}
	foreach($tables as $table)
	{
		echo "<a href=\"mkcustom.php?t=$table\">$table</a><br>\r\n";
	}
}

function GetOptions()
{
	global $dsevents,$noshow;
	
	$table = $_GET['t'];
	$tblname = strtoupper(substr($table,0,1)).substr($table,1);
	$sql = "show columns from $table";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Couldn't load columns from $table.");
	echo "<b>Describe your table's columns:</b><form action=\"{$_SERVER['REQUEST_URI']}\"><input type=\"hidden\" name=\"t\" value=\"$table\"><table>";
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
				$opts = "<input type=\"radio\" name=\"f$fld\" value=\"datetime\" checked>MySQL Date";
				break;
			default:
				$opts = "Unknown type: $type";
				break;
		}
		echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>$opts</td></tr>\r\n";
	}
	echo "</table><br /><b>Generate data event stubs:</b><table>";
	foreach ($dsevents as $event=>$code)
	{
		echo "<tr><td><input type=\"checkbox\" name=\"e$event\"> $event</td></tr>\r\n";
	}
	echo "</table><input type=\"submit\" value=\"Get Code\"></form>";
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

if (!array_key_exists('t',$_GET))
	GetTable();
else
{
	if (count($_GET)==1)
		GetOptions();
	else
		ShowCode();
}
include("footer.php");
?>
