<?
$breadcrumbTrail = "<a href=\"headtext.php\">Search Engines</a> &gt; Page Text";

include('header.php');
$page = 0 + $_GET['page'];
$sql = "select id,tag from sitetext,textpage where (metaid=$page) and (sitetextid=id)";
$ri = Zymurgy::$db->query($sql);
$count = Zymurgy::$db->num_rows($ri);
switch ($count)
{
	case (0):
		echo "This page contains no dynamic text.";
		break;
	case (1):
		header("Location: sitetext.php?editkey=".Zymurgy::$db->result($ri,0,0));
		break;
	default:
		echo "Select the dynamic page block you wish to update:<ul>\r\n";
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "<li><a href='sitetext.php?editkey={$row['id']}'>{$row['tag']}</a></li>\r\n";
		}
		echo "</ul>\r\n";
		break;
}
include('footer.php');
?>
