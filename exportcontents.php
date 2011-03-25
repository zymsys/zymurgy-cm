<?php
class ExportContents
{
	private $data;
	
	function __construct($authtoken)
	{
		$member = Zymurgy::$db->get("SELECT * FROM `zcm_member` WHERE `authkey`='".
			Zymurgy::$db->escape_string($authtoken)."'");
		if ($member === false)
		{
			throw new Exception("Bad auth token", 0);
		}
		$group = Zymurgy::$db->get("SELECT * FROM `zcm_membergroup` WHERE `memberid`={$member['id']} AND `groupid`=3");
		if ($group === false)
		{
			throw new Exception("Authenticated User not Authorized", 0);
		}
		$this->data = new stdClass();
	}
	
	function importTable($tname,$pk = array('id'))
	{
		$table = new stdClass();
		$table->pk = $pk;
		$createrow = Zymurgy::$db->get("SHOW CREATE TABLE `$tname`");
		$table->create = $createrow['Create Table'];
		$table->rows = array();
		$ri = Zymurgy::$db->run("SELECT * FROM `$tname`");
		while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
		{
			$table->rows[] = $row;
		}
		Zymurgy::$db->free_result($ri);
		$this->data->$tname = $table;
	}
	
	function importAllTbales()
	{
		$tables = array();
		$ri = Zymurgy::$db->run("SHOW TABLES");
		while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_NUM))!==false)
		{
			$tables[$row[0]] = true;
		}
		Zymurgy::$db->free_result($ri);
		foreach (array_keys($tables) as $table)
		{
			$keys = array();
			$ri = Zymurgy::$db->run("SHOW COLUMNS FROM `$table` WHERE `Key`='PRI'");
			while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_NUM))!==false)
			{
				$keys[] = $row[0];
			}
			Zymurgy::$db->free_result($ri);
			$tables[$table] = $keys;
		}
		foreach ($tables as $tname=>$keys)
		{
			$this->importTable($tname,$keys);
		}
	}
	
	function getJSON()
	{
		return json_encode($this->data);
	}
} 

require_once 'cmo.php';
try {
	$ec = new ExportContents($_REQUEST['authtoken']);
	$ec->importAllTbales();
	echo $ec->getJSON();
} catch (Exception $e) {
	die(json_encode($e));
}
?>