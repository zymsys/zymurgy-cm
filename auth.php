<?php 
require_once 'cmo.php';
$r = new stdClass();
if (array_key_exists('u', $_GET) && array_key_exists('p', $_GET))
{
	ob_start();
	if (Zymurgy::memberdologin($_GET['u'], $_GET['p']))
	{
		$r->authtoken = $_COOKIE['ZymurgyAuth'];
	}
	ob_clean();
}
if (!property_exists($r, 'authtoken'))
{
	$r->errormsg = "Invalid user ID or password.";
}
echo json_encode($r);
?>