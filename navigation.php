<?
/**
 * Should disable changing type from subnav to anything else.
 */
$adminlevel = 2;

require_once('cmo.php');

function navparents($nid)
{
	$r = array();
	do
	{
		$nav = Zymurgy::$db->get("select * from zcm_nav where id=$nid");
		$r[$nav['id']] = $nav['navname'];
		$nid = $nav['parent'];
	}
	while ($nid > 0);
	return $r;
}

function navcrumbs($nid)
{
	global $crumbs;

	$parents = navparents($nid);
	$tids = array_keys($parents);
	while (count($parents) > 0)
	{
		$tid = array_pop($tids);
		$crumb = array_pop($parents).' Sub-Menu';
		$crumbs["navigation.php?p=$tid"] = htmlspecialchars($crumb);
	}
}

$detailfor = array_key_exists('p',$_GET) ? (0 + $_GET['p']) : 0;
$crumbs = array("navigation.php"=>"Navigation");
if ($detailfor > 0)
{
	navcrumbs($detailfor);
}
if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$ek = isset($_GET["editkey"]) ? 0 + $_GET['editkey'] : 0;
	$nav = Zymurgy::$db->get("select * from zcm_nav where id=$ek");
	$navname = isset($tbl['navname']) ? $tbl['navname'] : "";
	$crumbs[''] = "Edit $navname";
}

include 'header.php';
include 'datagrid.php';

if (array_key_exists('action',$_GET) || array_key_exists('editkey',$_GET))
{
?>
<script type="text/javascript">
var lasturl = '';
var lastct = 0;
var lastpi = 0;
var lastFeature = 0;
var urlContent;
var ctContent;
var piContent;
var featureContent;
var smContent = 'n/a';
var ctOpts = [];
var piOpts = [];
var featureOptions = [];

function loadCtOpts() {
	var n = 0;
<?
$sql = "select * from zcm_customtable where detailfor=0 and trim(navname)<>'' order by disporder";
$ri = mysql_query($sql) or die("Can't load custom table items ($sql): ".mysql_error());
while (($row = mysql_fetch_array($ri))!==false)
{
	echo "\tctOpts[n++] = {id: {$row['id']}, name: \"".
		addslashes($row['navname'])."\"};\r\n";
}
mysql_free_result($ri);
?>
}

function loadPiOpts() {
	var n = 0;
<?
$sql = "select id,title from zcm_plugin";
$ri = mysql_query($sql) or die("Can't load plugin items ($sql): ".mysql_error());
while (($row = mysql_fetch_array($ri))!==false)
{
	echo "\tpiOpts[n++] = {id: {$row['id']}, name: \"".
		addslashes($row['title'])."\"};\r\n";
}
mysql_free_result($ri);
?>
}

function loadFeatureOptions()
{
	var n = 0;
<?
	$sql = "SELECT `id`, `label` FROM `zcm_features` ORDER BY `disporder`";
	$ri = Zymurgy::$db->query($sql)
		or die("Could not retrieve list of Zymurgy:CM features: ".Zymurgy::$db->error().", $sql");

	echo("// ".
		Zymurgy::$db->num_rows($ri).
		" ($sql) \r\n");

	while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
	{
		echo("\tfeatureOptions[n++] = { id: ".
			$row["id"].
			", name: \"".
			addslashes($row["label"]).
			"\" };\r\n");
	}

	Zymurgy::$db->free_result($ri);
?>
}

function setUrlContent() {
	urlContent = "<input id=\"zcm_nav.navto\" type=\"text\" value=\""+
	lasturl.replace(/\"/g,'\\"')+
	"\" name=\"zcm_nav.navto\" maxlength=\"200\" size=\"80\" onchange=\"updateContent()\">";
}

function setDropListContent(onchange,opts,last) {
	var dlHtml = "<select id=\"zcm_nav.navto\" name=\"zcm_nav.navto\" onchange=\""+onchange+"()\">";
	dlHtml += "<option value=\"0\">Choose...</option>";
	for (var i = 0; i < opts.length; i++) {
		dlHtml += "<option value=\"" + opts[i].id + "\"";
		if (last == opts[i].id) {
			dlHtml += " selected";
		}
		dlHtml += ">" + opts[i].name + "</option>";
	}
	dlHtml += "</select>";
	return dlHtml;
}

function setCtContent() {
	ctContent = setDropListContent('updateCtOpt',ctOpts,lastct);
}

function setPiContent() {
	piContent = setDropListContent('updatePiOpt',piOpts,lastpi);
}

function setFeatureContent() {
	featureContent = setDropListContent("updateFeatureOptions", featureOptions, lastFeature);
}

function setNavNameIfBlank(newname,opts) {
	var el = document.getElementById('zcm_nav.navname');
	if (el.value=='') {
		for (var n = 0; n < opts.length; n++) {
			if (opts[n].id==newname) {
				el.value = opts[n].name;
				break;
			}
		}
	}
}

function updateCtOpt() {
	var elCtOpt = document.getElementById('zcm_nav.navto');
	lastct = elCtOpt.value;
	setNavNameIfBlank(lastct,ctOpts);
}

function updatePiOpt() {
	var el = document.getElementById('zcm_nav.navto');
	lastpi = el.value;
	setNavNameIfBlank(lastpi,piOpts);
}

function updateFeatureOptions()
{
	var el = document.getElementById("zcm_nav.navto");
	lastFeature = el.value;
	setNavNameIfBlank(lastFeature, featureOptions);
}

function updateContent() {
	var elUrl = document.getElementById('zcm_nav.navto');
	lasturl = elUrl.value;
}

loadCtOpts();
loadPiOpts();
loadFeatureOptions();

function setDestination(content) {
	var elDest = document.getElementById('cell-zcm_nav.navto');
	elDest.innerHTML = content;
}
function updateNoSubMenuChange() {
	var elNavType = document.getElementById('zcm_nav.navtype');
	if (elNavType.value=='Sub-Menu')
	{
		YAHOO.util.Dom.setStyle('noSubMenuChange','display','block');
	} else {
		YAHOO.util.Dom.setStyle('noSubMenuChange','display','none');
	}
}

YAHOO.util.Event.onDOMReady(function() {
	var elNavType = document.getElementById('zcm_nav.navtype');
	var elNavTo = document.getElementById('zcm_nav.navto');
	var elNavTypeCell = document.getElementById('cell-zcm_nav.navtype');
	var newdiv = document.createElement('div');
	newdiv.setAttribute('id','noSubMenuChange');
	newdiv.innerHTML="Sub-Menu's cannot be changed to another type.";
	elNavTypeCell.appendChild(newdiv);
	updateNoSubMenuChange();
	switch(elNavType.value) {
		case 'URL':
			lasturl = elNavTo.value;
			setUrlContent();
			setDestination(urlContent);
			break;
		case 'Custom Table':
			lastct = elNavTo.value;
			setCtContent();
			setDestination(ctContent);
			break;
		case 'Plugin':
			lastpi = elNavTo.value;
			setPiContent();
			setDestination(piContent);
			break;
		case 'Zymurgy:CM Feature':
			lastFeature = elLavTo.value;
			setFeatureContent();
			setDestination(featureContent);
			break;
		case 'Sub-Menu':
		default:
			setDestination(smContent);
			elNavType.disabled = true;
			break;
	}
	YAHOO.util.Event.addListener("zcm_nav.navtype", "change", function() {
		switch(elNavType.value) {
			case 'URL':
				setUrlContent();
				setDestination(urlContent);
				break;
			case 'Custom Table':
				setCtContent();
				setDestination(ctContent);
				break;
			case 'Plugin':
				setPiContent();
				setDestination(piContent);
				break;
			case 'Zymurgy:CM Feature':
				setFeatureContent();
				setDestination(featureContent);
				break;
			case 'Sub-Menu':
			default:
				setDestination(smContent);
				break;
		}
	});
});
</script>
<?
}

$p = array_key_exists('p',$_GET) ? 0 + $_GET['p'] : 0;

function OnBeforeRenderCell($column,$values,$display)
{
	if ($column=='zcm_nav.id')
	{
		if ($values['zcm_nav.navtype']=='Sub-Menu')
		{
			return "<a href=\"navigation.php?p={$values['zcm_nav.id']}\">Sub-Menu Items</a>";
		}
		else
		{
			return ''; //Display nothing for other types.
		}
	}
	return $display;
}

$ds = new DataSet('zcm_nav','id');
$ds->AddColumns('id','disporder','parent','navname','navtype','navto','authlevel');
$ds->AddDataFilter('parent',$p);

$dg = new DataGrid($ds);
$dg->OnBeforeRenderCell = 'OnBeforeRenderCell';

$dg->AddConstant('parent',$p);

$dg->AddColumn('Navigation Name','navname');
$dg->AddColumn('Type','navtype');
$dg->AddColumn(
	"Visible To",
	"authlevel");
$dg->AddColumn('','id','<a href="navigation.php?p={0}">Sub-navigation</a>');
$dg->AddUpDownColumn('disporder');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'New Navigation Item';

$dg->AddInput('navname','Navigation Name:',60,60);
$dg->AddDropListEditor('navtype','Navigation Type:',array('URL'=>'URL',
	"Zymurgy:CM Feature"=>"Zymurgy:CM Feature",
	'Custom Table'=>'Custom Table',
	'Plugin'=>'Plugin',
	'Sub-Menu'=>'Sub-Menu'));
$dg->AddInput('navto','Destination:',200,80);
$dg->AddDropListEditor(
	"authlevel",
	"Visible To:",
	array(
		"0" => "User",
		"1" => "Administrator",
		"2" => "Webmaster"
	));

$dg->Render();

include('footer.php');
?>