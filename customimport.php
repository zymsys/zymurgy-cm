<?php
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
// Import bulk data into custom tables
$adminlevel = 2;

ob_start();

require_once('cmo.php');
require_once('datagrid.php');
require_once('InputWidget.php');

$t = 0 + $_GET['t'];
$parentrow = array_key_exists('d',$_GET) ? 0 + $_GET['d'] : 0;
$selfref = array_key_exists('s',$_GET) ? 0 + $_GET['s'] : 0;
$parentrow = array_key_exists('p',$_GET) ? 0 + $_GET['p'] : 0;

if (!function_exists('str_getcsv'))
{
	// Thanks to e at osterman dot com (http://ca.php.net/manual/en/function.fgetcsv.php)
	function str_getcsv($str, $delimiter = ',', $enclosure = '"', $len = 4096)
	{
	  $fh = fopen('php://memory', 'rw');
	  fwrite($fh, $str);
	  rewind($fh);
	  $result = fgetcsv( $fh, $len, $delimiter, $enclosure );
	  fclose($fh);
	  return $result;
	} 
}

class ImportWidget
{
	public $widget;
	public $inputspec;
	
	public function __construct($inputspec)
	{
		$this->widget = InputWidget::Get($inputspec);
		$this->inputspec = $inputspec;
	}
}

//Snagged from customedit...  Maybe could be placed in customlib for better re-use?
function gettable($t)
{
	$sql = "select * from zcm_customtable where id=$t";
	$ri = Zymurgy::$db->query($sql) or die("Can't get table ($sql): ".Zymurgy::$db->error());
	$tbl = Zymurgy::$db->fetch_array($ri);
	if (!is_array($tbl))
		die("No such table ($t)");
	return $tbl;
}

function getWidgets()
{
	global $t;
	$widgets = array();
	
	$ri = Zymurgy::$db->run("select * from zcm_customfield where tableid=$t order by disporder");
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$widgets[$row['cname']] = new ImportWidget($row['inputspec']);
	}
	Zymurgy::$db->free_result($ri);
	
	return $widgets;
}

function importData($rawdata)
{
	global $t;
	global $d;
	global $parentrow;
	
	$widgets = getWidgets();
	$table = gettable($t);
	//Zymurgy::DbgAndDie($table);
	if ($table['detailfor'])
	{
		$parent = gettable($table['detailfor']);
		$startvalues = array($parent['tname'] => $parentrow);
	}
	else 
	{
		$startvalues = array();
	}
	$lines = explode("\n",$rawdata);
	$i = new InputWidget();
	
	$importtype = 'csv';
	if (strpos($lines[0],"\t") !== false)
		$importtype = 'tab';
	foreach ($lines as $line)
	{
		if (empty($line)) continue;
		switch ($importtype)
		{
			case 'csv':
				$values = str_getcsv($line);
				break;
			default:
				$values = str_getcsv($line,"\t");
		}
		$dbvalues = $startvalues;
		foreach ($widgets as $cname=>$iw)
		{
			if ($values)
			{
				$value = array_shift($values);
				$dbvalues[$cname] = Zymurgy::$db->escape_string($value);
			}
		}
		//echo "<pre>"; print_r($dbvalues); echo "</pre>";
		$sql = 'INSERT INTO `'.$table['tname'].'` (`'.
			implode("`,`",array_keys($dbvalues)).
			'`) values (\''.
			implode("','",$dbvalues).
			"')";
		//echo "<div>$sql</div>\n"; continue;
		if (Zymurgy::$db->query($sql) == false)
		{
			echo "<div>Import error for line [$line]: ".Zymurgy::$db->error()."</div>\n";
		}
		else 
		{
			echo "<div>Imported [$line]</div>\n";
		}
		//Set disporder if required
		if ($table['hasdisporder'])
		{
			$disporder = Zymurgy::$db->insert_id();
			Zymurgy::$db->run("update `{$table['tname']}` set disporder=$disporder where id=$disporder");
		}
	}
}

require_once('header.php');
require_once('datagrid.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	echo "<div><a href=\"customedit.php?t=$t&d=$parentrow\">Return to table</a></div>\n";
	if ($_POST['text']) importData($_POST['text']);
	if ($_FILES['upload']['size']) importData(file_get_contents($_FILES['upload']['tmp_name']));
}
else 
{
	$widgets = getWidgets();
	echo "<p>Expecting the following fields in tab delimited format (will fall back on comma separated values if no tabs are found on the first line):<ul style=\"padding-left:200px;\">";
	foreach ($widgets as $cname=>$iw)
	{
		echo "<li>$cname</li>\n";
	}
	echo "</ul></p>";
?>
<form method="POST" enctype="multipart/form-data" action="customimport.php?<?php echo "t=$t&d=$parentrow&s=$selfref&p=$parentrow"; ?>">
	<p>
		Import text (comma or tab separated) file: <input type="file" name="upload" />
	</p>
	<p>
		Import text:<br />
		<textarea name="text" cols="80" rows="15"></textarea>
	</p>
	<p>
		<input type="submit" value="Import" />
	</p>
</form>
<?php
}
require_once('footer.php');
?>