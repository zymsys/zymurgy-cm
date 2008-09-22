<?php
	header("Content-Type: text/xml;charset=ISO-8859-1");

	include 'cmo.php';

	$tname = array_key_exists('tname',$_GET) ? $_GET['tname'] : 'broken';
	
	$sql = "select cname from zcm_customfield inner join zcm_customtable on zcm_customfield.tableid = " .
		"zcm_customtable.id where zcm_customtable.tname = '" . $tname . "' order by zcm_customfield.disporder";
	
	// echo($sql);
	
	$rsTopic = Zymurgy::$db->run($sql);

	echo("<?xml version=\"1.0\"?>");
	echo("<fieldnames>");
	
	while(($row = Zymurgy::$db->fetch_array($rsTopic)) == true)
	{			
		echo("<field>$row[cname]</field>");
	}
	
	echo("</fieldnames>");
	
	Zymurgy::$db->free_result($rsTopic);

?>
