<?
include('header.php');
ob_clean();
include('datagrid.php');
$st = $_GET['st'];
$ri = Zymurgy::$db->query("select id,body,inputspec from sitetext where tag='".Zymurgy::$db->escape_string($_GET['st'])."'");
$row = Zymurgy::$db->fetch_array($ri);
$_GET['editkey'] = $row['id']; //Fake out InputWidget
$inputspec = $row['inputspec'];

if (array_key_exists('action',$_GET))
{
	$action = $_GET['action'];
	if ($action=='cancel')
	{
		echo "<script lang='javascript'>
		window.close();
		</script>";
		exit;
	}
	if ($action=='save')
	{
		$elname = str_replace("'","\'","ST".$_GET['st']);
		$content = str_replace(array("'","\r","\n"),array("\'","\\r","\\n"),$row['body']);
		echo "<script lang='javascript'>
		var content = opener.document.getElementById('$elname');
		content.innerHTML = '$content';
		window.close();
		</script>";
		exit;
	}
}

function OnUpdate($dsr)
{
	//Load list of related pages
	$sql = "select metaid from textpage where sitetextid={$dsr['sitetext.id']}";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Unable to set update time using ($sql): ".Zymurgy::$db->error());
	$updated = array();
	while ((list($id) = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$updated[] = $id;
	}
	Zymurgy::$db->free_result($ri);
	//Update their modification times
	foreach($updated as $id)
	{
		Zymurgy::$db->query("update meta set mtime=".time()." where id=$id");
	}
	if (array_key_exists('dlg',$_GET))
	{
		echo "<script lang='javascript'>window.close();</script>";
		exit;
	}
}

$ds = new DataSet('sitetext','id');
$ds->AddColumn('id',false);
$ds->AddColumn('tag',true);
$ds->AddColumn('body',true);
$ds->OnUpdate = "OnUpdate";

$dg = new DataGrid($ds);
$dg->AddColumn('Text Block Name','tag');
$dg->AddColumn('','body','');
$dg->AddEditColumn();
$dg->insertlabel = '';
if (array_key_exists('editkey',$_GET))
{
	$id = 0 + $_GET['editkey'];
	$sql = "select id,inputspec from sitetext where id=$id";
	$ri = Zymurgy::$db->query($sql);
	$row = Zymurgy::$db->fetch_array($ri);
	$inputspec = $row['inputspec'];
	$dg->AddEditor('body','',$inputspec);
	Zymurgy::$db->free_result($ri);
}
$dg->customSaveLocation = "sitetextdlg.php?st=".urlencode($_GET['st'])."&extra=".urlencode($_GET['extra'])."&action=save";
$dg->customCancelLocation = "sitetextdlg.php?st=".urlencode($_GET['st'])."&extra=".urlencode($_GET['extra'])."&action=cancel";
$dg->Render();
?>