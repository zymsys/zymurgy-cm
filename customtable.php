<?
/**
 * Management screen for Custom Tables.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */

// Custom Table definitions may only be set by the webmaster
$adminlevel = 2;

ob_start();

require_once('cmo.php');
include 'datagrid.php';

$detailFor = array_key_exists('d',$_GET) ? (0 + $_GET['d']) : 0;
$crumbs = array("customtable.php"=>"Custom Tables");

$wikiArticleName = "Custom_Tables";

if ($detailFor > 0)
{
    Zymurgy::customTableTool()->tablecrumbs($detailFor);
}

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$ek = array_key_exists('editkey',$_GET) ? 0 + $_GET['editkey'] : 0;
	$tbl = Zymurgy::$db->get("select * from zcm_customtable where id=$ek");
	$tblname = empty($tbl['navname']) ? $tbl['tname'] : $tbl['navname'];
	$crumbs[''] = "Edit $tblname";
	$wikiArticleName = "Custom_Tables#Adding_and_Editing_Custom_Tables";
}

include 'header.php';

/**
 * Delete Event Handler. Performs the actual DROP operation on the Custom Table.
 *
 * @param $values mixed The values of the row to be deleted. Keys are in 
 * tablename.columname format.
 */
function OnDelete($values)
{
    Zymurgy::customTableTool()->dropTable($values['zcm_customtable.id']);
	return true; //Return false to override delete.
}

/**
 * Update Event Handler. Performs the actual ALTER TABLE operations on the 
 * Custom Table.
 * 
 * @param $values mixed The values of the row to be updated. Keys are in 
 * tablename.columname format.
 */
function OnBeforeUpdate($values)
{
	$okName = Zymurgy::customTableTool()->okname($values['zcm_customtable.tname']);
	if ($okName!==true)
	{
		return $okName;
	}
    $result = Zymurgy::customTableTool()->updateTable(array(
        "id"=>$values['zcm_customtable.id'],
        "tname"=>$values['zcm_customtable.tname'],
        "detailfor"=>$values['zcm_customtable.detailfor'],
        "detailforfield"=>$values['zcm_customtable.detailforfield'],
        "hasdisporder"=>$values['zcm_customtable.hasdisporder'],
        "ismember"=>$values['zcm_customtable.ismember'],
        "navname"=>$values['zcm_customtable.navname'],
        "selfref"=>$values['zcm_customtable.selfref'],
        "idfieldname"=>$values['zcm_customtable.idfieldname'],
        "globalacl"=>$values['zcm_customtable.globalacl'],
        "acl"=>$values['zcm_customtable.acl'],
    ));
    if ($result === true) return false; //Let customTableTool do the update
    if ($result === false) $result = "An unknown error occurred adding this table.";
    return $result;
}

/**
 * INSERT Event Handler. Performs the actual ALTER TABLE operations on the 
 * Custom Table.
 * 
 * @param $values mixed The values of the row to be inserted. Keys are in 
 * tablename.columname format.
 */
function OnBeforeInsert($values)
{
	global $detailFor;

	$okName = Zymurgy::customTableTool()->okname($values['zcm_customtable.tname']);
	if ($okName!==true)
	{
		return $okName;
	}
    $result = Zymurgy::customTableTool()->addTable(array(
        "tname"=>$values['zcm_customtable.tname'],
        "detailfor"=>$detailFor,
        "hasdisporder"=>$values['zcm_customtable.hasdisporder'],
        "ismember"=>$values['zcm_customtable.ismember'],
        "navname"=>$values['zcm_customtable.navname'],
        "selfref"=>$values['zcm_customtable.selfref'],
        "idfieldname"=>$values['zcm_customtable.idfieldname'],
        "globalacl"=>$values['zcm_customtable.globalacl'],
        "acl"=>$values['zcm_customtable.acl'],
    ));
    if ($result === true) return false; //Let customTableTool do the insert
    if ($result === false) $result = "An unknown error occurred adding this table.";
    return $result;
}

$ds = new DataSet('zcm_customtable','id');
$ds->AddColumns('id','disporder','tname','detailfor','hasdisporder','ismember','navname','selfref','idfieldname','detailforfield','globalacl','acl');
$ds->AddDataFilter('detailfor',$detailFor);
$ds->OnBeforeUpdate = 'OnBeforeUpdate';
$ds->OnBeforeInsert = 'OnBeforeInsert';
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddConstant('detailfor',$detailFor);
$dg->AddColumn('Table','tname');
$dg->AddColumn('Display Order?','hasdisporder');
$dg->AddColumn('Member Data?','ismember');
$dg->AddLookup("globalacl", "Global ACL", "zcm_acl", "id", "name", "name", true);
$dg->AddLookup("acl", "Member ACL", "zcm_acl", "id", "name", "name", true);
$dg->AddUpDownColumn('disporder');

$dg->AddColumn(
	"Contents",
	"id",
	"<a href=\"customedit.php?t={0}\">Contents</a>");

$dg->AddColumn('Fields','id','<a href="customfield.php?t={0}">Fields</a>');
$dg->AddColumn('Detail Tables','id','<a href="customtable.php?d={0}">Detail Tables</a>');
$dg->AddColumn("Export", "id", "<a href=\"exportcustomtable.php?t={0}\">Export</a>");
$dg->AddInput('tname','Table Name:',30,30);
$dg->AddInput('navname','Link Name:',30,30);
$dg->AddInput('selfref','Self Reference:',30,30);
$dg->AddDropListEditor('hasdisporder','Display Order?',array(0=>'No',1=>'Yes'));
$dg->AddDropListEditor('ismember','Member Data?',array(0=>'No',1=>'Yes'));
$dg->AddLookup("globalacl", "Global ACL:", "zcm_acl", "id", "name", "name", true);
$dg->AddLookup("acl", "Member Data ACL:", "zcm_acl", "id", "name", "name", true);
$dg->AddInput("idfieldname", "Primary Key:", 30, 30, "id");

if($detailFor > 0)
{
	$tbl = Zymurgy::customTableTool()->getTable($detailFor);
	$dg->AddInput("detailforfield", "Foreign Key Field Name:", 30, 30, $tbl["tname"]);
}

$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add a New Table';
$dg->Render();

include('footer.php');
?>
