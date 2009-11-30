<?php
	include_once("cmo.php");

	$userid = $_GET["user"];
	$passwd = $_GET["pass"];

	if (Zymurgy::memberdologin($userid,$passwd))
	{
		Zymurgy::memberauthenticate();

		if(!Zymurgy::memberauthorize("Zymurgy:CM - Webmaster"))
		{
			die("User does not have required access for content export. Aborting.");
		}

		$pageid = isset($_GET["pageid"])
			? $_GET["pageid"]
			: GetPageIDFromPath($_GET["path"]);
//		die($pageid." - ".$_GET["pageid"]);

		$sql = "SELECT `disporder`, `linktext`, `linkurl`, `retire`, `golive`, `softlaunch`, `zcm_template`.`name` AS `template`, `zcm_acl`.`name` AS `acl` FROM `zcm_sitepage` INNER JOIN `zcm_template` ON `zcm_template`.`id` = `zcm_sitepage`.`template` LEFT JOIN `zcm_acl` ON `zcm_acl`.`id` = `zcm_sitepage`.`acl` WHERE `zcm_sitepage`.`id` = '".
			Zymurgy::$db->escape_string($pageid)
			."'";
		$page = Zymurgy::$db->get($sql);

		$xml = <<<XML
<?xml version="1.0"?>
<page>
	<disporder>{0}</disporder>
	<linktext>{1}</linktext>
	<linkurl>{2}</linkurl>
	<fullpath>{10}</fullpath>
	<retire>{3}</retire>
	<golive>{4}</golive>
	<softlaunch>{5}</softlaunch>
	<template>{6}</template>
	<acl>{7}</acl>
	<content>
{8}
	</content>
	<gadgets>
{11}
	</gadgets>
	<children>
{9}
	</children>
</page>
XML;

		$xml = str_replace("{0}", $page["disporder"], $xml);
		$xml = str_replace("{1}", GetFlavouredLabel($page["linktext"]), $xml);
		$xml = str_replace("{2}", GetFlavouredLabel($page["linkurl"]), $xml);
		$xml = str_replace("{3}", $page["retire"], $xml);
		$xml = str_replace("{4}", $page["golive"], $xml);
		$xml = str_replace("{5}", $page["softlaunch"], $xml);
		$xml = str_replace("{6}", $page["template"], $xml);
		$xml = str_replace("{7}", $page["acl"], $xml);
		$xml = str_replace("{8}", GetPageContent($pageid), $xml);
		$xml = str_replace("{9}", GetPageChildren($pageid), $xml);
		$xml = str_replace("{10}", GetFullPath($pageid, GetFlavouredLabel($page["linkurl"])), $xml);
		$xml = str_replace("{11}", GetPageGadgets($pageid), $xml);

		ob_clean();
		ob_start();
		header("Content-type: text/xml");
		echo($xml);
	}

	function GetFlavouredLabel($flavourID)
	{
		$sql = "SELECT `default` FROM `zcm_flavourtext` WHERE `id` = '".
			Zymurgy::$db->escape_string($flavourID).
			"'";
		return Zymurgy::$db->get($sql);
	}

	function GetPageContent($pageid)
	{
		$template = <<<XML
		<block name="{0}" acl="{1}">
			<![CDATA[{2}]]>
		</block>
XML;
		$blocks = array();

		$sql = "SELECT `tag`, `body`, `zcm_acl`.`name` AS `acl` FROM `zcm_pagetext` LEFT JOIN `zcm_acl` ON `zcm_acl`.`id` = `zcm_pagetext`.`acl` WHERE `sitepage` = '".
			Zymurgy::$db->escape_string($pageid).
			"'";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve page text: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$block = $template;

			$block = str_replace("{0}", $row["tag"], $block);
			$block = str_replace("{1}", $row["acl"], $block);
			$block = str_replace("{2}", $row["body"], $block);

			$blocks[] = $block;
		}

		Zymurgy::$db->free_result($ri);

		return implode("", $blocks);
	}

	function GetPageChildren($pageid)
	{
		$template = <<<XML
		<child childid="{0}" />
XML;
		$children = array();

		$sql = "SELECT `id` FROM `zcm_sitepage` WHERE `parent` = '".
			Zymurgy::$db->escape_string($pageid).
			"'";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of children: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$child = $template;

			$child = str_replace("{0}", $row["id"], $child);

			$children[] = $child;
		}

		return implode("", $children);
	}

	function GetPageIDFromPath($path)
	{
		$parent = 0;
		$splitpath = explode("/", $path);

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

	function GetFullPath($id, $linkURL)
	{
		$splitpath = array();

		while($id > 0)
		{
			$sql = "SELECT `id`, `linkurl` FROM `zcm_sitepage` a WHERE EXISTS(SELECT 1 FROM `zcm_sitepage` b WHERE b.`parent` = a.`id` AND b.`id` = '".
				Zymurgy::$db->escape_string($id).
				"')";
			$parent = Zymurgy::$db->get($sql);

			if($parent["id"] <= 0)
			{
				break;
			}
			else
			{
				$id = $parent["id"];

				array_unshift($splitpath, GetFlavouredLabel($parent["linkurl"]));
			}
		}

		return implode("/", $splitpath)."/".$linkURL;
	}

	function GetPageGadgets($pageid)
	{
		$template = <<<XML
		<gadget name="{0}" align="{1}" acl="{2}">
{3}
		</gadget>
XML;

		$sql = "SELECT `disporder`, `plugin`, `align`, `acl` FROM `zcm_sitepageplugin` WHERE `zcm_sitepage` = '".
			Zymurgy::$db->escape_string($pageid).
			"'";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of gadgets for page: ".Zymurgy::$db->error().", $sql");

		$gadgets = array();

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$gadget = $template;

			$gadget = str_replace("{0}", htmlspecialchars($row["plugin"]), $gadget);
			$gadget = str_replace("{1}", $row["align"], $gadget);
			$gadget = str_replace("{2}", $row["acl"], $gadget);

			$gadget = str_replace("{3}", GetGadgetConfig($row["plugin"]), $gadget);

			$gadgets[] = $gadget;
		}

		Zymurgy::$db->free_result($ri);
//		die();
		return implode("", $gadgets);
	}

	function GetGadgetConfig($pluginName)
	{
		$template = <<<XML
			<config name="{0}">{1}</config>
XML;

		$pluginNameParts = explode("&", $pluginName);

		$sql = "SELECT `key`, `value` FROM `zcm_pluginconfigitem` WHERE EXISTS(SELECT 1 FROM `zcm_plugininstance` WHERE `name` = '".
			Zymurgy::$db->escape_string(urldecode($pluginNameParts[1])).
			"' AND `zcm_plugininstance`.`config` = `zcm_pluginconfigitem`.`config` AND EXISTS(SELECT 1 FROM `zcm_plugin` WHERE `name` = '".
			Zymurgy::$db->escape_string($pluginNameParts[0]).
			"' AND `zcm_plugin`.`id` = `zcm_plugininstance`.`plugin`))";
//		echo($sql);
//		die($sql);
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve plugin config: ".Zymurgy::$db->error().", $sql");

		$configs = array();

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$config = $template;

			$config = str_replace("{0}", $row["key"], $config);
			$config = str_replace("{1}", $row["value"], $config);

			$configs[] = $config;
		}

		Zymurgy::$db->free_result($ri);

		return implode("", $configs);
	}
?>