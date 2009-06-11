<?
ob_start();
include("cms.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Zymurgy:CM Content Management Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<base href="http://<?=$_SERVER['HTTP_HOST']?>/zymurgy/">
<style type="text/css">
<!--
body {
	font-family: <?= isset(Zymurgy::$config["font"]) ? Zymurgy::$config["font"] : "Verdana, Arial, Helvetica, sans-serif" ?>;
	font-size:small;
}
.ZymurgyHeader {
	background-color: <?= $ZymurgyConfig['headerbackground'] ?>;
	color: <?= $ZymurgyConfig['headercolor'] ?>;
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
		<? if ((isset($ZymurgyConfig['vendorlogo'])) && ($ZymurgyConfig['vendorlogo'] != '')) echo "<a target=\"_blank\" href=\"{$ZymurgyConfig['vendorlink']}\"><img border=\"0\" src=\"{$ZymurgyConfig['vendorlogo']}\" alt=\"".htmlspecialchars($ZymurgyConfig['vendorname'])."\"></a>"; ?>
	</div>
	<div class="ZymurgyLogo" title="Content Authoring and Search Engine Optimization">
		<?= Zymurgy::GetLocaleString("Common.ProductName") ?>
	</div>
	<div class="ZymurgyClient">
		<? if ((isset($ZymurgyConfig['clientlogo'])) && ($ZymurgyConfig['clientlogo'] != '')) echo "<a target=\"_blank\" href=\"http://{$ZymurgyConfig['sitehome']}/\"><img border=\"0\" src=\"{$ZymurgyConfig['clientlogo']}\" alt=\"".htmlspecialchars($ZymurgyConfig['defaulttitle'])."\"></a>"; ?>
	</div>
</div>
