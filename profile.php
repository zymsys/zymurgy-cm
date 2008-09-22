<?
$breadcrumbTrail = "My Profile";	

include('header.php');
$showform = true;
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	echo "bobo";
	if ($_POST['pass1'] != $_POST['pass2'])
	{
		echo "<font color='red'>Passwords must match.</font><p>";
	}
	else 
	{
		$showform = false;
		$sql = "update zcm_passwd set ";
		if ($_POST['pass1']!='')
			$sql .= "password='".Zymurgy::$db->escape_string($_POST['pass1'])."', ";
		$sql .= "email='".Zymurgy::$db->escape_string($_POST['email'])."', ".
			"fullname='".Zymurgy::$db->escape_string($_POST['fullname'])."' where username='".
			Zymurgy::$db->escape_string($zauth->authinfo['userid'])."'";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri)
		{
			echo Zymurgy::$db->error().": $sql";
		}
		else 
		{
			$zauth->authinfo['password'] = $_POST['pass1'];
			$zauth->authinfo['email'] = $_POST['email'];
			$zauth->authinfo['fullname'] = $_POST['fullname'];
			if ($zauth->authinfo['cookietype']=='P')
				$expires = 5*3600*24*365;
			else 
				$expires = false;
			$zauth->SetAuth($expires,$zauth->authinfo['userid'],$_POST['pass1'],
				"{$zauth->authinfo['userid']},{$_POST['email']},{$_POST['fullname']},{$zauth->authinfo['admin']},{$zauth->authinfo['id']},1",
				"index.php");
		}
	}
}
?>
<form method="POST" action="<?=$_SERVER['REQUEST_URI']?>">
<table>
<tr><td align="right">New Password:<br><i>(Leave blank to keep your existing password)</i></td><td><input type="password" name="pass1"></td></tr>
<tr><td align="right">New Password:<br><i>(Once again just to be sure)</i></td><td><input type="password" name="pass2"></td></tr>
<tr><td align="right">Email:</td><td><input type="text" name="email" value="<?=$zauth->authinfo['email']?>"></td></tr>
<tr><td align="right">Full Name:</td><td><input type="text" name="fullname" value="<?=$zauth->authinfo['fullname']?>"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" value="Update Profile"></td></tr>
</table>
</form>
<?
include('footer.php');
?>
