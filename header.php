<?php
/**
 * Start HTML document and display navigation for backend modules.
 * 
 * @package Zymurgy
 * @subpackage backend-base
 */

ob_start();

function getAppRoot()
{
	$r = dirname(__FILE__);
        $rp = explode(DIRECTORY_SEPARATOR, $r);
        array_pop($rp);
        return implode(DIRECTORY_SEPARATOR, $rp);
}

$ZymurgyRoot = getAppRoot();

require_once('cmo.php');

if (array_key_exists('showerrors',$_GET))
{
	Zymurgy::enableErrorHandler();
}

$adminlevel = isset($adminlevel) ? $adminlevel : 1;
Zymurgy::memberrequirezcmauth($adminlevel);

$includeNav = true;
include("header_html.php");

function renderZCMNav($parent)
{
	global $donefirstzcmnav;

	$authlevel = Zymurgy::memberzcmauth();
	$sql = "SELECT `zcm_nav`.`id`, `navname`, `navtype`, `navto`, `zcm_features`.`url` ".
		"FROM `zcm_nav` LEFT JOIN `zcm_features` ON `zcm_features`.`id` = `zcm_nav`.`navto` ".
		"WHERE `parent` = '".
		Zymurgy::$db->escape_string($parent).
		"' AND ( `authlevel` <= '".
		Zymurgy::$db->escape_string($authlevel).
		"' OR `authlevel` IS NULL ) ORDER BY `zcm_nav`.`disporder`";
	//echo "<div>$sql</div>";
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
				$href = $nav["url"];
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
		<?= Zymurgy::$member['fullname'] ?>
	</div>
	<div id="zcmnavContentNav" class="bd" style="border-style: none">
    	<ul class="first-of-type" style="padding: 0px">
    		<?php  renderZCMNav(0); ?>
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
		<? if(isset($wikiArticleName)) { ?>
		<div style="float: right; text-align: right; width: 50px;">
			<a target="_blank" href="<?= isset(Zymurgy::$config["userwikihome"]) ? Zymurgy::$config["userwikihome"] : "http://www.zymurgycm.com/userwiki/index.php/" ?><?= $wikiArticleName ?>">?</a>
		</div>
		<? } ?>
		<?= $breadcrumbTrail ?>
	</div>
<?php } ?>

<div class="ZymurgyClientArea" style="margin-left:150px;">
