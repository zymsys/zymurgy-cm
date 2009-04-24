<?
//Somehow I need an editor for zcm_pagetext which makes sense.
/*
CREATE TABLE `zcm_pagetext` (
  `id` bigint(20) NOT NULL auto_increment,
  `sitepage` bigint(20) NOT NULL default '0',
  `tag` varchar(35) NOT NULL default '',
  `body` longtext,
  KEY (`sitepage`),
  PRIMARY KEY  (`id`))
  
http://ym.cantechresearch.com/zymurgy/sitepagetext.php?p=11&pp=1
*/
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
	}
	Zymurgy::$db->free_result($ri);
	
	$newtags = array_diff($templatetags,$pagetags);
	foreach($newtags as $tag)
	{
		Zymurgy::$db->run("insert into zcm_pagetext (sitepage,tag) values ($p,'".
			Zymurgy::$db->escape_string($tag)."')");
	}
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
$ds->AddColumns('id','sitepage','tag','body');
$ds->AddDataFilter('sitepage',$p);
$ds->OnDelete = 'OnDelete';

$dg = new DataGrid($ds);
$dg->AddConstant('sitepage',$p);
$dg->AddColumn('Tag','tag');
$dg->AddInput('tag','Tag:',35,35);
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