<?
/**
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

$tbl = gettable($t);
$detailfor = 0 + $tbl['detailfor'];
$detailtbl = ($detailfor > 0) ? gettable($detailfor) : array();
$wheredidicomefrom = array();

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

function DeleteChildren(
	$tableID,
	$tableName,
	$baseRowID)
{
	// echo("DeleteChildren called ($tableID, $tableName, $baseRowID)<br>");

	$sql = "SELECT `id`, `tname` FROM `zcm_customtable` WHERE `detailfor` = '".
		Zymurgy::$db->escape_string($tableID).
		"'";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve list of child tables: ".mysql_error().", $sql");

	// echo(Zymurgy::$db->num_rows($ri)." child tables for $tableName found<br>");

	while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
	{
		// Delete any child records for this child before deleting the child
		// itself.
		$childSQL = "SELECT `id` FROM `".
			$row["tname"].
			"` WHERE `".
			$tableName.
			"` = '".
			Zymurgy::$db->escape_string($baseRowID).
			"'";
		// echo($childSQL."<br>");
		$childRI = Zymurgy::$db->query($childSQL)
			or die("Could not retrieve list of child records: ".mysql_error().", $childSQL");

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
			$tableName.
			"` = '".
			Zymurgy::$db->escape_string($baseRowID).
			"'";
		// echo($deleteSQL."<br>");
		Zymurgy::$db->query($deleteSQL)
			or die("Could not delete child records: ".mysql_error().", $deleteSQL");
	}
}

function getdkey($me,$parent,$myd)
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
	//echo "<div>[{$parent['tname']}: {$parent['detailfor']}]</div>";
	if ($parent['detailfor'] > 0 && strlen($myd) > 0)
	{
		$grandparent = gettable($parent['detailfor']);
		$dkey = Zymurgy::$db->get(
			"select {$grandparent['tname']} from {$parent['tname']} where id=$myd",
			"getdkey: Could not get parent table information");
		return $dkey;
	}
	else
	{
		return false; //There is no dkey for this crumb.
	}
}

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

function digdeeper($tid,$dkey)
{
	global $wheredidicomefrom;
	$tblrow = gettable($tid);
	$key = 'customedit.php?t='.$tblrow['id'];
	$parentdkey = getdkey(null,$tblrow,$dkey);
	if ($parentdkey > 0)
	{
		$key .= "&d=$parentdkey";
	}
	$wheredidicomefrom[$key] = empty($tblrow['navname']) ? $tblrow['tname'] : $tblrow['navname'];
	if ($tblrow['detailfor'] > 0)
		digdeeper($tblrow['detailfor'],$parentdkey);
}

addhistory($tbl,$parentrow);
if ($detailfor > 0)
{
	$parentdkey = getdkey($tbl,$detailtbl,$parentrow);
	addhistory($detailtbl,$parentdkey);
	if ($detailtbl['detailfor'] > 0)
		digdeeper($detailtbl['detailfor'],$parentdkey);
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

function gettable($t)
{
	$sql = "select * from zcm_customtable where id=$t";
	$ri = Zymurgy::$db->query($sql) or die("Can't get table ($sql): ".Zymurgy::$db->error());
	$tbl = Zymurgy::$db->fetch_array($ri);
	if (!is_array($tbl))
		die("No such table ($t)");
	return $tbl;
}

$sql = "select * from zcm_customfield where tableid=$t order by disporder";
$ri = Zymurgy::$db->query($sql) or die("Unable to get table fields ($sql): ".Zymurgy::$db->error());

$cols = array();
$capts = array();
$ingrid = array();
$thumbs = array();

$ds = new DataSet($tbl['tname'],'id');
$ds->OnDelete = "OnDelete";

$ds->AddColumn('id',false);
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

	if (!empty($row['gridheader']))
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

$dg = new DataGrid($ds);

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
	$dg->AddColumn($row['navname'],'id',"<a href=\"customedit.php?t={$row['id']}&d={0}\">{$row['navname']}</a>");
}

if ($tbl['hasdisporder']==1)
{
	$dg->AddUpDownColumn('disporder');
}

if (!empty($tbl['selfref']))
{
	$dg->AddColumn($tbl['selfref'],'id',"<a href=\"customedit.php?t=$t&s={0}\">{$tbl['selfref']}</a>");
}

foreach($cols as $col=>$inputspec)
{
	if (array_key_exists($col,$thumbs))
	{
		$dg->AddAttachmentEditor($col,$capts[$col]);
	}
	else
	{
		$dg->AddEditor($col,$capts[$col],$inputspec);
	}
}

$dg->AddEditColumn();
$dg->AddDeleteColumn();

$dg->Render();
?>
