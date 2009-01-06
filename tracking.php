<?php
require_once 'cmo.php';

if (array_key_exists('clear',$_GET))
{
	?>
	<script>
	document.cookie = "zcmtracking=0;expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/;";
	</script>
	<?php 
	exit;
}

if (array_key_exists('view',$_GET))
{
	header("Content-type: text/plain");
	print_r($_COOKIE);
	exit;
}

if (Zymurgy::memberauthenticate())
	$memberid = $member['id'];
else 
	$memberid = 0;

function addTracking($trkid)
{
	global $memberid;
	
	$tag = Zymurgy::$db->escape_string(array_key_exists('t',$_GET) ? $_GET['t'] : '');
	Zymurgy::$db->query("insert into zcm_tracking (id,created,lastload,member,addr,tag,referrer,ua) values ('".
		Zymurgy::$db->escape_string($trkid)."',".
		"now(),now(),$memberid,'{$_SERVER['REMOTE_ADDR']}','$tag','".
		Zymurgy::$db->escape_string($_GET['r'])."','".
		Zymurgy::$db->escape_string($_SERVER['HTTP_USER_AGENT'])."')");
	//Update orphaned page view record
	$userid=Zymurgy::$db->escape_string($_GET['u']);
	Zymurgy::$db->query("update zcm_pageview set trackingid='".
		Zymurgy::$db->escape_string($trkid)."', orphan=0 where trackingid='$userid'");
}
	
if (!array_key_exists('zcmtracking',$_COOKIE))
{
	$trkid = uniqid(true);
	addTracking($trkid);
	echo $trkid;
}
else 
{
	$trkid = $_COOKIE['zcmtracking'];
	Zymurgy::$db->query("update zcm_tracking set lastload=now(), member=$memberid where id='".
		Zymurgy::$db->escape_string($trkid)."'");
	if (Zymurgy::$db->affected_rows()==0)
	{
		addTracking($trkid);
	}
	echo $trkid;
}
/**
 * Old code for Visitor tracking.
 */
/*class ZymurgyTracking
{
	private $uatypes = array(
		'B' => 'Browser',
		'C' => 'Link-, bookmark-, server- checking',
		'D' => 'Downloading tool',
		'P' => 'Proxy server, web filtering',
		'R' => 'Robot, crawler, spider',
		'S' => 'Spam or bad bot',
		'O' => 'Other');
	
	private $uabits = array(
		'B' => 1,
		'C' => 2,
		'D' => 4,
		'P' => 8,
		'R' => 16,
		'S' => 32,
		'O' => 0);
		
	function LoadUserAgents()
	{
		$fd = fopen('http://www.user-agents.org/allagents.csv','r');
		while (($ln = fgets($fd))!==false)
		{
			$lp = explode(',',trim($ln));
			$uap = explode(' ',$lp[3]);
			$uatypes = 0;
			foreach ($uap as $uatype)
			{
				$uatypes |= $this->uabits[$uatype];
			}
			if ($uatypes == 0)
			{
				$uatypes = $this->uabits['O'];
			}
			for($n=0; $n<7; $n++)
			{
				$lp[$n] = Zymurgy::$db->escape_string($lp[$n]); 
			}
			echo "<div>Adding {$lp[1]} (ua:$uatypes) [{$lp[2]}]</div>\r\n";
			$sql = "insert into zcm_useragent (uaid,identifier,uatype) values ('{$lp[0]}','{$lp[1]}',$uatypes) 
				on duplicate key update identifier='{$lp[1]}', uatype=$uatypes";
			echo "<div>$sql</div>\r\n";
			Zymurgy::$db->run($sql);
		}
		fclose($fd);
	}
	
	function GetUserAgentTypeBits($ua)
	{
		$ri = Zymurgy::$db->run("select uatype from zcm_useragent where identifier='".
			Zymurgy::$db->escape_string($ua)."'");
		$row = Zymurgy::$db->fetch_array($ri);
		if ($row === false) return false;
		return $row['uatype'];
	}
	
	function UserAgentBitstoString($uatypes)
	{
		$t = array();
		foreach($this->uatypes as $uatype=>$uadesc)
		{
			if (($this->uabits[$uatype] & $uatypes) > 0)
			{
				$t[] = $uadesc;
			}
		}
		if (count($t)==0)
			$t[] = $this->uatypes['O'];
		return implode(', ',$t);
	}
}

$zt = new ZymurgyTracking();
//$zt->LoadUserAgents();
$useragent = $_SERVER['HTTP_USER_AGENT'];
$uatypes = $zt->GetUserAgentTypeBits($useragent);
echo "The agent $useragent has a uatypes of $uatypes which means ".$zt->UserAgentBitstoString($uatypes);*/
?>