<?
$adminlevel = 2;
$breadcrumbTrail = "<a href=\"plugin.php\">Plugins</a> &gt; Instances";

require_once('header.php');
require_once('datagrid.php');

$pid = 0+$_GET['plugin'];

function OnDelete($values)
{
	//print_r($values); exit;
	$sql = "select name from zcm_plugin where id={$values['zcm_plugininstance.plugin']}";
	$ri = Zymurgy::$db->query($sql) or die("Unable to find plugin name ($sql): ".Zymurgy::$db->error());
	$pname = Zymurgy::$db->result($ri,0,0);
	if ($pname=='') die ("Couldn't find plugin {$values['zcm_plugininstance.plugin']}.");
	$pi = Zymurgy::mkplugin($pname,$values['zcm_plugininstance.name']);
	$pi->RemoveInstance();
	return true;
}

$ds = new DataSet('zcm_plugininstance','id');
$ds->AddColumns('id','plugin','name');
$ds->AddDataFilter('plugin',$pid);
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddColumn('Instance Name','name');
$dg->AddColumn('','id',"<a href=\"pluginconfig.php?plugin=$pid&instance={0}\">Config</a>");
$dg->AddColumn('','id',"<a href=\"pluginsuperadmin.php?plugin=$pid&instance={0}\">Super Admin</a>");
$dg->AddDeleteColumn();
$dg->insertlabel = '';
$dg->Render();
echo "To add a new instance, create a page which calls plugin() for the instance you want.";
require_once('footer.php');
?>
