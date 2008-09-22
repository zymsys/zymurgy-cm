<?
if ($_SERVER['REQUEST_METHOD']=='POST')
{
	$breadcrumbTrail = "<a href=\"configuration.php\">Site Config</a>";
}
else
{
	$breadcrumbTrail = "Site Config";
}

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
			$sql = "update zcm_config set value='".
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
	$ri = Zymurgy::$db->query("select * from zcm_config order by disporder") or die("Can't get config.");
	if (Zymurgy::$db->num_rows($ri)==0)
	{
		echo "This site has no configuration values to set.";
	}
	else 
	{
		echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\"><table>\r\n";
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "<tr><td align=\"right\">{$row['name']}:</td><td><input type=\"text\" name=\"Config{$row['id']}\" value=\"".str_replace('"','&quot;',$row['value'])."\"></td></tr>\r\n";
		}
		echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\"></td></tr>\r\n";
		echo "</table></form>";
	}
}
include('footer.php');
?>
