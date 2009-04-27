<?
require_once('cmo.php');
include 'sitepageutil.php';
$crumbs[] = "Page Contents";

include 'header.php';
include 'datagrid.php';

$p = 0 + $_GET['p'];
$inputspec = 'html.600.400';
$editcaption = "Content:";

/**
 * Make sure we've got all the tags for this page that the template requests.
 * Also pick up the inputspec if we're editing so we know how to edit this puppy.
 */
$t = Zymurgy::$db->get("select template from zcm_sitepage where id=$p");
$currentcontent = array(); //Array of content relevent to the current template for the page.  Used in AddDataFilter below.

if ($t)
{
	$templatetags = array();
	$pagetags = array();
	$specs = array();
	$ri = Zymurgy::$db->run("select id,tag,inputspec from zcm_templatetext where template=$t");
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$templatetags[] = $row['tag'];
		$specs[$row['tag']] = $row['inputspec']; //Store for later when we need to figure out what spec to use in the editor.
	}
	Zymurgy::$db->free_result($ri);
	
	$ri = Zymurgy::$db->run("select id,tag from zcm_pagetext where sitepage=$p");
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$pagetags[] = $row['tag'];
		if (array_key_exists('editkey',$_GET) && ($row['id'] == $_GET['editkey']))
		{
			$inputspec = $specs[$row['tag']];
			$editcaption = $row['tag'].":";
		}
		if (array_key_exists($row['tag'],$specs))
			$currentcontent[] = $row['id'];
	}
	Zymurgy::$db->free_result($ri);
	
	$newtags = array_diff($templatetags,$pagetags);
	foreach($newtags as $tag)
	{
		Zymurgy::$db->run("insert into zcm_pagetext (sitepage,tag) values ($p,'".
			Zymurgy::$db->escape_string($tag)."')");
	}
}
else 
{
	echo "This template hasn't yet been processed by Zymurgy:CM.  Please load it once in your browser, and then try again.";
	include('footer.php');
	exit;
}

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	$t = Zymurgy::$db->get("select template from zcm_sitepage where id={$values['zcm_pagetext.sitepage']}");
	Zymurgy::$db->run("delete from zcm_templatetext where (template=$t) and (tag='".
		Zymurgy::$db->escape_string($values['zcm_pagetext.tag'])."')");
	return true; //Return false to override delete.
}

$ds = new DataSet('zcm_pagetext','id');
$ds->AddColumn('id',false);
$ds->AddColumns('sitepage','tag','body');
$ds->AddDataFilter('sitepage',$p);
if ($currentcontent)
{
	$ds->AddDataFilter('id','('.implode(',',$currentcontent).')','in');
}
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddConstant('sitepage',$p);
$dg->AddColumn('Tag','tag');
if ($zauth->authinfo['admin']>=2)
{
	$dg->AddInput('tag','Tag:',35,35);
}
$dg->AddEditor('body',$editcaption,$inputspec);
$dg->AddEditColumn();
if ($zauth->authinfo['admin']>=2)
{
	$dg->AddDeleteColumn();
}
$dg->insertlabel = '';
$dg->Render();

include('footer.php');
?>