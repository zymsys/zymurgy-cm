<?
/**
 * Index page for backend.  No actual editor, just navigation menus.
 * @package Zymurgy
 * @subpackage backend-modules
 */

/**
 * Return the FQDN without the "www." on it
 *
 * @param $domain string The FQDN.
 * @return string The normalized domain.
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

?>
<script type="text/JavaScript" language="JavaScript">
 var mine = window.open('','','width=1,height=1,left=0,top=0,scrollbars=no');
 if(mine) {
 	mine.close();
 } else {
 	alert('You have a pop-up blocker active in your browser.\r\nCertain <?= addslashes(Zymurgy::GetLocaleString("Common.ProductName")) ?> features may fail while the blocker is enabled for this site.');
 }
</script>
<?
include('footer.php');
?>
