<?
/**
 * Helper class for the Zymurgy:CM authentication token
 * 
 * @package Zymurgy
 * @subpackage auth
 */
class ZymurgyAuth
{
	/**
	 * Constructor.
	 */
	function ZymurgyAuth()
	{
		if(!isset($_SESSION)) session_start();
	}

	/**
	 * Add the authentication token into the session.
	 *
	 * @param expires int Number of seconds before the auth ticket expires
	 * @param userid string
	 * @param password string
	 * @param extra string Any extra data you want stored in the auth ticket
	 * @param location string URL to redirect to
	 * @return void
	 */
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
	<?php */
	}

	/**
	 * Remove the authentication token and log the user out of Zymurgy:CM.
	 */
	function Logout($location)
	{
		unset($_SESSION['AUTH']);
		header("Location: $location");
	}

	/**
	 * Retrieve the authentication token if it exists.
	 *
	 * @return mixed The authentication token, if it exists, Otherwise, this 
	 * method returns FALSE.
	 */
	function GetAuthentication()
	{
		if (isset($_SESSION['AUTH']))
		{
			$this->authinfo = $_SESSION['AUTH'];
			return $this->authinfo;
		}
		return false;
	}

	/**
	 * Check to see if the user has been authenticated.
	 *
	 * @return boolean TRUE if the user is authenticated. Otherwise, FALSE.
	 */
	function IsAuthenticated()
	{
		$ai = $this->GetAuthentication();
		return is_array($ai);
	}

	/**
	 * Check to see if the user has been authenticated. If the user has not 
	 * been authenticated, forward the user to the login screen.
	 *
	 * @param string $loginpath The path of the login screen to forward the 
	 * user to if the user has not been authenticated.
	 * @return True if the user is authenticated. The method does not return
	 * a value if the user has not been authenticated.
	 */
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