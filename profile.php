<?
/**
 * Management screen for Zymurgy:CM user profile details.
 * 
 * @package Zymurgy
 * @subpackage backend-modules
 */
$breadcrumbTrail = "My Profile";
$wikiArticleName = "Profile";

include("header.php");

$message = "";
$showform = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$salt = substr(Zymurgy::$member['password'],0,13);
	$hash = substr(Zymurgy::$member['password'],13);
	if (md5($salt.$_POST['passo']) !== $hash)
	{
		$message .= "<div>".Zymurgy::$member['password'].", $salt, $hash, ".md5($salt.$_POST['passo'])."</div>";
		$message .= "<p><font color='red'>Your existing password was not entered correctly.</font></p>";
	}
	if ($_POST['pass1'] != $_POST['pass2'])
	{
		$message .= "<p><font color='red'>Passwords must match.</font></p>";
	}
	if (empty($message))
	{
		$showform = false;

		$useMemberSystem = isset(Zymurgy::$config["usersystem"])
			&& Zymurgy::$config["usersystem"] == "member";

		if($useMemberSystem)
		{
			include("include/membership.zcm.php");

			$isAuthed = Zymurgy::memberauthenticate();
			$member = MemberPopulator::PopulateByID(Zymurgy::$member["id"]);

			$member->set_email($_POST["email"]);
			$member->set_fullname($_POST["fullname"]);

			// only reset the password if it was actually provided
			if(strlen($_POST["pass1"]) > 0)
			{
				$member->set_password($_POST["pass1"]);
			}

			if(!$member->validate())
			{
				$message .= "<ul>";
				foreach($member->get_errors() as $error)
				{
					$message .= "<li>$error</li>";
				}
				$message .= "</ul>";
			}
			else
			{
				MemberPopulator::SaveMember($member);
				$message .= "Profile saved.";
			}
		}
		else
		{
			//Update zcm_member:
			$sql = "UPDATE `zcm_member` SET ";
			if ($_POST['pass1']!='')
			{
				$salt = uniqid();
				$newpass = $salt.md5($salt.$_POST['pass1']);
				$sql .= "password='".Zymurgy::$db->escape_string($newpass)."', ";
			}
			$sql .= "email='".Zymurgy::$db->escape_string($_POST['email'])."', ".
				"fullname='".Zymurgy::$db->escape_string($_POST['fullname'])."' where username='".
				Zymurgy::$db->escape_string(Zymurgy::$member['username'])."'";
			$ri = Zymurgy::$db->run($sql);
			Zymurgy::JSRedirect('index.php');
		}
	}
}

if(strlen($message) > 0)
{
	echo($message);
}
?>

<form method="POST" action="<?=$_SERVER['REQUEST_URI']?>">
<table>
<tr><td align="right">Current Password:<br><i>(For security purposes)</i></td><td><input type="password" name="passo"></td></tr>
<tr><td align="right">New Password:<br><i>(Leave blank to keep your existing password)</i></td><td><input type="password" name="pass1"></td></tr>
<tr><td align="right">New Password:<br><i>(Once again just to be sure)</i></td><td><input type="password" name="pass2"></td></tr>
<tr><td align="right">Email:</td><td><input type="text" name="email" value="<?php echo Zymurgy::$member['email']; ?>"></td></tr>
<tr><td align="right">Full Name:</td><td><input type="text" name="fullname" value="<?php echo Zymurgy::$member['fullname']; ?>"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" value="Update Profile"></td></tr>
</table>
</form>
<?
include('footer.php');
?>
