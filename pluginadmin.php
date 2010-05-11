<?
/**
 * Management screen for instances of a given plugin.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
$pid = 0+$_GET['pid'];
if (array_key_exists('iid',$_GET))
	$iid = 0 +$_GET['iid'];
else
	$iid = 0;

require_once 'cmo.php';

$title = Zymurgy::$db->get("select title from zcm_plugin where id=$pid");
$pluginname = Zymurgy::$db->get("select name from zcm_plugin where id=$pid");

if (array_key_exists('delkey',$_GET))
{
	$delkey = 0 + $_GET['delkey'];
	$instancename = Zymurgy::$db->get("select name from zcm_plugininstance where id=$delkey");
	$pi = Zymurgy::mkplugin($pluginname,$instancename);
	$pi->RemoveInstance();
	Zymurgy::$db->query("delete from zcm_plugininstance where id=$delkey");
}

if(array_key_exists("duplicatekey", $_POST))
{
	$duplicatekey = 0 + $_POST["duplicatekey"];
	$oldName = Zymurgy::$db->get("select name from zcm_plugininstance where id=$duplicatekey");
	$newName = $_POST["newname"];
	$oldpi = Zymurgy::mkplugin($pluginname, $oldName);
	$pi = Zymurgy::mkplugin($pluginname, $newName);

	$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) SELECT '".
		Zymurgy::$db->escape_string($pi->configid).
		"', `key`, `value` FROM `zcm_pluginconfigitem` WHERE `config` = '".
		Zymurgy::$db->escape_string($oldpi->configid).
		"'";
//	die($sql);
	Zymurgy::$db->query($sql)
		or die("Could not duplicate settings: ".Zymurgy::$db->error().", $sql");

	if(method_exists($pi, "Duplicate"))
	{
		$pi->Duplicate($oldpi);
	}
}

$wikiArticleName = "Plugin_Management";

$breadcrumbTrail = "<a href=\"plugin.php\">Plugin Management</a> &gt; ";
if ($iid)
{
	$breadcrumbTrail .= "<a href=\"pluginadmin.php?pid=$pid\">$title Instances</a> &gt; {$_GET['name']}";
}
else
	$breadcrumbTrail .= "$title Instances";

require_once('header.php');
require_once('datagrid.php');

DumpDataGridCSS();

?>
<script type="text/javascript">
	var duplicateForm;

	YAHOO.util.Event.onAvailable("duplicate", function() {
		var handleSubmit = function()
		{
			document.duplicate.submit();
		}

		var handleCancel = function()
		{
			this.cancel();
		}

		duplicateForm = new YAHOO.widget.Dialog(
			"duplicate",
			{
				width: "400px",
//				height: "300px",
				fixedcenter: true,
				visible: false,
				constraintoviewport: true,
				buttons: [
					{ text: "Duplicate", handler: handleSubmit, isDefault: true },
					{ text: "Cancel", handler: handleCancel }
				]
			});

		duplicateForm.render();
	});

	function DisplayDuplicateForm(pid, iid, oldname)
	{
		document.getElementById("pid").value = pid;
		document.getElementById("duplicatekey").value = iid;
		document.getElementById("newname").value = oldname + " Copy";

		duplicateForm.show();
	}
</script>
<?php

if (($pid > 0) && ($iid > 0))
{ // We have both a plugin and an instance, load its config and render it.
	$sql = "SELECT `zcm_plugin`.`name`, `zcm_plugin`.`title`, `zcm_plugin`.`release`, COALESCE(`config`, `defaultconfig`) AS `config` FROM `zcm_plugin` LEFT JOIN `zcm_plugininstance` ON `zcm_plugininstance`.`plugin` = `zcm_plugin`.`id` AND `zcm_plugininstance`.`id` = '".
		Zymurgy::$db->escape_string($iid).
		"' WHERE `zcm_plugin`.`id` = '".
		Zymurgy::$db->escape_string($pid).
		"'";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Error loading plugin info: ".Zymurgy::$db->error()."<br>$sql");
	$plugin=Zymurgy::$db->fetch_array($ri);
	if ($plugin===false) die("No plugin with that pid available.");

	require_once('PluginBase.php');
	require_once("plugins/{$plugin['name']}.php");
	$fn = "{$plugin['name']}Factory";
	$pi = $fn();
	$pi->pid = $pid;
	$pi->iid = $iid;
	$pi->configid = $plugin["config"];
	$pi->InstanceName = $_GET['name'];
	$pi->GetDefaultConfig();
	Zymurgy::LoadPluginConfig($pi);
	$pi->RenderAdmin();

//	echo("<pre>");
//	print_r($pi);
//	echo("</pre>");

	if (!$hasdumpeddatagridcss)
	{
		DumpDataGridCSS();
		$hasdumpeddatagridcss = true;
	}
	$pi->RenderCommandMenu();

	// echo "<p><a href=\"pluginconfig.php?plugin=$pid&instance=$iid\">Configuration Options</a></p>";
}
else
{
	if($_SERVER['REQUEST_METHOD'] == "POST" && array_key_exists("instancename", $_POST))
	{
		Zymurgy::mkplugin($pluginname, $_POST["instancename"]);
	}

	$sql = "select id,name from zcm_plugininstance where (`private`=0) and (plugin=$pid)";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Error loading instance info: ".Zymurgy::$db->error()."<br>$sql");
	if (Zymurgy::$db->num_rows($ri)==0)
		echo "There are no instances of this plugin yet.";
	else
	{
		if (array_key_exists('autoskip',$_GET) && (Zymurgy::$db->num_rows($ri)==1))
		{
			$row=Zymurgy::$db->fetch_array($ri);
			$redirect = "pluginadmin.php?pid=$pid&iid={$row['id']}&name=".urlencode($row['name']);
			//echo "Redirecting to $redirect";
			header("Location: $redirect");
			exit;
		}
?>
	<table class="DataGrid" cellspacing="0" cellpadding="3" bordercolor="#999999" border="1" rules="cols">
		<tr class="DataGridHeader">
			<td><?= $title ?> Instance</td>
			<td>&nbsp;</td>
<?php
	if($zauth->authinfo['admin'] >= 2)
	{
?>
			<td>&nbsp;</td>
<?php
	}
?>
			<td>&nbsp;</td>
		</tr>
<?php
		$cntr = 0;

		while (($row=Zymurgy::$db->fetch_array($ri))!==false)
		{
			$cntr++;
			// echo "<a href=\"pluginadmin.php?pid=$pid&iid={$row['id']}&name=".urlencode($row['name'])."\">{$row['name']}</a><br>";
?>
		<tr class="DataGridRow<?= $cntr % 2 == 0 ? "Alternate" : "" ?>">
			<td><?= $row['name'] ?></td>
			<td><a href="pluginadmin.php?pid=<?= $pid ?>&amp;iid=<?= $row['id'] ?>&amp;name=<?= urlencode($row['name']) ?>">Edit</a></td>
<?php
	if($zauth->authinfo['admin'] >= 2)
	{
?>
			<td><a href="pluginadmin.php?pid=<?= $pid ?>&amp;delkey=<?= $row['id'] ?>" onclick="return confirm_delete();">Delete</a></td>
<?php
	}
?>
			<td><a href="javascript:;" onclick="DisplayDuplicateForm(<?= $pid ?>, <?= $row['id'] ?>, '<?= $row['name'] ?>');">Duplicate</a></td>
		</tr>
<?php
		}
?>
	</table>
<?php
	}

	if($zauth->authinfo['admin'] >= 1)
	{
?>

	<h2>Add Instance</h2>

	<form name="add" method="POST">
		<table>
			<tr>
				<td>Name:</td>
				<td><input type="text" name="instancename" value=""></td>
			</tr>
		</table>

		<p><input type="submit" value="Add Instance"></p>
	</form>

	<div class="yui-skin-sam">
		<div id="duplicate">
			<div class="hd">Duplicate <?= $title ?> Instance</div>
			<div class="bd">
				<form name="duplicate" method="POST" action="pluginadmin.php?pid=<?= $pid ?>">
					<input type="hidden" id="pid" name="pid" value="">
					<input type="hidden" id="duplicatekey" name="duplicatekey" value="">
					<table>
						<tr>
							<td>New Name:</td>
							<td><input type="text" name="newname" id="newname" size="20" maxlength="50" value=""></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
<?
	}
}

require_once('footer.php');
?>
