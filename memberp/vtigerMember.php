<?
/**
 * 
 * @package Zymurgy
 * @subpackage auth
 */
class vtigerMember extends ZymurgyMember
{
	/**
	 * Try to relate an existing Z:CM member to session data from vtiger.  If a link is found fill $member and create an auth key.
	 * Returns true if the link could be made.
	 *
	 * @return boolean
	 */
	private function findmemberfromsession()
	{
		if (empty($sid))
		{
			session_start();
		}
		if (array_key_exists('customer_id',$_SESSION))
		{
			$member = Zymurgy::$db->get("select * from zcm_member where mpkey='".
				Zymurgy::$db->escape_string($_SESSION['customer_id'])."'");
			if ($member === false)
			{
				$member = Zymurgy::$db->get("select * from zcm_member where email='".
					Zymurgy::$db->escape_string($_SESSION['customer_name'])."'");
				if (is_array($member))
				{
					//Found by email; update their mpkey
					Zymurgy::$db->run("update zcm_member set mpkey='".
						Zymurgy::$db->escape_string($_SESSION['customer_id'])."' where id={$member['id']}");
				}
			}
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
		require_once(Zymurgy::$root."/zymurgy/include/nusoap.php");
		$client = new soapclient2(Zymurgy::$config['vtiger Server Path']."/vtigerservice.php?service=customerportal");
		$r = $client->call('authenticate_user', array('user_name' => "$userid",'user_password'=>"$password"),
			Zymurgy::$config['vtiger Server Path'], Zymurgy::$config['vtiger Server Path']);
		$member = $r[0];
		if (is_array($member))
		{
			$sid = session_id();
			if (empty($sid))
			{
				session_start();
			}
			$_SESSION['customer_id'] = $member['id'];
			$_SESSION['customer_sessionid'] = $member['sessionid'];
			$_SESSION['customer_name'] = $member['user_name'];
			$_SESSION['last_login'] = $member['last_login_time'];
			$_SESSION['support_start_date'] = $member['support_start_date'];
			$_SESSION['support_end_date'] = $member['support_end_date'];
			if (!$this->findmemberfromsession())
			{
				//Member isn't yet known to Z:CM, add it.
				Zymurgy::$db->run("insert into zcm_member (email,password,regtime,lastauth,mpkey) values ('".
					Zymurgy::$db->escape_string($member['user_name'])."','".
					Zymurgy::$db->escape_string($member['user_password'])."',now(),now(),'".
					Zymurgy::$db->escape_string($member['id'])."')");
				$this->findmemberfromsession();
			}
			return true;
		}
		else
		{
			$this->memberaudit("Failed login attempt for [$userid]: $member");
			return false;
		}
	}

	/**
	 * Clear all Zymurgy and vtiger authentication and log out from both.  Redirect the user to $logoutpage.
	 *
	 * @param string $logoutpage
	 */
	public function memberlogout($logoutpage)
	{
		require_once(Zymurgy::$root."/zymurgy/include/nusoap.php");
		$this->memberauthenticate();
		$client = new soapclient2(Zymurgy::$config['vtiger Server Path']."/vtigerservice.php?service=customerportal");
        $r = $client->call('update_login_details', array('id'=>$_SESSION['customer_id'],'sessionid'=>$_SESSION['customer_sessionid'],'flag'=>'logout'));
		session_unregister('customer_id');
		session_unregister('customer_sessionid');
		session_unregister('customer_name');
		session_unregister('last_login');
		session_unregister('support_start_date');
		session_unregister('support_end_date');
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
		$pi = Zymurgy::mkplugin('Form',$formname);
		$pi->LoadInputData();
		$userid = $password = $confirm = $firstname = $lastname = '';
		$authed = Zymurgy::memberauthenticate();

		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			if ($_POST['formname']!=$pi->InstanceName)
			{
				//Another form is posting, just render the form as usual.
				$pi->RenderForm();
				return ;
			}
			//Look for user id, password and password confirmation fields
			$values = array(); //Build a new array of inputs except for password.

			$this->membersignup_GetValuesFromVTigerForm(
				$pi,
				$values,
				$userid,
				$password,
				$confirm,
				$firstname,
				$lastname,
				$useridfield,
				$passwordfield,
				$confirmfield,
				'firstname',
				'lastname');
			$this->membersignup_ValidateVTigerForm(
				$pi,
				$userid,
				$password,
				$confirm,
				$firstname,
				$lastname,
				$authed);

			if (!$pi->IsValid())
			{
				$pi->RenderForm();
				return;
			}

			if (array_key_exists('rurl',$_GET))
				$rurl = $_GET['rurl'];
			else
				$rurl = $redirect;

			if (strpos($rurl,'?')===false)
				$joinchar = '?';
			else
				$joinchar = '&';

			if (!$authed)
			{
				//New registration
				$ri = $this->membersignup_CreateVTigerMember(
					$pi,
					$userid,
					$password,
					$firstname,
					$lastname);

				if($ri)
				{
					$this->membersignup_AuthenticateNewMember($userid, $password);
				}
			}
			else
			{ //Update existing registration
				//Has email changed?
				if (Zymurgy::$member['email']!==$userid)
				{
					$this->membersignup_UpdateUserID($userid);
				}
				//Has password changed?
				if (!empty($password))
				{
					$thiss->membersignup_UpdatePassword($password);
				}
				//Update other user info (XML)
				$sql = "update zcm_form_capture set formvalues='".Zymurgy::$db->escape_string($pi->MakeXML($values))."' where id=".Zymurgy::$member['formdata'];
				Zymurgy::$db->query($sql) or die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
				Zymurgy::JSRedirect($rurl.$joinchar.'memberaction=update');
			}
		}
		else
		{
			if ($authed)
			{
				//We're logged in so update existing info.
				$sql = "select formvalues from zcm_form_capture where id=".Zymurgy::$member['formdata'];
				$ri = Zymurgy::$db->query($sql) or die("Can't get form data ($sql): ".Zymurgy::$db->error());
				$xml = Zymurgy::$db->result($ri,0,0);
				$pi->XmlValues = $xml;
				return $pi->Render();
			}
			else
				return $pi->Render();
		}
		return '';
	}

	private function membersignup_GetValuesFromVTigerForm(
		$pi,
		&$values,
		&$userid,
		&$password,
		&$confirm,
		&$firstname,
		&$lastname,
		$useridfield,
		$passwordfield,
		$confirmfield,
		$firstnamefield,
		$lastnamefield)
	{
		foreach($pi->InputRows as $row)
		{
			$fldname = 'Field'.$row['fid'];

			if (array_key_exists($fldname,$_POST))
				$row['value'] = $_POST[$fldname];
			else
				$row['value'] = '';

			switch($row['header'])
			{
				case $useridfield:
					$userid = $row['value'];
					$values[$row['header']] = $row['value'];
					break;

				case $passwordfield:
					$password = $row['value'];
					break;

				case $confirmfield:
					$confirm = $row['value'];
					break;

				case $firstnamefield:
					$firstname = $row['value'];
					break;

				case $lastnamefield:
					$lastname = $row['value'];
					break;

				default:
					$values[$row['header']] = $row['value'];
			}
		}
	}

	private function membersignup_ValidateVTigerForm(
		&$pi,
		$userid,
		$password,
		$confirm,
		$firstname,
		$lastname,
		$authed)
	{
		parent::ValidateForm(
			$userid,
			$password,
			$confirm,
			$authed);

		if ($firstname == '')
			$pi->ValidationErrors[] = 'First name is a required field.';

		if ($lastname == '')
			$pi->ValidationErrors[] = 'Last name is a required field.';
	}

	private function membersignup_CreateVTigerMember(
		&$pi,
		$userid,
		$password,
		$firstname,
		$lastname)
	{
		$ri = parent::membersignup_CreateMember($userid, $password, $pi);

		if($ri)
		{
			require_once(Zymurgy::$root."/zymurgy/include/nusoap.php");
			$client = new soapclient2(
				Zymurgy::$config['vtiger Server Path']."/zcmservice.php?service=zcm");

			$input_array = array(
		    		'firstname' => $firstname,
		    		'lastname' => $lastname,
		    		'email' => $userid,
		    		'emailoptout' => 'off',
		    		'leadsource' => 'Web Site',
		    		'portal' => 'on',
		    		'password' => $password,
		    		'fromemailname' => Zymurgy::$config['vtiger E-mail Name'],
		    		'fromemailaddress' => Zymurgy::$config['vtiger E-mail Address']);

		    $result = $client->call(
		    	'create_member',
		    	$input_array);
		}
	}
}
?>