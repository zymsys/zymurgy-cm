<?php
/**
 * Zymurgy:CM Help section. Has been replaced by the User and Development wikis at:
 *   http://www.zymurgycm.com/userwiki/
 *   http://www.zymurgycm.com/devwiki/
 *
 * @package Zymurgy
 * @subpackage help
 * @deprecrated
 */

$adminlevel = 2;

$breadcrumbTrail = "<a href=\"helpeditor.php\">Help Editor</a> &gt; References";

include 'header.php';
$h = 0 + $_GET['h'];

if (array_key_exists('action',$_GET))
{
	switch ($_GET['action'])
	{
		case 'insert':
			if ($_SERVER['REQUEST_METHOD']=='GET')
			{
				echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">New Reference: <select name=\"seealso\">\r\n";
				$ri = Zymurgy::$db->query("select id,title from zcm_help where id<>$h");
				while (($row = Zymurgy::$db->fetch_array($ri))!==false)
				{
					echo "<option value=\"{$row['id']}\">".htmlspecialchars($row['title'])."</option>";
				}
				echo "</select><input type=\"submit\" value=\"Add Reference\"></form>\r\n";
				include('footer.php');
				exit;
			}
			else 
			{
				//Must be a post
				$a = 0 + $_POST['seealso'];
				Zymurgy::$db->query("insert into zcm_helpalso (help,seealso) values ($h,$a)");
			}
			break;
		case 'delete':
			$k = $_GET['deletekey'];
			Zymurgy::$db->query("delete from zcm_helpalso where (help=$h) and (seealso=$k)");
			break;
	}
}
?>
<script language="JavaScript">
<!--
		function confirm_delete()
		{
		if (confirm("Are you sure you want to delete this record?")==true)
			return true;
		else
			return false;
		}
//-->
</script>
<style type="text/css">
<!--
<?php echo Zymurgy::$config['gridcss']; ?>
-->
</style>
<?php
$ri = Zymurgy::$db->query("select zcm_help.id, seealso, zcm_help.title from zcm_help join zcm_helpalso on zcm_help.id=zcm_helpalso.seealso where zcm_helpalso.help=$h");
echo "<table cellspacing=\"0\" cellpadding=\"3\" rules=\"cols\" bordercolor=\"#999999\" border=\"1\" class=\"DataGrid\">";
if (Zymurgy::$db->num_rows($ri) > 0)
{
	echo "<tr class=\"DataGridHeader\"><td colspan=\"2\">Existing 'See Also' References:</td></tr>\r\n";
	$alternate = false;
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		if ($alternate)
			$trclass = "DataGridRowAlternate";
		else
			$trclass = "DataGridRow";
		$alternate = !$alternate;
		echo "<tr class=\"$trclass\"><td>{$row['title']}</td><td><a href=\"helpalso.php?h=$h&action=delete&deletekey={$row['seealso']}\" onclick=\"return confirm_delete();\">Delete</a></td></tr>\r\n";
	}
}
echo "<tr class=\"DataGridHeader\"><td colspan='2' align='middle'><a href='helpalso.php?h=$h&action=insert'><font color=\"white\">Add a new Reference</font></a></td></tr>";
echo "</table>"; 
include('footer.php');
?>
