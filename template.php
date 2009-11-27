<?
/**
 *
 * @package Zymurgy
 * @subpackage frontend
 */
ob_start();
require_once('cmo.php');
require_once('sitenav.php');
class ZymurgyTemplate
{
	/**
	 * ZymurgySiteNavItem for the current page
	 *
	 * @var ZymurgySiteNavItem
	 */
	public $sitepage;
	public $navpath;
	public $template;

	private $pagetextcache = array();
	private $inputspeccache = array();
	private $pagetextids = array();
	private $pagetextacls = array();

	static function GetNavInfo($parent,$navpart)
	{
		$nav = Zymurgy::getsitenav();
		$item = $nav->items[$parent];
		return array_key_exists($navpart,$item->childrenbynavname) ?
			$nav->items[$item->childrenbynavname[$navpart]] : false;
	}

	function __construct($navpath, $hrefroot = 'pages', $id = 0)
	{
		//echo $navpath;
		//$this->LoadParams();
		if($id > 0)
		{
			$this->sitepage = $this->GetSitePage("`id` = '".
				Zymurgy::$db->escape_string($id).
				"'");
		}
		else if (empty($navpath))
		{
			$this->sitepage = $this->GetSitePage("`parent` = 0");
			$np = array();
		}
		else
		{
			$np = explode('/',$navpath);
			$parent = 0;
			$newpath = array();
			$do404 = false;
			$doredirect = false;
			foreach ($np as $navpart)
			{
				$navpart = Zymurgy::$db->escape_string($navpart);
				$navpage = ZymurgyTemplate::GetNavInfo($parent,$navpart);
				if ($navpage === false)
				{
					// Is there a redirect available for this navpart?
					$flavour = Zymurgy::GetActiveFlavour();
					$flavourid = $flavour ? $flavour['id'] : 0;
					$sql = "select * from zcm_sitepageredirect where parent=$parent and flavour=$flavourid and linkurl='$navpart'";
					$redirect = Zymurgy::$db->get($sql);
					if ($redirect)
					{
						//Yes, this page has a new home.  Find it.
						$newpart = Zymurgy::$db->get("select linkurl from zcm_sitepage where id = {$redirect['sitepage']}");
						if ($newpart)
						{
							$newpath[] = ZIW_Base::GetFlavouredValue($newpart);
							$parent = $redirect['sitepage'];
							$doredirect = true;
							continue;
						}
						else
						{
							$do404 = "No navpage, redirect found but no newpart.";
							break;
						}
					}
					else
					{
						$do404 = "No navpage, no redirect: $sql";
						break;
					}
				}
				else
				{
					$newpath[] = $navpart;
				}
				$parent = $navpage->id;
			}
			if ($do404)
			{
				$this->DisplayFileNotFound($navpart, $navpath, $newpath, $do404);
			}
			if ($doredirect)
			{
				header("Location: /$hrefroot/".implode('/',$newpath));
				/*echo "Redirect: <pre>\r\n";
				print_r($newpath);
				echo "</pre>";*/
				exit;
			}

			$this->sitepage = $navpage;
		}

//		die("<pre>".print_r($this->sitepage, true)."</pre>");

		// -----
		// Check the page to make sure the user actually has permission to view it
		if(!Zymurgy::getsitenav()->haspermission($this->sitepage->id, null))
		{
			$this->DisplayForbidden($navpart, $navpath, $newpath, "Failed ACL Check");
		}

		// -----
		// Check the page to make sure it within the published range

		$retire = strtotime($this->sitepage->retiredate);
		$golive = strtotime($this->sitepage->livedate);
		$softlaunch = strtotime($this->sitepage->softlaunchdate);

		// If all three dates are the same, this page was created while the
		// default JSCalendar date was set to the current timestamp. Ignore
		// these fields when this has occurred.
		if($retire == $golive && $retire == $softlaunch)
		{
			// do nothing
		}
		else
		{
			// echo("retire: $retire<br>golive: $golive<br>softlaunch: $softlaunch<bR>time: ".time());

			if($retire > 0 && $retire < time())
			{
				$this->DisplayFileNotFound($navpart, $navpath, $newpath, "Page retired");
			}
			else if($golive > time())
			{
				// die(array_key_exists('zymurgy',$_COOKIE) ? "Logged in" : "Not logged in");

				if(!array_key_exists('zymurgy',$_COOKIE) || $softlaunch <= 0)
				{
					$this->DisplayFileNotFound($navpart, $navpath, $newpath, "Page not yet live");
				}
				else if($softlaunch > time())
				{
					$this->DisplayFileNotFound($navpart, $navpath, $newpath, "Page not yet soft launched");
				}
			}
		}
		// -----

		$this->template = Zymurgy::$db->get("select * from zcm_template where id={$this->sitepage->template}");
		$this->template['path'] = ZIW_Base::GetFlavouredValue($this->template['path'],NULL,true);
		$this->navpath = $navpath;
		$this->LoadPageText();
	}

	private function GetSitePage($criteria)
	{
		$sql = "SELECT `id`, `linktext`, `parent`, `template`, `retire`, `golive`, `softlaunch`, `retire`, `acl` ".
			"FROM `zcm_sitepage` WHERE $criteria ORDER BY `disporder` LIMIT 0, 1";
		$row = Zymurgy::$db->get($sql);

		$sitepage = new ZymurgySiteNavItem(
			$row["id"],
			$row["linktext"],
			$row["linktext"],
			$row["parent"],
			$row["golive"],
			$row["softlaunch"],
			$row["retire"],
			$row["acl"],
			$row["template"]);

		return $sitepage;
	}

	public function DisplayFileNotFound($navpart, $navpath, $newpath, $msg = "")
	{
		header("HTTP/1.0 404 Not Found");

		if (array_key_exists("PagesError404", Zymurgy::$config) && (!empty(Zymurgy::$config["PagesError404"])))
		{
			header("Location: ".Zymurgy::$config["PagesError404"]);
		}
		else
		{
			echo("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n");
			echo("<html>\n");
			echo("<head>\n");
			echo(Zymurgy::headtags());
			echo("</head>\n");
			echo("<body>\n");
			echo("<h1>Not Found</h1>\n");
			echo("<p>$navpart couldn't be found from $navpath.</p>\n");
			echo('<p>'.$_SERVER['REQUEST_URI']."</p>\n");
			echo("<!--\n");
			print_r($newpath);
			echo("\n$msg");
			echo "-->\n";
			echo("<hr>\n");
			echo("<i>Zymurgy:CM ".date("F d, Y H:i:s")."</i>\n");
			echo("</body>\n");
			echo("</html>\n");
		}


		exit;
	}

	private function DisplayForbidden($navpart, $navpath, $newpath, $msg = "")
	{
		header("HTTP/1.0 403 Forbidden");

		// echo(Zymurgy::memberauthenticate() ? "AUTHED" : "NOT AUTHED")."<br>";
		// echo(Zymurgy::$config["PagesOnACLFailure"]);
		// die();

		if(!Zymurgy::memberauthenticate()
			&& array_key_exists("PagesOnACLFailure",Zymurgy::$config) &&
			( isset(Zymurgy::$config["PagesOnACLFailure"])
			&& !(Zymurgy::$config["PagesOnACLFailure"] == "disable")
			|| (Zymurgy::$config["PagesOnACLFailure"] == "hide")))
		{
			header("Location: ".Zymurgy::$config["PagesOnACLFailure"]);
		}

		if(array_key_exists("PagesError403", Zymurgy::$config))
		{
			header("Location: ".Zymurgy::$config["PagesError403"]);
		}
		else
		{
			echo("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n");
			echo("<html>\n");
			echo("<head>\n");
			echo(Zymurgy::headtags());
			echo("</head>\n");
			echo("<body>\n");
			echo("<h1>Forbidden</h1>\n");
			echo("<p>User does not have permission to view $navpath</p>\n");
			echo("<!--\n");
			print_r($newpath);
			echo("\n$msg");
			echo "-->\n";
			echo("<hr>\n");
			echo("<i>Zymurgy:CM ".date("F d, Y H:i:s")."</i>\n");
			echo("</body>\n");
			echo("</html>\n");
		}


		exit;
	}

	/**
	 * Parse get parameters out of REQUEST_URI into $_GET so that things which expect $_GET parameters see them normally.
	 *
	 */
	public static function LoadParams()
	{
		$regexparts = array();
		// was mod_rewrite used?
		if (preg_match('@^/([^/]+)/([^?#]+)@', $_SERVER['REQUEST_URI'], $regexparts)){
			unset($_GET['f']);
			// redo the rewrite because of a bug in mod_rewrite

			$_GET['p']=$regexparts[2];
			if ($regexparts[1] != 'pages')
				$_GET['f'] = $regexparts[1];
		}else{
			// this page was called directly
			$ru = explode('?',$_SERVER['REQUEST_URI'],2);
			if (array_key_exists(1,$ru))
			{
				$pp = explode('&',$ru[1]);
				foreach ($pp as $part)
				{
					$get = explode('=',$part,2);
					$_GET[$get[0]] = array_key_exists(1,$get) ? $get[1] : false;
				}
			}
		}
	}

	private function LoadPageText()
	{
		//Load content types
		$ri = Zymurgy::$db->run("select * from zcm_templatetext where template=".$this->sitepage->template);
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$this->inputspeccache[$row['tag']] = $row['inputspec'];
		}
		Zymurgy::$db->free_result($ri);
		$ri = Zymurgy::$db->run("select * from zcm_pagetext where sitepage=".$this->sitepage->id);
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$this->pagetextids[$row['tag']] = $row['id'];
			$this->pagetextacls[$row["tag"]] = $row["acl"];

			$mayView = true;

			// -----
			// Check to see if the user has access to this block of site text
			if($row["acl"] > 0)
			{
				$mayView = false;

				Zymurgy::memberauthenticate();
				Zymurgy::memberauthorize("");

				$aclsql = "SELECT `group` FROM `zcm_aclitem` WHERE `zcm_acl` = '".
					Zymurgy::$db->escape_string($row["acl"]).
					"' AND `permission` = 'Read'";
				$aclri = Zymurgy::$db->query($aclsql)
					or die("Could not confirm ACL: ".Zymurgy::$db->error().", $aclsql");

				while(($aclRow = Zymurgy::$db->fetch_array($aclri)) !== FALSE)
				{
					if(array_key_exists($aclRow["group"], Zymurgy::$member["groups"]))
					{
						$mayView = true;
						break;
					}
				}

				Zymurgy::$db->free_result($aclri);
			}

			if($mayView)
			{
				$this->pagetextcache[$row['tag']] = $row['body'];
			}
			else
			{
				$this->pagetextcache[$row['tag']] = "";
			}
		}
		Zymurgy::$db->free_result($ri);
	}

	private function populatepagetextcache($tag,$type)
	{
		if (!array_key_exists($tag,$this->pagetextcache))
		{
			$row = Zymurgy::$db->get("select * from zcm_templatetext where template=".
				$this->sitepage->template." and tag='".
				Zymurgy::$db->escape_string($tag)."'");
			if ($row === false)
			{
				//Add it
				Zymurgy::$db->run("insert into zcm_templatetext (template,tag,inputspec) values (".
					$this->sitepage->template.",'".
					Zymurgy::$db->escape_string($tag)."','".
					Zymurgy::$db->escape_string($type)."')");
				$this->pagetextids[$tag] = Zymurgy::$db->insert_id();
			}
			else
			{
				$this->pagetextids[$tag] = 0; //Will fail to relate to data, but at this point there's no data anyway.
			}
			$this->pagetextcache[$tag] = null;
			$this->inputspeccache[$tag] = $type;
		}
		if ($this->inputspeccache[$tag] != $type)
		{
			//Input spec has changed.  Update the DB and the cache.
			$this->inputspeccache[$tag] = $type;
			Zymurgy::$db->run("update zcm_templatetext set inputspec='".
				Zymurgy::$db->escape_string($type)."' where (template=".$this->sitepage->template.") and (tag='".
				Zymurgy::$db->escape_string($tag)."')");
		}
	}

	public function overridepagetextcache($tag, $content)
	{
			$this->pagetextcache[$tag] = $content;
	}

	public function pagetext($tag,$type='html.600.400')
	{
		require_once(Zymurgy::$root.'/zymurgy/InputWidget.php');
		$this->populatepagetextcache($tag,$type);
		$w = new InputWidget();
		$w->datacolumn = 'zcm_pagetext.body';
		$w->editkey = $this->pagetextids[$tag];
		return $w->Display($type,'{0}',$this->pagetextcache[$tag]);
	}

	public function pagetextraw($tag,$type='html.600.400')
	{
		$this->populatepagetextcache($tag,$type);
		return $this->pagetextcache[$tag];
	}

	public function pageimage($tag,$width,$height,$alt='')
	{
		$img = $this->pagetext($tag,"image.$width.$height");
		$ipos = strpos($img,"src=\"");
		if ($ipos>0)
			$img = substr($img,0,$ipos)."alt=\"$alt\" ".substr($img,$ipos);
		return $img;
	}

	function pagegadgets(
		$alignFilter = "")
	{
		$alignFilterCriteria = "1 = 1";

		if(strlen($alignFilter) > 0)
		{
			$alignFilterCriteria = "`align` = '".
				Zymurgy::$db->escape_string($alignFilter).
				"'";
		}

		$sql = "SELECT `plugin`, `align`, `acl` FROM `zcm_sitepageplugin` WHERE `zcm_sitepage` = '".
			Zymurgy::$db->escape_string($this->sitepage->id).
			"' AND ".
			$alignFilterCriteria.
			" ORDER BY `disporder`";

		$ri = Zymurgy::$db->run($sql);

		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$mayView = true;

			// -----
			// Check to see if the user has access to this block of site text
			if($row["acl"] > 0)
			{
				$mayView = false;

				Zymurgy::memberauthenticate();
				Zymurgy::memberauthorize("");

				$aclsql = "SELECT `group` FROM `zcm_aclitem` WHERE `zcm_acl` = '".
					Zymurgy::$db->escape_string($row["acl"]).
					"' AND `permission` = 'Read'";
				$aclri = Zymurgy::$db->query($aclsql)
					or die("Could not confirm ACL: ".Zymurgy::$db->error().", $aclsql");

				while(($aclRow = Zymurgy::$db->fetch_array($aclri)) !== FALSE)
				{
					if(array_key_exists($aclRow["group"], Zymurgy::$member["groups"]))
					{
						$mayView = true;
						break;
					}
				}

				Zymurgy::$db->free_result($aclri);
			}

			if($mayView)
			{
				$pp = explode('&',$row['plugin']);
				$instance = urldecode($pp[1]);

				if ($instance == "Page Navigation Name")
					$instance = $navpart;

				echo "<div align=\"{$row['align']}\">";
				echo Zymurgy::plugin(urldecode($pp[0]),$instance);
				echo "</div>";
			}
		}

		Zymurgy::$db->free_result($ri);
	}

	private $m_pluginCount = array();

	public function pagegadget($pluginName, $configName)
	{
		// ----------
		// Determine the next ID for the plugin/config combination being
		// rendered on the page
		$instanceName = $this->navpath." ".$configName;

		if(array_key_exists($instanceName, $this->m_pluginCount))
		{
			$this->m_pluginCount[$instanceName] =
				$this->m_pluginCount[$instanceName] + 1;
		}
		else
		{
			$this->m_pluginCount[$instanceName] = 1;
		}

		$instanceName = $instanceName." ".$this->m_pluginCount[$instanceName];

		// ----------
		// Search for an existing instance of the plugin.

		$sql = "SELECT `id` FROM `zcm_plugininstance` WHERE EXISTS(SELECT 1 FROM `zcm_plugin` WHERE `zcm_plugin`.`id` = `zcm_plugininstance`.`plugin` AND `zcm_plugin`.`name` = '".
			Zymurgy::$db->escape_string($pluginName).
			"') AND `name` = '".
			Zymurgy::$db->escape_string($instanceName).
			"'";
		$instanceID = Zymurgy::$db->get($sql);

		// ----------
		// If the plugin instance does not exist, create one associated with
		// the given config.

		if($instanceID <= 0)
		{
			$sql = "SELECT `id` FROM `zcm_pluginconfiggroup` WHERE `name` = '".
				Zymurgy::$db->escape_string($pluginName).
				": ".
				Zymurgy::$db->escape_string($configName).
				"'";
			$configID = Zymurgy::$db->get($sql);

			if($configID <= 0)
			{
				echo("Creating config<br>");

				$sql = "INSERT INTO `zcm_pluginconfiggroup` ( `name` ) VALUES ( '".
					Zymurgy::$db->escape_string($pluginName).
					": ".
					Zymurgy::$db->escape_string($configName).
					"')";
				Zymurgy::$db->query($sql)
					or die("Could not save new plugin config group: ".Zymurgy::$db->error().", $sql");
				$configID = Zymurgy::$db->insert_id();

				$sql = "INSERT INTO `zcm_pluginconfigitem` ( `config`, `key`, `value` ) SELECT '".
					Zymurgy::$db->escape_string($configID).
					"', `key`, `value` FROM `zcm_pluginconfigitem` WHERE `config` IN ( SELECT `id` FROM `zcm_pluginconfiggroup` WHERE `name` = '".
					Zymurgy::$db->escape_string($pluginName).
					": Default' )";
				Zymurgy::$db->query($sql)
					or die("Could not save new plugin config items: ".Zymurgy::$db->error().", $sql");
			}

//			echo("Creating plugin instance<br>");

			$sql = "INSERT INTO `zcm_plugininstance` ( `plugin`, `name`, `private`, `config` ) SELECT `id`, '".
				Zymurgy::$db->escape_string($instanceName).
				"', 0, '".
				Zymurgy::$db->escape_string($configID).
				"' FROM `zcm_plugin` WHERE `name` = '".
				Zymurgy::$db->escape_string($pluginName).
				"'";
			Zymurgy::$db->query($sql)
				or die("Could not create new plugin instance: ".Zymurgy::$db->error().", $sql");
		}

//		echo("Rendering plugin");

		// ----------
		// Return the rendered plugin

		return Zymurgy::plugin($pluginName, $instanceName);
	}
}

$do404 = false;

ZymurgyTemplate::LoadParams();

if(!array_key_exists("pageid", $_GET) && array_key_exists("f", $_GET))
{
	$flavour = $_GET["f"];
	if (!Zymurgy::SetActiveFlavour($flavour))
	{
		$do404 = true;
	}
}

Zymurgy::$template = new ZymurgyTemplate(
	(array_key_exists('p',$_GET)) ? $_GET['p'] : '',
	isset($flavour) ? $flavour : 'pages',
	(array_key_exists('pageid', $_GET)) ? $_GET["pageid"] : 0);

if ($do404)
{
	Zymurgy::$template->DisplayFileNotFound($flavour, 'flavours', '');
}

$path = Zymurgy::$template->template['path'];
if (file_exists(Zymurgy::$root.$path))
	require_once(Zymurgy::$root.$path);
else
	echo "This page is trying to use a template from $path, but no such file exists.";
?>
