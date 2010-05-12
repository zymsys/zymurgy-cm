<?
/**
 * Management screen for site text categories.
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */

// Categories may only be edited by the administrator
$adminlevel = 2;

$_GET['sortcolumn']='zcm_stcategory.name';

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"sitetext.php\">General Content</a> &gt; " .
		"<a href=\"stcategory.php\">Categories</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "<a href=\"sitetext.php\">General Content</a> &gt; Categories";
}

include 'header.php';
include 'datagrid.php';

/**
 * Delete Event Handler. 
 * 
 * @param $values mixed The values of the row to be updated. Keys are in 
 * tablename.columname format.
 */
function OnDelete($values)
{
	$sql = "update zcm_sitetext set category=0 where category={$_GET['deletekey']}";
	Zymurgy::$db->query($sql) or die("Unable to reset category id's ($sql): ".Zymurgy::$db->error());
	return true; //Return false to override delete.
}

$ds = new DataSet('zcm_stcategory','id');
$ds->AddColumns('id','name');
$ds->OnDelete = 'OnDelete';
$ds->AddDataFilter('id',0,'<>');

$dg = new DataGrid($ds);
$dg->AddColumn('Name','name');
$dg->AddInput('name','Name:',60,60);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Category';
$dg->Render();

include('footer.php');
?>
