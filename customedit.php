<?
/**
 * Standard management screen for editing the contents of Custom Tables.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
ini_set("display_errors", 1);
require_once('cmo.php');
Zymurgy::$yuitest = true;

$t = 0 + $_GET['t'];
$parentrow = array_key_exists('d',$_GET) ? 0 + $_GET['d'] : 0;
$selfref = array_key_exists('s',$_GET) ? 0 + $_GET['s'] : 0;

$tbl = Zymurgy::customTableTool()->getTable($t);
$detailFor = 0 + $tbl['detailfor'];
$detailtbl = ($detailFor > 0) ? Zymurgy::customTableTool()->getTable($detailFor) : array();
$wheredidicomefrom = array();

/**
 * Dataset Event handler for deleting records. Used to call DeleteChildren.
 * 
 * @param $values mixed
 */
function OnDelete($values)
{
	global $tbl;

	// print_r($values);

	DeleteChildren(
		$tbl["id"],
		$tbl["tname"],
		$values[$tbl["tname"].".id"]);

	// die();
}

/**
 * Delete the records in any Detail Tables for the identified record.
 * 
 * @param tableID int
 * @param tableName string
 * @param baseRowID int
 */
function DeleteChildren(
	$tableID,
	$tableName,
	$baseRowID)
{
	// echo("DeleteChildren called ($tableID, $tableName, $baseRowID)<br>");

	$sql = "SELECT `id`, `tname`, `idfieldname`, `detailforfield` FROM `zcm_customtable` WHERE `detailfor` = '".
		Zymurgy::$db->escape_string($tableID).
		"'";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve list of child tables: ".mysql_error().", $sql");

	// echo(Zymurgy::$db->num_rows($ri)." child tables for $tableName found<br>");

	while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
	{
		// Get the DetailForField value from the database. If it's not specified, then use 
		// the default field name, which matches the name of the parent table
		$detailForField = $row["detailforfield"];
		if(strlen($detailForField) <= 0) $detailForField = $tableName;

		// Delete any child records for this child before deleting the child
		// itself.
		// select child.idfieldname from child.tname where child.detailforfield = baseRowID
		$childSQL = "SELECT `".
			Zymurgy::$db->escape_string($row["idfieldname"]).
			"` FROM `".
			Zymurgy::$db->escape_string($row["tname"]).
			"` WHERE `".
			Zymurgy::$db->escape_string($detailForField).
			"` = '".
			Zymurgy::$db->escape_string($baseRowID).
			"'";
		// echo($childSQL."<br>");
		$childRI = Zymurgy::$db->query($childSQL)
			or die("Could not retrieve list of child records: ".Zymurgy::$db->error().", $childSQL");

		// echo(Zymurgy::$db->num_rows($childRI)." child records found for ID $baseRowID<br>");

		while(($childRow = Zymurgy::$db->fetch_array($childRI)) !== FALSE)
		{
			DeleteChildren(
				$row["id"],
				$row["tname"],
				$childRow["id"]);
		}

		$deleteSQL = "DELETE FROM `".
			$row["tname"].
			"` WHERE `".
			Zymurgy::$db->escape_string($detailForField).
			"` = '".
			Zymurgy::$db->escape_string($baseRowID).
			"'";
		// echo($deleteSQL."<br>");
		Zymurgy::$db->query($deleteSQL)
			or die("Could not delete child records: ".Zymurgy::$db->error().", $deleteSQL");
	}
}

/**
 * Get the ID of the parent record associated with the ID of the child record
 * being passed into the function. This is used to build the breadcrumb trail.
 *
 * @param $parent array from zcm_customtable - What table are we getting a key from?
 * @param $myd string - My primary key value
 * @return int
 */
function getdkey($parent,$myd)
{
	/**
	 * http://www.zymurgy.ca/zymurgy/customedit.php?t=3&d=1
	 * Images > First Detail > Second Detail
	 * $me=t=3=Second Detail
	 * $parent=2=First Detail, id = 1 (d)
	 *
	 * http://www.zymurgy.ca/zymurgy/customedit.php?t=2&d=1
	 * Images > First Detail
	 * $me = t = 2 = First Detail
	 * $parent = 1 = Images, id = 1 (d)
	 */
	//echo "<div>[DKEY:detailfor:{$parent['detailfor']},myd:$myd],ake:".array_key_exists('detailforfield',$parent)."</div>";
	if (array_key_exists('detailforfield',$parent) && ($parent['detailfor'] > 0) && (strlen($myd) > 0))
	{
		$detailForField = $parent["detailforfield"];
		if(strlen($detailForField) <= 0) 
		{
			$grandparent = Zymurgy::customTableTool()->getTable($parent['detailfor']);
			$detailForField = $grandparent["tname"];
		}

		$sql = "SELECT `".
			Zymurgy::$db->escape_string($detailForField).
			"` FROM `".
			Zymurgy::$db->escape_string($parent["tname"]).
			"` WHERE `".
			Zymurgy::$db->escape_string($parent["idfieldname"]).
			"` = '".
			Zymurgy::$db->escape_string($myd).
			"'";
		//echo "<div>[{$parent['tname']}: {$parent['detailfor']}]: $sql</div>";
		$dkey = Zymurgy::$db->get($sql);

		return $dkey;
	}
	else
	{
		return false; //There is no dkey for this crumb.
	}
}

/**
 * Generate the link for the identified table/row combination for the 
 * breadcrumb trail.
 * 
 * @param $tblrow mixed
 * @param $dkey int
 */
function addhistory($tblrow,$dkey)
{
	global $wheredidicomefrom;
	$key = 'customedit.php?t='.$tblrow['id'];
	if ($dkey > 0)
	{
		$key .= "&d=$dkey";
	}
	$wheredidicomefrom[$key] = empty($tblrow['navname']) ? $tblrow['tname'] : $tblrow['navname'];
}

//$tbl is a row from zcm_customtable representing the table who's data we're editing
//$parentrow is the primary key value of the parent row this view belongs to
$crumbtbl = $tbl;
$crumbrow = $parentrow;
while ($crumbtbl)
{
	addhistory($crumbtbl,$crumbrow);
	$crumbtbl = Zymurgy::$db->get("SELECT * FROM `zcm_customtable` WHERE `id`=".$crumbtbl['detailfor']);
	if ($crumbtbl)
	{
		$crumbrow = getdkey($crumbtbl,$crumbrow);
	}
}

$wdicfkeys = array_keys($wheredidicomefrom);
$crumbs = array();
while(count($wheredidicomefrom)>0)
{
	$key = array_pop($wdicfkeys);
	$name = array_pop($wheredidicomefrom);
	$crumbs[$key] = $name;
}

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$crumbs[''] = 'Edit';
}

include 'header.php';
include 'datagrid.php';

$sql = "select * from zcm_customfield where tableid=$t order by disporder";
$ri = Zymurgy::$db->query($sql) or die("Unable to get table fields ($sql): ".Zymurgy::$db->error());

$cols = array();
$capts = array();
$ingrid = array();
$thumbs = array();

$idfieldname = $tbl["idfieldname"];
if(strlen($idfieldname) <= 0)
{
	$idfieldname = "id";
}

$ds = new DataSet($tbl['tname'], $idfieldname);
$ds->OnDelete = "OnDelete";

$ds->AddColumn($idfieldname,false);
if ($tbl['hasdisporder']==1)
{
	$ds->AddColumn('disporder',false);
}
if($tbl["ismember"])
{
	$ds->AddColumn("member", false);
}

while (($row = Zymurgy::$db->fetch_array($ri))!==false)
{
	$isp = explode('.',$row['inputspec']);

	if ($isp[0]=='image')
	{
		array_shift($isp);
		$thumbs[$row['cname']] = $isp; //Set thumbs value to w,h of thumb.
	}

	$cols[$row['cname']] = $row['inputspec'];
	$capts[$row['cname']] = $row['caption'];

	$ingrid[$row['cname']] = $row['gridheader'];

	$ds->AddColumn($row['cname'],true);
}

if ($parentrow>0)
{
	$ds->AddColumn($detailtbl['tname'],false);
	$ds->AddDataFilter($detailtbl['tname'],$parentrow);
}

if (!empty($tbl['selfref']))
{
	$ds->AddColumn('selfref',false);
	$ds->AddDataFilter('selfref',$selfref);
}

$customfn = Zymurgy::getFilePath("~custom/datagrid/".$tbl['tname'].".php");
if(file_exists($customfn))
{
	include_once($customfn);
}

$dg = new DataGrid($ds);

if(isset($displayID) && $displayID == true)
{
	$dg->AddColumn("ID", $idfieldname, "{0}");
}

if ($parentrow>0)
{
	$dg->AddConstant($detailtbl['tname'],$parentrow);
}

if (!empty($tbl['selfref']))
{
	$dg->AddConstant('selfref',$selfref);
}

if($tbl["ismember"])
{
	$dg->AddColumn("Member", "member", "member");
}

foreach($ingrid as $col=>$header)
{
    if (empty($header))
    {
        $dg->AddColumn($header, $col, '');
        continue;
    }
	if (array_key_exists($col,$thumbs))
	{
		list($width,$height) = $thumbs[$col];
		$dg->AddThumbColumn($header,$col,$width,$height,true);
	}
	else
	{
		$dg->AddColumn($header,$col);
	}
}

//$sql = "select * from zcm_customtable where detailfor=$t";\
$sql = "SELECT `id`, `navname` FROM `zcm_customtable` WHERE `detailfor` = '".
	Zymurgy::$db->escape_string($t).
	"' ORDER BY `disporder`";
$ri = Zymurgy::$db->query($sql) or die("Unable to get detail tables ($sql): ".Zymurgy::$db->error());

while (($row = Zymurgy::$db->fetch_array($ri))!==false)
{
	$dg->AddColumn($row['navname'],$idfieldname,"<a href=\"customedit.php?t={$row['id']}&d={0}\">{$row['navname']}</a>");
}

if ($tbl['hasdisporder']==1)
{
	$dg->AddUpDownColumn('disporder');
}

if (!empty($tbl['selfref']))
{
	$dg->AddColumn($tbl['selfref'],$idfieldname,"<a href=\"customedit.php?t=$t&s={0}\">{$tbl['selfref']}</a>");
}

if($tbl["ismember"])
{
	$dg->AddEditor("member","Member:", "lookup.zcm_member.id.username.username.", "member");
}
foreach($cols as $col=>$inputspec)
{
	if (!empty($capts[$col]))
	{
		$dg->AddEditor($col,$capts[$col],$inputspec);
	}
}

$dg->AddEditColumn();
$dg->AddDeleteColumn();

if (Zymurgy::memberzcmauth()>=2)
{
	$dg->insertlabel = "Insert a new Item</font></a> <font color=\"white\">|</font> <a href=\"customimport.php?t=$t&d=$detailFor&s=$selfref&p=$parentrow\"><font color=\"white\">Bulk Import";
}
$dg->Render();
?>
