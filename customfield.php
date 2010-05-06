<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
// Custom field definitions may only be set by a webmaster
$adminlevel = 2;

ob_start();

$t = 0 + $_GET['t'];

require_once('cmo.php');
include 'datagrid.php';
include 'customlib.php';

$crumbs = array("customtable.php"=>"Custom Tables");
tablecrumbs($t);
$crumbs["customfield.php?t=$t"] = 'Fields';
if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$crumbs[''] = 'Edit';
}

$wikiArticleName = "Custom_Tables#Adding_and_Editing_Fields";

include 'header.php';

echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
echo Zymurgy::YUI('connection/connection-min.js');

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	global $t;
	$tbl = gettable($t);
	$sql = "alter table `{$tbl['tname']}` drop `{$values['zcm_customfield.cname']}`";
	mysql_query($sql) or die("Unabel to remove column ($sql): ".mysql_error());
	return true; //Return false to override delete.
}

//The values array contains tablename.columnname keys with the proposed new values for the updated row.
function OnBeforeUpdate($values)
{
	$okname = okname($values['zcm_customfield.cname']);
	if ($okname!==true)
	{
		return $okname;
	}
	global $t;
	$tbl = gettable($t);
	$sqltype = inputspec2sqltype($values['zcm_customfield.inputspec']);
	$sql = "select * from zcm_customfield where id={$values['zcm_customfield.id']}";
	$ri = mysql_query($sql) or die("Unable to get old field info ($sql): ".mysql_error());
	$old = mysql_fetch_array($ri) or die ("No such field ($sql)");
	if (($old['cname']!=$values['zcm_customfield.cname']) || ($old['inputspec']!=$values['zcm_customfield.inputspec']))
	{
		$oldiw = InputWidget::GetFromInputSpec($old['inputspec']);
		$newiw = InputWidget::GetFromInputSpec($values['zcm_customfield.inputspec']);
		//echo "<div>[".$oldiw->SupportsFlavours().",".$newiw->SupportsFlavours()."]</div>"; exit;
		if ($oldiw->SupportsFlavours() && !$newiw->SupportsFlavours())
		{
			//Moving from flavoured to vanilla
			Zymurgy::ConvertFlavouredToVanilla($tbl['tname'],$old['cname']);
		}
		elseif (!$oldiw->SupportsFlavours() && $newiw->SupportsFlavours())
		{
			//Moving from vanilla to flavoured
			//echo "<div>v2f: {$tbl['tname']} - {$old['cname']}</div>"; exit;
			Zymurgy::ConvertVanillaToFlavoured($tbl['tname'],$old['cname']);
		}
		//Do this even if we converted to/from flavoured to support column rename
		//Column name or type has changed, update the db.
		$renamecolumn = false; //Done right here
		$sql = "alter table `{$tbl['tname']}` change `{$old['cname']}` `{$values['zcm_customfield.cname']}` $sqltype";
		mysql_query($sql) or die("Unable to change field ($sql): ".mysql_error());
	}
	if ($old['indexed']!=$values['zcm_customfield.indexed'])
	{
		//Add or remove an index
		if ($values['zcm_customfield.indexed']=='Y')
		{
			$sql = "alter table `{$tbl['tname']}` add index(`{$values['zcm_customfield.cname']}`)";
		}
		else
		{
			$sql = "alter table `{$tbl['tname']}` drop index(`{$values['zcm_customfield.cname']}`)";
		}
		mysql_query($sql) or die("Can't change index ($sql): ".mysql_error());
	}
	return $values; // Change values you want to alter before the update occurs.
}

//The values array contains tablename.columnname keys with the proposed new values for the new row.
function OnBeforeInsert($values)
{
	$okname = okname($values['zcm_customfield.cname']);
	if ($okname!==true)
	{
		return $okname;
	}
	global $t;
	$tbl = gettable($t);
	$sqltype = inputspec2sqltype($values['zcm_customfield.inputspec']);
	$sql = "alter table `{$tbl['tname']}` add `{$values['zcm_customfield.cname']}` $sqltype";
	mysql_query($sql) or die("Unable to add column ($sql): ".mysql_error());
	if ($values['zcm_customfield.indexed']=='Y')
	{
		$sql = "alter table `{$tbl['tname']}` add index(`{$values['zcm_customfield.cname']}`)";
	}
	return $values; // Change values you want to alter before the insert occurs.
}

$ds = new DataSet('zcm_customfield','id');
$ds->AddColumns('id','disporder','tableid','cname','inputspec','caption','indexed','gridheader');
$ds->AddDataFilter('tableid',$t);
$ds->OnBeforeUpdate = 'OnBeforeUpdate';
$ds->OnBeforeInsert = 'OnBeforeInsert';
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddConstant('tableid',$t);
$dg->AddColumn('Field Name','cname');
$dg->AddColumn('Indexed','indexed');
$dg->AddColumn('Grid Header','gridheader');
$dg->AddUpDownColumn('disporder');
$dg->AddInput('cname','Field Name:',30,30);
$dg->AddDropListEditor('indexed','Indexed?',array('N'=>'No','Y'=>'Yes'));
$dg->AddInput('gridheader','Grid Header:',30,30);
$dg->AddEditor('inputspec','Input Spec:','inputspec');
$dg->AddInput('caption','Caption:',4096,40);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Field';
$dg->Render();
?>
<?php
include('footer.php');
?>
