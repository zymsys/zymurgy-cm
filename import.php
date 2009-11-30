<?php
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

		while(count($pagesToImport) > 0)
		{
//			echo(count($pagesToImport)."<br>");
			$pageID = array_pop($pagesToImport);
//			echo(count($pagesToImport)."<br>");
//			die();
			if(!is_array($pageID)) break;

			$page = RetrievePageByID(
				$_POST["domain"],
				$_POST["user"],
				$_POST["pass"],
				$pageID["pageid"]);
			$pageXML = new SimpleXMLElement($page);

//			echo("<pre>");
//			print_r(htmlentities($page));
//			echo("</pre>");

			$oldPath = (string) $pageXML->fullpath;
//			echo($oldPath."<br>");
			$newPath = preg_replace("/".$_POST["oldpath"]."/", $_POST["newpath"], ((string) $oldPath), 1);

			$childrenToImport = ImportPage(
				$page,
				$oldPath,
				$newPath,
				GetParentIDFromPath($newPath));

			$pagesToImport = array_merge($childrenToImport, $pagesToImport);
		}

		echo("<br>Import complete.<br>");
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

	function RetrievePageByPath($domain, $user, $pass, $path)
	{
		$url = "http://".$domain."/zymurgy/contentexport.php?user=".$user."&pass=".$pass."&path=".$path;
//		die($url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$httpResponse = curl_exec($ch);

		return $httpResponse;
	}

	function RetrievePageByID($domain, $user, $pass, $id)
	{
		$url = "http://".$domain."/zymurgy/contentexport.php?user=".$user."&pass=".$pass."&pageid=".$id;
//		die($url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$httpResponse = curl_exec($ch);

		return $httpResponse;
	}

	function ImportPage($pageXML, $oldPath, $newPath, $parentID)
	{
//		$page = new SimpleXMLElement($pageXML);
		$page = simplexml_load_string($pageXML, "SimpleXMLElement", LIBXML_NOCDATA);

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
		$sql = str_replace("{3}", $parentID, $sql);
		$sql = str_replace("{4}", $page->retire[0], $sql);
		$sql = str_replace("{5}", $page->golive[0], $sql);
		$sql = str_replace("{6}", $page->softlaunch[0], $sql);
		$sql = str_replace("{7}", $templateID, $sql);
		$sql = str_replace("{8}", GetACL($page->acl[0]), $sql);

//		echo($sql."<br>");

		Zymurgy::$db->query($sql)
			or die("Could not insert page: ".Zymurgy::$db->error().", $sql");
		$pageID = Zymurgy::$db->insert_id();

		$contentXML = $page->xpath("//content/block");

		foreach($contentXML as $block)
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
				echo "---- ".((string) $block["name"]).": Flavoured widget detected.<br>";

				$textID = InsertFlavourText((string) $block[0]);
				InsertPageText($pageID, ((string) $block["name"]), $textID, GetACL($page->acl[0]));
			}
			else
			{
				echo "---- ".((string) $block["name"]).": Non-flavoured widget detected.<br>";

				InsertPageText($pageID, ((string) $block["name"]), ((string) $block[0]), GetACL($page->acl[0]));
			}

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

		return $children;
	}

	function InsertFlavourText($text)
	{
		$sql = "INSERT INTO `zcm_flavourtext` ( `default` ) VALUES ( '".
			Zymurgy::$db->escape_string($text).
			"')";
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
?>