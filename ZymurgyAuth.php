<?
class ZymurgyAuth
{
	function ZymurgyAuth()
	{
		if(!isset($_SESSION)) session_start();
	}

	/**
	 * @desc Sets the authentication token into a cookie, and redirects the user.
	 * @return void
	 * @param expires int Number of seconds before the auth ticket expires
	 * @param userid string
	 * @param password string
	 * @param extra string Any extra data you want stored in the auth ticket
	 * @param location string URL to redirect to
	 **/
	function SetAuth($expires,$userid,$password,$extra,$location)
	{
		if (strpos($extra,';')!==false)
		{
			echo "<p>SetAuth can't take extra data that contains a semicolin (;) character.</p>\r\n";
			return;
		}
		$_SESSION['AUTH'] = array('userid'=>$userid,
			'password'=>$password,
			'extra'=>$extra);
		ob_clean();
		@header("Location: $location");

		Zymurgy::JSRedirect($location);

		exit;
	/*?>
	<script language="JavaScript">
	<!--
	document.location.href = '<?=$location?>';
	//-->
	</script>
	<?*/
	}

	function Logout($location)
	{
		unset($_SESSION['AUTH']);
		header("Location: $location");
	}

	function GetAuthentication()
	{
		if (isset($_SESSION['AUTH']))
		{
			$this->authinfo = $_SESSION['AUTH'];
			return $this->authinfo;
		}
		return false;
	}

	function IsAuthenticated()
	{
		$ai = $this->GetAuthentication();
		return is_array($ai);
	}

	function Authenticate($loginpath)
	{
		$r = $this->IsAuthenticated();
		if ($r===false)
		{
			//User failed to authenticate
			header("Location: $loginpath");
			exit;
		}
		else
			return $r;
	}
}
?>