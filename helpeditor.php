<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
$adminlevel = 2;
$p = array_key_exists('h',$_GET) ? 0 + $_GET['h'] : 0;

if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"helpeditor.php\">Help Editor</a> &gt; Edit";
}
else if($p > 0)
{
	$breadcrumbTrail = "<a href=\"helpeditor.php\">Help Editor</a> &gt; Subsections";
}
else 
{
	$breadcrumbTrail = "Help Editor";	
}

include 'header.php';
include 'datagrid.php';

//The values array contains tablename.columnname keys with values from the row to be deleted.
function OnDelete($values)
{
	//Remote See Also references to this help page
	Zymurgy::$db->query("delete from zcm_helpalso where seealso={$values['zcm_help.id']}");
	//Get a list of phrases from the index which point to this page
	$ri = Zymurgy::$db->query("select distinct(phrase) from zcm_helpindex where help={$values['zcm_help.id']}");
	$phrases = array();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$phrases[] = $row[0];
	}
	Zymurgy::$db->free_result($ri);
	//Remove phrase references to this help page
	Zymurgy::$db->query("delete from zcm_helpindex where help={$values['zcm_help.id']}");
	//Search for orphaned phrases
	if (count($phrases)>0)
	{
		$sql = "select zcm_helpindex.phrase, zcm_helpindexphrase.id from zcm_helpindexphrase left join zcm_helpindex on zcm_helpindexphrase.id=zcm_helpindex.phrase 
			where zcm_helpindexphrase.id in (".implode(',',$phrases).") group by zcm_helpindex.phrase";
		$ri = Zymurgy::$db->query($sql) or die("Can't search orphaned phrases ($sql): ".Zymurgy::$db->error());
		$orhpans = array();
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			if (empty($row['phrase']))
			{
				$orhpans[] = $row['id'];
			}
		}
		Zymurgy::$db->free_result($ri); 
		if (count($orhpans)>0)
		{
			//Now remove phrases which are no longer referenced.  Yes this introduces a race condition where a phrase could be added for another page which
			//makes it no longer an orphan in the same instant we would have orphaned it, but I'll live with the odds.
			Zymurgy::$db->query("delete from zcm_helpindexphrase where id in (".implode(',',$orhpans).")");
		}
	}
	return true; //Return false to override delete.
}

//The values array contains tablename.columnname keys with the proposed new values for the updated row.
function OnBeforeUpdate($values)
{
	$values['zcm_help.plain'] = strip_tags($values['zcm_help.body']); //Used for full text index
	return $values; // Change values you want to alter before the update occurs.
}

$ds = new DataSet('zcm_help','id');
$ds->AddColumns('id','parent','disporder','authlevel','title','body','plain');
$ds->OnDelete = 'OnDelete';
$ds->OnBeforeUpdate = 'OnBeforeUpdate';
$ds->OnBeforeInsert = 'OnBeforeUpdate';
$ds->AddDataFilter('parent',$p);

$dg = new DataGrid($ds);
$dg->AddConstant('parent',$p);
$dg->AddColumn('Authlevel','authlevel');
$dg->AddColumn('Title','title');
$dg->AddColumn('Subsections','id',"<a href=\"helpeditor.php?h={0}\">Subsections</a>");
$dg->AddColumn('References','id',"<a href=\"helpalso.php?h={0}\">See Also</a>");
$dg->AddColumn('Index','id',"<a href=\"helpindex.php?h={0}\">Index Phrases</a>");
$dg->AddUpDownColumn('disporder');
$dg->AddDropListEditor('authlevel','Authlevel',array(-1=>'All Users',0=>'Normal Users',1=>'Administrators',2=>'Webmasters/Developers'));
$dg->AddInput('title','Title:',200,60);
$dg->AddHtmlEditor('body','Body:');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->AddValidator('title','ValidateTitle','You must enter a title.','');
$dg->insertlabel = 'Add New Help Page';
$dg->Render();

include('footer.php');
?>
