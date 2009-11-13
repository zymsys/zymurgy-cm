<?php
	ini_set("display_errors", 1);
	require_once("cmo.php");

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		switch($_POST["action"])
		{
			case "act_enable":
				EnablePlugin($_POST["id"]);
				Zymurgy::JSRedirect("plugin.php");

			case "act_add":
				$error = ValidateFileUpload($_FILES["file"]);

				if(strlen($error) > 0)
				{
					$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; Add/Enable Plugin";
					$ri = GetDisabledPlugins();
					DisplayPluginList($ri, "addlist", $error);
					Zymurgy::$db->free_result($ri);
				}
				else
				{
					InstallPlugin($_FILES["file"]);
				}
				Zymurgy::JSRedirect("plugin.php");
				break;

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

	function ValidateFileUpload($file)
	{
		switch ($file['error'])
		{
		    case UPLOAD_ERR_OK:
		   		return "";
		        break;
		    case UPLOAD_ERR_INI_SIZE:
		        return "The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.";
		       break;

		    case UPLOAD_ERR_FORM_SIZE:
		       return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
		       break;

		    case UPLOAD_ERR_PARTIAL:
		       return "The uploaded file was only partially uploaded.";
		       break;

		    case UPLOAD_ERR_NO_FILE:
		       return "No file was uploaded.";
		       break;

		    case UPLOAD_ERR_NO_TMP_DIR:
		       return "Missing a temporary folder.";
		       break;
		    case UPLOAD_ERR_CANT_WRITE:
		       return "Failed to write file to disk";
		       break;

		    default:
		       return "Unknown File Error";
		}
	}

	function InstallPlugin($file)
	{
		echo("InstallPlugin START<br>");

		if(!move_uploaded_file($file["tmp_name"], Zymurgy::$root."/zymurgy/plugins/temp.php"))
		{
			echo("Could not move file ".$file["tmp_name"]."<br>");
		}
		else
		{
			echo("File moved to temp.php<br>");

			$pluginName = $file["name"];
			$className = str_replace(".php", "", $pluginName);

			include_once("PluginBase.php");
			include_once("plugins/temp.php");

			echo("Instantiating ".$pluginName);

			$plugin = new $className;

			if($plugin instanceof PluginBase)
			{
				rename("plugins/temp.php", "plugins/".$pluginName);
				ExecuteAdd($className);
			}
		}

		echo("InstallPlugin END<br>");
	}
	function  ExecuteAdd($source)
	{
		global $plugins;

		//Get an instance of the plugin class
		echo("---- Getting instance of $source plugin<br>");
		$factory = "{$source}Factory";
		$plugin = $factory();

		//Create a configuration group for this plugin
		Zymurgy::$db->run("insert into zcm_pluginconfiggroup (name) values ('".
			Zymurgy::$db->escape_string($plugin->GetTitle()).": Default')");
		$pcg = Zymurgy::$db->insert_id();

		//Add plugin to the plugin table
		echo("---- Adding plugin definition to database<br>");
		Zymurgy::$db->query("insert into zcm_plugin(title,name,uninstallsql,enabled,defaultconfig) values ('".
			Zymurgy::$db->escape_string($plugin->GetTitle())."','".
			Zymurgy::$db->escape_string($source)."','".
			Zymurgy::$db->escape_string($plugin->GetUninstallSQL())."',1,$pcg)");
		$id = Zymurgy::$db->insert_id();
		//	$id = 7;

		//Add default configuration
		echo("---- Retrieving default plugin configuration<br>");
		$defconf = $plugin->GetDefaultConfig();

		//	print_r($defconf);
		//	echo("<br><br><br>");
		//	die();

		foreach ($defconf as $cv)
		{
			//echo("cv: ");
			//print_r($cv);
			//echo("<br>");

			$key = $cv->key;
			$value = $cv->value;

			// echo($key.": ".$value."<br>");
			echo("------ $key<br>");

			$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) values ($pcg, '".
				Zymurgy::$db->escape_string($key)."', '".
				Zymurgy::$db->escape_string($value)."')";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
				die("Error adding plugin config: ".Zymurgy::$db->error()."<br>$sql");

			// echo(htmlentities($sql)."<br>");
		}

		// die();

		echo("---- Initializing plugin<br>");

		$plugin->Initialize();
	}

	function DisplayPluginList($ri, $action, $error = "")
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
		if($action == "addlist")
		{
?>
			<h3>Add Plugin</h3>

			<?= strlen($error) > 0 ? "<p>".$error."</p>" : "" ?>

			<form name="frm" action="pluginlist.php" method="POST" enctype="multipart/form-data">
				<input type="hidden" name="action" value="act_add">
				<p>File: <input type="file" name="file"></p>

				<p><input type="submit" value="Add Plugin"></p>
			</form>
<?
		}

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