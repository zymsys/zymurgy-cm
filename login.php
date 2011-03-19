<?

/**
 * Login screen. 
 *
 * @package Zymurgy
 * @subpackage auth
 */

// ini_set("display_errors", 1);
//echo "<pre>"; print_r($_POST); echo "</pre>"; exit;
ob_start();
$onload = 'onload="document.login.userid.focus();"';
include_once("cmo.php");

if (isset($_POST['userid']))
{
	$userid = $_POST['userid'];
	$passwd = $_POST['passwd'];

	/*$sql = "SELECT `zcm_member`.`id` AS `id`, `email`, `password`, `fullname` ".
		"FROM `zcm_member` ".
		"INNER JOIN `zcm_membergroup` ON `zcm_membergroup`.`memberid` = `zcm_member`.`id` ".
		"INNER JOIN `zcm_groups` ON `zcm_groups`.`id` = `zcm_membergroup`.`groupid` ".
		"AND `zcm_groups`.`name` = 'Zymurgy:CM - User' ".
		"WHERE ( `username` = '".
		Zymurgy::$db->escape_string($userid).
		"' ) AND `password` = '".
		Zymurgy::$db->escape_string($passwd).
		"'";

	$ri = Zymurgy::$db->query($sql);
	if (Zymurgy::$db->num_rows($ri)>0)*/
	if (Zymurgy::memberdologin($userid,$passwd))
	{
		Zymurgy::memberrequirezcmauth(1);

		$landing = array_key_exists('defaultpage',Zymurgy::$config)
			? Zymurgy::$config['defaultpage']
			: 'index.php';

		Zymurgy::JSRedirect($landing);
	}

	$error = 'Your username or password are incorrect.';
}

include('nlheader.php');

?>
<form name="login" method="post" action="login.php">
<div align="center">
<table border="0" style="margin-top: 100px; padding:25px; background-color: <?= strpos("   ".Zymurgy::$config['headerbackground'], "#") > 0 ? Zymurgy::$config['headerbackground'] : "#".Zymurgy::$config['headerbackground'] ?>;">
<?
if (isset($error)) echo "<tr><td colspan=\"2\" align=\"center\" style=\"background-color:#ffffff; padding:5px;\"><font color='red'>$error</font></td></tr>\r\n";
?>
<tr><td align="right">User ID:</td><td><input type="text" name="userid" class="noborder" style="width:120px"></td></tr>
<tr><td align="right">Password:</td><td><input type="password" name="passwd" class="noborder" style="width:120px"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="CMSLogin_Button" value="login"></td></tr>
</table>
</div>
</form>
</div>
</body>
</html>
