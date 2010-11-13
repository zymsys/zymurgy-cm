<?
/**
 * Template for redirects
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
$link = Zymurgy::pagetext('Link URL','inputf.60.255');
header('Location: '.$link);
?>