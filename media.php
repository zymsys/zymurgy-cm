<?php
/**
 * no references
 * 
 * @package Zymurgy
 * @subpackage orphanned
 */
	include("cmo.php");
	include("include/media.php");
	
	$mediaController = new MediaController();
	
	$action = isset($_GET["action"]) 
		? $_GET["action"] : 
		"list_media_files";
	$action = isset($_POST["action"]) 
		? $_POST["action"] : 
		$action;
	
	$mediaController->Execute($action);
?>