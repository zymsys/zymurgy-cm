<?
	$breadcrumbTrail = "Flavours";

	include 'header.php';
	include 'datagrid.php';

	$ds = new DataSet('zcm_flavour','id');
	$ds->AddColumns('id','disporder','code','label');

	$dg = new DataGrid($ds);
	$dg->AddColumn('Code','code');
	$dg->AddColumn('Label','label');
	$dg->AddUpDownColumn('disporder');
	$dg->AddInput('code','Code:',50,20);
	$dg->AddInput('label','Label:',200,50);
	$dg->AddEditColumn();
	$dg->AddDeleteColumn();
	$dg->insertlabel = 'Add New Flavour';
	$dg->Render();

	include('footer.php');
?>