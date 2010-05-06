<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
// Master Config is only editable by webmasters
$adminlevel = 2;

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"configconfig.php\">Appearance Items</a> &gt; Edit";
	$wikiArticleName = "Appearance_Items#Editing_Appearance_Items";
}
else
{
	$breadcrumbTrail = "Appearance Items";
	$wikiArticleName = "Appearance_Items";
}

include('header.php');
include('datagrid.php');
if ($zauth->authinfo['admin']<2) return;

$ds = new DataSet('zcm_config','id');
$ds->AddColumn('id',false);
$ds->AddColumn('name',true);
$ds->AddColumn('value',true);
$ds->AddColumn('inputspec',true);
$ds->AddColumn('disporder',true);

$dg = new DataGrid($ds);
$dg->AddColumn('Config Name','name');
$dg->AddEditColumn();
$dg->AddUpDownColumn('disporder');
$dg->AddDeleteColumn();
$dg->AddInput('name','Config Name:',40,40);
$dg->AddEditor('inputspec','Input Spec:','inputspec');
$dg->Render();
include('footer.php');
?>
