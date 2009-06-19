<?
function userErrorHandler ($errno, $errmsg, $filename, $linenum,  $vars)
{
	$time=date("d M Y H:i:s");
	// Get the error type from the error number
	$errortype = array (1    => "Error",
	                 2    => "Warning",
	                 4    => "Parsing Error",
	                 8    => "Notice",
	                 16   => "Core Error",
	                 32   => "Core Warning",
	                 64   => "Compile Error",
	                 128  => "Compile Warning",
	                 256  => "User Error",
	                 512  => "User Warning",
	                 1024 => "User Notice",
	                 2048 => "Run Time Notice",
	                 4096 => "Catchable Fatal Error");
	$errlevel=$errortype[$errno];
	if (empty($errlevel)) $errlevel = $errno;

	echo "<div>[$errlevel: $errmsg in $filename on line $linenum]</div>\r\n";
}

if (array_key_exists('showerrors',$_GET))
{
	error_reporting(0);
	$old_error_handler = set_error_handler("userErrorHandler");
}

if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
	$ZymurgyRoot = $_SERVER["APPL_PHYSICAL_PATH"];
else
	$ZymurgyRoot = $_SERVER['DOCUMENT_ROOT'];

require_once("$ZymurgyRoot/zymurgy/ZymurgyAuth.php");
require_once("$ZymurgyRoot/zymurgy/cmo.php");

global $zauth;
$zauth = new ZymurgyAuth();
$zauth->Authenticate("/zymurgy/login.php");
$zp = explode(',',$zauth->authinfo['extra']);
if (count($zp)<6)
{
	echo $zauth->authinfo['extra']; exit;
	header("Location: logout.php");
	exit;
}
$zauth->authinfo['email'] = $zp[1];
$zauth->authinfo['fullname'] = $zp[2];
$zauth->authinfo['admin'] = $zp[3];
$zauth->authinfo['id'] = $zp[4];
$zauth->authinfo['eula'] = $zp[5];
if ((isset($adminlevel)) && ($zauth->authinfo['admin']<$adminlevel))
{
	header("Location: login.php");
	exit;
}

$enableEula = !isset(Zymurgy::$config["EnableEULA"]) || !(Zymurgy::$config["EnableEULA"] == "no");

if ($enableEula && $zauth->authinfo['eula'] != 1)
{
	header("Location: eula.php");
	exit;
}

if (!array_key_exists("zymurgy",$_COOKIE))
{
	setcookie("zymurgy",$zauth->authinfo['admin'],null,'/');
}

ob_start();

$includeNav = true;
include("header_html.php");

function renderZCMNav($parent)
{
	global $donefirstzcmnav, $zauth;

	// echo("zauth: ");
	// print_r($zauth);

	$sql = "SELECT `id`, `navname`, `navtype`, `navto` FROM `zcm_nav` WHERE `parent` = '".
		Zymurgy::$db->escape_string($parent).
		"' AND ( `authlevel` <= '".
		Zymurgy::$db->escape_string($zauth->authinfo['admin']).
		"' OR `authlevel` IS NULL ) ORDER BY `disporder`";
	// $sql = "select * from zcm_nav where parent=$parent order by disporder";
	// echo($sql);
	$ri = Zymurgy::$db->run($sql);

	$navs = array();
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$navs[] = $row;
	}
	mysql_free_result($ri);
	if (count($navs)==0) return;
	foreach($navs as $nav)
	{
		echo "<li class=\"yuimenuitem";
		if (!isset($donefirstzcmnav))
		{
			$donefirstzcmnav = true;
			echo " first-of-type";
		}
		echo "\"><a class=\"yuimenuitemlabel\" href=\"";
		switch ($nav['navtype'])
		{
			case 'Sub-Menu':
				$href = "#zcmnav{$nav['id']}";
				break;
			case 'Custom Table':
				$href = "customedit.php?t={$nav['navto']}";
				break;
			case 'Plugin':
				$href = "pluginadmin.php?pid={$nav['navto']}&autoskip=1";
				break;
			case 'URL':
				$href = $nav['navto'];
				break;
			case "Zymurgy:CM Feature":
				$sql = "SELECT `url` FROM `zcm_features` WHERE `id` = '".
					Zymurgy::$db->escape_string($nav["navto"]).
					"'";
				$href = Zymurgy::$db->get($sql);
				break;
		}
		echo $href."\"";
		if ($parent==0)
		{
			echo " style=\"text-align:right\"";
		}
		echo ">{$nav['navname']}</a>";
		if ($nav['navtype']=='Sub-Menu')
		{
			echo "<div id=\"".substr($href,1)."\" class=\"yuimenu\"><div class=\"bd\"><ul>";
			renderZCMNav($nav['id']);
			echo "</ul></div></div>";
		}
		echo "</li>";
	}
}
?>
<div id="zcmnavContent" class="yuimenu" style="float:left; margin-right: 5px">
	<div class="ZymurgyLoginName">
		<?= $zauth->authinfo['fullname'] ?>
	</div>
	<div id="zcmnavContentNav" class="bd" style="border-style: none">
    	<ul class="first-of-type" style="padding: 0px">
    		<? renderZCMNav(0); ?>
        </ul>
    </div>
</div>

<?php
if (isset($crumbs))
{
	//Build $breadcrumbTrail
	$crumbbits = array();
	$crumblinks = array_keys($crumbs);
	//print_r($crumblinks); exit;
	while (count($crumbs) > 0)
	{
		$crumbname = array_shift($crumbs);
		$crumblink = array_shift($crumblinks);
		$bittxt = '';
		if (count($crumbs) > 0)
		{
			$bittxt = "<a href=\"$crumblink\">";
		}
		$bittxt .= $crumbname;
		if (count($crumbs) > 0)
		{
			$bittxt .= "</a>";
		}
		$crumbbits[] = $bittxt;
	}
	if (count($crumbbits) > 0)
	{
		$breadcrumbTrail = implode(" &gt; ",$crumbbits);
	}
}
if(isset($breadcrumbTrail)) {
?>
	<div id="breadcrumbTrail" class="ZymurgyBreadcrumbs">
		<?= $breadcrumbTrail ?>
	</div>
<?php } ?>

<div class="ZymurgyClientArea">
