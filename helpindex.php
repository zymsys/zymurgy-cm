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
$breadcrumbTrail = "<a href=\"helpeditor.php\">Help Editor</a> &gt; Index Phrases";

include 'header.php';

if (array_key_exists('action',$_GET))
{
	switch ($_GET['action'])
	{
		case 'edit':
			$k = $_GET['editkey'];
			$ri = Zymurgy::$db->query("select zcm_helpindexphrase.phrase, zcm_helpindex.phrase as pid from zcm_helpindex join zcm_helpindexphrase on zcm_helpindex.phrase=zcm_helpindexphrase.id where zcm_helpindex.help=$h and zcm_helpindex.phrase=$k");
			$edit = Zymurgy::$db->fetch_array($ri);
			//Intentionally falling through to 'insert' block to complete the job.
		case 'insert':
			if ($_SERVER['REQUEST_METHOD']=='GET')
			{
				echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\">Index Phrase: \r\n";
				echo "<input type=\"text\" name=\"phrase\"";
				if (isset($edit))
				{
					echo "value=\"{$edit['phrase']}\"";
				}
				echo " />";
				echo "<input type=\"submit\" value=\"Save\" /></form>\r\n";
				include('footer.php');
				exit;
			}
			else
			{
				//Must be a post
				$phrase = Zymurgy::$db->escape_string($_POST['phrase']);
				$ri = Zymurgy::$db->query("select * from zcm_helpindexphrase where phrase like '$phrase'") or die("Can't search phrase ($sql): ".Zymurgy::$db->error());
				$entry = Zymurgy::$db->fetch_array($ri);
				if ($entry===false)
				{
					Zymurgy::$db->query("insert into zcm_helpindexphrase (phrase) values ('$phrase')") or die("Can't add phrase ($sql): ".Zymurgy::$db->error());
					$entry = array('id'=>Zymurgy::$db->insert_id());
				}
				if (isset($edit))
				{
					//Remove the old reference before adding the new one.
					Zymurgy::$db->query("delete from zcm_helpindex where help=$h and phrase={$edit['pid']}");
				}
				Zymurgy::$db->query("insert into zcm_helpindex (help,phrase) values ($h,{$entry['id']})") or die("Can't add index ($sql): ".Zymurgy::$db->error());
			}
			break;
		case 'delete':
			$k = $_GET['deletekey'];
			Zymurgy::$db->query("delete from zcm_helpindex where (help=$h) and (phrase=$k)");
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
$ri = Zymurgy::$db->query("select zcm_helpindexphrase.phrase, zcm_helpindex.phrase as pid from zcm_helpindex join zcm_helpindexphrase on zcm_helpindex.phrase=zcm_helpindexphrase.id where zcm_helpindex.help=$h order by zcm_helpindexphrase.phrase");
$numColumns = 3;
echo "<table cellspacing=\"0\" cellpadding=\"3\" rules=\"cols\" bordercolor=\"#999999\" border=\"1\" class=\"DataGrid\">";
if (Zymurgy::$db->num_rows($ri) > 0)
{
	echo "<tr class=\"DataGridHeader\"><td colspan=\"{$numColumns}\">Existing index References:</td></tr>\r\n";
	$alternate = false;

	$prevIndexEntry = "";

	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		if ($alternate)
			$trclass = "DataGridRowAlternate";
		else
			$trclass = "DataGridRow";
		$alternate = !$alternate;
		echo "<tr class=\"$trclass\"><td>{$row['phrase']}</td><td><a href=\"helpindex.php?h=$h&action=delete&deletekey={$row['pid']}\" onclick=\"return confirm_delete();\">Delete</a></td>
			<td><a href=\"helpindex.php?h=$h&action=edit&editkey={$row['pid']}\">Edit</a></td></tr>\r\n";
	}
}
echo "<tr class=\"DataGridHeader\"><td colspan='{$numColumns}' align='middle'><a href='helpindex.php?h=$h&action=insert'><font color=\"white\">Add a new Entry</font></a></td></tr>";
echo "</table>";
include('footer.php');
?>
