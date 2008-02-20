<?
$breadcrumbTrail = "Site Config";

include('header.php');
include('datagrid.php');

if ($_SERVER['REQUEST_METHOD']=='POST')
{
	foreach($_POST as $key=>$value)
	{
		$key = str_replace('_',' ',$key);
		$sql = "update config set value='".
			Zymurgy::$db->escape_string($value)."' where name='".
			Zymurgy::$db->escape_string($key)."'";
		$ri = Zymurgy::$db->query($sql);
		//echo "$sql<br>";
		if (!$ri)
		{
			echo Zymurgy::$db->error().": $sql";
			exit;
		}
	}
	echo "Configuration saved.";
	//header("Location: index.php");
}
else 
{
	echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\"><table>\r\n";
	foreach(Zymurgy::$userconfig as $key=>$value)
	{
		echo "<tr><td align=\"right\">{$key}:</td><td><input type=\"text\" name=\"{$key}\" value=\"{$value}\"></td></tr>\r\n";
	}
	echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\"></td></tr>\r\n";
	echo "</table></form>";
}
include('footer.php');
?>
