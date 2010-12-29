<?
/**
 *
 * @package Zymurgy
 * @subpackage auth
 */
class httpMember extends ZymurgyMember
{
	public function httpMember()
	{
		$isValid = true;

		$issue = '';
		$isValid = $this->ValidateConfigurationItem($issue, "Membership Auth URL");

		if(!$isValid)
		{
			$issue = "Could not set up HTTP Membership Provider: <ul>\n".
				$issue.
				"</ul>\n";

			die($issue);
		}
	}

	protected function ValidateConfigurationItem(&$issue, $name)
	{
		$isValid = true;

		if(!isset(Zymurgy::$config[$name]))
		{
			$issue .= "<li>The <b>$name</b> configuration must be set.</li>\n";
			$isValid = false;
		}

		return $isValid;
	}
	
	private function findmemberfromsession()
	{
		$sid = session_id();
		if (empty($sid))
		{
			session_start();
		}
		if (array_key_exists('AuthName',$_SESSION))
		{
			$member = Zymurgy::$db->get("select * from zcm_member where mpkey='".
				Zymurgy::$db->escape_string($_SESSION['AuthName'])."'");
			if (is_array($member))
			{
				$this->populatememberfromrow($member);
				$this->createauthkey($member['id']);
				return true;
			}
		}
		return false;
	}

	/**
	 * Authenticate that the user is logged in.
	 *
	 * @return boolean
	 */
	public function memberauthenticate()
	{
		$sid = session_id();
		if (empty($sid))
		{
			session_start();
		}
		if (parent::memberauthenticate())
		{
			//Parent think's we're logged in, but are we still logged into the MP?
			if (array_key_exists('customer_name',$_SESSION))
			{
				if ($_SESSION['customer_name'] == Zymurgy::$member['email'])
				{
					return true;
				}
			}
		}
		return $this->findmemberfromsession();
	}

	/**
	 * Try to log in with the provided user ID and password using vtiger's portal authentication soap service.
	 * If log in is successful then emulate vtiger's session variables for compatibility with the portal.
	 *
	 * @param string $userid
	 * @param string $password
	 * @return boolean
	 */
	public function memberdologin($userid, $password)
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, Zymurgy::$config['Membership Auth URL']);
		curl_setopt($ch, CURLOPT_USERPWD,"$userid:$password"); 
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$head=curl_exec($ch);
		curl_close($ch);
		$hp = explode("\n",$head);
		$lp = explode(' ', array_shift($hp),3);
		$code = $lp[1];
		$authed = (($code == '404') || (substr($code, 0, 1) == '2'));
		if ($authed)
		{
			$sid = session_id();
			if (empty($sid))
			{
				session_start();
			}
			$_SESSION['AuthName'] = $userid;
			if (!$this->findmemberfromsession())
			{
				//Member isn't yet known to Z:CM, add it.
				$email = strtolower(substr($userid, -1).".".substr($userid, 0, -1))."@healthforceontario.ca";
				Zymurgy::$db->run("insert into zcm_member (email,username,password,fullname,regtime,lastauth,mpkey) values ('".
					Zymurgy::$db->escape_string($email)."','".
					Zymurgy::$db->escape_string($userid)."','n/a','".
					Zymurgy::$db->escape_string(substr($userid, -1))." ".
					Zymurgy::$db->escape_string(substr($userid, 0, -1)).
					"', now(),now(),'".
					Zymurgy::$db->escape_string($userid)."')");
				$this->findmemberfromsession();
			}
		}
		return $authed;
	}

	/**
	 * Clear all Zymurgy and Infusionsoft authentication and log out from both.
	 * Redirect the user to $logoutpage.
	 *
	 * @param string $logoutpage
	 */
	public function memberlogout($logoutpage)
	{
		$this->memberauthenticate();

		if (is_array(Zymurgy::$member))
		{
			$sql = "update zcm_member set authkey=null where id=".Zymurgy::$member['id'];
			Zymurgy::$db->query($sql) or die("Unable to logout ($sql): ".Zymurgy::$db->error());
			if(!headers_sent())
			{
				setcookie('ZymurgyAuth');
			}
		}

		Zymurgy::JSRedirect($logoutpage);
	}

	public function membersignup(
		$formname,
		$useridfield,
		$passwordfield,
		$confirmfield,
		$redirect)
	{
	}
}
?>