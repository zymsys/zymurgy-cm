<?
	$breadcrumbTrail = "Flavours";

	include 'header.php';
	include 'datagrid.php';
	
	class FlavourLookup extends ZIW_Lookup 
	{
		function PreRender()
		{
			parent::PreRender();
			$this->extra['lookups']['zcm_flavour']->values[-1] = 'Create New';
			$this->extra['lookups']['zcm_flavour']->keys[-1] = -1;
			ksort($this->extra['lookups']['zcm_flavour']->keys);
		}
	}
	
	function OnInsert($values)
	{
		if ($values['zcm_flavour.contentprovider']==-1)
			Zymurgy::$db->run("UPDATE `zcm_flavour` SET `contentprovider`={$values['zcm_flavour.id']} where `id`={$values['zcm_flavour.id']}");
		if ($values['zcm_flavour.templateprovider']==-1)
			Zymurgy::$db->run("UPDATE `zcm_flavour` SET `templateprovider`={$values['zcm_flavour.id']} where `id`={$values['zcm_flavour.id']}");
	}

	$mungelookups = (($_SERVER['REQUEST_METHOD']=='GET') && array_key_exists('action',$_GET) && ($_GET['action'] == 'insert'));
	if ($mungelookups)
	{
		InputWidget::Register('lookup',new FlavourLookup());
	}
	
	$ds = new DataSet('zcm_flavour','id');
	$ds->AddColumns('id','disporder','code','label','contentprovider','templateprovider');
	$ds->OnInsert = 'OnInsert';
	
	$dg = new DataGrid($ds);
	$dg->AddColumn('Code','code');
	$dg->AddColumn('Label','label');
	$dg->AddUpDownColumn('disporder');
	$dg->AddInput('code','Code:',50,20);
	$dg->AddInput('label','Label:',200,50);
	$dg->AddLookup('contentprovider','Content Provider:','zcm_flavour','id','label','disporder',true);
	$dg->AddLookup('templateprovider','Template Provider:','zcm_flavour','id','label','disporder',true);
	$dg->AddEditColumn();
	$dg->AddDeleteColumn();
	$dg->insertlabel = 'Add New Flavour';
	$dg->Render();

	include('footer.php');
?>