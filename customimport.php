<?php
/**
 * Import bulk data into Custom Tables.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
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
	/**
	 * Thanks to e at osterman dot com (http://ca.php.net/manual/en/function.fgetcsv.php)
	 *
	 * @param $str
	 * @param $delimiter
	 * @param $enclosure
	 * @param $len
	 * @return
	 */
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

/**
 * Contains an instance of the InputWidget class matching the Input Spec 
 * provided in the class' constructor.
 *
 */
class ImportWidget
{
	/**
	 * Instance of an InputWidget class matching the Input Spec provided in the 
	 * constructor.
	 */
	public $widget;

	/**
	 * The Input Spec provided in the constructor.
	 */
	public $inputspec;

	/**
	 * Constructor.
	 *
	 * @param $inputspec string The Input Spec of the Input Widget to 
	 * instantiate.
	 */
	public function __construct($inputspec)
	{
		$this->widget = InputWidget::Get($inputspec);
		$this->inputspec = $inputspec;
	}
}

/**
 * Gets the list of fields and associated Input Widgets for the provided
 * Custom Table, based on the Input Specs defined for the fields in the 
 * database.
 *
 * @return mixed
 */
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

/**
 * Perform the actual import operation.
 *
 * @param rawdata The contents of the imported data file. Expects the data in 
 * either CSV or tab-delimited format.
 */
function importData($rawdata)
{
	global $t;
	global $d;
	global $parentrow;

	$widgets = getWidgets();
	$table = Zymurgy::customTableTool()->getTable($t);
	//Zymurgy::DbgAndDie($table);
	if ($table['detailfor'])
	{
		$parent = Zymurgy::customTableTool()->getTable($table['detailfor']);
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

$breadcrumbTrail = "Bulk Import";

require_once('header.php');
require_once('datagrid.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	echo "<div><a href=\"customedit.php?t=$t&d=$parentrow\">Return to table</a></div>\n";
	if ($_POST['text']) importData($_POST['text']);
	if ($_FILES['upload']['size'])
	{
		if(pathinfo($_FILES["upload"]["name"], PATHINFO_EXTENSION) == "xml")
		{
			require_once("import.php");
			$file = file_get_contents($_FILES["upload"]["tmp_name"]);
			ImportCustomTable($file);
		}
		else
		{
			importData(file_get_contents($_FILES['upload']['tmp_name']));
		}
	}
}
else
{
	$widgets = getWidgets();
?>
<form method="POST" enctype="multipart/form-data" action="customimport.php?<?php echo "t=$t&d=$parentrow&s=$selfref&p=$parentrow"; ?>" style="margin-left: 150px;">
	<h2>Importing from File</h2>
	<p>You can import data from the following types of files:</p>
	<ul>
		<li>Tab-delimited text (TXT) files</li>
		<li>Comma-seperated value (CSV) files</li>
		<li>XML files generated by the Export link in Zymurgy:CM</li>
	</ul>
	<p>Tab-delimited and comma-seperated files expect the fields in the following order:</p>
	<ul>
<? foreach($widgets as $cname => $iw) { ?>
		<li><?= $cname ?></li>
<? } ?>
	</ul>
	<p>File to Import: <input type="file" name="upload" /></p>
	<p>
		<input type="submit" value="Import File" />
	</p>
	<h2>Importing from Microsoft Office Excel</h2>
	<p>In your Microsoft Office Excel document, make sure the columns are arranged in the following order:</p>
	<ul>
<? foreach($widgets as $cname => $iw) { ?>
		<li><?= $cname ?></li>
<? } ?>
	</ul>
	<p>Once the fields are in the right order, select the rows you want to import, copy them into the text box below, and click the Import Text button.</p>
	<p>
		Import text:<br />
		<textarea name="text" cols="80" rows="10"></textarea>
	</p>
	<p>
		<input type="submit" value="Import Text" />
	</p>
</form>
<?php
}
require_once('footer.php');
?>