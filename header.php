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
<script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/yuiloader/yuiloader-beta-min.js"></script>
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
	var el = YAHOO.util.Dom.get('zcmnavContent');
	var htstyle = (ht-100)+'px';
	el.style.height = htstyle;
	//YAHOO.util.Dom.setStyle(el,'height',ht-100);
	ht++;
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
	height: 600px;
}
.yui-skin-sam #zcmnavContent .yuimenu ul {
	padding: 0px 0px;
}
.yui-skin-sam #zcmnavContent .yuimenu .bd {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? Zymurgy::$config['navbackground'] : '#9999cb' ?>;
}
.yuimenuitem {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? Zymurgy::$config['navbackground'] : '#9999cb' ?>;
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
	
	$sql = "select * from zcmnav where parent=$parent order by disporder";
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
	<div class="bd" style="border-style: none">
    	<ul class="first-of-type" style="padding: 0px">
    		<? renderZCMNav(0); ?>
        	<!--li class="yuimenuitem first-of-type"><a class="yuimenuitemlabel" href="#content" style="text-align:right">Content</a>
            	<div id="content" class="yuimenu">
                	<div class="bd">
                    	<ul>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="sitetext.php">Simple Content</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="headtext.php">SEO</a></li>
                        </ul>
                    </div>
                </div>
            </li>
            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#plugins" style="text-align:right">Plugins</a>
            	<div id="plugins" class="yuimenu">
                	<div class="bd">
                    	<ul>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="pluginadmin.php?pid=1">Forms</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="pluginadmin.php?pid=3">Galleries</a></li>
                        </ul>
                    </div>
                </div>
            </li>
            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#admin" style="text-align:right">Admin</a>
            	<div id="admin" class="yuimenu">
                	<div class="bd">
                    	<ul>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="usermng.php">User Management</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="useractivity.php">User Activity</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="helpeditor.php">Help Editor</a></li>
                        </ul>
                    </div>
                </div>
            </li>
            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="#webmaster" style="text-align:right">Webmaster</a>
            	<div id="webmaster" class="yuimenu">
                	<div class="bd">
                    	<ul>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="navigation.php">Navigation</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="configconfig.php">Master Config</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="plugin.php">Plugin Management</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="customtable.php">Custom Tables</a></li>
                            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="mkcustom.php">Custom Code</a></li>
                        </ul>
                    </div>
                </div>
            </li>
            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="profile.php" style="text-align:right">Profile</a></li>
            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="help.php" style="text-align:right">Help</a></li>
            <li class="yuimenuitem"><a class="yuimenuitemlabel" href="logout.php" style="text-align:right">Logout</a></li-->
        </ul>
    </div>
</div>

<div class="ZymurgyNavigation" style="display:none">
	<div class="ZymurgyLoginName">
		<?= $zauth->authinfo['fullname'] ?>
	</div>
	<div class="ZymurgyMenu">
		<? if (count(Zymurgy::$userconfig)>0) { ?>
		<a href="configuration.php" class="nav">Site Config</a><br>
		<? } ?>
		<a href="sitetext.php" class="nav">General Content</a><br>
		<a href="headtext.php" class="nav">Search Engines</a><br>
		<? 
		// Show custom table items
		$sql = "select * from customtable where detailfor=0 order by disporder";
		$ri = mysql_query($sql) or die("Can't load custom table items ($sql): ".mysql_error());
		while (($row = mysql_fetch_array($ri))!==false)
		{
			if (!empty($row['navname']))
			{
				echo "<a href=\"customedit.php?t={$row['id']}\">{$row['navname']}</a><br>";
			}
		}
		mysql_free_result($ri);
		// Show custom menu items
		if (file_exists("$ZymurgyRoot/zymurgy/custom/menu.php"))
			require_once("$ZymurgyRoot/zymurgy/custom/menu.php");
		// Show plug-in menu items
		require_once("$ZymurgyRoot/zymurgy/PluginBase.php");
		$sql = "select * from plugin where enabled=1";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri)
			die("Unable to check for plugins:  ".Zymurgy::$db->error()."<br>$sql");
		while (($row = Zymurgy::$db->fetch_array($ri)) !== false)
		{
			//echo "[Creating {$row['name']}]";
			require_once("$ZymurgyRoot/zymurgy/plugins/{$row['name']}.php");
			$pf = "{$row['name']}Factory";
			$pi = $pf();
			$menutext = $pi->AdminMenuText();
			if ($menutext != '') echo "<a class=\"nav\" href=\"pluginadmin.php?pid={$row['id']}\">$menutext</a><br>";
			//echo "[Done create]";
		}	
		if ($zauth->authinfo['admin']>0) { ?>
		<a href="usermng.php" class="nav">User Management</a><br>
		<? } ?>
		<? if ($zauth->authinfo['admin']>1) { ?>
		<a href="helpeditor.php" class="nav">Help Editor</a><br>
		<a href="configconfig.php" class="nav">Master Config</a><br>
		<a href="plugin.php" class="nav">Plugins</a><br>
		<a href="customtable.php" class="nav">Custom Tables</a><br>
		<a href="mkcustom.php" class="nav">Custom Code</a><br>
		<? } ?>
		<a href="profile.php" class="nav">My Profile</a><br>
		<a href="help.php" target="_blank" class="nav">Help</a><br>
		<a href="logout.php" class="nav">Logout</a>
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
