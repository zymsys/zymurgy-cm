<?
	/**
	 * Retrieve data from a remote datasource.
	 *
	 * @package Zymurgy
	 * @subpackage backend-modules
	 */

	//Get query parameters.  Barf if they're nfg.
	$table = $_GET['t'];
	$idcolumn = $_GET['i'];
	$column = $_GET['c'];
	$query = $_GET['q'];

	require_once('../cmo.php');
	Zymurgy::memberrequirezcmauth(1);

	$oktables = explode(',',array_key_exists('RemoteTables',Zymurgy::$config) ? Zymurgy::$config['RemoteTables'] : '');

	if (array_search($table,$oktables) === false)
	{
		die ('["Add '.$table.' to RemoteTables in config.php."]');
	}

	//Store possible answers in $r
	$response = Zymurgy::memberremotelookup($table,$column,$query);
	foreach ($response as $key=>$value)
	{
		$r[] = '{"id": "'.$key.'", "value": "'.$value."\"}";
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