<?
/**
 * Index page for backend.  No actual editor, just navigation menus.
 * @package Zymurgy
 * @subpackage backend-modules
 */

function normalizedomain($domain)
{
	$domain = strtolower($domain);
	if (substr($domain,0,4)=='www.')
		$domain = substr($domain,4);
	return $domain;
}

$host = normalizedomain($_SERVER['HTTP_HOST']);

$overridelicense = true;
$licensemsg = array();

$breadcrumbTrail = "Home";

include('header.php');

//echo "<script src=\"https://www.zymurgy2.com/cmf/lservjs.php?build=$build&auth={$zauth->authinfo['admin']}&host=".urlencode($host)."\"></script>\r\n";
?>
<script type="text/JavaScript" language="JavaScript">
 var mine = window.open('','','width=1,height=1,left=0,top=0,scrollbars=no');
 if(mine) {
 	mine.close();
 } else {
 	alert('You have a pop-up blocker active in your browser.\r\nCertain Zymurgy:CM features may fail while the blocker is enabled for this site.');
 }
</script>
<?
include('footer.php');
?>
