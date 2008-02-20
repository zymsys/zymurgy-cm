<?php
	header("Content-Type: text/xml;charset=ISO-8859-1");

	include 'cmo.php';

	$tname = array_key_exists('tname',$_GET) ? $_GET['tname'] : 'broken';
	
	$sql = "select cname from customfield inner join customtable on customfield.tableid = " .
		"customtable.id where customtable.tname = '" . $tname . "' order by customfield.disporder";
	
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
