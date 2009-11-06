<?

/**
 * Ask for user to approve EULA.
 *
 * @access private
 * @package
 */

$onload = '';
include('nlheader.php');
require_once("$ZymurgyRoot/zymurgy/ZymurgyAuth.php");
$zauth = new ZymurgyAuth();
$zauth->Authenticate("login.php");
$zp = explode(',',$zauth->authinfo['extra']);
$zauth->authinfo['email'] = $zp[1];
$zauth->authinfo['fullname'] = $zp[2];
$zauth->authinfo['admin'] = $zp[3];
$zauth->authinfo['id'] = $zp[4];
$zauth->authinfo['eula'] = $zp[5];
if ($_SERVER['REQUEST_METHOD']=='POST')
{
	if (array_key_exists('agree',$_POST))
	{
		$sql = "update zcm_passwd set eula=1 where id={$zauth->authinfo['id']}";
		Zymurgy::$db->query($sql) or die("Unable to set EULA status ($sql): ".Zymurgy::$db->error());
		$zp[5] = 1;
		$_SESSION['AUTH']['extra']=implode(',',$zp);
		header("Location: index.php");
		exit;
	}
}
echo "<pre style=\"font-size: 12pt\">";
include("license.txt");
echo "</pre>";
?>
<script language="javascript">
<!--
function allowContinue()
{
	var agree=document.getElementById('agree');
	var cont=document.getElementById('continue');
	cont.disabled = !agree.checked;
}
//-->
</script>
<form method="POST" action="<?=$_SERVER['REQUEST_URI']?>">
<p>
<input type="checkbox" id="agree" name="agree" onchange="allowContinue()"><label for="agree">I agree to the <?= Zymurgy::GetLocaleString("Common.ProductName") ?> End User License Agreement.</label>
</p>
<p>
<input type="submit" id="continue" value="Continue" disabled>
</p>
</form>
