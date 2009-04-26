<?
require_once('cmo.php');
if (array_key_exists('epi',$_GET))
{
	$epi = 0 + $_GET['epi'];
	$pitxt = Zymurgy::$db->get("select plugin from zcm_sitepageplugin where id=$epi");
	$ep = explode('&',$pitxt);
	$piname = urldecode($ep[0]);
	$instance = urldecode($ep[1]);
	$pi = Zymurgy::mkplugin($piname,$instance); //Make sure it exists; look up its vitals
	$link = "pluginadmin.php?pid={$pi->pid}&iid={$pi->iid}&name=".urlencode($instance);
	header("Location: $link");
	exit;
}

include 'sitepageutil.php';
$crumbs[] = "Gadgets";

if (array_key_exists('action',$_GET) || array_key_exists('editkey',$_GET))
{
	$crumbs[] = 'Edit';
}
include 'header.php';
include 'datagrid.php';

$ds = new DataSet('zcm_sitepageplugin','id');
$ds->AddColumns('id','zcm_sitepage','disporder','plugin','align');
$ds->AddDataFilter('zcm_sitepage',$p);

$dg = new DataGrid($ds);
$dg->AddConstant('zcm_sitepage',$p);
$dg->AddColumn('Extra Page Content','plugin');
$dg->AddColumn('Alignment','align');
$dg->AddUpDownColumn('disporder');
$dg->AddEditor('plugin','Extra Page Content:','plugin');
$dg->AddDropListEditor('align','Alignment:',array('left'=>'Left','center'=>'Center','right'=>'Right'));
$dg->AddButton('Edit Content','sitepageextra.php?epi={0}');
$dg->editlabel = 'Content Type and Position';
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add Extra Page Content';
$dg->Render();

include('footer.php');
?>