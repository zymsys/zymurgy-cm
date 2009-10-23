<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
	require_once('cmo.php');
	include 'sitepageutil.php';

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$sql = "";

		if($_POST["id"] <= 0)
		{
			$sql = "INSERT INTO `zcm_sitepageseo` ( `zcm_sitepage`, `title`, `description`, `keywords`, `changefreq`, `priority` ) VALUES ( '{0}', '{1}', '{2}', '{3}', '{4}', '{5}' )";
		}
		else
		{
			$sql = "UPDATE `zcm_sitepageseo` SET `title` = '{1}', `description` = '{2}', `keywords` = '{3}', `changefreq` = '{4}', `priority` = '{5}' WHERE `id` = '{6}'";
		}

		$sql = str_replace("{0}", Zymurgy::$db->escape_string($_POST["zcm_sitepage"]), $sql);
		$sql = str_replace("{1}", Zymurgy::$db->escape_string($_POST["title"]), $sql);
		$sql = str_replace("{2}", Zymurgy::$db->escape_string($_POST["description"]), $sql);
		$sql = str_replace("{3}", Zymurgy::$db->escape_string($_POST["keywords"]), $sql);
		$sql = str_replace("{4}", Zymurgy::$db->escape_string($_POST["changefreq"]), $sql);
		$sql = str_replace("{5}", Zymurgy::$db->escape_string($_POST["priority"]), $sql);
		$sql = str_replace("{6}", Zymurgy::$db->escape_string($_POST["id"]), $sql);

		Zymurgy::$db->query($sql)
			or die("Could not update SEO information: ".Zymurgy::$db->error().", $sql");

		$crumbvalues = array_keys($crumbs);

//		print_r($crumbs);
//		die($crumbs[count($crumbs) - 2]);

		Zymurgy::JSRedirect($crumbvalues[count($crumbvalues) - 2]);
	}
	else
	{
		$crumbs[] = "SEO";

		include 'header.php';

		require_once("InputWidget.php");
		$widget = new InputWidget();

		$sql = "SELECT `id`, `zcm_sitepage`, `title`, `description`, `keywords`, `changefreq`, `priority` FROM `zcm_sitepageseo` WHERE `zcm_sitepage` = '".
			Zymurgy::$db->escape_string($_GET["p"]) .
			"' LIMIT 0, 1";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve SEO information for page: ".Zymurgy::$db->error().", $sql");

		if(Zymurgy::$db->num_rows($ri) > 0)
		{
			$row = Zymurgy::$db->fetch_array($ri);
		}
		else
		{
			$row = array(
				"id" => -1,
				"zcm_sitepage" => $_GET["p"],
				"title" => "",
				"description" => "",
				"keywords" => "",
				"changefreq" => "always",
				"priority" => 5);
		}

		Zymurgy::$db->free_result($ri);

?>
		<form name="frm" method="POST">
			<?= $widget->Render("hidden", "id", $row["id"]) ?>
			<?= $widget->Render("hidden", "zcm_sitepage", $row["zcm_sitepage"]) ?>
			<table>
				<tr>
					<td>Page Title:</td>
					<td><?= $widget->Render("input.40.80", "title", $row["title"]) ?></td>
				</tr>
				<tr>
					<td valign="top">Description:</td>
					<td><?= $widget->Render("textarea.60.5", "description", $row["description"]) ?></td>
				</tr>
				<tr>
					<td valign="top">Keywords:</td>
					<td><?= $widget->Render("textarea.60.3", "keywords", $row["keywords"]) ?></td>
				</tr>
				<tr>
					<td>Change Frequency:<br><i>Estimating how frequently the content<br>on this page will be updated helps Google<br>to index your site more effectively.</i></td>
					<td valign="top"><?= $widget->Render("drop.always,hourly,daily,weekly,monthly,yearly,never", "changefreq", $row["changefreq"]) ?></td>
				</tr>
				<tr>
					<td>Page Priority:<br><i>How important is this page within your<br>site as a number from 0 to 10, where 10<br>is the most important.  Google will<br>use this value to help choose which<br>pages from your site to show a user.</i></td>
					<td valign="top"><?= $widget->Render("drop.0,1,2,3,4,5,6,7,8,9,10", "priority", $row["priority"]) ?></td>
				</tr>
			</table>

			<p><input type="submit" value="Save SEO settings"></p>
		</form>
<?
		include('footer.php');

	}
?>