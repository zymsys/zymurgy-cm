<?
include('header.php');
include('datagrid.php');

$ds = new DataSet('button','id');
$ds->AddColumn('id',false);
$ds->AddColumn('name',true);
$ds->AddColumn('page',true);
$ds->AddColumn('disporder',true);

$dg = new DataGrid($ds);
$dg->AddColumn('Button Name','name');
$dg->AddEditColumn();
$dg->AddUpDownColumn('disporder');
if ($zauth->authinfo['admin']<2)
{
	$dg->insertlabel = '';
}
else 
{
	$dg->AddInput('page','Page:',30,30);
	$dg->AddDeleteColumn();
}
$dg->AddInput('name','Button Name:',35,35);
$dg->Render();
include('footer.php');
?>
