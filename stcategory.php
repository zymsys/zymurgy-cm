<?
// Categories may only be edited by the administrator
$adminlevel = 2;

$_GET['sortcolumn']='stcategory.name';

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

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	$sql = "update sitetext set category=0 where category={$_GET['deletekey']}";
	Zymurgy::$db->query($sql) or die("Unable to reset category id's ($sql): ".Zymurgy::$db->error());
	return true; //Return false to override delete.
}

$ds = new DataSet('stcategory','id');
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
