<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Zymurgy:CM - Content Management</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<base href="http://<?=$_SERVER['HTTP_HOST']?>/zymurgy/">
<? 
if (isset($includeNav) && $includeNav) 
{
echo Zymurgy::YUI("fonts/fonts-min.css");
echo Zymurgy::YUI("menu/assets/skins/sam/menu.css");
echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
echo Zymurgy::YUI("container/container_core-min.js");
echo Zymurgy::YUI("menu/menu-min.js");
echo '<script type="text/javascript">
YAHOO.util.Event.onContentReady("zcmnavContent", function () {
	var oMenu = new YAHOO.widget.Menu("zcmnavContent", { 
											position: "static", 
											hidedelay:  750, 
											lazyload: true });
	oMenu.render();            
});
function resizenav() {
	var ht = YAHOO.util.Dom.getViewportHeight();
	YAHOO.util.Dom.setStyle("zcmnavContent","height",(ht-100)+"px");
}
YAHOO.util.Event.onContentReady("zcmnavContentNav", function () {
	YAHOO.util.Dom.setStyle("zcmnavContentNav","display","block");
	resizenav();
});
</script>';
} 
?>
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
	//overflow: auto;
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