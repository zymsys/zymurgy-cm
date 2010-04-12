<?

/**
 * Ask for user to approve EULA.
 *
 * @access private
 * @package
 */

$onload = '';
include('nlheader.php');
require_once(Zymurgy::$root."/zymurgy/ZymurgyAuth.php");
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
?>
<h1>License</h1>
<p>
<a target="_blank" rel="license" href="http://creativecommons.org/licenses/by-nc/2.5/ca/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc/2.5/ca/88x31.png" /></a><br /><span xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://purl.org/dc/dcmitype/Text" property="dc:title" rel="dc:type">Zymurgy:CM</span> by <a target="_blank" xmlns:cc="http://creativecommons.org/ns#" href="http://www.zymurgysystems.com/" property="cc:attributionName" rel="cc:attributionURL">Zymurgy Systems Inc.</a> is licensed under a <a target="_blank" rel="license" href="http://creativecommons.org/licenses/by-nc/2.5/ca/">Creative Commons Attribution-Non-Commercial 2.5 Canada License</a>.<br />Based on a work at <a target="_blank" xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://www.zymurgycm.com/" rel="dc:source">www.zymurgycm.com</a>.<br />Permissions beyond the scope of this license may be available at <a target="_blank" xmlns:cc="http://creativecommons.org/ns#" href="http://www.zymurgycm.com/pages/Main/Licensing" rel="cc:morePermissions">http://www.zymurgycm.com/pages/Main/Licensing</a>.
</p>
<p>
If you wish to use Zymurgy:CM for commercial web sites you must purchase
a commercial license for the product.  If any of the following is true then you will most likely need a commercial license for the product:
<ul>
<li>This web site is for a business which is not registered as not for profit.
<li>This web site runs paid advertising such as AdSense.
<li>This web site is used for affiliate marketing of 3rd party products.
<li>This web site is an e-Commerce site.
</ul>
This is not to be considered an exhaustive list, but to provide examples of commercial use.  Please see our <a href="http://www.zymurgycm.com/pages/Main/Licensing" target="_blank">licensing page</a> for more information.
</p>
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
<input type="checkbox" id="agree" name="agree" onchange="allowContinue()"><label for="agree">I agree to use Zymurgy:CM under the terms of the Creative Commons Attribution-Non-Commercial 2.5 Canada Licence or to purchase a commercial license.</label>
</p>
<p>
<input type="submit" id="continue" value="Continue" disabled>
</p>
</form>
