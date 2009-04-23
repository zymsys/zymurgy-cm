<?
ini_set("display_errors", 1);

include 'sitepageutil.php';
include 'header.php';
include 'datagrid.php';


//The values array contains tablename.columnname keys with the proposed new values for the updated row.
//TODO: If the nav name has changed, create a redirect from the old nav name to maintain links to the old address.
function OnBeforeInsertUpdate($values)
{
	return $values; // Change values you want to alter before the update occurs.
}

$ds = new DataSet('zcm_sitepage','id');
$ds->AddColumns('id','disporder','linktext','body','parent','retire','golive','softlaunch');
$ds->OnBeforeUpdate = $ds->OnBeforeInsert = 'OnBeforeInsertUpdate';
$ds->AddDataFilter('parent',$p);

$dg = new DataGrid($ds);
$dg->AddConstant('parent',$p);
$dg->AddColumn('Menu Text','linktext');
$dg->AddColumn('Sub-Pages','id','<a href="sitepage.php?p={0}">Sub-Pages</a>');
$dg->AddColumn('Extras','id','<a href="sitepageextra.php?p={0}&pp=1">Extras</a>');
/*TODO: I'm not showing these in the grid, but I'd love to show a status column with 'Soft Launch', 'Live' or 'Retired'.  Need to extend the template feature to allow this, or some other stroke of genius.
$dg->AddColumn('Retire','retire');
$dg->AddColumn('Golive','golive');
$dg->AddColumn('Softlaunch','softlaunch');*/
$dg->AddUpDownColumn('disporder');
$dg->AddInput('linktext','Menu Text:',40,40);
$dg->AddYuiHtmlEditor('body','Body:');
$dg->AddEditor('retire','Retire After:','datetime');
$dg->AddEditor('golive','Go Live:','datetime');
$dg->AddEditor('softlaunch','Soft Launch:','datetime');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Page';
$dg->Render();

include('footer.php');
?>