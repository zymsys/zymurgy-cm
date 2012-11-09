<?
/**
 * Screen for managing the list of fields in a Custom Table.
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

$crumbs = array("customtable.php"=>"Custom Tables");
Zymurgy::customTableTool()->tablecrumbs($t);
$crumbs["customfield.php?t=$t"] = 'Fields';
if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$crumbs[''] = 'Edit';
}

$wikiArticleName = "Custom_Tables#Adding_and_Editing_Fields";

include 'header.php';

echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
echo Zymurgy::YUI('connection/connection-min.js');

/**
 * Delete Event Handler. Performs the actual ALTER/DROP operation on the Custom Table.
 *
 * @param $values mixed The values of the row to be deleted. Keys are in 
 * tablename.columname format.
 */
function OnDelete($values)
{
    $fieldId = $values['zcm_customfield.id'];
    Zymurgy::customTableTool()->dropColumn($fieldId);
	return false; //Return false to override delete.
}

/**
 * Update Event Handler. Performs the actual ALTER TABLE operations on the 
 * Custom Table.
 * 
 * @param $values mixed The values of the row to be updated. Keys are in 
 * tablename.columname format.
 */
function OnBeforeUpdate($values)
{
	$okName = Zymurgy::customTableTool()->okname($values['zcm_customfield.cname']);
	if ($okName!==true)
	{
		return $okName;
	}
    Zymurgy::customTableTool()->updateField(
        $values['zcm_customfield.id'],
        array(
            'cname'=>$values['zcm_customfield.cname'],
            'inputspec'=>$values['zcm_customfield.inputspec'],
            'indexed'=>$values['zcm_customfield.indexed'],
            'caption'=>$values['zcm_customfield.caption'],
            'gridheader'=>$values['zcm_customfield.gridheader'],
            'globalacl'=>$values['zcm_customfield.globalacl'],
            'acl'=>$values['zcm_customfield.acl'],
        )
    );
    return false; //Allow customTableTool to update the row, not the datagrid
}

/**
 * INSERT Event Handler. Performs the actual ALTER TABLE operations on the 
 * Custom Table.
 * 
 * @param $values mixed The values of the row to be inserted. Keys are in 
 * tablename.columname format.
 */
function OnBeforeInsert($values)
{
	$okname = Zymurgy::customTableTool()->okname($values['zcm_customfield.cname']);
	if ($okname!==true)
	{
		return $okname;
	}
    global $t;
    Zymurgy::customTableTool()->addField(intval($t),
        array(
            'cname'=>$values['zcm_customfield.cname'],
            'inputspec'=>$values['zcm_customfield.inputspec'],
            'indexed'=>$values['zcm_customfield.indexed'],
            'caption'=>$values['zcm_customfield.caption'],
            'gridheader'=>$values['zcm_customfield.gridheader'],
            'globalacl'=>$values['zcm_customfield.globalacl'],
            'acl'=>$values['zcm_customfield.acl'],
        )
    );
    return false; //Allow customTableTool to insert the row, not the datagrid
}

$ds = new DataSet('zcm_customfield','id');
$ds->AddColumns('id','disporder','tableid','cname','inputspec','caption','indexed','gridheader','globalacl','acl');
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
$dg->AddLookup("globalacl", "Global ACL:", "zcm_acl", "id", "name", "name", true);
$dg->AddLookup("acl", "Member Data ACL:", "zcm_acl", "id", "name", "name", true);
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Field';
$dg->Render();
?>
<?php
include('footer.php');
?>
