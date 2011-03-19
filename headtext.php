<?
/**
 * Management screen for the Simple Content SEO.
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
$editing = array_key_exists('editkey',$_GET);
$adding = (array_key_exists('action', $_GET) && $_GET['action'] == 'insert');

// Pages may only be added by the webmaster
if($adding)
{
	$adminlevel = 2;
}

if ($editing | $adding)
{
	$breadcrumbTrail = "<a href=\"headtext.php\">Search Engines</a> &gt; Header Text";
	$wikiArticleName = "Simple_Content_SEO#Editing_SEO_Settings";
}
else 
{
	$breadcrumbTrail = "Search Engines";	
	$wikiArticleName = "Simple_Content_SEO";
}

include('header.php');
include('datagrid.php');

$ds = new DataSet('zcm_meta','id');
$ds->AddColumn('id',false);
$ds->AddColumn('document',true);
$ds->AddColumn('description',true);
$ds->AddColumn('keywords',true);
$ds->AddColumn('title',true);
$ds->AddColumn('mtime',false);
$ds->AddColumn('changefreq',true);
$ds->AddColumn('priority',true);

$dg = new DataGrid($ds);
$dg->editlabel = 'Header Text';
$dg->AddConstant('mtime',time());
$dg->AddColumn('Document','document');
$dg->AddColumn('','id','<a href="pagetext.php?page={0}">Page Text</a>');
$dg->AddColumn('','title','');
$dg->AddColumn('','description','');
$dg->AddColumn('','keywords','');
$dg->AddColumn('','changefreq','');
$dg->AddColumn('','priority','');
$dg->AddEditColumn();
if (Zymurgy::memberzcmauth()<2)
{
	$dg->insertlabel = '';
}
else 
{
	$dg->AddDeleteColumn();
	$dg->AddInput('document','Document File Name:',80,35);
}
$dg->AddInput('title','Page Title:',80,40);
$dg->AddTextArea('description','Description:');
$dg->AddTextArea('keywords','Keywords:');
$dg->AddDropListEditor('changefreq','Change Frequency:<br><i>Estimating how frequently the content<br>on this page will be updated helps Google<br>to index your site more effectively.</i><br>&nbsp;',array(
	'always'=>'Always',
	'hourly'=>'Hourly',
	'daily'=>'Daily',
	'weekly'=>'Weekly',
	'monthly'=>'Monthly',
	'yearly'=>'Yearly',
	'never'=>'Never'
	));
$toten = array();
for ($i = 0; $i <= 10; $i++) $toten[$i] = $i;
$dg->AddDropListEditor('priority','Page Priority:<br><i>How important is this page within your<br>site as a number from 0 to 10, where 10<br>is the most important.  Google will<br>use this value to help choose which<br>pages from your site to show a user.</i>',$toten);
$dg->Render();
include('footer.php');
?>
