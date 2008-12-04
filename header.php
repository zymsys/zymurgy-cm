<?
function userErrorHandler ($errno, $errmsg, $filename, $linenum,  $vars) 
{
	$time=date("d M Y H:i:s"); 
	// Get the error type from the error number 
	$errortype = array (1    => "Error",
	                 2    => "Warning",
	                 4    => "Parsing Error",
	                 8    => "Notice",
	                 16   => "Core Error",
	                 32   => "Core Warning",
	                 64   => "Compile Error",
	                 128  => "Compile Warning",
	                 256  => "User Error",
	                 512  => "User Warning",
	                 1024 => "User Notice",
	                 2048 => "Run Time Notice",
	                 4096 => "Catchable Fatal Error");
	$errlevel=$errortype[$errno];
	if (empty($errlevel)) $errlevel = $errno;
	
	echo "<div>[$errlevel: $errmsg in $filename on line $linenum]</div>\r\n"; 
}

if (array_key_exists('showerrors',$_GET))
{
	error_reporting(0); 
	$old_error_handler = set_error_handler("userErrorHandler");
}

if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
	$ZymurgyRoot = $_SERVER["APPL_PHYSICAL_PATH"];
else 
	$ZymurgyRoot = $_SERVER['DOCUMENT_ROOT'];
require_once("$ZymurgyRoot/zymurgy/ZymurgyAuth.php");
$zauth = new ZymurgyAuth();
$zauth->Authenticate("/zymurgy/login.php");
$zp = explode(',',$zauth->authinfo['extra']);
if (count($zp)<6)
{
	echo $zauth->authinfo['extra']; exit;
	header("Location: logout.php");
	exit;
}
$zauth->authinfo['email'] = $zp[1];
$zauth->authinfo['fullname'] = $zp[2];
$zauth->authinfo['admin'] = $zp[3];
$zauth->authinfo['id'] = $zp[4];
$zauth->authinfo['eula'] = $zp[5];
if ((isset($adminlevel)) && ($zauth->authinfo['admin']<$adminlevel))
{
	header("Location: login.php");
	exit;
}
if ($zauth->authinfo['eula'] != 1)
{
	header("Location: eula.php");
	exit;
}
if (!array_key_exists("zymurgy",$_COOKIE))
{
	setcookie("zymurgy",$zauth->authinfo['admin'],null,'/');
}
require_once("$ZymurgyRoot/zymurgy/cmo.php");
ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Zymurgy:CM - Content Management</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<base href="http://<?=$_SERVER['HTTP_HOST']?>/zymurgy/">
<? echo Zymurgy::YUI("yuiloader/yuiloader-min.js"); ?>
<script type="text/javascript">
var loader = new YAHOO.util.YUILoader({
    require: ["event","menu"],
    loadOptional: true,
    onSuccess: function() {
		YAHOO.util.Event.onContentReady("zcmnavContent", function () {
			var oMenu = new YAHOO.widget.Menu("zcmnavContent", { 
													position: "static", 
													hidedelay:  750, 
													lazyload: true });
			oMenu.render();            
		});
		YAHOO.util.Dom.setStyle('zcmnavContentNav','display','block');
		resizenav();
		if (window.yuiLoaded)
		{
			yuiLoaded();
		}
    }
});
loader.insert();
function resizenav() {
	var ht = YAHOO.util.Dom.getViewportHeight();
	YAHOO.util.Dom.setStyle('zcmnavContent','height',(ht-100)+'px');
}
</script>
<style type="text/css">
<!--
body {
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size:small;
}
.ZymurgyHeader {
	background-color: <?= Zymurgy::$config['headerbackground'] ?>;
	color: <?= Zymurgy::$config['headercolor'] ?>;
	height:85px;
	position: relative;
	width: 100%;
}
.ZymurgyHeader .ZymurgyVendor {
	display:table-cell;
	vertical-align:middle;
	float: left;
	padding: 0px;
	margin: 0px;
}
.ZymurgyHeader .ZymurgyLogo {
	float: left;
	font-size: 40px;
	margin-top:35px;
	margin-left:10px;
	padding: 0px;
}
.ZymurgyHeader .ZymurgyVendor img {
	padding: 0px;
	margin: 0px;
}
.ZymurgyHeader .ZymurgyClient {
	float: right;
}
.ZymurgyNavigation {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? Zymurgy::$config['navbackground'] : '#9999cb' ?>;
	float: left;
	padding: 10px;
	margin-right: 3px;
	height: 100%;
}
.ZymurgyLoginName {
	font-weight: bold;
	text-align: center;
	margin-bottom:10px;
}
.ZymurgyNavigation .ZymurgyMenu {
	text-align: right;
}
.ZymurgyNavigation .ZymurgyMenu a, .ZymurgyNavigation .ZymurgyMenu a:link,.ZymurgyNavigation .ZymurgyMenu a:visited,.ZymurgyNavigation .ZymurgyMenu a:active {
	color: <?= (array_key_exists('navcolor',Zymurgy::$config)) ? Zymurgy::$config['navcolor'] : '#000000' ?>;
	text-decoration: none;
	line-height: 2;
}
.ZymurgyNavigation .ZymurgyMenu a:hover {
	text-decoration: overline underline;
}
div.ZymurgyBreadcrumbs
{
	border-bottom: 1px solid #666698;
	padding: 3px;
}
.ZymurgyClientArea {
	margin: 10px;
	overflow: auto;
}
#zcmnavContent {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? Zymurgy::$config['navbackground'] : '#9999cb' ?>;
	width: 138px;
}
#zcmnavContentNav {
	display:none;
}
.yui-skin-sam #zcmnavContent .yuimenu ul {
	padding: 0px 0px;
}
.yui-skin-sam #zcmnavContent .yuimenu .bd {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? Zymurgy::$config['navbackground'] : '#9999cb' ?>;
}
.yui-skin-sam #zcmnavContent .yuimenuitem-selected {
	background-color: <?= (array_key_exists('navselected',Zymurgy::$config)) ? Zymurgy::$config['navselected'] : '#B3D4FF' ?>;
}
.yuimenuitem {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? Zymurgy::$config['navbackground'] : '#9999cb' ?>;
}
#DraftTool {
	background-color: #CCCCCC;
	width: 90%;
	border: thin solid #333333;	
	padding: 5px;
}
-->
</style>
<style type"text/css" media="print">
<!--
.ZymurgyHeader, .ZymurgyNavigation, .DoNotPrint {
	display: none;
}
-->
</style>
</head>
<body id="zcmbody" class="yui-skin-sam" onresize="resizenav()">
<div class="ZymurgyHeader">
	<div class="ZymurgyVendor">
		<? if ((isset(Zymurgy::$config['vendorlogo'])) && (Zymurgy::$config['vendorlogo'] != '')) echo "<a target=\"_blank\" href=\"".Zymurgy::$config['vendorlink']."\"><img border=\"0\" src=\"".Zymurgy::$config['vendorlogo']."\" alt=\"".htmlspecialchars(Zymurgy::$config['vendorname'])."\"></a>"; ?>
	</div>
	<div class="ZymurgyLogo" title="Content Authoring and Search Engine Optimization">
		Zymurgy:CM
	</div>
	<div class="ZymurgyClient">
		<? if ((isset(Zymurgy::$config['clientlogo'])) && (Zymurgy::$config['clientlogo'] != '')) echo "<a target=\"_blank\" href=\"http://".Zymurgy::$config['sitehome']."/\"><img border=\"0\" src=\"".Zymurgy::$config['clientlogo']."\" alt=\"".htmlspecialchars(Zymurgy::$config['defaulttitle'])."\"></a>"; ?>
	</div>
</div>
<?
function renderZCMNav($parent)
{
	global $donefirstzcmnav;
	
	$sql = "select * from zcm_nav where parent=$parent order by disporder";
	$ri = Zymurgy::$db->run($sql);
	$navs = array();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$navs[] = $row;
	}
	mysql_free_result($ri);
	if (count($navs)==0) return;
	foreach($navs as $nav)
	{
		echo "<li class=\"yuimenuitem";
		if (!isset($donefirstzcmnav))
		{
			$donefirstzcmnav = true;
			echo " first-of-type";
		}
		echo "\"><a class=\"yuimenuitemlabel\" href=\"";
		switch ($nav['navtype'])
		{
			case 'Sub-Menu':
				$href = "#zcmnav{$nav['id']}";
				break;
			case 'Custom Table':
				$href = "customedit.php?t={$nav['navto']}";
				break;
			case 'Plugin':
				$href = "pluginadmin.php?pid={$nav['navto']}";
				break;
			case 'URL':
				$href = $nav['navto'];
				break;
		}
		echo $href."\"";
		if ($parent==0)
		{
			echo " style=\"text-align:right\"";
		}
		echo ">{$nav['navname']}</a>";
		if ($nav['navtype']=='Sub-Menu')
		{
			echo "<div id=\"".substr($href,1)."\" class=\"yuimenu\"><div class=\"bd\"><ul>";
			renderZCMNav($nav['id']);
			echo "</ul></div></div>";
		}
		echo "</li>";
	}
}
?>
<div id="zcmnavContent" class="yuimenu" style="float:left; margin-right: 5px">
	<div class="ZymurgyLoginName">
		<?= $zauth->authinfo['fullname'] ?>
	</div>
	<div id="zcmnavContentNav" class="bd" style="border-style: none">
    	<ul class="first-of-type" style="padding: 0px">
    		<? renderZCMNav(0); ?>
        </ul>
    </div>
</div>

<?php 
if (isset($crumbs))
{
	//Build $breadcrumbTrail
	$crumbbits = array();
	$crumblinks = array_keys($crumbs);
	//print_r($crumblinks); exit;
	while (count($crumbs) > 0)
	{
		$crumbname = array_shift($crumbs);
		$crumblink = array_shift($crumblinks);
		$bittxt = '';
		if (count($crumbs) > 0)
		{
			$bittxt = "<a href=\"$crumblink\">";
		}
		$bittxt .= $crumbname;
		if (count($crumbs) > 0)
		{
			$bittxt .= "</a>";
		}
		$crumbbits[] = $bittxt;
	}
	if (count($crumbbits) > 0)
	{
		$breadcrumbTrail = implode(" &gt; ",$crumbbits);
	}
}
if(isset($breadcrumbTrail)) { 
?>
	<div id="breadcrumbTrail" class="ZymurgyBreadcrumbs">
		<?= $breadcrumbTrail ?>
	</div>
<?php } ?>

<div class="ZymurgyClientArea">
