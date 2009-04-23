<?
include 'header.php';
include 'datagrid.php';

$ds = new DataSet('zcm_template','id');
$ds->AddColumns('id','name','path');

$dg = new DataGrid($ds);
$dg->AddColumn('Name','name');
$dg->AddColumn('Path','path');
$dg->AddInput('name','Name:',30,30);
$dg->AddInput('path','Path:',200,60);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Template';
$dg->Render();

include('footer.php');
?>