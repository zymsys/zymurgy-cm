<?php
	/**
	 * Migrates content from one Zymurgy:CM installation to another.
	 *
	 * @package Zymurgy
	 * @subpackage import-export
	 */

//	die();
	ini_set("display_errors", 1);
	$breadcrumbTrail = "Import Content";

	include_once("header.php");
	include_once("InputWidget.php");

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		echo("Processing<br>");

		$page = RetrievePageByPath(
			$_POST["domain"],
			$_POST["user"],
			$_POST["pass"],
			$_POST["oldpath"]);

//		echo($page);

		$pagesToImport = ImportPage(
			$page,
			$_POST["oldpath"],
			$_POST["newpath"],
			GetParentIDFromPath($_POST["newpath"]));
//		print_r($pagesToImport);

//		echo(count($pagesToImport));
//		die();

		echo("<br>Import complete.<br>");
	}
	else
	{
		DisplayImportForm();
	}

	include_once("footer.php");

	/**
	 * Display the migration input form.
	 *
	 */
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

	<form name="frm" method="POST">
		<table>
			<tr>
				<td>Domain:</td>
				<td><input type="text" name="domain" size="30" maxlength="100"></td>
			</tr>
			<tr>
				<td>Zymurgy:CM Username:</td>
				<td><input type="text" name="user" size="15" maxlength="30"></td>
			</tr>
			<tr>
				<td>Zymurgy:CM Password:</td>
				<td><input type="password" name="pass" size="15" maxlength="30"></td>
			</tr>
			<tr>
				<td>Path of Root Node:</td>
				<td>pages/<input type="text" name="oldpath" size="30" maxlength="200"></td>
			</tr>
			<tr>
				<td>Path to Import Into:</td>
				<td>pages/<input type="text" name="newpath" size="30" maxlength="200"></td>
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
	 * Retrieve the XML content for a page/tree by the path on the original server.
	 *
	 * @param unknown_type $domain
	 * @param unknown_type $user
	 * @param unknown_type $pass
	 * @param unknown_type $path
	 * @return unknown
	 */
	function RetrievePageByPath($domain, $user, $pass, $path)
	{
		$url = "http://".$domain."/zymurgy/contentexport.php"; // ?user=".$user."&pass=".$pass."&path=".$path;
//		die($url);

		$postFields = array(
			"user" => $user,
			"pass" => $pass,
			"path" => $path);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		$httpResponse = curl_exec($ch);

		return $httpResponse;
	}

	/**
	 * Retrieve the XML content for a page/tree by the ID on the original server.
	 *
	 * @param unknown_type $domain
	 * @param unknown_type $user
	 * @param unknown_type $pass
	 * @param unknown_type $id
	 * @return unknown
	 */
	function RetrievePageByID($domain, $user, $pass, $id)
	{
		$url = "http://".$domain."/zymurgy/contentexport.php"; // ?user=".$user."&pass=".$pass."&pageid=".$id;
//		die($url);

		$postFields = array(
			"user" => $user,
			"pass" => $pass,
			"pageid" => $id);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		$httpResponse = curl_exec($ch);

		return $httpResponse;
	}

	/**
	 * Import a page, given the specified page XML snippet.
	 *
	 * @param unknown_type $pageXML
	 * @param unknown_type $theOldPath
	 * @param unknown_type $theNewPath
	 * @param unknown_type $parentID
	 * @return unknown
	 */
	function ImportPage($pageXML, $theOldPath, $theNewPath, $parentID)
	{
//		$page = new SimpleXMLElement($pageXML);
		$pageXML = simplexml_load_string($pageXML, "SimpleXMLElement", LIBXML_NOCDATA);

		foreach($pageXML->page as $page)
		{
			$oldPath = (string) $page->fullpath;
			if(substr($oldPath, 0, 1) == "/")
			{
				$oldPath = substr($oldPath, 1);
			}
//			$newPath = preg_replace("/".$theOldPath."/", $theNewPath, ((string) $oldPath), 1);
			$newPath = preg_replace("/".$theOldPath."/", $theNewPath, $oldPath, 1);

//			echo("-- $oldPath<br>-- $newPath<br><br>");

//			$page = $pageXML->page[0];

	//		echo("<pre>".print_r($page, true)."</pre>");

			$templateID = GetTemplate($page->template[0]);

			$linkTextID = InsertFlavourText(
				str_replace($oldPath, $newPath, $page->linktext[0]));
			$linkURLID = InsertFlavourText(
				str_replace($oldPath, $newPath, $page->linkurl[0]));

			$sql = "INSERT INTO `zcm_sitepage` ( `disporder`, `linktext`, `linkurl`, `parent`, `retire`, `golive`, `softlaunch`, `template`, `acl` ) VALUES ( '{0}', '{1}', '{2}', '{3}', '{4}', '{5}', '{6}', '{7}', '{8}')";

			$sql = str_replace("{0}", $page->disporder[0], $sql);
	//		$sql = str_replace("{1}", str_replace($oldPath, $newPath, $page->linktext[0]), $sql);
	//		$sql = str_replace("{2}", str_replace($oldPath, $newPath, $page->linkurl[0]), $sql);
			$sql = str_replace("{1}", $linkTextID, $sql);
			$sql = str_replace("{2}", $linkURLID, $sql);
//			$sql = str_replace("{3}", $parentID, $sql);
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

//			$contentXML = $page->xpath("//content/block");

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

			$children = array();
			$childrenXML = $page->xpath("//children/child");

			foreach($childrenXML as $child)
			{
	//			echo("<pre>");
	//			print_r($child);
	//			echo("</pre>");

				$children[] = array(
					"parentid" => $pageID,
					"pageid" => intval($child["childid"]));
			}
		}

		return $children;
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
?>