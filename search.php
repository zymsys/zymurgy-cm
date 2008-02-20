<?php
$adminlevel = 2;
$breadcrumbTrail = "Search Results";

include 'helpheader.php';

$queryString = $_GET['q'];

if (array_key_exists('q',$_GET))
{
	//$ri = Zymurgy::$db->query("SELECT id, title FROM help WHERE help.plain LIKE '%{$queryString}%'");
	$ri = Zymurgy::$db->query("SELECT id, title FROM help WHERE MATCH (title,plain) AGAINST (\"{$queryString}\")");
}
?>
<style type="text/css">
<!--
<?php echo Zymurgy::$config['gridcss']; ?>
-->
</style>
<?php
$numColumns = 1;

echo "Search criteria: " . $queryString . "<br><br>";
echo "<table cellspacing=\"0\" cellpadding=\"3\" rules=\"cols\" bordercolor=\"#999999\" border=\"1\" class=\"DataGrid\">";
if (Zymurgy::$db->num_rows($ri) > 0)
{
//	echo "<tr class=\"DataGridHeader\"><td colspan=\"3\">Existing index References:</td></tr>\r\n";
	echo "<tr class=\"DataGridHeader\"><td colspan=\"{$numColumns}\">Existing index References:</td></tr>\r\n";
	$alternate = false;
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		if ($alternate)
			$trclass = "DataGridRowAlternate";
		else
			$trclass = "DataGridRow";
		$alternate = !$alternate;
		echo "<tr class=\"$trclass\"><td><a href=\"help.php?id={$row['id']}\">{$row['title']}</a></td></tr>\r\n";
	}
}
if (Zymurgy::$db->num_rows($ri) > 0)
{
	echo "<tr class=\"DataGridHeader\"><td colspan='{$numColumns}' align='middle'>&nbsp;</td></tr>";	
}
else 
{
	echo "The index is empty.";
}
echo "</table>"; 
include('footer.php');
?>
