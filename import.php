<?php
	/**
	 * Import page export XML files, custom table export XML files, or
	 * Zymurgy:CM extension Zip files.
	 *
	 * @package Zymurgy
	 * @subpackage import-export
	 */

//	die();
	ini_set("display_errors", 1);
	$breadcrumbTrail = "Import Content";

	include_once("header.php");
	include_once("InputWidget.php");
	include_once("customlib.php");

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		if(isset($_FILES["file"]))
		{
			echo("Processing files<br>");

			switch(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION))
			{
				case "xml":
					$file = file_get_contents($_FILES["file"]["tmp_name"]);

	//				echo htmlentities($file);

	//				echo("Pages: ".strpos($file, "<pages>")."<br>");
	//				echo("Custom Table: ".strpos($file, "<customtable>")."<br>");

					if(strpos($file, "<pages>") > 0)
					{
						ImportPage($file);
					}
					else if(strpos($file, "<customtable>") > 0)
					{
						ImportCustomTable($file);
					}
					else
					{
						echo "Unsupported XML document.";
					}

					break;

				case "zip":
					ProcessZip();
					break;

				default:
					echo "Unsupported file type.";
			}

			echo "Import complete<br>";
		}
	}
	else
	{
		DisplayImportForm();
	}

	include_once("footer.php");

	/**
	 * Process the Zip file sent via the file upload control.
	 *
	 */
	function ProcessZip()
	{
		$zipfile = zip_open($_FILES["file"]["tmp_name"]);
		$xml = "";
		$customtableXML = "";

		while($entry = zip_read($zipfile))
		{
			if(zip_entry_name($entry) == "content.xml")
			{
				$xml = zip_entry_read($entry, zip_entry_filesize($entry));
				// break;
			}
			else if(zip_entry_name($entry) == "customtable.xml")
			{
				$customtableXML = zip_entry_read($entry, zip_entry_filesize($entry));
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
				else if(strpos($filename, "zymurgy/plugins") === 0
					&& strpos($filename, ".php") > 0)
				{
					// This is probably a plugin - try to install it

					file_put_contents(
						Zymurgy::$root."/".$filename,
						zip_entry_read($entry, zip_entry_filesize($entry)));
					include_once(Zymurgy::$root."/".$filename);

					list($name,$extension) = explode('.',$filename);
					ExecuteAdd($name);
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

		echo("Page content processed.<br><br>Importing custom tables.<br>");

		if(strlen($customtableXML) > 0)
		{
			ImportCustomTable($customtableXML);
		}

		echo("Custom tables processed.<br><br>");
	}

	/**
	 * Display the Import Content input form.
	 *
	 */
	function DisplayImportForm()
	{
?>
	<p>This section allows you to install Zymurgy:CM add-ons.</p>

	<p>This section may also be used to migrate Zymurgy:CM Custom Tables from
	one installation to another.</p>

	<p><b>Before Importing</b></p>

	<ul>
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

	/**
	 * Import the specified page, based on the XML content.
	 *
	 * @param string $pageXML
	 */
	function ImportPage($pageXML)
	{
		$pageXML = simplexml_load_string($pageXML, "SimpleXMLElement", LIBXML_NOCDATA);

		foreach($pageXML->page as $page)
		{
			$oldPath = (string) $page->fullpath;
			$newPath = $oldPath;

			$templateID = GetTemplate($page->template[0]);

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
		}
	}

	/**
	 * Insert the given flavoured text
	 *
	 * @param string $text
	 * @return The ID of the new flavoured text record
	 */
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

	/**
	 * Insert the given page text
	 *
	 * @param int $pageID The ID of the page the text is a part of
	 * @param string $tag The name of the body content item being added
	 * @param string $body The body content to insert
	 * @param int $acl The ID of the ACL to apply to the content
	 */
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

	/**
	 * Get the ID of the parent page, based on the path of the child page
	 *
	 * @param string $path The path of the child page
	 * @return string The ID of the parent page
	 */
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

	/**
	 * Get the ID of the template based on the template's name.
	 *
	 * @param string $templateName The name of the template
	 * @return int The ID of the template
	 */
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

	/**
	 * Get the ID of an ACL based on the ACL's name.
	 *
	 * @param string $aclName The name of the ACL
	 * @return int the ID of the ACL
	 */
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

	/**
	 * Import a page gadget
	 *
	 * @param string $gadget The name of the gadget
	 * @param int $pageID The ID of the page to insert the gadget into
	 * @param int $gadgetCount The current number of gadgets on the page
	 */
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

	/**
	 * Import a custom table, given the Export XML string
	 *
	 * @param string $xml
	 */
	function ImportCustomTable($xml)
	{
		$tableXML = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);

//		echo("<pre>".print_r($tableXML, true)."</pre>");

		$tableIDMap = array(0 => 0);

		foreach($tableXML->table as $table)
		{
			echo("-- Table: ".$table->name."<br>");

			$sql = "SELECT `id` FROM `zcm_customtable` WHERE `tname` = '".
				Zymurgy::$db->escape_string($table->name).
				"'";
			$newTableID = Zymurgy::$db->get($sql);

			if($newTableID <= 0)
			{
				$newTableID = CreateCustomTable(
					$table->name,
					$table->linkname,
					$table->displayorder,
					$table->memberdata,
					$table->selfref,
					$tableIDMap[intval($table->detailfor)]);
			}

			$tableIDMap[intval($table->id)] = $newTableID;
			$fieldIndex = 1;

			foreach($table->fields as $fieldXML)
			{
//				echo("<pre>".print_r($fieldXML->field, true)."</pre>");
				$field = $fieldXML->field;

				echo("---- Field: ".$field->name."<br>");

				$sql = "SELECT `id` FROM `zcm_customfield` WHERE `tableid` = '".
					Zymurgy::$db->escape_string($newTableID).
					"' AND `cname` = '".
					Zymurgy::$db->escape_string($field->name).
					"'";
				$newFieldID = Zymurgy::$db->get($sql);

				if($newFieldID <= 0)
				{
					$newFieldID = CreateCustomField(
						$newTableID,
						$field->name,
						$field->gridheader,
						$field->caption,
						$field->inputspec,
						$field->indexed,
						$fieldIndex);
				}

				$fieldIndex++;
			}

			foreach($table->rows as $rowXML)
			{
				$row = $rowXML->row;
				$fields = array();

				foreach($row->children() as $child)
				{
					$fields[$child->getName()] = Zymurgy::$db->escape_string((string) $child);
				}

				$sql = "INSERT INTO `{$table->name}` ( `".
					implode("`, `", array_keys($fields)).
					"` ) VALUES ( '".
					implode("', '", $fields).
					"' ) ON DUPLICATE KEY UPDATE id = id";
//				echo($sql."<br>");
				Zymurgy::$db->query($sql)
					or die("Could not insert data: ".Zymurgy::$db->error().", $sql");
			}
		}
	}

	/**
	 * Create a custom table.
	 *
	 * @param string $tableName
	 * @param string $navName
	 * @param bool $hasdisporder
	 * @param bool $ismember
	 * @param string $selfref
	 * @param int $detailfor
	 * @return int The ID of the new custom table, as set in the zcm_customtable table
	 */
	function CreateCustomTable(
		$tableName,
		$navName,
		$hasdisporder,
		$ismember,
		$selfref,
		$detailfor)
	{
		echo("---- Creating<br>");

		$newTableID = -1;

		$okname = okname($tableName);
		if ($okname!==true)
		{
			return $okname;
		}
		//Try to create table
		$sql = "create table `{$tableName}` (id bigint not null auto_increment primary key";
		if ($detailfor>0)
		{
			$tbl = gettable($detailfor);
			$sql .= ", `{$tbl['tname']}` bigint, key `{$tbl['tname']}` (`{$tbl['tname']}`)";
		}
		if ($hasdisporder == 1)
		{
			$sql .= ", disporder bigint, key disporder (disporder)";
		}
		if ($ismember == 1)
		{
			$sql .= ", member bigint, key member (member)";
		}
		$sql .= ")";
		$ri = mysql_query($sql) or die("Unable to create table ($sql): ".mysql_error());
		if (!$ri)
		{
			$e = mysql_errno();
			switch ($e)
			{
				case 1050:
					return "The table {$tableName} already exists.  Please select a different name.";
				default:
					return "<p>SQL error $e trying to create table: ".mysql_error()."</p>";
			}
			return false;
		}
		if (strlen($selfref) > 0)
		{
			Zymurgy::$db->run("alter table `{$tableName}` add selfref bigint default 0");
			Zymurgy::$db->run("alter table `{$tableName}` add index(selfref)");
		}

		$sql = "INSERT INTO `zcm_customtable` ( `tname`, `detailfor`, `hasdisporder`, `ismember`, `navname`, `selfref` ) VALUES ( '".
			Zymurgy::$db->escape_string($tableName).
			"', '".
			Zymurgy::$db->escape_string($detailfor).
			"', '".
			Zymurgy::$db->escape_string($hasdisporder).
			"', '".
			Zymurgy::$db->escape_string($ismember).
			"', '".
			Zymurgy::$db->escape_string($navName).
			"', '".
			Zymurgy::$db->escape_string($selfref).
			"' )";
		Zymurgy::$db->query($sql)
			or die("Could not record custom table: ".Zymurgy::$db->error().", $sql");
		$newTableID = Zymurgy::$db->insert_id();

		return $newTableID;
	}

	/**
	 * Create a field for a custom table.
	 *
	 * @param int $tableID
	 * @param string $fieldName
	 * @param string $gridheader
	 * @param string $caption
	 * @param string $inputspec
	 * @param bool $isIndexed
	 * @param int $disporder
	 * @return int The ID of the new field, as set in the zcm_customtable table
	 */
	function CreateCustomField(
		$tableID,
		$fieldName,
		$gridheader,
		$caption,
		$inputspec,
		$isIndexed,
		$disporder)
	{
		echo("------ Creating<br>");

		$okname = okname($fieldName);
		if ($okname!==true)
		{
			return $okname;
		}
		$tbl = gettable($tableID);
		$sqltype = inputspec2sqltype($inputspec);
		$sql = "alter table `{$tbl['tname']}` add `{$fieldName}` $sqltype";
		mysql_query($sql) or die("Unable to add column ($sql): ".mysql_error());
		if ($isIndexed == 1)
		{
			$sql = "alter table `{$tbl['tname']}` add index(`{$fieldName}`)";
		}

		$sql = "INSERT INTO `zcm_customfield` ( `disporder`, `tableid`, `cname`, `inputspec`, `caption`, `indexed`, `gridheader` ) VALUES ( '".
			Zymurgy::$db->escape_string($disporder).
			"', '".
			Zymurgy::$db->escape_string($tableID).
			"', '".
			Zymurgy::$db->escape_string($fieldName).
			"', '".
			Zymurgy::$db->escape_string($inputspec).
			"', '".
			Zymurgy::$db->escape_string($caption).
			"', '".
			Zymurgy::$db->escape_string($isIndexed == 1 ? "Y" : "N").
			"', '".
			Zymurgy::$db->escape_string($gridheader).
			"' )";
		Zymurgy::$db->query($sql)
			or die("Could not record field: ".Zymurgy::$db->error().", $sql");

		return true;
	}
?>