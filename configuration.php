<?
$breadcrumbTrail = "Site Config";

include('header.php');
include('datagrid.php');

if ($_SERVER['REQUEST_METHOD']=='POST')
{
	foreach($_POST as $key=>$value)
	{
		if (substr($key,0,6)=='Config')
		{
			$id = substr($key,6);
			if (!is_numeric($id)) die("Can't get ID ($id) from key ($key).");
			$sql = "update config set value='".
				Zymurgy::$db->escape_string($value)."' where id=$id";
			$ri = Zymurgy::$db->query($sql);
			//echo "$sql<br>";
			if (!$ri)
			{
				echo Zymurgy::$db->error().": $sql";
				exit;
			}
		}
	}
	echo "Configuration saved.";
	//header("Location: index.php");
}
else 
{
	echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\"><table>\r\n";
	$ri = Zymurgy::$db->query("select * from config order by disporder") or die("Can't get config.");
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		echo "<tr><td align=\"right\">{$row['name']}:</td><td><input type=\"text\" name=\"Config{$row['id']}\" value=\"".str_replace('"','&quot;',$row['value'])."\"></td></tr>\r\n";
	}
	echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\"></td></tr>\r\n";
	echo "</table></form>";
}
include('footer.php');
?>
