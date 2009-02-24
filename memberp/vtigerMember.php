<?
class vtigerMember extends ZymurgyMember 
{
	/**
	 * Try to relate an existing Z:CM member to session data from vtiger.  If a link is found fill $member and create an auth key.
	 * Returns true if the link could be made.
	 *
	 * @return boolean
	 */
	private static function findmemberfromsession()
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
		return vtigerMember::findmemberfromsession();
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
			if (!vtigerMember::findmemberfromsession())
			{
				//Member isn't yet known to Z:CM, add it.
				Zymurgy::$db->run("insert into zcm_member (email,password,regtime,lastauth,mpkey) values ('".
					Zymurgy::$db->escape_string($member['user_name'])."','".
					Zymurgy::$db->escape_string($member['user_password'])."',now(),now(),'".
					Zymurgy::$db->escape_string($member['id'])."')");
				vtigerMember::findmemberfromsession();
			}
			return true;
		}
		else 
		{
			Zymurgy::memberaudit("Failed login attempt for [$userid]: $member");
			return false;
		}
	}
	
	/**
	 * Clear all Zymurgy and vtiger authentication and log out from both.  Redirect the user to $logoutpage.
	 *
	 * @param string $logoutpage
	 */
	static function memberlogout($logoutpage)
	{
		require_once(Zymurgy::$root."/zymurgy/include/nusoap.php");
		vtigerMember::memberauthenticate();
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
			setcookie('ZymurgyAuth');
		}
		Zymurgy::JSRedirect($logoutpage);
	}
}
?>