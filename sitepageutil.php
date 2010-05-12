<?
/**
 * Utility functions for working with Pages.
 *
 * @package Zymurgy
 * @subpackage backend-modules 
 */

require_once('cmo.php');

/**
 * Get the name of the script actually being accessed by the user.
 *
 * @return string The name of the PHP file currently being accessed.
 */
function whoami()
{
	$up = explode('/',$_SERVER['REQUEST_URI']);
	$up = explode('?',array_pop($up),2);
	return $up[0];
}

$thisscript = whoami();

/**
 * Return a list of the parent pages. Used to populate the breadcrumb trail.
 * 
 * @param int $pid The ID of the leaf node
 * @return mixed
 */
function pageparents($pid)
{
	global $thisscript;
	
	$r = array();
	do
	{
		$page = Zymurgy::$db->get("select id,linktext,parent from zcm_sitepage where id=$pid");
		$r[$page['id']] = ZIW_Base::GetFlavouredValue($page['linktext']);
		$pid = $page['parent'];
	}
	while ($pid > 0);
	return $r;
}

/**
 * Return a list of the breadcrumb trail items.
 *
 * @param int $pid The ID of the leaf node
 * @return mixed
 */
function pagecrumbs($pid)
{
	global $crumbs;

	$parents = pageparents($pid);
	$tids = array_keys($parents);
	while (count($parents) > 0)
	{
		$tid = array_pop($tids);
		$crumb = array_pop($parents);
		$crumbs["sitepage.php?p=$tid"] = htmlspecialchars($crumb);
			
	}
}

$crumbs = array("sitepage.php"=>"Pages");
$p = array_key_exists('p',$_GET) ? (0 + $_GET['p']) : 0;
if ($p > 0)
{
	pagecrumbs($p);
}

global $wikiArticleName;
$wikiArticleName = "Pages";
?>