<?php 
/**
 * Takes two get params, t (Tag) and d (Default value) plus an optional inputspec (i).
 * 
 * Returns a JSON response with either an errormsg on error or the value on success.
 * 
 * Note:  This doesn't do anything with flavours; if the page was flavoured to a different
 * language then the sitetext cache should be built from that language, and the righ text
 * should be picked up by cmo.js.  If this is new text then the default text in the default
 * language will be used until the correct translation is entered into Z:CM.
 */
require_once '../cmo.php';
Zymurgy::headtags(false);
$result = new stdClass();
if (!array_key_exists('t', $_GET) || !array_key_exists('d', $_GET) || !array_key_exists('pt', $_GET) || !array_key_exists('pi', $_GET))
{
	$result->errormsg = 'Bad Simple Text Request';
}
else 
{
	Zymurgy::$pagetype = ($_GET['pt'] == 'Template') ? 'Template' : 'Simple';
	Zymurgy::$pageid = intval($_GET['pi']);
	if (array_key_exists('i', $_GET))
	{
		$inputspec = $_GET['i'];
	}
	else 
	{
		$inputspec = 'html.600.400';
	}
	$result->value = Zymurgy::sitetext($_GET['t'],$inputspec,false,$_GET['d'],true);
}
echo json_encode($result);
?>