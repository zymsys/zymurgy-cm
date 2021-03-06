<?
/**
 * Management screen for Page body content.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
require_once('cmo.php');
include 'sitepageutil.php';
$crumbs[] = "Page Contents";
$wikiArticleName = "Pages#Page_Contents";

include 'header.php';
include 'datagrid.php';

$p = 0 + $_GET['p'];
$inputspec = 'html.600.400';
$editcaption = "Content:";
$path = null;
$t = null;

/**
 * Make sure we've got all the tags for this page that the template requests.
 * Also pick up the inputspec if we're editing so we know how to edit this puppy.
 */
//$t = Zymurgy::$db->get("select template from zcm_sitepage where id=$p");
$sql = "SELECT `template`, `path` FROM `zcm_sitepage` INNER JOIN `zcm_template` ON `zcm_template`.`id` = `zcm_sitepage`.`template` WHERE `zcm_sitepage`.`id` = '".
	Zymurgy::$db->escape_string($p).
	"'";
// echo($sql);
$ri = Zymurgy::$db->query($sql)
	or die("Could not retrieve template information: ".Zymurgy::$db->error().", $sql");

if(Zymurgy::$db->num_rows($ri) > 0)
{
	$row = Zymurgy::$db->fetch_array($ri);

	$t = $row["template"];
	$path = $row["path"];
}

Zymurgy::$db->free_result($ri);

$currentcontent = array(); //Array of content relevent to the current template for the page.  Used in AddDataFilter below.

if ($t)
{
	$templatetags = array();
	$pagetags = array();
	$specs = array();
	$sql = "select id,tag,inputspec from zcm_templatetext where template=$t";
	$ri = Zymurgy::$db->run($sql);
	//echo("specs: ".Zymurgy::$db->num_rows($ri).": $sql<br>");
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
		$currentcontent[] = Zymurgy::$db->insert_id();
	}
}
else
{
	echo "This template hasn't yet been processed by ".Zymurgy::GetLocaleString("Common.ProductName").".  Please load it once in your browser, and then try again.";
	include('footer.php');
	exit;
}

/**
 * Update Event Handler. 
 * 
 * @param $values mixed The values of the row to be updated. Keys are in 
 * tablename.columname format.
 */
function OnUpdate($dsr)
{
	$sql = "UPDATE `zcm_sitepageseo` SET `mtime` = UNIX_TIMESTAMP() WHERE `zcm_sitepage` = '".
		Zymurgy::$db->escape_string($_GET["p"]).
		"'";
	Zymurgy::$db->query($sql)
		or die("Could not update SEO information for page: ".Zymurgy::$db->error().", $sql");
}

/**
 * Update Event Handler. 
 * 
 * @param $values mixed The values of the row to be updated. Keys are in 
 * tablename.columname format.
 */
function OnDelete($values)
{
	$t = Zymurgy::$db->get("select template from zcm_sitepage where id={$values['zcm_pagetext.sitepage']}");
	Zymurgy::$db->run("delete from zcm_templatetext where (template=$t) and (tag='".
		Zymurgy::$db->escape_string($values['zcm_pagetext.tag'])."')");
	return true; //Return false to override delete.
}

$ds = new DataSet('zcm_pagetext','id');
$ds->AddColumn('id',false);
$ds->AddColumns('sitepage','tag','body','acl');
$ds->AddDataFilter('sitepage',$p);
if ($currentcontent)
{
	$ds->AddDataFilter('id','('.implode(',',$currentcontent).')','in');
}
$ds->OnDelete = 'OnDelete';
$ds->OnUpdate = "OnUpdate";

$dg = new DataGrid($ds);
$dg->AddConstant('sitepage',$p);
$dg->AddColumn('Tag','tag');
if (Zymurgy::memberzcmauth()>=2)
{
	$dg->AddInput('tag','Tag:',35,35);
}
$dg->AddEditor('body',$editcaption,$inputspec);
$dg->AddLookup("acl", "Access Control List:", "zcm_acl", "id", "name", "name", true);
$dg->AddEditColumn();
if (Zymurgy::memberzcmauth()>=2)
{
	$dg->AddDeleteColumn();
}
$dg->insertlabel = '';
$dg->Render();

/*
ZK: Display the editor using the site's CSS file.
This has been commented out for now, until we have decided how we're going to
handle the editor's CSS - we may want to use a different stylesheet that better
reflects the main content area of the site, as opposed to the main sheet.

if($path && isset($_GET["editkey"]))
{
	$path = str_replace(".php", "style.php", $path);

	if(file_exists(Zymurgy::$root.$path))
	{
		echo("<script type=\"text/javascript\">\n");
		echo("function UsePageCSS() {\n");
		echo(" zcm_pagetext_bodyEditor.on(\"editorContentLoaded\", function() {\n");
		echo("  var link = this._getDoc().createElement('link');\n");
		echo("  link.setAttribute('rel', 'stylesheet');\n");
		echo("  link.setAttribute('type', 'text/css');\n");
		echo("  link.setAttribute('href', '$path');\n");
		echo("  this._getDoc().getElementsByTagName('head')[0].appendChild(link);\n");
		// echo("  alert('http://www.easilybuildawebsite.com$path');\n");
		echo("  this.render();\n");
		echo(" }, zcm_pagetext_bodyEditor, true);\n");
		echo("}\n");
		echo("YAHOO.util.Event.onDOMReady(UsePageCSS);\n");
		echo("</script>\n");
	}
}
*/

include('footer.php');
?>
