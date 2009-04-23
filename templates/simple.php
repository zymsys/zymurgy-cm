<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?
if (!class_exists('Zymurgy'))
	require_once('../cmo.php');
echo Zymurgy::headtags();
echo Zymurgy::YUI('grids/grids-min.css');
$navpos = Zymurgy::Config('Navigation Position','0','drop.a:7:{i:0;s:14:"Top Navigation";i:1;s:21:"Small Left Navigation";i:2;s:22:"Medium Left Navigation";i:3;s:21:"Large Left Navigation";i:4;s:22:"Small Right Navigation";i:5;s:23:"Medium Right Navigation";i:6;s:22:"Large Right Navigation";}');
$body = (array_key_exists('p',$_GET)) ? $_GET['p'] : '';
?>
<link href="/zymurgy/templates/simplestyle.php" rel="stylesheet" type="text/css" />
</head>

<body class="yui-skin-sam">
<?
echo "<div id=\"".Zymurgy::Config('Page Width','doc2','drop.a:4:{s:3:"doc";s:21:"Optimized for 800x600";s:4:"doc2";s:22:"Optimized for 1024x768";s:4:"doc3";s:18:"Full Browser Width";s:4:"doc4";s:24:"Restricted Browser Width";}')."\"";
if ($navpos != 0)
	echo " class=\"yui-t".$navpos."\"";
echo ">";
?>
	<div id="hd">
    	<div id="sitetitle"><?= Zymurgy::Config('Site Title',"Your Site's Title",'input.45.45') ?></div>
    	<div id="slogan"><?= Zymurgy::Config('Slogan',"Your Site's Slogan",'input.60.60') ?></div>
    	<? if ($navpos == 0) Zymurgy::sitenav(true); ?>
    </div>
    <div id="bd">
    <? 
    if ($navpos != 0)
    {
    	echo "<div id=\"yui-main\"><div id=\"zymurgy-main\" class=\"yui-b\">";
    }
    //echo Zymurgy::pagetext($body);
    echo Zymurgy::pagetext('Body');
    echo Zymurgy::pagegadgets();
    if ($navpos != 0)
    {
    	echo "</div></div>";
    	echo "<div class=\"yui-b\">";
    	Zymurgy::sitenav(false);
    	echo "</div>";
    }
    ?>
    </div>
    <div id="ft">
    <?= Zymurgy::Config('Copyright','Copyright (c) Your Company Name, '.date('Y'),'textarea.40.5') ?>
    </div>
</div>
</body>
</html>
