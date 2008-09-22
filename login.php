<?
//echo "<pre>"; print_r($_POST); echo "</pre>"; exit;
$onload = 'onload="document.login.userid.focus();"';
include('nlheader.php');
if (isset($_POST['userid']))
{
	$userid = $_POST['userid'];
	$passwd = $_POST['passwd'];
	
	$sql = "select * from zcm_passwd where username='".
		Zymurgy::$db->escape_string($userid)."' and password='".
		Zymurgy::$db->escape_string($passwd)."'";
	$ri = Zymurgy::$db->query($sql);
	if (Zymurgy::$db->num_rows($ri)>0)
	{
		include("ZymurgyAuth.php");
		$zauth = new ZymurgyAuth();
		$row=Zymurgy::$db->fetch_array($ri);
		$zauth->SetAuth(0,$userid,$passwd,"{$row['username']},{$row['email']},{$row['fullname']},{$row['admin']},{$row['id']},{$row['eula']}","index.php");
	}
	$error = 'Your username or password are incorrect.';
}
?>
<form name="login" method="post" action="login.php">
<div align="center">
<table border="0" style="margin-top: 100px; padding:25px; background-color: #9999cb;">
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
