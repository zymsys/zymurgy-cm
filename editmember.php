<?php
/**
 * Wrapper for the Membership MVC. 
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
	ini_set("display_errors", 1);

	include("cmo.php");
	include("include/membership.zcm.php");

	$memberController = new MembershipController();

	$action = isset($_GET["action"])
		? $_GET["action"] :
		"list_members";
	$action = isset($_POST["action"])
		? $_POST["action"] :
		$action;

	$memberController->Execute($action);
?>
