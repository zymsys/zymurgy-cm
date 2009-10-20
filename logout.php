<?
/**
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
setcookie("zymurgy",'',0,'/');
include('ZymurgyAuth.php');
$zauth = new ZymurgyAuth();
$zauth->Logout('login.php');
?>
