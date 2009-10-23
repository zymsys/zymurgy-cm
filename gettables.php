<?php
/**
 * Get a list of table names.
 * 
 * @package Zymurgy
 * @subpackage customtables
 * @access private
 */
	header("Content-Type: text/xml;charset=utf-8");

	include 'cmo.php';

	$sql = "select tname from zcm_customtable order by tname";
	
	$rsTopic = Zymurgy::$db->run($sql);

	echo("<?xml version=\"1.0\"?>");
	echo("<tablenames>");
	
	while(($row = Zymurgy::$db->fetch_array($rsTopic)) == true)
	{			
		echo("<table>$row[tname]</table>");
	}
	
	echo("</tablenames>");
	
	Zymurgy::$db->free_result($rsTopic);

?>
