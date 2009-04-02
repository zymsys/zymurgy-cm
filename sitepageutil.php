<?
require_once('cmo.php');

function pageparents($pid)
{
	$r = array();
	do
	{
		$page = Zymurgy::$db->get("select id,linktext,parent from zcm_sitepage where id=$pid");
		$r[$page['id']] = $page['linktext'];
		if (array_key_exists('pp',$_GET) && (count($r)==1))
			$r[$page['id']] .= ' Extras';
		$pid = $page['parent'];
	}
	while ($pid > 0);
	return $r;
}

function pagecrumbs($pid)
{
	global $crumbs;

	$parents = pageparents($pid);
	$tids = array_keys($parents);
	while (count($parents) > 0)
	{
		$tid = array_pop($tids);
		$crumb = array_pop($parents);
		if (array_key_exists('pp',$_GET) && (count($parents)==0))
			$crumbs["sitepageextra.php?p=$tid&pp=1"] = htmlspecialchars($crumb);
		else 
			$crumbs["sitepage.php?p=$tid"] = htmlspecialchars($crumb);
	}
}

$crumbs = array("sitepage.php"=>"Pages");
$p = array_key_exists('p',$_GET) ? (0 + $_GET['p']) : 0;
$pp = array_key_exists('pp',$_GET) ? (0 + $_GET['pp']) : 0;
if ($p > 0)
{
	pagecrumbs($p);
}
/*if ($pp > 0)
{
	$crumbs["sitepageextra.php?p=$p&pp=1"] = "Extras";
}*/
?>