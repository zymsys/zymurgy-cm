<?php 
if (array_key_exists('pii',$_GET))
{
	$breadcrumbTrail = "<a href=\"tagmgr.php\">Tag Manager</a> &gt; Edit Tags";
	//$wikiArticleName = "Simple_Content#Edit";
}
else
{
	$breadcrumbTrail = "Tag Manager";
	//$wikiArticleName = "Simple_Content";
}

include('header.php');
include('datagrid.php');

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	Zymurgy::$db->run("delete from zcm_tagcloudrelatedrow where tag=".$values['zcm_tagcloudtag.id']);
	return true; //Return false to override delete.
}

if (array_key_exists('pii', $_GET))
{
	$ds = new DataSet('zcm_tagcloudtag','id');
	$ds->AddColumns('id','instance','name');
	$ds->AddDataFilter('instance', $_GET['pii']);
	$ds->OnDelete = 'OnDelete';
	
	$dg = new DataGrid($ds);
	$dg->AddConstant('instance',$_GET['pii']);
	$dg->AddColumn('Name','name');
	$dg->AddEditor('name', 'Name', 'inputf.100.100');
	$dg->AddEditColumn();
	$dg->AddDeleteColumn();
	$dg->insertlabel = 'Add New Tag';
	$dg->Render();
}
else
{
	$pluginid = Zymurgy::$db->get("SELECT `id` FROM `zcm_plugin` WHERE `name`='TagCloud'");
	
	$ds = new DataSet('zcm_plugininstance','id');
	$ds->AddColumns('id','plugin','name','private','config');
	$ds->AddDataFilter('plugin',$pluginid);
	
	$dg = new DataGrid($ds);
	$dg->AddColumn('Name','name');
	$dg->AddButton('Edit Tags', "tagmgr.php?pii={0}");
	$dg->insertlabel = '';
	$dg->Render();
}
include('footer.php');
?>