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
require_once('InputWidget.php');

if ($_SERVER['REQUEST_METHOD']=='POST')
{
	$updates = array();
	$ri = Zymurgy::$db->run("select * from zcm_config order by disporder");
	$widget = new InputWidget();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$id = $row['id'];
		$value = $widget->PostValue($row['inputspec'],"Config$id");
		$isp = explode('.',$row['inputspec']);
		$type = array_shift($isp);
		switch($type)
		{
			case 'attachment':
				//??
				break;
			case 'image':
				Zymurgy::MakeThumbs('zcm_config.value',$id,array("{$isp[0]}x{$isp[1]}"),$_FILES["Config$id"]['tmp_name']);
				break;
		}
		$sql = "update zcm_config set value='".
			Zymurgy::$db->escape_string($value)."' where id=$id";
		$updates[] = $sql;
	}
	Zymurgy::$db->free_result($ri);
	foreach($updates as $sql)
	{
		Zymurgy::$db->run($sql);
	}
	echo "Configuration saved.";
	//header("Location: index.php");
}
else 
{
	$pretext = array();
	$ri = Zymurgy::$db->run("select * from zcm_config order by disporder");
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$type = array_shift(explode('.',$row['inputspec']));
		if (!array_key_exists($type,$pretext))
			$pretext[$type] = InputWidget::GetPretext($type);
	}
	echo implode("\r\n",$pretext);
	Zymurgy::$db->free_result($ri);
	$ri = Zymurgy::$db->run("select * from zcm_config order by disporder");
	if (Zymurgy::$db->num_rows($ri)==0)
	{
		echo "This site has no configuration values to set.";
	}
	else 
	{
		echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\" enctype=\"multipart/form-data\"><table>\r\n";
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "<tr><td align=\"right\">{$row['name']}:</td><td>";
			$iw = new InputWidget();
			$iw->Render($row['inputspec'],"Config{$row['id']}",$row['value']);
			//echo "<input type=\"text\" name=\"Config{$row['id']}\" value=\"".str_replace('"','&quot;',$row['value'])."\">";
			echo "</td></tr>\r\n";
		}
		echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\"></td></tr>\r\n";
		echo "</table></form>";
	}
}
include('footer.php');
?>