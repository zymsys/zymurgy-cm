<?php
//	die();
	ini_set("display_errors", 1);
	$breadcrumbTrail = "Import Content";

	include_once("header.php");
	include_once("InputWidget.php");

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		echo("Processing files<br>");

		$zipfile = zip_open($_FILES["file"]["tmp_name"]);
		$xml = "";

		while($entry = zip_read($zipfile))
		{
			if(zip_entry_name($entry) == "content.xml")
			{
				$xml = zip_entry_read($entry, zip_entry_filesize($entry));
				// break;
			}
			else
			{
				$filename = zip_entry_name($entry);

				echo("-- ".$filename."<br>");
				if(substr($filename, -1) == "/")
				{
					if(!file_exists(Zymurgy::$root."/".$filename))
					{
						mkdir(Zymurgy::$root."/".$filename);
					}
				}
				else
				{
					if(!file_exists(Zymurgy::$root."/".$filename))
					{
						file_put_contents(
							Zymurgy::$root."/".$filename,
							zip_entry_read($entry, zip_entry_filesize($entry)));
					}
					else
					{
						echo("---- File exists. Skipping.<br>");
					}
				}
			}
		}

		echo("Files processed.<br><br>Importing page content.<br>");

		if(strlen($xml) > 0)
		{
			ImportPage($xml);
		}

		echo("Page content imported.<br><br>Import complete.<br>");
	}
	else
	{
		DisplayImportForm();
	}

	include_once("footer.php");

	function DisplayImportForm()
	{
?>
	<p>This section allows you to import Pages from one Zymurgy:CM installation
	into another.</p>

	<p><b>Before Importing</b></p>

	<ul style="margin-left: 120px;">
		<li>Make sure that all of the templates used for the content being imported are installed on this copy of Zymurgy:CM.</li>
		<li>Make sure that all of the ACLs used for the content being imported are installed on this copy of Zymurgy:CM. The ACLs do not have to reference the same groups, but must have the same name.</li>
	</ul>

	<form name="frm" method="POST" enctype="multipart/form-data">
		<table>
			<tr>
				<td>Add-on File:</td>
				<td><input type="file" name="file"></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input type="submit" value="Start Import"></td>
			</tr>
		</table>
	</form>
<?
	}

	function ImportPage($pageXML)
	{
		$pageXML = simplexml_load_string($pageXML, "SimpleXMLElement", LIBXML_NOCDATA);

		foreach($pageXML->page as $page)
		{
			$oldPath = (string) $page->fullpath;
			$newPath = $oldPath;
//			if(substr($oldPath, 0, 1) == "/")
//			{
//				$oldPath = substr($oldPath, 1);
//			}
//			$newPath = preg_replace("/".$theOldPath."/", $theNewPath, $oldPath, 1);

//			echo("-- $oldPath<br>-- $newPath<br><br>");
//			echo("<pre>".print_r($page, true)."</pre>");

			$templateID = GetTemplate($page->template[0]);

//			$linkTextID = InsertFlavourText(
//				str_replace($oldPath, $newPath, $page->linktext[0]));
//			$linkURLID = InsertFlavourText(
//				str_replace($oldPath, $newPath, $page->linkurl[0]));

			$linkTextID = InsertFlavourText($page->linktext[0]);
			$linkURLID = InsertFlavourText($page->linkurl[0]);

			$sql = "INSERT INTO `zcm_sitepage` ( `disporder`, `linktext`, `linkurl`, `parent`, `retire`, `golive`, `softlaunch`, `template`, `acl` ) VALUES ( '{0}', '{1}', '{2}', '{3}', '{4}', '{5}', '{6}', '{7}', '{8}')";

			$sql = str_replace("{0}", $page->disporder[0], $sql);
			$sql = str_replace("{1}", $linkTextID, $sql);
			$sql = str_replace("{2}", $linkURLID, $sql);
			$sql = str_replace("{3}", GetParentIDFromPath($newPath), $sql);
			$sql = str_replace("{4}", $page->retire[0], $sql);
			$sql = str_replace("{5}", $page->golive[0], $sql);
			$sql = str_replace("{6}", $page->softlaunch[0], $sql);
			$sql = str_replace("{7}", $templateID, $sql);
			$sql = str_replace("{8}", GetACL($page->acl[0]), $sql);

//			echo($sql."<br>");

			Zymurgy::$db->query($sql)
				or die("Could not insert page: ".Zymurgy::$db->error().", $sql");
			$pageID = Zymurgy::$db->insert_id();

			foreach($page->content[0]->block as $block)
			{
				$sql = "SELECT `inputspec` FROM `zcm_templatetext` WHERE `template` = '".
					Zymurgy::$db->escape_string($templateID).
					"' AND `tag` = '".
					Zymurgy::$db->escape_string((string) $block["name"]).
					"'";
	//			echo($sql."<br>");
				$inputspec = Zymurgy::$db->get($sql);
				$ep = explode(".", $inputspec);
				$widget = InputWidget::Get($ep[0]);

	//			die("<pre>".print_r($block, true)."</pre>");

				if($widget->SupportsFlavours())
				{
//					echo "---- ".((string) $block["name"]).": Flavoured widget detected.<br>";

					$textID = InsertFlavourText((string) $block[0]);
					InsertPageText($pageID, ((string) $block["name"]), $textID, GetACL($page->acl[0]));
				}
				else
				{
//					echo "---- ".((string) $block["name"]).": Non-flavoured widget detected.<br>";

					InsertPageText($pageID, ((string) $block["name"]), ((string) $block[0]), GetACL($page->acl[0]));
				}
			}

			$gadgetXML = $page->xpath("//gadgets/gadget");
			$gadgetCount = 1;

			foreach($gadgetXML as $gadget)
			{
				ImportGadget($gadget, $pageID, $gadgetCount);

				$gadgetCount++;
			}

//			$children = array();
//			$childrenXML = $page->xpath("//children/child");

//			foreach($childrenXML as $child)
//			{
	//			echo("<pre>");
	//			print_r($child);
	//			echo("</pre>");

//				$children[] = array(
//					"parentid" => $pageID,
//					"pageid" => intval($child["childid"]));
//			}
		}

//		return $children;
	}

	function InsertFlavourText($text)
	{
		$sql = "INSERT INTO `zcm_flavourtext` ( `default` ) VALUES ( '".
			Zymurgy::$db->escape_string($text).
			"')";
//		echo($sql."<br>");
		Zymurgy::$db->query($sql)
			or die("Could not add linktext: ".Zymurgy::$db->error().", $sql");

		return Zymurgy::$db->insert_id();
	}

	function InsertPageText($pageID, $tag, $body, $acl)
	{
		$sql = "INSERT INTO `zcm_pagetext` ( `sitepage`, `tag`, `body`, `acl` ) VALUES ( '".
			Zymurgy::$db->escape_string($pageID).
			"', '".
			Zymurgy::$db->escape_string($tag).
			"', '".
			Zymurgy::$db->escape_string($body).
			"', '".
			Zymurgy::$db->escape_string($acl).
			"' )";
		Zymurgy::$db->query($sql)
			or die("Could not add page text block: ".Zymurgy::$db->error().", $sql");
	}

	function GetParentIDFromPath($path)
	{
		echo("-- $path<br>");

		$parent = 0;
		$splitpath = explode("/", $path);

		// Ignore the last item in the array
		array_pop($splitpath);

		foreach($splitpath as $navpart)
		{
			$sql = "SELECT `id` FROM `zcm_sitepage` WHERE `parent` = '".
				Zymurgy::$db->escape_string($parent).
				"' AND ( `linkurl` = '".
				Zymurgy::$db->escape_string($navpart).
				"' OR EXISTS( SELECT 1 FROM `zcm_flavourtext` WHERE `default` = '".
				Zymurgy::$db->escape_string($navpart).
				"' AND `zcm_flavourtext`.`id` = `zcm_sitepage`.`linkurl` ) )";
//			echo($sql."<br>");
			$parent = Zymurgy::$db->get($sql);

			if($parent <= 0)
			{
				$parent = -1;
				break;
			}
		}

		return $parent;
	}

	function GetTemplate($templateName)
	{
		$sql = "SELECT `id` FROM `zcm_template` WHERE `name` = '".
			Zymurgy::$db->escape_string($templateName).
			"'";
		$templateID = Zymurgy::$db->get($sql);

		if($templateID <= 0)
		{
			echo("Warning: Template $templateName is not installed on this copy of Zymurgy:CM. Data may not be migrated properly. If this occurs, add the $templateName template to this installation, and try the import again.<br>");

			$templateID = 1;
		}

		return $templateID;
	}

	function GetACL($aclName)
	{
		$sql = "SELECT `id` FROM `zcm_acl` WHERE `name` = '".
			Zymurgy::$db->escape_string($aclName).
			"'";
		$acl = Zymurgy::$db->get($sql);


		if($acl <= 0 && strlen($aclName) > 0)
		{
			echo("Warning: ACL $aclName is not installed on this copy of Zymurgy:CM. ACL will not be applied to this document.<br>");

			$acl = 0;
		}

		return $acl;

	}

	function ImportGadget($gadget, $pageID, $gadgetCount)
	{
		$pluginParts = explode("&", urldecode((string) $gadget["name"]));

		$sql = "SELECT `id` FROM `zcm_plugininstance` WHERE `name` = '".
			Zymurgy::$db->escape_string($pluginParts[1]).
			"' AND EXISTS(SELECT 1 FROM `zcm_plugin` WHERE `name` = '".
			Zymurgy::$db->escape_string($pluginParts[0]).
			"' AND `zcm_plugininstance`.`plugin` = `zcm_plugin`.`id`)";
		$instanceID = Zymurgy::$db->get($sql);

		if($instanceID <= 0)
		{
			echo("---- Gadget ".((string) $gadget["name"])." not found. Creating.<br>");

			$configGroupName = $pluginParts[0].": ".$pluginParts[1];


			$sql = "INSERT INTO `zcm_pluginconfiggroup` ( `name`) VALUES ('".
				Zymurgy::$db->escape_string($configGroupName).
				"')";
			Zymurgy::$db->query($sql)
				or die("Could not create plugin config group: ".Zymurgy::$db->error().", $sql");
			$configGroupID = Zymurgy::$db->insert_id();

			foreach($gadget->config as $configItem)
			{
				$key = ((string) $configItem["name"]);
				$value = ((string) $configItem[0]);

				$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) VALUES ( '".
					Zymurgy::$db->escape_string($configGroupID).
					"', '".
					Zymurgy::$db->escape_string($key).
					"', '".
					Zymurgy::$db->escape_string($value).
					"')";
				Zymurgy::$db->query($sql)
					or die("Could not create plugin config item: ".Zymurgy::$db->error().", $sql");
			}

			$sql = "INSERT INTO `zcm_plugininstance` ( `plugin`, `name`, `private`, `config` ) SELECT `id`, '".
				Zymurgy::$db->escape_string($pluginParts[1]).
				"', '0', '".
				Zymurgy::$db->escape_string($configGroupID).
				"' FROM `zcm_plugin` WHERE `name` = '".
				Zymurgy::$db->escape_string($pluginParts[0]).
				"' LIMIT 0, 1";
			Zymurgy::$db->query($sql)
				or die("Could not create plugin instance: ".Zymurgy::$db->error().", $sql");
		}

		$sql = "INSERT INTO `zcm_sitepageplugin` ( `zcm_sitepage`, `disporder`, `plugin`, `align`, `acl` ) VALUES ('".
			Zymurgy::$db->escape_string($pageID).
			"', '".
			Zymurgy::$db->escape_string($gadgetCount).
			"', '".
			Zymurgy::$db->escape_string((string) $gadget["name"]).
			"', '".
			Zymurgy::$db->escape_string((string) $gadget["align"]).
			"', '".
			Zymurgy::$db->escape_string(GetACL((string) $gadget["acl"])).
			"')";
		Zymurgy::$db->query($sql)
			or die("Could not associate plugin with page: ".Zymurgy::$db->error().", $sql");
	}
?>