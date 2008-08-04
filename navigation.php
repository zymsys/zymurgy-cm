<?
/**
 * Needs plugin support, similar to custom table support.
 * Needs breadcrumbs
 * Should disable changing type from subnav to anything else.
 */
$adminlevel = 2;
if (array_key_exists('editkey',$_GET) | (array_key_exists('action', $_GET) && $_GET['action'] == 'insert'))
{
	$breadcrumbTrail = "<a href=\"navigation.php\">Navigation</a> &gt; " .
		"<a href=\"stcategory.php\">Categories</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "<a href=\"navigation.php\">Navigation</a> &gt; Categories";
}

include 'header.php';
include 'datagrid.php';

if (array_key_exists('action',$_GET) || array_key_exists('editkey',$_GET))
{
?>
<script type="text/javascript">
var lasturl = '';
var lastct = 0;
var urlContent;
var ctContent;
var smContent = 'n/a';
var ctOpts = [];

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

function setUrlContent() {
	urlContent = "<input id=\"zcmnav.navto\" type=\"text\" value=\""+
	lasturl.replace(/\"/g,'\\"')+
	"\" name=\"zcmnav.navto\" maxlength=\"200\" size=\"80\" onchange=\"updateContent()\">";
}

function setCtContent() {
	ctContent = "<select id=\"zcmnav.navto\" name=\"zcmnav.navto\" onchange=\"updateCtOpt()\">";
	for (var i = 0; i < ctOpts.length; i++) {
		ctContent += "<option value=\"" + ctOpts[i].id + "\"";
		if (lastct == ctOpts[i].id) {
			ctContent += " selected";
		}
		ctContent += ">" + ctOpts[i].name + "</option>";
	}
	ctContent += "</select>";
}

function updateCtOpt() {
	var elCtOpt = document.getElementById('zcmnav.navto');
	lastct = elCtOpt.value;
}

function updateContent() {
	var elUrl = document.getElementById('zcmnav.navto');
	lasturl = elUrl.value;
}

setUrlContent();
loadCtOpts();
setCtContent();

function setDestination(content) {
	var elDest = document.getElementById('cell-zcmnav.navto');
	elDest.innerHTML = content;
}
function yuiLoaded() {
	YAHOO.util.Event.onDOMReady(function() {
		var elUrlRadio = document.getElementById('zcmnav.navtype-URL');
		var elCtRadio = document.getElementById('zcmnav.navtype-Custom Table');
		var elSmRadio = document.getElementById('zcmnav.navtype-Sub-Menu');
		var elNavTo = document.getElementById('zcmnav.navto');
		if (!elUrlRadio.checked && !elCtRadio.checked && !elSmRadio.checked) {
			elUrlRadio.checked = true;
		}
		if (elUrlRadio.checked) {
			lasturl = elNavTo.value;
			setUrlContent();
			setDestination(urlContent);
		}
		if (elCtRadio.checked) {
			lastct = elNavTo.value;
			setCtContent();
			setDestination(ctContent);
		}
		if (elSmRadio.checked) {
			setDestination(smContent);
		}
		YAHOO.util.Event.addListener("zcmnav.navtype-URL", "click", function() {
			setUrlContent();
			setDestination(urlContent);
		}); 
		YAHOO.util.Event.addListener("zcmnav.navtype-Custom Table", "click", function() {
			setCtContent();
			setDestination(ctContent);
		}); 
		YAHOO.util.Event.addListener("zcmnav.navtype-Sub-Menu", "click", function() {
			setDestination(smContent);
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
$dg->AddRadioEditor('navtype','Navtype',array('URL'=>'URL','Custom Table'=>'Custom Table','Sub-Menu'=>'Sub-Menu'));
$dg->AddInput('navto','Destination:',200,80);
$dg->AddUpDownColumn('disporder');
$dg->AddEditColumn();
$dg->AddDeleteColumn();
$dg->insertlabel = 'New Navigation Item';
$dg->Render();

include('footer.php');
?>