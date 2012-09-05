<?php
/**
 * Wrapper for the Membership MVC. 
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */
include("cmo.php");
include("include/membership.zcm.php");
$adminlevel = 2;

$memberController = new MembershipController();

$action = isset($_GET["action"])
	? $_GET["action"] :
	"list_members";
$action = isset($_POST["action"])
	? $_POST["action"] :
	$action;

$memberController->Execute($action);
?>
