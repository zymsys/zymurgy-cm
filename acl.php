<?
$breadcrumbTrail = "Access Control Lists";

include 'header.php';
include 'datagrid.php';



$ds = new DataSet('zcm_acl','id');
$ds->AddColumns('id','name');


$dg = new DataGrid($ds);
$dg->AddColumn('Name','name');
$dg->AddColumn("Items", "id", "<a href=\"aclitem.php?acl={0}\">Items</a>");
$dg->AddInput('name','Name:',50,50);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Access Control List';
$dg->Render();

include('footer.php');
?>