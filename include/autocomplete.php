<?
//Get query parameters.  Barf if they're nfg.
$table = $_GET['t'];
$idcolumn = $_GET['i'];
$column = $_GET['c'];
$query = $_GET['q'];

require_once('../cmo.php');
require_once("../ZymurgyAuth.php");
$zauth = new ZymurgyAuth();
$zauth->Authenticate("/zymurgy/login.php");

$oktables = explode(',',array_key_exists('AutocompleteTables',Zymurgy::$config) ? Zymurgy::$config['AutocompleteTables'] : 'zcm_member');

if (array_search($table,$oktables) === false)
{
	die ('["Add '.$table.' to AutocompleteTables in config.php."]');
}

//Store possible answers in $r
$r = array();
$column = "`".Zymurgy::$db->escape_string($column)."`";
$idcolumn = "`".Zymurgy::$db->escape_string($idcolumn)."`";
$table = "`".Zymurgy::$db->escape_string($table)."`";
$sql = "select $idcolumn, $column from $table where $column like '".
	Zymurgy::$db->escape_string($query)."%' order by $column";
$ri = Zymurgy::$db->run($sql);
while (($row = Zymurgy::$db->fetch_row($ri))!==false)
{
	$r[] = '{"id": "'.$row[0].'", "value": "'.$row[1]."\"}";
}
if ($r)
{
	echo "{ 
	    \"found\": \"".count($r)."\", 
	    \"results\": 
	        [";
	echo implode(",\r\n",$r);
	echo "]}";
}
else 
	echo '{}';
?>