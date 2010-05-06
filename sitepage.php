<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */

include 'sitepageutil.php';

if(isset($_GET["editkey"]))
{
	$wikiArticleName = "Pages#Editing_Pages";
}

include 'header.php';
include 'datagrid.php';
require_once('sitenav.php');

$templatecount = Zymurgy::$db->get("select count(*) from zcm_template");
if ($templatecount == 0)
{
	//Create default template
	Zymurgy::$db->run("insert into zcm_template (name,path) values ('Simple','/zymurgy/templates/simple.php')");
	$defaulttemplate = Zymurgy::$db->insert_id();
}
else if ($templatecount==1)
{
	$defaulttemplate = Zymurgy::$db->get("select min(id) from zcm_template");
}

//The values array contains tablename.columnname keys with the proposed new values for the updated row.
//TODO: If the nav name has changed, create a redirect from the old nav name to maintain links to the old address.
function OnBeforeInsertUpdate($values)
{
	/*$values['zcm_sitepage.linkurl_default'] = ZymurgySiteNav::linktext2linkpart($_POST['zcm_sitepage_linktext_default']);
	$flavours = Zymurgy::GetAllFlavours();
	foreach ($flavours as $flavour)
	{
		if ($flavour['providescontent'])
		{
			$values["zcm_sitepage.linkurl_{$flavour['code']}"] = ZymurgySiteNav::linktext2linkpart($_POST["zcm_sitepage_linktext_{$flavour['code']}"]);
		}
	}*/
	return $values; // Change values you want to alter before the update occurs.
}

function OnBeforeInsert($values)
{
	$values = OnBeforeInsertUpdate($values);
	$newnavname = $values['zcm_sitepage.linkurl'];
	//Remove any old redirects for this nav url.  They'll be superceded by this new item.
	Zymurgy::$db->run("delete from zcm_sitepageredirect where parent={$values['zcm_sitepage.parent']} and linkurl='".
		Zymurgy::$db->escape_string($newnavname)."'");
	return $values;
}

function OnBeforeUpdate($values)
{
	$values = OnBeforeInsertUpdate($values);
	$flavours = Zymurgy::GetAllFlavours();
	foreach ($flavours as $flavour)
	{
		if ($flavour['providescontent'])
		{

		}
	}
	//Zymurgy::DbgAndDie($_POST,$values);
	$newnavname = $values['zcm_sitepage.linkurl'];
	//Handle zcm_sitepageredirect updates to preserve old links
	$oldrow = Zymurgy::$db->get("select * from zcm_sitepage where id=".$values['zcm_sitepage.id']);
	if ($newnavname != $oldrow['linkurl'])
	{
		//Link has changed.  Save change so that we can redirect anyone who tries the old link.
		//Is this redirect already active?  Maybe the user is flip-flopping names.
		$oldredirect = Zymurgy::$db->get("select * from zcm_sitepageredirect where parent={$values['zcm_sitepage.parent']} and linkurl='".
			Zymurgy::$db->escape_string($oldrow['linkurl'])."'");
		if ($oldredirect)
		{
			//We had an old redirect, make sure it points to the new location.
			Zymurgy::$db->run("update zcm_sitepageredirect set sitepage={$values['zcm_sitepage.id']} where id={$oldredirect['id']}");
		}
		else
		{
			//This is a fresh redirect; create it.
			Zymurgy::$db->run("insert into zcm_sitepageredirect (sitepage,parent,linkurl) values ({$values['zcm_sitepage.id']}, {$values['zcm_sitepage.parent']}, '".
				Zymurgy::$db->escape_string($oldrow['linkurl'])."')");
		}
	}
	return $values;
}

/**
 * Get all flavour values for content flavours, indexed by the flavour's code.
 *
 * @return array
 */
function GetFLValues()
{
	$flvalues = array();
	$flavours = Zymurgy::GetAllFlavours();
	foreach ($flavours as $flavour)
	{
		if ($flavour['providescontent'])
		{
			$flvalues[$flavour['code']] = ZymurgySiteNav::linktext2linkpart($_POST["zcm_sitepage_linktext_{$flavour['code']}"]);
		}
	}
	return $flvalues;
}

function SetRedirect($page,$parent,$flavour,$linkurl)
{
	//Link has changed.  Save change so that we can redirect anyone who tries the old link.
	//Is this redirect already active?  Maybe the user is flip-flopping names.
	$oldredirect = Zymurgy::$db->get("select * from zcm_sitepageredirect where parent=$parent and linkurl='".
		Zymurgy::$db->escape_string($linkurl)."'");
	if ($oldredirect)
	{
		//We had an old redirect, make sure it points to the new location.
		Zymurgy::$db->run("update zcm_sitepageredirect set sitepage=$page where id={$oldredirect['id']}");
	}
	else
	{
		//This is a fresh redirect; create it.
		Zymurgy::$db->run("insert into zcm_sitepageredirect (sitepage,parent,flavour,linkurl) values ($page, $parent, $flavour, '".
			Zymurgy::$db->escape_string($linkurl)."')");
	}
}

function OnUpdate($values)
{
	$flvalues = GetFLValues(); //Gets new values from post
	$codes = Zymurgy::GetAllFlavoursByCode();
	foreach ($flvalues as $code=>$value)
	{
		$flavour = $codes[$code]['id'];
		$oldflvalue = Zymurgy::$db->get("select `text` from zcm_flavourtextitem
			where (zcm_flavourtext={$values['zcm_sitepage.linkurl']})
			and (flavour=$flavour)");
		if ($value != $oldflvalue)
		{
			SetRedirect($values['zcm_sitepage.id'],$values['zcm_sitepage.parent'],$flavour,$oldflvalue);
		}
	}
	$oldflvalue = ZymurgySiteNav::linktext2linkpart(
		Zymurgy::$db->get("select `default` from zcm_flavourtext where id={$values['zcm_sitepage.linkurl']}"));
	if ($_POST['zcm_sitepage_linktext_default'] != $oldflvalue)
	{
		SetRedirect($values['zcm_sitepage.id'],$values['zcm_sitepage.parent'],0,$oldflvalue);
	}
	OnInsertUpdate($values,$flvalues);
}

function OnInsertUpdate($values,$flvalues = null)
{
	if (is_null($flvalues))
	{
		$flvalues = GetFLValues();
	}
	$newfv = ZIW_Base::StoreFlavouredValue(
		array_key_exists('zcm_sitepage.linkurl',$values) ? $values['zcm_sitepage.linkurl'] : false,
		ZymurgySiteNav::linktext2linkpart($_POST['zcm_sitepage_linktext_default']),
		$flvalues);
	Zymurgy::$db->run("update zcm_sitepage set linkurl=$newfv where id=".$values['zcm_sitepage.id']);
}

function OnDelete($values)
{
	//Clear out flavoured text values
	if ($values['zcm_sitepage.linkurl'])
	{
		Zymurgy::$db->run("delete from zcm_flavourtextitem where zcm_flavourtext=".$values['zcm_sitepage.linkurl']);
		Zymurgy::$db->run("delete from zcm_flavourtext where id=".$values['zcm_sitepage.linkurl']);
	}
}

$ds = new DataSet('zcm_sitepage','id');
$ds->AddColumns('id','disporder','linktext','linkurl','parent','retire','golive','softlaunch','template','acl');
$ds->OnInsert = 'OnInsertUpdate';
$ds->OnUpdate = 'OnUpdate';
$ds->OnDelete = 'OnDelete';
$ds->AddDataFilter('parent',$p);

$dg = new DataGrid($ds);
$dg->AddConstant('parent',$p);
$dg->AddColumn('Menu Text','linktext');
$dg->AddColumn('Page Contents','id','<a href="sitepagetext.php?p={0}">Page Contents</a>');
$dg->AddColumn("SEO", "id", "<a href=\"sitepageseo.php?p={0}\">SEO</a>");
$dg->AddColumn('Gadgets','id','<a href="sitepageextra.php?p={0}">Gadgets</a>');
$dg->AddColumn('Sub-Pages','id','<a href="sitepage.php?p={0}">Sub-Pages</a>');

//Use the softlaunch, golive, and retire columns to decide what the page's
//status is.
$dg->AddColumn('Launch Status','golive');
function launchstatus($column, $values, $display)
{
    if ($column == "zcm_sitepage.golive")
    {
        $today = mktime();
        $softlaunch_time = strtotime($values['zcm_sitepage.softlaunch']);
        $golive_time = strtotime($values['zcm_sitepage.golive']);
        $retired_time = strtotime($values['zcm_sitepage.retired']);
        $soft = $today > $softlaunch_time || $softlaunch_time == 0;
        $golive = $today > $golive_time || $golive_time == 0;
        $retired = $today > $retired_time && $retired_time != 0;
        $status = "<font color='red'>Not in use</font>";
        if ($soft)
            $status = "<font color='yellow'>Soft launched</font>";
        if ($golive)
            $status = "<font color='green'>Live</font>";
        if ($retired)
            $status = "<font color='red'>Retired</font>";
        return $status;
    }
    else
        return $display;
}
$dg->OnBeforeRenderCell = 'launchstatus';
$dg->AddUpDownColumn('disporder');
if ($templatecount > 1)
{
	$dg->AddEditor('template','Template:','lookup.zcm_template.id.name.name');
}
else
{
	$dg->AddConstant('template',$defaulttemplate);
}
//$dg->AddInput('linktext','Menu Text:',40,40);
$dg->AddEditor('linktext','Menu Text','inputf.40.40');
$dg->AddEditor('retire','Retire After:','datetime');
$dg->AddEditor('golive','Go Live:','datetime');
$dg->AddEditor('softlaunch','Soft Launch:','datetime');
$dg->AddLookup("acl", "Access Control List:", "zcm_acl", "id", "name", "name", true);
$dg->AddColumn('View', 'id', '<a href="template.php?pageid={0}">View</a>');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'Add New Page';
$dg->Render();

include('footer.php');
?>
