<?
class infusionsoftMember extends ZymurgyMember
{
	function insfusionsoftMember()
	{
		$isValid = true;

		$issue = '';
		$isValid = $this->ValidateConfigurationItem($issue, "Infusionsoft URL");
		$isValid = $this->ValidateConfigurationItem($issue, "Infusionsoft API Key");

		if(!$isValid)
		{
			$issue = "Could not set up Infusionsoft Membership Provider: <ul>\n".
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

	/**
	 * Try to relate an existing Z:CM member to session data from Infusionsoft.  If a link is found fill $member and create an auth key.
	 * Returns true if the link could be made.
	 *
	 * @return boolean
	 */
	private static function findmemberfromsession()
	{
		$sid = session_id();
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
				ZymurgyMember::populatememberfromrow($member);
				ZymurgyMember::createauthkey($member['id']);
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
	static function memberauthenticate()
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
		return infusionsoftMember::findmemberfromsession();
	}

	/**
	 * Try to log in with the provided user ID and password using vtiger's portal authentication soap service.
	 * If log in is successful then emulate vtiger's session variables for compatibility with the portal.
	 *
	 * @param string $userid
	 * @param string $password
	 * @return boolean
	 */
	static function memberdologin($userid, $password)
	{
		require_once(Zymurgy::$root."/zymurgy/include/infusionsoft.php");
		$infusion = new ZymurgyInfusionsoftWrapper();

		$r = $infusion->execute_fetch_array_va(
			'DataService.query',
			'Contact',
			1,
			0,
			array(
				'Email'=>$userid,
				'Password'=>$password),
			array(
				'Id',
				'FirstName',
				'LastName',
				'Email',
				'Password'));
				
		if(is_array($r))
		{
			$sid = session_id();
			if (empty($sid))
			{
				session_start();
			}

			$_SESSION['customer_id'] = $r['Id'];
			$_SESSION['customer_name'] = $r['Email'];

			if (!infusionsoftMember::findmemberfromsession())
			{
				//Member isn't yet known to Z:CM, add it.
				Zymurgy::$db->run("insert into zcm_member (email,password,regtime,lastauth,mpkey) values ('".
					Zymurgy::$db->escape_string($r['Email'])."','".
					Zymurgy::$db->escape_string($r['Password'])."',now(),now(),'".
					Zymurgy::$db->escape_string($r['Id'])."')");
				infusionsoftMember::findmemberfromsession();
			}
			return true;
		}
		else
		{
			Zymurgy::memberaudit("Failed login attempt for [$userid]: $r");
			return false;
		}
	}
	
	static function remotelookup($table,$field,$value,$exact=false)
	{
		require_once(Zymurgy::$root."/zymurgy/include/infusionsoft.php");
		$infusion = new ZymurgyInfusionsoftWrapper();

		$wildcard = $exact ? '' : '%';
		$ir = $infusion->execute_va('DataService.query',$table,100,0,array($field=>$value.$wildcard),array('Id',$field));
		$r = array();
		foreach ($ir->val as $val)
		{
			$r[$val['Id']] = $val[$field];
		}
		return $r;
	}

	static function remotelookupbyid($table,$field,$value)
	{
		require_once(Zymurgy::$root."/zymurgy/include/infusionsoft.php");
		$infusion = new ZymurgyInfusionsoftWrapper();

		$ir = $infusion->execute_va('DataService.query',$table,1,0,array('Id'=>$value),array('Id',$field));
		$r = array();
		foreach ($ir->val as $val)
		{
			$r[$val['Id']] = $val[$field];
		}
		return $r;
	}

	/**
	 * Clear all Zymurgy and vtiger authentication and log out from both.  Redirect the user to $logoutpage.
	 *
	 * @param string $logoutpage
	 */
	static function memberlogout($logoutpage)
	{
		infusionsoftMember::memberauthenticate();

		if (is_array(Zymurgy::$member))
		{
			session_unregister("customer_id");
			session_unregister("customer_name");

			$sql = "update zcm_member set authkey=null where id=".Zymurgy::$member['id'];
			Zymurgy::$db->query($sql) or die("Unable to logout ($sql): ".Zymurgy::$db->error());
			setcookie('ZymurgyAuth');
		}

		Zymurgy::JSRedirect($logoutpage);
	}

	static function membersignup(
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

			infusionsoftMember::membersignup_GetValuesFromInfusionsoftForm(
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
			infusionsoftMember::membersignup_ValidateInfusionsoftForm(
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
				$ri = infusionsoftMember::membersignup_CreateInfusionsoftMember(
					$pi,
					$userid,
					$password,
					$firstname,
					$lastname);

				if($ri)
				{
					infusionsoftMember::membersignup_AuthenticateNewMember($userid, $password);
				}
			}
			else
			{ //Update existing registration
				//Has email changed?
				if (Zymurgy::$member['email']!==$userid)
				{
					infusionsoftMember::membersignup_UpdateUserID($userid);
				}
				//Has password changed?
				if (!empty($password))
				{
					infusionsoftMember::membersignup_UpdatePassword($password);
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

	function membersignup_GetValuesFromInfusionsoftForm(
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

	function membersignup_ValidateInfusionsoftForm(
		&$pi,
		$userid,
		$password,
		$confirm,
		$firstname,
		$lastname,
		$authed)
	{
		parent::membersignup_ValidateForm(
			$userid,
			$password,
			$confirm,
			$authed,
			$pi);

		if ($firstname == '')
			$pi->ValidationErrors[] = 'First name is a required field.';

		if ($lastname == '')
			$pi->ValidationErrors[] = 'Last name is a required field.';
	}

	function membersignup_CreateInfusionsoftMember(
		&$pi,
		$userid,
		$password,
		$firstname,
		$lastname)
	{
		$ri = parent::membersignup_CreateMember($userid, $password, $pi);

		if($ri)
		{
			require_once(Zymurgy::$root."/zymurgy/include/infusionsoft.php");
			$infusion = new ZymurgyInfusionsoftWrapper();

			$r = $infusion->execute_va(
				'DataService.add',
				"Contact",
				array(
					'FirstName' => $firstname,
					'LastName' => $lastname,
					'Email' => $userid,
					'Password' => $password));
		}
	}
}
?>