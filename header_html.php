<?php 
/**
 * Used by {@link header.php} to open the html.
 * @package Zymurgy
 * @subpackage backend-modules
 * @todo merge this into hearer.php?
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Zymurgy:CM - Content Management</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<base href="http://<?=$_SERVER['HTTP_HOST']?>/zymurgy/">
<?php
if (isset($includeNav) && $includeNav)
{
echo Zymurgy::YUI("fonts/fonts-min.css");
echo Zymurgy::YUI("menu/assets/skins/sam/menu.css");
echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
echo Zymurgy::YUI("container/container_core-min.js");
echo Zymurgy::YUI("menu/menu-min.js");
?>
<script type="text/javascript">
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
</script>
<?php } //endif ?>
<style type="text/css">
<!--
body {
	font-family: <?= isset(Zymurgy::$config["font"]) ? Zymurgy::$config["font"] : "Verdana, Arial, Helvetica, sans-serif" ?>;
	font-size:small;
}
.ZymurgyHeader {
	background-color: <?= strpos("   ".Zymurgy::$config['headerbackground'], "#") > 0 ? Zymurgy::$config['headerbackground'] : "#".Zymurgy::$config['headerbackground'] ?>;
	color: <?= strpos("   ".Zymurgy::$config['headercolor'], "#") > 0 ? Zymurgy::$config['headercolor'] : "#".Zymurgy::$config['headercolor'] ?>;
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
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? strpos("   ".Zymurgy::$config['navbackground'], "#") > 0 ? Zymurgy::$config['navbackground'] : "#".Zymurgy::$config['navbackground'] : '#9999cb' ?>;
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
	color: <?= (array_key_exists('navcolor',Zymurgy::$config)) ? strpos("   ".Zymurgy::$config['navcolor'], "#") > 0 ? Zymurgy::$config['navcolor'] : "#".Zymurgy::$config['navcolor'] : '#000000' ?>;
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
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? strpos("   ".Zymurgy::$config['navbackground'], "#") > 0 ? Zymurgy::$config['navbackground'] : "#".Zymurgy::$config['navbackground'] : '#9999cb' ?>;
	width: 138px;
}
#zcmnavContentNav {
	display:none;
}
.yui-skin-sam #zcmnavContent .yuimenu ul {
	padding: 0px 0px;
}
.yui-skin-sam #zcmnavContent .yuimenu .bd {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? strpos("   ".Zymurgy::$config['navbackground'], "#") > 0 ? Zymurgy::$config['navbackground'] : "#".Zymurgy::$config['navbackground'] : '#9999cb' ?>;
}
.yui-skin-sam #zcmnavContent .yuimenuitem-selected {
	background-color: <?= (array_key_exists('navselected',Zymurgy::$config)) ? strpos("   ".Zymurgy::$config['navbackground'], "#") > 0 ? Zymurgy::$config['navselected'] : "#".Zymurgy::$config['navselected'] : '#B3D4FF' ?>;
}
.yuimenuitem {
	background-color: <?= (array_key_exists('navbackground',Zymurgy::$config)) ? strpos("   ".Zymurgy::$config['navbackground'], "#") > 0 ? Zymurgy::$config['navbackground'] : "#".Zymurgy::$config['navbackground'] : '#9999cb' ?>;
}
#DraftTool {
	background-color: #CCCCCC;
	width: 90%;
	border: thin solid #333333;
	padding: 5px;
}

.yui-toolbar-mediafile span.yui-toolbar-icon, .yui-toolbar-zcmimage span.yui-toolbar-icon {
	background-image: url(/zymurgy/images/zcmLibrary.gif) !important;
	background-position: 2px 1px !important;
	left: 5px !important;
}

.yui-toolbar-editcode span.yui-toolbar-icon,
.yui-toolbar-editcode-selected span.yui-toolbar-icon
{
	background-image: url(http://developer.yahoo.com/yui/examples/editor/assets/html_editor.gif) !important;
	background-position: 0 1px !important;
	left: 5px !important;
}

.editor-hidden
{
	visibility: hidden;
	top: -9999px;
	left: -9999px;
	position: absolute;
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
		<?php  if ((isset(Zymurgy::$config['vendorlogo'])) && (Zymurgy::$config['vendorlogo'] != ''))
			echo "<a target=\"_blank\" href=\"".(isset($ZymurgyConfig['vendorlink']) ? $ZymurgyConfig['vendorlink'] : "javascript:;")."\"><img border=\"0\" src=\"{$ZymurgyConfig['vendorlogo']}\" alt=\"".(isset($ZymurgyConfig['vendorname']) ? htmlspecialchars($ZymurgyConfig['vendorname']) : "")."\"></a>";
		?>
	</div>
	<div class="ZymurgyLogo" title="Content Authoring and Search Engine Optimization">
		<?= Zymurgy::GetLocaleString("Common.ProductName") ?>
	</div>
	<div class="ZymurgyClient">
		<?php  if ((isset(Zymurgy::$config['clientlogo'])) && (Zymurgy::$config['clientlogo'] != '')) echo "<a target=\"_blank\" href=\"http://".Zymurgy::$config['sitehome']."/\"><img border=\"0\" src=\"".Zymurgy::$config['clientlogo']."\" alt=\"".htmlspecialchars(Zymurgy::$config['defaulttitle'])."\"></a>"; ?>
	</div>
</div>