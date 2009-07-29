<?php
class ZymurgyMember
{
	/**
	 * Is member authenticated?  If yes then loads auth info into global $member array.
	 *
	 * @return boolean
	 */
	public function memberauthenticate()
	{
		//Are we already authenticated?
		if (isset(Zymurgy::$member) && is_array(Zymurgy::$member))
		{
			return true;
		}
		if (array_key_exists('ZymurgyAuth',$_COOKIE))
		{
			$authkey = $_COOKIE['ZymurgyAuth'];
			$sql = "select * from zcm_member where authkey='".Zymurgy::$db->escape_string($authkey)."'";
			$ri = Zymurgy::$db->query($sql) or die("Unable to authenticate ($sql): ".Zymurgy::$db->error());
			if (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$this->populatememberfromrow($row);
				return true;
			}
		}
		return false;
	}

	public function populatememberfromrow($row)
	{
		Zymurgy::$member = array(
			'id'=>$row['id'],
			'username'=>$row["username"],
			'email'=>$row['email'],
			'password'=>$row['password'],
			'formdata'=>$row['formdata'],
			'orgunit'=>$row['orgunit'],
			'groups'=>array() // 'Registered User')
		);
	}

	/**
	 * Is member authorized (by group name) to view this page?
	 *
	 * @param string $groupname
	 * @return boolean
	 */
	public function memberauthorize($groupname)
	{
		$authorized = false;
		//echo "<div>Authorizing for [$groupname]: ";
		if ($this->memberauthenticate())
		{
			//echo "authenticated ";
			$sql = "select id, name from zcm_groups,zcm_membergroup where (zcm_membergroup.memberid=".Zymurgy::$member['id'].") and (zcm_membergroup.groupid=zcm_groups.id)";
			$ri = Zymurgy::$db->query($sql) or die("Unable to authorize ($sql): ".Zymurgy::$db->error());
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				Zymurgy::$member['groups'][$row["id"]] = $row['name'];
			}
			return in_array($groupname,Zymurgy::$member['groups']);
		}
		else
		{
			//echo "not authenticated ";
		}
		//echo "</div>";
		return $authorized;
	}

	/**
	 * Log member activity
	 *
	 * @param unknown_type $activity
	 */
	public function memberaudit($activity)
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if (array_key_exists('X_FORWARDED_FOR',$_SERVER))
			$realip = $_SERVER['X_FORWARDED_FOR'];
		else
			$realip = $ip;
		if (is_array(Zymurgy::$member))
			$mid = 0 + Zymurgy::$member['id'];
		else
			$mid = 0;
		$sql = "insert into zcm_memberaudit (member, audittime, remoteip, realip, audit) values ($mid,".
			"now(),'$ip','".Zymurgy::$db->escape_string($realip)."','".Zymurgy::$db->escape_string($activity)."')";
		Zymurgy::$db->query($sql) or die("Unable to log activity ($sql): ".Zymurgy::$db->error());
	}

	/**
	 * Use in the header with the include and metatags().
	 * Verify that the user is a member of the required group to view this page.
	 * If not, redirect to the login page.
	 *
	 * @param string $groupname
	 */
	public function memberpage($groupname='Registered User')
	{
		if (!array_key_exists('MemberLoginPage',Zymurgy::$config))
		{
			die("Please define \$ZymurgyConfig['MemberLoginPage'] before using membership functions.");
		}
		$matuh8 = $this->memberauthenticate();
		$matuhz = $this->memberauthorize($groupname);
		if ($matuh8 && $matuhz)
		{
			$this->memberaudit("Opened page {$_SERVER['REQUEST_URI']}");
		}
		else
		{
			$rurl = urlencode($_SERVER['REQUEST_URI']);
			Zymurgy::JSRedirect(Zymurgy::$config['MemberLoginPage']."?rurl=$rurl");
		}
	}

	public function createauthkey($id)
	{
		//Set up the authkey and last auth
		$authkey = md5(uniqid(rand(),true));
		$sql = "update zcm_member set lastauth=now(), authkey='$authkey' where id=$id";
		Zymurgy::$db->query($sql) or die("Unable to set auth info ($sql): ".Zymurgy::$db->error());

		//Set authkey session cookie
		$_COOKIE['ZymurgyAuth'] = $authkey;

		if(!headers_sent())
		{
			$cookieSet = setcookie("ZymurgyAuth", $authkey, null, "/");
		}

		// die($cookieSet ? "Cookie set" : "Cookie not set");
		// if(!$cookieSet) die("Could not set cookie.");

		echo "<script language=\"javascript\">
			<!--
			document.cookie = \"ZymurgyAuth=$authkey; path=/\";
			//-->
			</script>";

		$this->memberaudit("Successful login for [$id]");
	}

	/**
	 * Attempt to log into the membership system with the provided user ID and password.  Returns true
	 * if the login was successful or false if it was not.
	 *
	 * @param string $userid
	 * @param string $password
	 * @return boolean
	 */
	public function memberdologin($userid, $password)
	{
		// die("memberdologin called");

		$sql = "SELECT `id` FROM `zcm_member` WHERE ( `username` = '".
			Zymurgy::$db->escape_string($userid).
			"' ) AND `password` = '".
			Zymurgy::$db->escape_string($password).
			"'";
		// echo "<div>$sql</div>";
		// $sql = "select * from zcm_member where email='".Zymurgy::$db->escape_string($userid).
		//	"' and password='".Zymurgy::$db->escape_string($password)."'";
		$ri = Zymurgy::$db->query($sql) or die("Unable to login ($sql): ".Zymurgy::$db->error());

		if (($row = Zymurgy::$db->fetch_array($ri)) !== false)
		{
			$this->createauthkey($row['id']);
			return true;
		}
		else
		{
			$this->memberaudit("Failed login attempt for [$userid]");
			return false;
		}
	}

	/**
	 * Clear existing credentials and go to the supplied URL.
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
		/*else
		{
			echo "not logged in.";
			exit;
		}*/

		if(strlen($logoutpage) > 0)
		{
			Zymurgy::JSRedirect($logoutpage);
		}
	}

	/**
	 * Handle new signups.  Takes a form (from the Form plugin), the field names for the user ID, password and password confirmation,
	 * and the link to send users to after registration.  Returns UI HTML.
	 *
	 * @param string $formname
	 * @param string $useridfield
	 * @param string $passwordfield
	 * @param string $confirmfield
	 * @param string $redirect
	 * @return string
	 */
	public function membersignup(
		$formname,
		$useridfield,
		$passwordfield,
		$confirmfield,
		$redirect)
	{
		$pi = Zymurgy::mkplugin('Form',$formname);
		$pi->LoadInputData();
		$userid = $password = $confirm = '';
		$authed = $this->memberauthenticate();

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

			$this->membersignup_GetValuesFromForm(
				$pi,
				$values,
				$userid,
				$password,
				$confirm,
				$useridfield,
				$passwordfield,
				$confirmfield);
			$this->membersignup_ValidateForm(
				$userid,
				$password,
				$confirm,
				$authed,
				$pi);

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
				$ri = $this->membersignup_CreateMember($userid, $password, $pi);

				if($ri)
				{
					$this->membersignup_AuthenticateNewMember($userid, $password, $pi, $rurl, $joinchar, $values);
				}
			}
			else
			{ //Update existing registration
				//Has email changed?
				if (Zymurgy::$member['email']!==$userid)
				{
					$this->membersignup_UpdateUserID($userid, $pi);
				}
				//Has password changed?
				if (!empty($password))
				{
					$this->membersignup_UpdatePassword($password);
				}
				//Update other user info (XML)
				$capture = new FormCaptureToDatabase();
				$xml = $capture->MakeXML($values);
				$sql = "update zcm_form_capture set formvalues='".Zymurgy::$db->escape_string($xml)."' where id=".Zymurgy::$member['formdata'];
				Zymurgy::$db->query($sql) or die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
				Zymurgy::JSRedirect($rurl.$joinchar.'memberaction=update');
			}
		}
		else
		{
			if ($authed)
			{
				//We're logged in so update existing info.
				if(strlen(Zymurgy::$member['formdata']) > 0)
				{
					$sql = "select formvalues from zcm_form_capture where id=".Zymurgy::$member['formdata'];
					$ri = Zymurgy::$db->query($sql) or die("Can't get form data ($sql): ".Zymurgy::$db->error());
					$xml = Zymurgy::$db->result($ri,0,0);
					$pi->XmlValues = $xml;
				}
				return $pi->Render();
			}
			else
				return $pi->Render();
		}
		return '';
	}

	private function membersignup_GetValuesFromForm(
		&$pi,
		&$values,
		&$userid,
		&$password,
		&$confirm,
		$useridfield,
		$passwordfield,
		$confirmfield)
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

				default:
					$values[$row['header']] = $row['value'];
			}
		}
	}

	private function membersignup_ValidateForm(
		$userid,
		$password,
		$confirm,
		$authed,
		&$pi)
	{
		//Now we have our rows, is there anything obviously wrong?
		if ($userid == '')
			$pi->ValidationErrors[] = 'Email address is a required field.';

		if ($password != $confirm)
			$pi->ValidationErrors[] = 'Passwords do not match.';

		if (($password == '') && !$authed)
			$pi->ValidationErrors[] = 'Password is a required field.';
	}

	private function membersignup_CreateMember($userid,	$password, $pi)
	{
		// die("membersignup_CreateMember() Start");

		$sql = "INSERT INTO `zcm_member` ( `username`, `email`, `password`, `regtime` ) VALUES ( '".
			Zymurgy::$db->escape_string($userid).
			"', '".
			Zymurgy::$db->escape_string($userid).
			"', '".
			Zymurgy::$db->escape_string($password).
			"', NOW() )";

		// $sql = "insert into zcm_member(email,password,regtime) values ('".
		// 	Zymurgy::$db->escape_string($userid)."','".
		// 	Zymurgy::$db->escape_string($password)."',now())";

		$ri = Zymurgy::$db->query($sql);

		if(!$ri)
		{
			if (Zymurgy::$db->errno() == 1062)
			{
				$pi->ValidationErrors[] = "That user ID is already in use.";
				$pi->RenderForm();
			}
			else
			{
				die("Unable to create member ($sql): ".Zymurgy::$db->error());
			}
		}

		$memberID = Zymurgy::$db->insert_id();

		$sql = "INSERT INTO `zcm_membergroup` ( `memberid`, `groupid` ) SELECT '".
			Zymurgy::$db->escape_string($memberID).
			"', `id` FROM `zcm_groups` where `name` = 'Registered User'";
		Zymurgy::$db->query($sql)
			or die("Could not assign Registered User group to member:".Zymurgy::$db->error().", $sql");

		if(isset(Zymurgy::$config["CreateMemberGroup"]) && Zymurgy::$config["CreateMemberGroup"])
		{
			$sql = "INSERT INTO `zcm_groups` ( `name`, `builtin` ) VALUES ( '".
				Zymurgy::$db->escape_string($userid).
				"', 0 )";
			Zymurgy::$db->query($sql)
				or die("Could not create group for new member: ".Zymurgy::$db->error().", $sql");

			$sql = "INSERT INTO `zcm_membergroup` ( `memberid`, `groupid` ) VALUES ( '".
				Zymurgy::$db->escape_string($memberID).
				"', '" .
				Zymurgy::$db->escape_string(Zymurgy::$db->insert_id()).
				"' )";
			Zymurgy::$db->query($sql)
				or die("Could not assign group to member: ".Zymurgy::$db->error().", $sql");
		}

		return $ri;
	}

	private function membersignup_AuthenticateNewMember($userid, $password, $pi, $rurl, $joinchar, $values)
	{
		if ($this->memberdologin($userid,$password))
		{
			$this->memberauthenticate();

			// ZK: Call the extension methods for capturing form data and sending e-mail.
			// These are no longer handled purely by the Form class.
			$pi->CallExtensionMethod("CaptureFormData");
			$pi->CallExtensionMethod("SendEmail");

			// $pi->StoreCapture($pi->MakeXML($values));
			// $pi->SendEmail();

			$iid = Zymurgy::$db->insert_id();

			if ($iid)
			{
				$sql = "update zcm_member set formdata=$iid where id=".Zymurgy::$member['id'];
				Zymurgy::$db->query($sql) or die("Can't set form data ($sql): ".Zymurgy::$db->error());
			}

			Zymurgy::JSRedirect($rurl.$joinchar.'memberaction=new');
		}
		else
		{
			echo "Oddly we couldn't log you in.";
		}
	}

	private function membersignup_UpdateUserID($userid, &$pi)
	{
		//Is the new user id already in use?
		$sql = "update zcm_member set email='".Zymurgy::$db->escape_string($userid)."' where id=".Zymurgy::$member['id'];
		$ri = Zymurgy::$db->query($sql);

		if (!$ri)
		{
			if (Zymurgy::$db->errno() == 1062)
			{
				$pi->ValidationErrors[] = "That user ID is already in  use.";
				$pi->RenderForm();
			}
			else
			{
				die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
			}
		}
	}

	private function membersignup_UpdatePassword($password)
	{
		$sql = "update zcm_member set password='".Zymurgy::$db->escape_string($password)."' where id=".Zymurgy::$member['id'];
		Zymurgy::$db->query($sql) or die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
	}

	/**
	 * Render login interface.  Uses reg GET variable, which can be:
	 * 	- username: create a new username/account
	 * 	- extra: get extra info from the user using a client defined form
	 * If the reg GET variable isn't supplied it just tries to log the user in.
	 *
	 * @return string HTML for login process
	 */
	public function memberlogin()
	{
		$r = array();
		$e = array();
		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			//Determine where we are and go from there
			if (array_key_exists("reg",$_GET))
			{
				$reg = $_GET['reg'];
				switch ($reg)
				{
					case 'username':
						//If passwords don't match or if email not supplied, ask again with error.
						$email = trim($_POST['email']);
						$pass = $_POST['pass'];
						$pass2 = $_POST['pass2'];
						if ($email == '')
							$e[] = 'Email address is a required field.';
						if ($pass != $pass2)
							$e[] = 'Passwords do not match.';
						if ($pass == '')
							$e[] = 'Password is a required field.';
						if (count($e)==0)
						{
							$sql = "insert into zcm_member(username,email,password,regtime) values ('".
								Zymurgy::$db->escape_string($email)."','".
								Zymurgy::$db->escape_string($email)."','".
								Zymurgy::$db->escape_string($pass)."',now())";
							$ri = Zymurgy::$db->query($sql);
							if (!$ri)
							{
								if (Zymurgy::$db->errno() == 1062)
								{
									$e[] = "That user ID is already  in use.";
								}
								else
								{
									die("Unable to create zcm_member ($sql): ".Zymurgy::$db->error());
								}
							}
							else
							{
								//Created member successfully.  Login and redirect to extra info page if set up.
								if ($this->memberdologin($email,$pass))
								{
									if (!array_key_exists('MembershipInfoForm',Zymurgy::$config))
									{
										if (array_key_exists('rurl',$_GET))
											$rurl = $_GET['rurl'];
										else
										{
											if (array_key_exists('MemberDefaultPage',Zymurgy::$config))
												$rurl = Zymurgy::$config['MemberDefaultPage'];
											else
											{
												$rp = explode('/',$_SERVER['REQUEST_URI']);
												array_pop($rp); //Remove document name;
												$rurl = implode('/',$rp);
											}
										}
										Zymurgy::JSRedirect($rurl);
									}
									else
										Zymurgy::JSRedirect(Zymurgy::$config['MemberLoginPage']."?reg=extra&rurl=$rurl");
								}
							}
						}
						break;
					case 'extra':
						//May also confirm email from step one.
						Zymurgy::memberpage();
						//Get it on with the bogus form fields for password and email.
						$pi = Zymurgy::mkplugin('Form',Zymurgy::$config['MembershipInfoForm']);
						$pi->SaveID = Zymurgy::$member['formdata'];
						if (array_key_exists('Fieldemail',$_POST))
						{
							//Try to update the email address
							$sql = "update zcm_member set email='".Zymurgy::$db->escape_string($_POST['Fieldemail']).
								"' where id=".Zymurgy::$member['id'];
							$ri = Zymurgy::$db->query($sql);
							if (!$ri)
							{
								if (Zymurgy::$db->errno()==1062)
									$pi->ValidationErrors[] = "That email address is already   in use.";
								else
									die("Unable to update email address ($sql): ".Zymurgy::$db->error());
							}
							//Try to update password
							if ($_POST['Fieldoldpass'] != '')
							{
								if (Zymurgy::$member['password']!=$_POST['Fieldoldpass'])
								{
									$pi->ValidationErrors[] = "The old password you supplied is not correct.";
								}
								if ($_POST['Fieldpass']!=$_POST['Fieldpass2'])
								{
									$pi->ValidationErrors[] = "The new passwords don't match.";
								}
								if (count($pi->ValidationErrors)==0)
								{
									//All good...  Update the password.
									$sql = "update zcm_member set password='".Zymurgy::$db->escape_string($_POST['Fieldpass']).
										"' where id=".Zymurgy::$member['id'];
									Zymurgy::$db->query($sql) or die("Unable to update password ($sql): ".Zymurgy::$db->error());
								}
							}
							//If validation errors try to return record to it's old email.  Password should only set if all is well.
							if (count($pi->ValidationErrors)>0)
							{
								$sql = "update zcm_member set email='".Zymurgy::$db->escape_string(Zymurgy::$member['email']).
									"' where id=".Zymurgy::$member['id'];
								Zymurgy::$db->query($sql) or die("Unable to restore email ($sql): ".Zymurgy::$db->error());
							}
						}
						$r[] = $pi->Render();
						if (count($pi->ValidationErrors)==0)
						{
							$formid = Zymurgy::$db->insert_id();
							if ($formid)
							{
								$sql = "update zcm_member set formdata=$formid where id=".Zymurgy::$member['id'];
								Zymurgy::$db->query($sql) or die("Can't set form data ($sql): ".Zymurgy::$db->error());
							}
							//return implode("\r\n",$r);
							Zymurgy::JSRedirect($_GET['rurl']);
						}
						else
						{
							//Failed validation.  Just return so we can try again.
							return '';
						}
				}
				//None of the above?  Fall through to login.
			}
			if (count($e)==0)
			{
				if ($this->memberdologin($_POST['email'],$_POST['pass']))
				{
					//Redirect to source page or root page if none provided.
					if (array_key_exists('rurl',$_GET))
						$rurl = $_GET['rurl'];
					else
						$rurl = Zymurgy::$config['MemberDefaultPage'];
					Zymurgy::JSRedirect($rurl);
				}
				else
				{
					$e[] = "Your email address or password are not correct.";
				}
			}
		}
		if (count($e)>0)
		{
			$r[] = "<div class=\"MemberBadLogin\">".implode("<br />\r\n",$e)."</div>";
		}
		if (array_key_exists("reg",$_GET))
		{
			$reg = $_GET['reg'];
			switch ($reg)
			{
				case 'username':
					if (array_key_exists('MembershipUsernameForm',Zymurgy::$config))
						$r[] = Zymurgy::$config['MembershipUsernameForm'];
					else
						$r[] = '<form class="MemberForm" method="post"><table>
        <tr><td align="right">Email Address:</td><td><input type="text" name="email" id="email"></td></tr>
        <tr><td align="right">Password:</td><td><input type="password" name="pass" id="pass"></td></tr>
        <tr><td align="right">Confirm Password:</td><td><input type="password" name="pass2" id="pass2"></td></tr>
        <tr><td align="center" colspan="2"><input type="Submit" value="Signup"></td></tr>
        </table></form>';
					return implode("\r\n",$r);
				case 'extra':
					//May also confirm email from step one.
					memberpage();
					$pi = mkplugin('Form',Zymurgy::$config['MembershipInfoForm']);
					$pi->LoadInputData();
					if ($zcm_member['formdata'])
					{
						$sql = "select formvalues from zcm_form_capture where id=".Zymurgy::$member['formdata'];
						$ri = Zymurgy::$db->query($sql) or die("Unable to load form data ($sql): ".Zymurgy::$db->error());
						$pi->XmlValues = Zymurgy::$db->result($ri,0,0);
						array_unshift($pi->InputRows,array(
							"fid"=>"pass2",
							"header"=>"pass2",
							"defaultvalue"=>"",
							"caption"=>"Confirm New Password:",
							"specifier"=>"password.20.32"));
						array_unshift($pi->InputRows,array(
							"fid"=>"pass",
							"header"=>"pass",
							"defaultvalue"=>"",
							"caption"=>"New Password:",
							"specifier"=>"password.20.32"));
						array_unshift($pi->InputRows,array(
							"fid"=>"oldpass",
							"header"=>"oldpass",
							"defaultvalue"=>"",
							"caption"=>"Old Password:",
							"specifier"=>"password.20.32"));
						array_unshift($pi->InputRows,array(
							"fid"=>"email",
							"header"=>"email",
							"defaultvalue"=>$member['email'],
							"caption"=>"E-mail:",
							"specifier"=>"input.20.80"));
					}
					$r[] = $pi->Render();
					return implode("\r\n",$r);
			}
			//None of the above?  Fall through to login.
		}
		if (array_key_exists('MembershipLoginForm',Zymurgy::$config))
			$r[] = Zymurgy::$config['MembershipLoginForm'];
		else
			$r[] = '<form class="MemberLogin" method="post"><table>
        <tr><td align="right">Email Address:</td><td><input type="text" name="email" id="email"></td></tr>
        <tr><td align="right">Password:</td><td><input type="password" name="pass" id="pass"></td></tr>
        <tr><td align="center" colspan="2"><input type="Submit" value="Login"></td></tr>
        </table></form>';
		return implode("\r\n",$r);
	}

	/**
	 * Render data entry form for user data using the navigation name for the Custom Table used for user data.
	 *
	 * @param string $navname
	 * @param string $exitpage
	 */
	public function memberform($navname,$exitpage)
	{
		require_once('memberdata.php');
		$f = ZymurgyMemberDataTable::FactoryByNavName($navname, $exitpage);
		if (count($f->children) > 0)
		{
			//Get YUI dependancies
			$min = "-min"; //$min = '';
			echo Zymurgy::YUI('tabview/assets/skins/sam/tabview.css');
			echo Zymurgy::YUI('datatable/assets/skins/sam/datatable.css');
			echo Zymurgy::YUI('container/assets/skins/sam/container.css');
			echo Zymurgy::YUI('button/assets/skins/sam/button.css');
			echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
			echo Zymurgy::YUI("element/element-beta$min.js");
			echo Zymurgy::YUI("datasource/datasource$min.js");
			echo Zymurgy::YUI("json/json$min.js");
			echo Zymurgy::YUI("connection/connection$min.js");
			echo Zymurgy::YUI("get/get$min.js");
			echo Zymurgy::YUI("dragdrop/dragdrop$min.js");
			echo Zymurgy::YUI("datatable/datatable$min.js");
			echo Zymurgy::YUI("tabview/tabview$min.js");
			echo Zymurgy::YUI("button/button$min.js");
			echo Zymurgy::YUI("container/container$min.js");
			//Common JS for form feature
?>
<script type="text/javascript">
var zymurgyDialogButtons = [ { text:"Save", handler:function() {
						this.submit();
					}, isDefault:true },
				    { text:"Cancel", handler:function() {
	this.cancel();
}} ];
</script>
<?
		}
		echo "<div class=\"yui-skin-sam\">\r\n";
		$f->renderDialogs();
		$f->renderForm();
		echo "</div>"; //yui-skin-sam
	}
}
?>