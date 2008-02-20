<?
// Master Config is only editable by webmasters
$adminlevel = 2;

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"configconfig.php\">Master Config</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "Master Config";	
}

include('header.php');
include('datagrid.php');
if ($zauth->authinfo['admin']<2) return;

$ds = new DataSet('config','id');
$ds->AddColumn('id',false);
$ds->AddColumn('name',true);
$ds->AddColumn('value',true);
$ds->AddColumn('disporder',true);

$dg = new DataGrid($ds);
$dg->AddColumn('Config Name','name');
$dg->AddEditColumn();
$dg->AddUpDownColumn('disporder');
$dg->AddDeleteColumn();
$dg->AddInput('name','Config Name:',40,40);
$dg->Render();
include('footer.php');
?>
