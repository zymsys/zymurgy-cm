<?
include 'header.php';
include 'datagrid.php';

//Hack flavours in the Zymurgy class so that we can handle paths in the same way that we normally would flavoured content.
Zymurgy::MapTemplateToContentFlavours();

$ds = new DataSet('zcm_template','id');
$ds->AddColumns('id','name','path');

$dg = new DataGrid($ds);
$dg->AddColumn('Name','name');
$dg->AddColumn('Path','path');
$dg->AddInput('name','Name:',30,30);
$dg->AddEditor('path','Path:','inputf.60.200');
//$dg->AddInput('path','Path:',200,60);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Template';
$dg->Render();

include('footer.php');
?>