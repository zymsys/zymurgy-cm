<?
$crumbs = array("ssoapp.php"=>"Single Sign On Apps");

if(isset($_GET["editkey"]))
{
	$crumbs[''] = "Edit";
}

include 'header.php';
include 'datagrid.php';

$ds = new DataSet('zcm_ssoapp','id');
$ds->AddColumns('id','name','link');

$dg = new DataGrid($ds);
$dg->AddColumn('ID#','id');
$dg->AddColumn('Name','name');
$dg->AddColumn('Link','link');
$dg->AddInput('name', 'Name:', 100, 100);
$dg->AddInput('link', 'Link:', 100, 100);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New "Single Sign On" App';
$dg->Render();

include('footer.php');
?>