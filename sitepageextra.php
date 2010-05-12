<?
/**
 * Management screen for Page Gadgets.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
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

if (array_key_exists('action',$_GET) || array_key_exists('editkey',$_GET))
{
	$crumbs['/zymurgy/sitepageextra.php?p='.$p] = "Gadgets";
	$crumbs[] = 'Edit';
}
else
{
	$crumbs[] = "Gadgets";
}

$wikiArticleName = "Pages#Gadgets";

include 'header.php';
include 'datagrid.php';

$ds = new DataSet('zcm_sitepageplugin','id');
$ds->AddColumns('id','zcm_sitepage','disporder','plugin','align','acl');
$ds->AddDataFilter('zcm_sitepage',$p);

$dg = new DataGrid($ds);
$dg->AddConstant('zcm_sitepage',$p);
$dg->AddColumn('Gadget','plugin');
$dg->AddColumn('Alignment','align');
$dg->AddUpDownColumn('disporder');
$dg->AddEditor('plugin','Gadget:','plugin');
$dg->AddDropListEditor('align','Alignment:',array('left'=>'Left','center'=>'Center','right'=>'Right'));
$dg->AddLookup("acl", "Access Control List:", "zcm_acl", "id", "name", "name", true);
$dg->AddButton('Edit Content','sitepageextra.php?epi={0}');
$dg->editlabel = 'Content Type and Position';
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add a Gadget';
$dg->Render();

?>
<div id="instancenamescratch" style="display: none;">
</div>
<script type="text/javascript">
	document.datagridform.onsubmit = function() {
		zcm_sitepageplugin_plugin_update();

		if(document.getElementById("zcm_sitepageplugin.plugin-input").value == "")
		{
			alert("You must provide the name of the gadget.");
			return false;
		}
		else
		{
//			alert(document.getElementById("zcm_sitepageplugin.plugin").value);
		}
//		alert("Submitting Form");
	};
</script>
<?

include('footer.php');
?>