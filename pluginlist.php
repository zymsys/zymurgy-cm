<?php
	require_once("cmo.php");

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		switch($_POST["action"])
		{
			case "act_enable":
				EnablePlugin($_POST["id"]);
				Zymurgy::JSRedirect("plugin.php");

			case "act_disable":
				DisablePlugin($_POST["id"]);
				Zymurgy::JSRedirect("plugin.php");

			case "act_remove":
				RemovePlugin($_POST["id"]);
				Zymurgy::JSRedirect("plugin.php");

			default:
				die("Invalid action ".$_POST["action"]);
		}
	}
	else
	{
		switch($_GET["action"])
		{
			case "addlist":
				$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; Add/Enable Plugin";
				$ri = GetDisabledPlugins();
				DisplayPluginList($ri, $_GET["action"]);
				Zymurgy::$db->free_result($ri);
				break;

			case "removelist":
				$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; Remove/Disable Plugin";
				$ri = GetInstalledPlugins();
				DisplayPluginList($ri, $_GET["action"]);
				Zymurgy::$db->free_result($ri);
				break;

			case "enable":
			case "disable":
			case "remove":
				$plugin = GetPluginDetails($_GET["id"]);
				$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; <a href=\"pluginlist.php?action=removelist\">".ucfirst($_GET["action"])." Plugin</a> &gt; ".
					$plugin->GetTitle();
				DisplayPluginDetails($plugin, $_GET["id"], $_GET["action"]);
				break;

			default:
				die("Invalid action ".$_GET["action"]);
		}
	}

	function GetDisabledPlugins()
	{
		$sql = "SELECT `zcm_plugin`.`id`, `zcm_plugin`.`title`, `zcm_plugin`.`name`, `zcm_plugin`.`enabled`, CASE WHEN `zcm_plugininstance`.`id` > 0 THEN 1 ELSE 0 END AS `hasinstances`, COUNT(*) AS `count` FROM `zcm_plugin` LEFT JOIN `zcm_plugininstance` ON `zcm_plugininstance`.`plugin` = `zcm_plugin`.`id` WHERE `enabled` <> 1 GROUP BY `zcm_plugin`.`id`, `zcm_plugin`.`title`, `zcm_plugin`.`name`, `zcm_plugin`.`enabled` ORDER BY `zcm_plugin`.`id`, `zcm_plugin`.`title`, `zcm_plugin`.`name`, `zcm_plugin`.`enabled`";

		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of disabled plugins: ".Zymurgy::$db->error().", $sql");

		return $ri;
	}

	function GetInstalledPlugins()
	{
		$sql = "SELECT `zcm_plugin`.`id`, `zcm_plugin`.`title`, `zcm_plugin`.`name`, `zcm_plugin`.`enabled`, CASE WHEN `zcm_plugininstance`.`id` > 0 THEN 1 ELSE 0 END AS `hasinstances`, COUNT(*) AS `count` FROM `zcm_plugin` LEFT JOIN `zcm_plugininstance` ON `zcm_plugininstance`.`plugin` = `zcm_plugin`.`id` GROUP BY `zcm_plugin`.`id`, `zcm_plugin`.`title`, `zcm_plugin`.`name`, `zcm_plugin`.`enabled` ORDER BY `zcm_plugin`.`id`, `zcm_plugin`.`title`, `zcm_plugin`.`name`, `zcm_plugin`.`enabled`";

		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of installed plugins: ".Zymurgy::$db->error().", $sql");

		return $ri;
	}

	function GetPluginDetails($id)
	{
		$sql = "SELECT `name` FROM `zcm_plugin` WHERE `id` = '".
			Zymurgy::$db->escape_string($id).
			"'";
		$pluginName = Zymurgy::$db->get($sql);

		include_once("PluginBase.php");
		include_once("plugins/".$pluginName.".php");

		return new $pluginName;
	}

	function EnablePlugin($id)
	{
		$sql = "UPDATE `zcm_plugin` SET `enabled` = 1 WHERE `id` = '".
			Zymurgy::$db->escape_string($id).
			"'";
		Zymurgy::$db->query($sql)
			or die("Could not disable plugin: ".Zymurgy::$db->error().", $sql");
	}

	function DisablePlugin($id)
	{
		$sql = "UPDATE `zcm_plugin` SET `enabled` = 0 WHERE `id` = '".
			Zymurgy::$db->escape_string($id).
			"'";
		Zymurgy::$db->query($sql)
			or die("Could not disable plugin: ".Zymurgy::$db->error().", $sql");
	}

	function RemovePlugin($id)
	{
		$sql = "DELETE FROM `zcm_pluginconfigitem` WHERE EXISTS( SELECT 1 FROM `zcm_pluginconfiggroup` WHERE `zcm_pluginconfiggroup`.`id` = `zcm_pluginconfigitem`.`config` AND EXISTS( SELECT 1 FROM `zcm_plugininstance` WHERE `zcm_plugininstance`.`config` = `zcm_pluginconfiggroup`.`id` AND `zcm_plugininstance`.`plugin` = '".
			Zymurgy::$db->escape_string($id).
			"' ) OR EXISTS( SELECT 1 FROM `zcm_plugin` WHERE `zcm_plugin`.`defaultconfig` = `zcm_pluginconfiggroup`.`id` AND `zcm_plugin`.`id` = '".
			Zymurgy::$db->escape_string($id).
			"' ) )";
		Zymurgy::$db->query($sql)
			or die("Could not remove config items associated with plugin: ".Zymurgy::$db->error().", $sql");

		$sql = "DELETE FROM `zcm_pluginconfiggroup` WHERE EXISTS( SELECT 1 FROM `zcm_plugininstance` WHERE `zcm_plugininstance`.`config` = `zcm_pluginconfiggroup`.`id` AND `zcm_plugininstance`.`plugin` = '".
			Zymurgy::$db->escape_string($id).
			"' ) OR EXISTS( SELECT 1 FROM `zcm_plugin` WHERE `zcm_plugin`.`defaultconfig` = `zcm_pluginconfiggroup`.`id` AND `zcm_plugin`.`id` = '".
			Zymurgy::$db->escape_string($id).
			"' )";
		Zymurgy::$db->query($sql)
			or die("Could not remove config groups associated with plugin: ".Zymurgy::$db->error().", $sql");

		$sql = "DELETE FROM `zcm_plugininstance` WHERE `plugin` = '".
			Zymurgy::$db->escape_string($id).
			"'";
		Zymurgy::$db->query($sql)
			or die("Could not remove orphaned instances of plugin: ".Zymurgy::$db->error().", $sql");

		$sql = "DELETE FROM `zcm_plugin` WHERE `id` = '".
			Zymurgy::$db->escape_string($id).
			"'";
		Zymurgy::$db->query($sql)
			or die("Could not remove plugin: ".Zymurgy::$db->error().", $sql");
	}

	function DisplayPluginList($ri, $action)
	{
		global $breadcrumbTrail;
		include("header.php");
		include_once("datagrid.php");
		DumpDataGridCSS();
?>
	<table class="DataGrid" rules="cols" cellspacing="0" cellpadding="3" border="1" bordercolor="#999999">
		<tr class="DataGridHeader">
			<td>Plugin</td>
			<? if($action == "addlist") { ?>
				<td>Enable</td>
			<? } else { ?>
				<td>Disable</td>
				<td>Remove</td>
			<? } ?>
		</tr>
<?
		$isEven = false;

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
?>
		<tr class="DataGridRow<?= $isEven ? "Alternate" : "" ?>">
			<td><?= $row["title"] ?></td>
			<? if($action == "addlist") { ?>
				<td><a href="pluginlist.php?action=enable&amp;id=<?= $row["id"] ?>">Enable</a></td>
			<? } else { ?>
				<td><?= $row["hasinstances"] <= 0
						? $row["enabled"] > 0
							? "<a href=\"pluginlist.php?action=disable&amp;id=".$row["id"]."\">Disable</a>"
							: "Already disabled"
						: "<a href=\"pluginadmin.php?pid=".$row["id"]."\">".$row["count"]." instances</a>, cannot disable" ?></td>
				<td><?= $row["hasinstances"] <= 0
						? "<a href=\"pluginlist.php?action=remove&amp;id=".$row["id"]."\">Remove</a>"
						: "<a href=\"pluginadmin.php?pid=".$row["id"]."\">".$row["count"]." instances</a>, cannot delete" ?></td>
			<? } ?>
		</tr>
<?
			$isEven = !$isEven;
		}
?>
	</table>
<?
		include("footer.php");
	}

	function DisplayPluginDetails($plugin, $id, $action)
	{
		global $breadcrumbTrail;
		include("header.php");

		echo($plugin->GetDescription());
?>
	<form name="frm" action="pluginlist.php" method="POST">
		<input type="hidden" name="action" value="act_<?= $action ?>">
		<input type="hidden" name="id" value="<?= $id ?>">
		<input type="submit" value="<?= ucfirst($action) ?>">
		<input type="button" value="Cancel" onclick="history.go(-1);">
	</form>
<?
		include("footer.php");
	}
?>