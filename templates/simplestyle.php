<?
/**
 * Stylesheet for {@link simple.php}
 * 
 * @package Zymurgy
 * @subpackage defaulttemplates
 */

require_once('../cmo.php');
Zymurgy::headtags();
header("Content-type: text/css");
$navpos = Zymurgy::Config('Navigation Position','0','drop.a:7:{i:0;s:14:"Top Navigation";i:1;s:21:"Small Left Navigation";i:2;s:22:"Medium Left Navigation";i:3;s:21:"Large Left Navigation";i:4;s:22:"Small Right Navigation";i:5;s:23:"Medium Right Navigation";i:6;s:22:"Large Right Navigation";}');
Zymurgy::Config('Color Theme','#275176,#275176,#4085C2,#336B9C,#757575,#FFFFFF,#C29240','theme');
Zymurgy::Config('Header Graphic','image/jpg','image.200.150');
?>
@charset "utf-8";
body {
	color: #<?= Zymurgy::theme('Text Color') ?>;
	background-color: #<?= Zymurgy::theme('Page Background') ?>;
	font-family:Verdana, Arial, Helvetica, sans-serif;
}
#hd {
	background-color: #<?= Zymurgy::theme('Header Background') ?>;
	background-image: url(/UserFiles/DataGrid/zcm_config.value/<?= Zymurgy::$userconfigid['Header Graphic'] ?>thumb200x150.jpg);
	background-repeat: no-repeat;
	background-position: right top;
	min-height:<?= ($navpos == 0) ? 176 : 150 ?>px;
	font-family:Arial, Helvetica, sans-serif;
	font-weight:bold;
	text-align:center;
}
#sitetitle {
	font-size: 30px;
	white-space: nowrap;
	height: 60px;
	padding-top: 30px;
}
#slogan {
	font-size: 20px;
	white-space: nowrap;
	height: 60px;
}
#navigation {
	font-size: 15px;
}
#bd {
	background-color: #<?= Zymurgy::Config('Body Background','ffffff','colour') ?>;
<? if ($navpos == 0) { ?>	
	padding: 15px;
<? } ?>	
	min-height: 350px;
}
#ft {
	font-style:italic;
}
#zymurgy-main {
	margin-top: 10px;
	margin-bottom: 10px;
	margin-left: 100px;
	margin-right: 100px;
}
.NormalLabel {
	text-align: right;
}
.SubmitCell {
	text-align: center;
}
#ZymurgyMenu_pages .zymurgy-horizontal-menu {
	background-image: url(/zymurgy/templates/simplegradient.php);
}
#ZymurgyMenu_pages .yuimenubaritem {
	background-image: url(/zymurgy/templates/simplegradient.php);
}
#ZymurgyMenu_pages .yuimenubaritemlabel-hassubmenu, #ZymurgyMenu_pages .yuimenubaritemlabel-selected {
	background-image: url(/zymurgy/templates/simplegradient.php?s=1);
}
#ZymurgyMenu_pages ul, #ZymurgyMenu_pages .yuimenu .bd .yuimenuitem {
	background-color: #<?= Zymurgy::theme('Menu Background') ?>;
}
#ZymurgyMenu_pages .yuimenuitemlabel {
        color: #<?= Zymurgy::theme('Text Color') ?>;!
}
#ZymurgyMenu_pages .yuimenu .bd .yuimenuitemlabel-selected {
	background-color: #<?= Zymurgy::theme('Menu Highlight') ?>;
}
#ZymurgyMenu_pages {
	display:none;
}
