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
		$nav = Zymurgy::$db->get("select * from zcmnav where id=$nid");
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
	$ek = 0 + $_GET['editkey'];
	$nav = Zymurgy::$db->get("select * from zcmnav where id=$ek");
	$navname = $tbl['navname'];
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
var urlContent;
var ctContent;
var piContent;
var smContent = 'n/a';
var ctOpts = [];
var piOpts = [];

function loadCtOpts() {
	var n = 0;
<?
$sql = "select * from customtable where detailfor=0 and trim(navname)<>'' order by disporder";
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
$sql = "select id,title from plugin";
$ri = mysql_query($sql) or die("Can't load plugin items ($sql): ".mysql_error());
while (($row = mysql_fetch_array($ri))!==false)
{
	echo "\tpiOpts[n++] = {id: {$row['id']}, name: \"".
		addslashes($row['title'])."\"};\r\n";
}
mysql_free_result($ri);
?>
}

function setUrlContent() {
	urlContent = "<input id=\"zcmnav.navto\" type=\"text\" value=\""+
	lasturl.replace(/\"/g,'\\"')+
	"\" name=\"zcmnav.navto\" maxlength=\"200\" size=\"80\" onchange=\"updateContent()\">";
}

function setDropListContent(onchange,opts,last) {
	var dlHtml = "<select id=\"zcmnav.navto\" name=\"zcmnav.navto\" onchange=\""+onchange+"()\">";
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

function setNavNameIfBlank(newname,opts) {
	var el = document.getElementById('zcmnav.navname');
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
	var elCtOpt = document.getElementById('zcmnav.navto');
	lastct = elCtOpt.value;
	setNavNameIfBlank(lastct,ctOpts);
}

function updatePiOpt() {
	var el = document.getElementById('zcmnav.navto');
	lastpi = el.value;
	setNavNameIfBlank(lastpi,piOpts);
}

function updateContent() {
	var elUrl = document.getElementById('zcmnav.navto');
	lasturl = elUrl.value;
}

loadCtOpts();
loadPiOpts();

function setDestination(content) {
	var elDest = document.getElementById('cell-zcmnav.navto');
	elDest.innerHTML = content;
}
function updateNoSubMenuChange() {
	var elNavType = document.getElementById('zcmnav.navtype');
	if (elNavType.value=='Sub-Menu')
	{
		YAHOO.util.Dom.setStyle('noSubMenuChange','display','block');
	} else {
		YAHOO.util.Dom.setStyle('noSubMenuChange','display','none');
	}
}
function yuiLoaded() {
	YAHOO.util.Event.onDOMReady(function() {
		var elNavType = document.getElementById('zcmnav.navtype');
		var elNavTo = document.getElementById('zcmnav.navto');
		var elNavTypeCell = document.getElementById('cell-zcmnav.navtype');
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
			case 'Sub-Menu':
			default:
				setDestination(smContent);
				elNavType.disabled = true;
				break;
		}
		YAHOO.util.Event.addListener("zcmnav.navtype", "change", function() {
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
				case 'Sub-Menu':
				default:
					setDestination(smContent);
					break;
			}
		}); 
	});
}
</script>
<?
}

$p = array_key_exists('p',$_GET) ? 0 + $_GET['p'] : 0;

function OnBeforeRenderCell($column,$values,$display)
{
	if ($column=='zcmnav.id')
	{
		if ($values['zcmnav.navtype']=='Sub-Menu')
		{
			return "<a href=\"navigation.php?p={$values['zcmnav.id']}\">Sub-Menu Items</a>";
		}
		else 
		{
			return ''; //Display nothing for other types.
		}
	}
	return $display;
}

$ds = new DataSet('zcmnav','id');
$ds->AddColumns('id','disporder','parent','navname','navtype','navto');
$ds->AddDataFilter('parent',$p);

$dg = new DataGrid($ds);
$dg->OnBeforeRenderCell = 'OnBeforeRenderCell';
$dg->AddConstant('parent',$p);
$dg->AddColumn('Navigation Name','navname');
$dg->AddColumn('Type','navtype');
$dg->AddColumn('','id','<a href="navigation.php?p={0}">Sub-navigation</a>');
$dg->AddInput('navname','Navigation Name:',60,60);
$dg->AddDropListEditor('navtype','Navtype',array('URL'=>'URL',
	'Custom Table'=>'Custom Table',
	'Plugin'=>'Plugin',
	'Sub-Menu'=>'Sub-Menu'));
$dg->AddInput('navto','Destination:',200,80);
$dg->AddUpDownColumn('disporder');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'New Navigation Item';
$dg->Render();

include('footer.php');
?>