<?php
/**
 * Management screen for the Zymurgy:CM Configuration File
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
	ini_set("display_errors", 1);
	
	require_once('cmo.php');

	$breadcrumbTrail = Zymurgy::GetLocaleString("Common.ProductName")." Configuration";
	$wikiArticleName = "Zymurgy:CM_Config";

	include_once("header.php");
	include_once("InputWidget.php");

	$groupIndex = isset($_GET["group"]) ? 0 + $_GET["group"] : 0;
	$cntr = 0;

	//Thanks to comment at http://php.net/manual/en/language.variables.external.php
	function getRealPOST() {
	    $pairs = explode("&", file_get_contents("php://input"));
	    $vars = array();
	    foreach ($pairs as $pair) {
	        $nv = explode("=", $pair);
	        $name = urldecode($nv[0]);
	        $value = urldecode($nv[1]);
	        $vars[$name] = $value;
	    }
	    return $vars;
	}

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$_POST = getRealPOST();
		foreach($_POST as $key => $value)
		{
			Zymurgy::$config[$key] = $value;
		}

		$newConfig = "<?php\n";

		foreach(Zymurgy::$config as $key => $value)
		{
			$newConfig .= "\$ZymurgyConfig[\"".
				$key.
				"\"] = \"".
				addslashes($value).
				"\";\n";
		}

		$newConfig .= "?>";

		if (@rename(
			"config/config.php",
			"config/config.backup.".date("Y.m.d.H.i.s").".php"))
		{
			file_put_contents(
				Zymurgy::$root."/zymurgy/config/config.php",
				$newConfig);
		}
		else
		{
			$pwd = getcwd();
			echo "<div style=\"background-color: #ff0000; color: #ffffff;\">Unable to backup old config file, changes have <b>NOT</b> been saved ($pwd).  Make sure PHP has write permission for the /zymurgy/config folder.</div>";
		}
	}

	$xmlstring = file_get_contents(Zymurgy::$root."/zymurgy/include/configitems.xml");
	$xml = new SimpleXMLElement($xmlstring);

	$widget = new InputWidget();

	$groups = $xml->children();
	$inputspecs = array();

	foreach($groups[$groupIndex]->children() as $item)
	{
		if(!in_array($item->inputspec, $inputspecs))
		{
			//echo($item->inputspec." not in ".print_r($inputspecs, true)."<br>\n");

			$inputspecs[] = (string) $item->inputspec;
			echo $widget->GetPretext($item->inputspec);
		}
	}
?>
<table>
	<tr>
		<td valign="top" style="border-right: 1px solid black;">
			<div style="width: 170px; margin-right: 10px;">
<?
	foreach($groups as $group)
	{
		if($cntr == $groupIndex)
		{
			echo("<b>".
				((string) $group->name).
				"</b><br>");
		}
		else
		{
			echo("<a href=\"zcmconfig.php?group=$cntr\">".
				((string) $group->name).
				"</a><br>");
		}

		$cntr++;
	}
?>
			</div>
		</td>
		<td valign="top" style="padding-left: 10px;">
			<?= (string) $groups[$groupIndex]->description ?>

			<form name="frm" method="POST">
				<table>
<?
	foreach($groups[$groupIndex]->children() as $item)
	{
		if($item->getName() == "item")
		{
			//$key = strtr((string) $item->code,'.','_');
			$key = (string) $item->code;
			$value = array_key_exists($key, Zymurgy::$config)
				? Zymurgy::$config[$key]
				: "";

			if($item->inputspec == "color")
			{
				$value = str_replace("#", "", $value);
			}

?>
					<tr>
						<td><?= (string) $item->label ?></td>
						<td><?php  $widget->Render(
								$item->inputspec,
								$item->code,
								$value) ?></td>
					</tr>
<?
		}
	}
?>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" value="Save"></td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
<?
	include_once("footer.php");
?>
