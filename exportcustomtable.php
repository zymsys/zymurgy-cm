<?php
	/**
	 * Exports a custom table, for later import into another instance of
	 * Zymurgy:CM.
	 *
	 * @package Zymurgy
	 * @subpackage backend-modules
	 */


	include_once("cmo.php");

	$tables = array();

	$tables[] = GetTableDefinition($_GET["t"]);

	header("Content-Type: text/xml");
	header("Content-Disposition: attachment;filename=customtable.xml");
	echo "<?xml version=\"1.0\"?><tables>\n".
		XMLSerialize($tables, "tables").
		"</tables>\n";

	/**
	 * Get the table definition for the specified custom table
	 *
	 * @param int $tableID The ID of the table, as set in the zcm_customtable table
	 * @return array
	 */
	function GetTableDefinition($tableID)
	{
		$table = array();

		$sql = "SELECT `tname`, `detailfor`, `hasdisporder`, `ismember`, `navname`, `selfref` FROM `zcm_customtable` WHERE `id` = '".
			Zymurgy::$db->escape_string($tableID).
			"'";
		$tableData = Zymurgy::$db->get($sql);

		$table["id"] = $tableID;
		$table["name"] = $tableData["tname"];
		$table["displayorder"] = $tableData["hasdisporder"];
		$table["memberdata"] = $tableData["ismember"];
		$table["linkname"] = $tableData["navname"];
		$table["selfref"] = $tableData["selfref"];
		$table["detailfor"] = $tableData["detailfor"];

		$table["fields"] = array();
		$table["rows"] = array();

		$sql = "SELECT `cname`, `inputspec`, `caption`, `indexed`, `gridheader` FROM `zcm_customfield` WHERE `tableid` = '".
			Zymurgy::$db->escape_string($tableID).
			"' ORDER BY `disporder`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve field list: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$field = array();

			$field["name"] = $row["cname"];
			$field["index"] = $row["indexed"] == "Y" ? 1 : 0;
			$field["gridheader"] = $row["gridheader"];
			$field["inputspec"] = $row["inputspec"];
			$field["caption"] = $row["caption"];

			$table["fields"][] = $field;
		}

		Zymurgy::$db->free_result($ri);

		$sql = "SELECT * FROM `".
			$table["name"].
			"`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve rows: ".Zymurgy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_assoc($ri)) !== FALSE)
		{
			$table["rows"][] = $row;
		}

		Zymurgy::$db->free_result($ri);

		return $table;
	}

	/**
	 * Serialize the specified array into XML.
	 *
	 * @param array $array The input array
	 * @param string $baseNodeName The root node for the returned XML
	 * @return string
	 */
	function XMLSerialize($array, $baseNodeName)
	{
		$xml = "";
		$itemName = substr($baseNodeName, 0, strlen($baseNodeName) - 1);

		foreach($array as $key => $value)
		{
			if(is_array($value))
			{
				if(is_numeric($key))
				{
//					die("numeric");
					$xml .= "<{$itemName}>\n".XMLSerialize($value, $itemName)."</{$itemName}>\n";
				}
				else
				{
					$xml .= "<{$key}>\n".XMLSerialize($value, $key)."</{$key}>\n";
				}
			}
			else
			{
				$xml .= "<{$key}><![CDATA[{$value}]]></{$key}>\n";
			}
		}

//		return "<{$baseNodeName}>\n{$xml}</{$baseNodeName}>\n";

		return $xml;
	}
?>