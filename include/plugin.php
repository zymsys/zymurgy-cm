<?
//Get query parameters.  Barf if they're nfg.
$plugin = 0 + $_GET['pi'];
$query = $_GET['q'];
if (!$plugin)
{
	die('["Bad query; please try again."]');
}

require_once('../cmo.php');

//Store possible answers in $r
$r = array();
$sql = "select name from zcm_plugininstance where name like '".
	Zymurgy::$db->escape_string($query)."%' and plugin=$plugin";
$ri = Zymurgy::$db->run($sql);
while (($row = Zymurgy::$db->fetch_array($ri))!==false)
{
	$r[] = $row['name'];
}
if ($r)
	echo '["'.implode('", "',$r).'"]';
else 
	echo '[]';
?>