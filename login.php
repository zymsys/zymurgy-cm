<?

// ini_set("display_errors", 1);
//echo "<pre>"; print_r($_POST); echo "</pre>"; exit;
$onload = 'onload="document.login.userid.focus();"';
include_once("cmo.php");

if (isset($_POST['userid']))
{
	$userid = $_POST['userid'];
	$passwd = $_POST['passwd'];

	$sql = "select * from zcm_passwd where username='".
		Zymurgy::$db->escape_string($userid)."' and password='".
		Zymurgy::$db->escape_string($passwd)."'";

	$useMemberSystem = isset(Zymurgy::$config["usersystem"])
		&& Zymurgy::$config["usersystem"] == "member";

	if($useMemberSystem)
	{
		$sql = "SELECT `zcm_member`.`id` AS `id`, `email`, `password`, `fullname` ".
			"FROM `zcm_member` ".
			"INNER JOIN `zcm_membergroup` ON `zcm_membergroup`.`memberid` = `zcm_member`.`id` ".
			"INNER JOIN `zcm_groups` ON `zcm_groups`.`id` = `zcm_membergroup`.`groupid` ".
			"AND `zcm_groups`.`name` = 'Zymurgy:CM - User' ".
			"WHERE ( `username` = '".
			Zymurgy::$db->escape_string($userid).
			"' ) AND `password` = '".
			Zymurgy::$db->escape_string($passwd).
			"'";

		// echo($sql);
	}

	$ri = Zymurgy::$db->query($sql);
	if (Zymurgy::$db->num_rows($ri)>0)
	{
		include_once("ZymurgyAuth.php");
		$zauth = new ZymurgyAuth();
		$row=Zymurgy::$db->fetch_array($ri);
		$landing = array_key_exists('defaultpage',Zymurgy::$config)
			? Zymurgy::$config['defaultpage']
			: 'index.php';

		if($useMemberSystem)
		{
			Zymurgy::memberdologin(
				$userid,
				$passwd);

			// dummy call to memberauthorize to populate the group
			// listing properly
			Zymurgy::memberauthenticate();
			Zymurgy::memberauthorize("idonotexist");

			// die("Member logged in");

			// print_r(Zymurgy::$member);

			$isWebmaster = intval(Zymurgy::memberauthorize("Zymurgy:CM - Webmaster"));
			$isAdministrator = Zymurgy::memberauthorize("Zymurgy:CM - Administrator");
			$isUser = Zymurgy::memberauthorize("Zymurgy:CM - User");

			$authAdminLevel = -1;

			if($isWebmaster)
			{
				$authAdminLevel = 2;
			}
			else if($isAdministrator)
			{
				$authAdminLevel = 1;
			}
			else if($isUser)
			{
				$authAdminLevel = 0;
			}

			// die("level: '".$authAdminLevel."' (".$isWebmaster.", ".$isAdministrator.", ".$isUser.")");

			$authData = array(
				"username" => $row["email"],
				"email" => $row["email"],
				"fullname" => $row["fullname"],
				"adminlevel" => $authAdminLevel,
				"id" => $row["id"],
				"eula" => "1");

			// echo("Level: ".$authAdminLevel."<br>");
			// print_r($authData);
			// die();

			$zauth->SetAuth(
				0,
				$userid,
				$passwd,
				implode(",", $authData),
				$landing);
		}
		else
		{
			$zauth->SetAuth(
				0,
				$userid,
				$passwd,
	//			"{$row['username']},{$row['email']},{$row['fullname']},{$row['admin']},{$row['id']},{$row['eula']}",
				"{$row['email']},{$row['email']},Registered User,1,{$row['id']},1",
				$landing);
		}
	}

	$error = 'Your username or password are incorrect.';
}

include('nlheader.php');

?>
<form name="login" method="post" action="login.php">
<div align="center">
<table border="0" style="margin-top: 100px; padding:25px; background-color: <?= Zymurgy::$config['headerbackground'] ?>;">
<?
if (isset($error)) echo "<tr><td colspan=\"2\" align=\"center\" style=\"background-color:#ffffff; padding:5px;\"><font color='red'>$error</font></td></tr>\r\n";
?>
<tr><td align="right">User ID:</td><td><input type="text" name="userid" class="noborder"></td></tr>
<tr><td align="right">Password:</td><td><input type="password" name="passwd" class="noborder"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="CMSLogin_Button" value="login"></td></tr>
</table>
</div>
</form>
</div>
</body>
</html>
