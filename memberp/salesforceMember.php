<?
/**
 *
 * @package Zymurgy
 * @subpackage auth
 */
class salesforceMember extends ZymurgyMember
{
	public function salesforceMember()
	{
		$isValid = true;

		$issue = '';
		$isValid = $this->ValidateConfigurationItem($issue, "WSDL Path");
		$isValid = $this->ValidateConfigurationItem($issue, "salesforce Login");
		$isValid = $this->ValidateConfigurationItem($issue, "salesforce Token");

		if(!$isValid)
		{
			$issue = "Could not set up the Salesforce Membership Provider: <ul>\n".
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
		require_once(Zymurgy::$root.Zymurgy::$config['WSDL Path']."/SforceEnterpriseClient.php");
		try {
			$connection = new SforceEnterpriseClient();
			$soapClient = $connection->createConnection(Zymurgy::$root.Zymurgy::$config['WSDL Path']."/enterprise.wsdl.xml");
			$login = $connection->login(Zymurgy::$config['salesforce Login'], Zymurgy::$config['salesforce Token']);
		} catch (Exception $e) {
			Zymurgy::Dbg($e);
		}
		try {
			$query = "SELECT Id, FirstName, LastName, Email, Pref_Lang_Contact__c from Contact where Email='".addslashes($userid)."'";
			$result = $connection->query($query);
			if (!isset($result->records) || count($result->records < 1))
			{
				//No such user, fail login
				return false;
			}
			$sfcontact = $result->records[0]; //TODO: More than one contact with this email?  Too bad.
			$zcmmember = Zymurgy::$db->get("SELECT * FROM `zcm_member` WHERE `email`='".
				Zymurgy::$db->escape_string($userid)."'");
			if ($zcmmember === false)
			{
				//SF contact exists, but Z:CM member doesn't exist.  Create it and ask for email confirmation.
				//Pref_Lang_Contact__c is a picklist, either English or French
			}
		} catch (Exception $e) {
			Zymurgy::Dbg($e);
		}
		Zymurgy::DbgAndDie('done dologin');
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