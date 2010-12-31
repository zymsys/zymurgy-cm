<?php 
require_once 'cmo.php';

class ZymurgySSO
{
	protected $isSSOServer = false;
	
	public function __construct()
	{
		if (array_key_exists('SingleSignon',Zymurgy::$config) && (!empty(Zymurgy::$config['SingleSignon'])))
		{
			$this->isSSOServer = true;
		}
	}
	
	private function handleGet_JS()
	{ //Get javascript with auth key
		header('Content-type: text/javascript');
		
		if (array_key_exists('ZymurgyAuth', $_COOKIE))
			echo "var authkey = '".addslashes($_COOKIE['ZymurgyAuth'])."';\n";
		else
			echo "var authkey = '';\n";
		return;
	}
	
	private function handleGet_HTML()
	{ //Get HTML to load javascript from remote
?>
<!doctype html>
<html>
<head>
<title>SSO - <?php echo htmlspecialchars(Zymurgy::$config['defaulttitle']) ?></title>
<script type="text/javascript" src="<?php if ($this->isSSOServer) echo Zymurgy::$config['SingleSignon']; ?>zymurgy/sso.php?type=js"></script>
<?php echo Zymurgy::jQuery(); ?>
<script type="text/javascript">
function loadLoginPage() {
	//No auth - redirect to remote SSO server for login.
	document.location.href = '<?php if ($this->isSSOServer) echo Zymurgy::$config['SingleSignon']; ?>zymurgy/memberlogin.php?rurl=<?php
	$rurl = strtolower(array_shift(explode('/',$_SERVER['SERVER_PROTOCOL'],2)));
	$rurl .= '://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	echo urlencode($rurl); 
	?>';
}

if (authkey) {
	//Our sso server gave us an auth key through remotely included javascript.  Post it back to this server to verify and set session vars.
	$.ajax({
		url: '/zymurgy/sso.php', //Post AJAX back to this server
		type: 'POST',
		dataType: 'json',
		data: {
			authkey: authkey
		},
		success: function(data) {
			if (data) {
				document.cookie = "ZymurgyAuth=" + authkey + "; path=/";
				document.location.href = '<?php echo Zymurgy::$config['MemberDefaultPage']; ?>';
			} else {
				loadLoginPage();
			}
		},
		failure: function() {
			$('body').html('Unable to post your authentication information.  Please try again.');
		}
	});
} else {
	loadLoginPage();
}
</script>
</head>
<body>
</body>
</html>
<?php 
	}

	protected function curlGet($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
		$r = curl_exec($ch);
		curl_close($ch);
		return $r;
	}
	
	/**
	 * Meh -
	 * User signs out through Zymurgy::memberlogout() - notify sso server, calling this method on it.
	 * SSO signals SSO client, calling this method.
	 * Don't send sso server notification from here - only from cmo.
	 */
	private function handleGet_Signout()
	{
		$key = Zymurgy::$db->escape_string($_GET['authkey']);
		if ($this->isSSOServer)
		{
			//SSO Server has advised us to de-auth a user.
			Zymurgy::$db->run("UPDATE `zcm_member` SET `authkey`=null WHERE `authkey`='$key'");
			//$this->curlGet(Zymurgy::$config['SingleSignon'].'zymurgy/sso.php?type=signout');
		}
		else
		{
			//Notify all authed SSO clients that the user is no longer authed
			$noauth = array();
			$member = 0; 
			$ri = Zymurgy::$db->run("SELECT `zcm_ssoapp`.`link`,`zcm_ssoauth`.`app`,`zcm_ssoauth`.`member` 
				FROM `zcm_ssoauth` 
				LEFT JOIN `zcm_member` ON `zcm_ssoauth`.`member`=`zcm_member`.`id` 
				LEFT JOIN `zcm_ssoapp` on `zcm_ssoauth`.`app`=`zcm_ssoapp`.`id` 
				WHERE `zcm_member`.`authkey`='$key' and `zcm_ssoauth`.`authed`=1");
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$this->curlGet($row['link'].'zymurgy/sso.php?type=signout&authkey='.$_GET['authkey']);
				$noauth[] = $row['app'];
				$member = $row['member']; //Should keep overwriting the same value within loop.
			}
			Zymurgy::$db->free_result($ri);
			if ($noauth)
			{
				Zymurgy::$db->run("UPDATE `zcm_ssoauth` SET `authed`=0 WHERE `member`=$member AND `app` IN (".
					implode(',',$noauth).")");
			}
			//Log out of SSO server too.
			Zymurgy::$db->run("UPDATE `zcm_member` SET `authkey`=null where `authkey`='$key'");
		}
	}
	
	private function handleGet()
	{
		if (array_key_exists('type', $_GET))
		{
			switch($_GET['type'])
			{
				case 'signout':
					$this->handleGet_Signout();
					break;
				default:
					$this->handleGet_JS();
					break;
			}
		}
		else
		{
			$this->handleGet_HTML();
		}
	}

	private function handlePost_SSOClient()
	{
		//AJAX request posted authkey to us so that we could confirm with remote SSO
		$ch = curl_init(Zymurgy::$config['SingleSignon'].'zymurgy/sso.php');
		curl_setopt($ch, CURLOPT_POST      ,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS    , 'appid='.Zymurgy::$config["SingleSignonAppID"].'&authkey='.urlencode($_POST['authkey']));
		curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		$memberdata = curl_exec($ch);
		curl_close($ch);
		if ($memberdata)
		{
			$data = json_decode($memberdata);
			if ($data == false)
			{
				//Bad response from SSO server - report it.
				header("HTTP/1.0 500 Server Error");
				die('Bad data from SSO Server: '.$memberdata);
			}
			$sql = "INSERT INTO `zcm_member` (`username`,`email`,`fullname`,`regtime`,`authkey`,`orgunit`) VALUES ('".
				Zymurgy::$db->escape_string($data->username)."','".
				Zymurgy::$db->escape_string($data->email)."','".
				Zymurgy::$db->escape_string($data->fullname)."',now(),'".
				Zymurgy::$db->escape_string($data->authkey)."','".
				Zymurgy::$db->escape_string($data->orgunit)."') ON DUPLICATE KEY UPDATE `authkey`='".
				Zymurgy::$db->escape_string($_POST['authkey'])."'";
			//$data->sql = $sql;
			echo json_encode($data);
			Zymurgy::$db->run($sql);
		}
	}
	
	private function handlePost_SSOServer()
	{
		//We are the SSO server - this request would have come from the remote server
		//Which app is requesting auth service?
		$app = Zymurgy::$db->get("SELECT * FROM `zcm_ssoapp` WHERE `id`=".intval($_POST['appid']));
		if ($app === false)
		{
			//Unknown app - 404 out.
			header("HTTP/1.0 404 Not Found");
			die('Application not found.');
		}
		$ri = Zymurgy::$db->run("SELECT * FROM `zcm_member` WHERE `authkey`='".
			Zymurgy::$db->escape_string($_POST['authkey'])."'");
		Zymurgy::$member = $member = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC);
		if (is_array($member))
		{
			unset($member['password']);
			//Record auth so we can send a sign-out notification later if required
			Zymurgy::$db->run("INSERT INTO `zcm_ssoauth` (`app`,`member`,`authed`,`lastauth`) VALUES ".
				"({$app['id']}, {$member['id']}, 1, now()) ON DUPLICATE KEY UPDATE `authed`=1, `lastauth`=now()");
			Zymurgy::memberaudit("Authenticated for '".$app['name']."'");
			echo json_encode($member);
		}
	}
	
	private function handlePost()
	{
		if ($this->isSSOServer)
		{
			$this->handlePost_SSOClient();
		}
		else 
		{
			$this->handlePost_SSOServer();
		}
	}
	
	public function dispatch()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			$this->handleGet();
		}
		else 
		{
			$this->handlePost();
		}
	}
}

$sso = new ZymurgySSO();
$sso->dispatch();
?>