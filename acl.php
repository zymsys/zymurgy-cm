<?
/**
 * Management screen(s) for Access Control Lists
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
$breadcrumbTrail = "Access Control Lists";
$wikiArticleName = "Access_Control_Lists";

include 'header.php';
include 'datagrid.php';

$ds = new DataSet('zcm_acl','id');
$ds->AddColumns('id','name');

$dg = new DataGrid($ds);
$dg->AddColumn('Name','name');
$dg->AddColumn("Access Groups", "id", "<a href=\"aclitem.php?acl={0}\">Access Groups</a>");
$dg->AddInput('name','Name:',50,50);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Access Control List';
$dg->Render();

include('footer.php');
?>
