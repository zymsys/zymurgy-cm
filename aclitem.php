<?
include_once("cmo.php");

$sql = "SELECT `name` FROM `zcm_acl` WHERE `id` = '".
	Zymurgy::$db->escape_string($_GET["acl"]).
	"'";
$aclName = Zymurgy::$db->get($sql);

$breadcrumbTrail = "<a href=\"acl.php\">Access Control Lists</a> &gt; $aclName";

include 'header.php';
include 'datagrid.php';



$ds = new DataSet('zcm_aclitem','id');
$ds->AddColumns('id','zcm_acl','disporder','group','permission');
$ds->AddDataFilter("zcm_acl", $_GET["acl"]);


$dg = new DataGrid($ds);
$dg->AddConstant('zcm_acl',$_GET["acl"]);
$dg->AddColumn('Group','group');
$dg->AddColumn('Permission','permission');
$dg->AddUpDownColumn('disporder');
$dg->AddLookup('group','Group:','zcm_groups','id','name','name');
$dg->AddDropListEditor('permission','Permission:',array("Read" => "Read"));
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Item';
$dg->Render();

include('footer.php');
?>