<?
/**
 * Template for changing flavours, but keeping the same path
 * 
 * WARNING: This file will be over-written when Zymurgy:CM is upgraded.  Copy it to a location outside of
 * the /zymurgy folder to make customizations to it.
 * 
 * @package Zymurgy
 * @subpackage defaulttemplates
 */
if (!class_exists('Zymurgy'))
	require_once('../cmo.php');
Zymurgy::headtags();
$newflavour = Zymurgy::pagetext('New Flavour Name','inputf.60.255');
$newnav = new ZymurgySiteNav($newflavour);
$link = Zymurgy::pagetext('Default URL','inputf.60.255');
if (!array_key_exists('HTTP_REFERER', $_SERVER))
{
	header('Location: '.$link);
	exit;
}
$referer = $_SERVER['HTTP_REFERER'];
$rp = explode('/',$referer);
array_shift($rp); //Protocol - http:
array_shift($rp); //Blank from // after http:
array_shift($rp); //Host name - throw away
array_shift($rp); //Old flavour name
$newpath = array('',$newflavour);
$node = 0; //Start with root node
while ($rp)
{
	$pathpart = array_shift($rp);
	$node = Zymurgy::$sitenav->items[$node]->childrenbynavname[$pathpart];
	$newpath[] = $newnav->items[$node]->linkurl;
}
$link = implode('/', $newpath);
//Zymurgy::DbgAndDie($link,$newpath,$newflavour,$link,$referer,$newnav,Zymurgy::$sitenav);
header('Location: '.$link);
?>