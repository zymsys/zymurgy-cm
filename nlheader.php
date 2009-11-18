<?php
/**
 *
 * @package Zymurgy
 * @subpackage backend-base
 */
ob_start();
include("cmo.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?= Zymurgy::GetLocaleString("Common.ProductName") ?> Content Management Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<base href="http://<?=$_SERVER['HTTP_HOST']?>/zymurgy/">
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
	padding: 2px;
}
.ZymurgyHeader .ZymurgyVendor {
	display:table-cell;
	vertical-align:middle;
	float: left;
}
.ZymurgyHeader .ZymurgyLogo {
	float: left;
	font-size: 40px;
	margin-top:35px;
	margin-left:10px;
}
.ZymurgyHeader .ZymurgyClient {
	float: right;
}
.ZymurgyNavigation {
	background-color: #9999cb;
	float: left;
	padding: 10px;
	margin-right: 3px;
	height: 100%;
}
.ZymurgyNavigation .ZymurgyLoginName {
	font-weight: bold;
	text-align: center;
	margin-bottom:10px;
}
.ZymurgyNavigation .ZymurgyMenu {
	text-align: right;
}
.ZymurgyNavigation .ZymurgyMenu a, .ZymurgyNavigation .ZymurgyMenu a:link,.ZymurgyNavigation .ZymurgyMenu a:visited,.ZymurgyNavigation .ZymurgyMenu a:active {
	color: #000000;
	text-decoration: none;
	line-height: 2;
}
.ZymurgyNavigation .ZymurgyMenu a:hover {
	text-decoration: overline underline;
}
.ZymurgyClientArea {
	margin: 10px;
	overflow: auto;
}
-->
</style>
</head>
<body <?=$onload?>>
<div class="ZymurgyHeader">
	<div class="ZymurgyVendor">
		<?php
		if ((isset(Zymurgy::$config['vendorlogo'])) && (Zymurgy::$config['vendorlogo'] != '')){
			echo "<a target=\"_blank\" href=\""
			.(isset(Zymurgy::$config['vendorlink']) ? Zymurgy::$config['vendorlink'] : "javascript:;")
			.'"><img border="0" src="'.Zymurgy::$config['vendorlogo'].'" alt="'
			.(isset(Zymurgy::$config['vendorname']) ? htmlspecialchars(Zymurgy::$config['vendorname']) : "")."\"></a>";
		}
		?>
	</div>
	<div class="ZymurgyLogo" title="Content Authoring and Search Engine Optimization">
		Zymurgy:CM
	</div>
	<div class="ZymurgyClient">
		<?php
		if ((isset(Zymurgy::$config['clientlogo'])) && (Zymurgy::$config['clientlogo'] != '')){
			echo '<a target="_blank" href="http://'.Zymurgy::$config['sitehome'].'/">'
				.'<img border="0" src="'.Zymurgy::$config['clientlogo'].'" alt="'
				.htmlspecialchars(Zymurgy::$config['defaulttitle']).'"></a>';
		}
		?>
	</div>
</div>
