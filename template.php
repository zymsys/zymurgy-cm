<?php
/**
 * Displays a Page in the assigned Template.
 *
 * @package Zymurgy
 * @subpackage frontend
 */

ob_start();
require_once('cmo.php');
require_once('sitenav.php');

if (array_key_exists('ql', $_GET))
{
	$target = Zymurgy::$db->get("SELECT `targeturl` FROM `zcm_quicklink` WHERE `name`='".
		Zymurgy::$db->escape_string($_GET['ql'])."'");
	if ($target)
	{
		header('Location: '.$target);
		exit;
	}
	else 
	{
		ZymurgyTemplate::DisplayFileNotFound($_GET['ql'], 'Quick Links', '');
	}
}

/**
 * Model class for a Page Template.
 */
class ZymurgyTemplate
{
	/**
	 * ZymurgySiteNavItem for the current page
	 * @var ZymurgySiteNavItem
	 */
	public $sitepage;

	/**
	 * The navigation path for the current page.
	 */
	public $navpath;

	/**
	 * The template used to render the current page.
	 */
	public $template;
	
	/**
	 * Flavour name - defaults to 'pages'
	 *
	 * @var string
	 */
	public $hrefroot;

	private $pagetextcache = array();
	private $inputspeccache = array();
	private $pagetextids = array();
	private $pagetextacls = array();

	/**
 	 * Get the navigation information for a page, given the parent ID and 
	 * the page's texturl.
	 *
	 * @param int $parent The ID of the parent node
	 * @param string $navpart the texturl of the leaf node
	 * @return ZymurgySiteNavItem The navigation information for the page, if 
	 * found. Otherwise, this returns False.
 	 */
	static function GetNavInfo($parent,$navpart)
	{
		$nav = Zymurgy::getsitenav();
		$item = $nav->items[$parent];
//Zymurgy::DbgAndDie($parent, $navpart, $item, $nav->items[0]);
//		echo("Looking for ".$navpart." in ".print_r($item->childrenbynavname, true));

		return array_key_exists(urlencode($navpart), $item->childrenbynavname) ?
			$nav->items[$item->childrenbynavname[urlencode($navpart)]] : false;
	}

	/**
	 * Constructor.
	 *
	 * @param string $navpath The navigation path of the page to render.
	 * @param string $hrefroot The root path used to access the pages system. Used to determine the Flavour of the page content.
	 * @param int $id The ID of the page. Provide if the $navpath and $hrefroot
	 * are not known. Used primarily by the View link in the Pages edit screen.
	 */
	function __construct($navpath, $hrefroot = 'pages', $id = 0)
	{
		$this->hrefroot = $hrefroot;
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
			$flavour = Zymurgy::GetActiveFlavour();
			$flavourid = $flavour ? $flavour['contentprovider'] : 0;
			//Zymurgy::DbgAndDie($flavourid,$flavour);
			foreach ($np as $navpart)
			{
				$navpart = urldecode($navpart);
				$navpage = ZymurgyTemplate::GetNavInfo($parent,$navpart);
				$navpart = Zymurgy::$db->escape_string($navpart);
				if ($navpage === false)
				{
					// Is there a redirect available for this navpart?
					$sql = "select * from zcm_sitepageredirect where parent=$parent and flavour=$flavourid and linkurl='$navpart'";
					$redirect = Zymurgy::$db->get($sql);
					if ($redirect)
					{
						//Yes, this page has a new home.  Find it.
						$newpart = Zymurgy::$db->get("select linkurl from zcm_sitepage where id = {$redirect['sitepage']}");
						if ($newpart)
						{
                            $newparttext = ZIW_Base::GetFlavouredValue($newpart);
                            if ($newparttext != $navpart)
                            {
                                $newpath[] = $newparttext;
                                $parent = $redirect['sitepage'];
                                $doredirect = true;
                                continue;
                            }
                            else
                            {
                                $do404 = "No navpage, redirect to self.";
                                break;
                            }
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

	/**
	 * Display a 404 error message.
	 *
	 * @param string $navpart The texturl of the leaf node that could not 
	 * be found.
	 * @param string $navpath The navigation path of the leaf node that could 
	 * not be found.
	 * @param string $newpath
	 * @param string $msg The reason why the leaf node could not be found.
	 */
	static public function DisplayFileNotFound($navpart, $navpath, $newpath, $msg = "")
	{
		//Zymurgy::DbgAndDie($msg,debug_backtrace());
		
		header("HTTP/1.0 404 Not Found");

		if (array_key_exists("PagesError404", Zymurgy::$config) && (!empty(Zymurgy::$config["PagesError404"])))
		{
			$redirect = Zymurgy::$config["PagesError404"];
			header("Location: ".Zymurgy::flavourMyLink($redirect));
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

	/**
	 * Display a 403 error message.
	 *
	 * @param string $navpart The texturl of the leaf node that could not 
	 * be found.
	 * @param string $navpath The navigation path of the leaf node that could 
	 * not be found.
	 * @param string $newpath
	 * @param string $msg The reason why the leaf node could not be found.
	 */
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
	 * Parse get parameters out of REQUEST_URI into $_GET so that things which 
	 * expect $_GET parameters see them normally.
	 */
	public static function LoadParams()
	{
		if(!isset(Zymurgy::$config["PagesDontLoadParams"])
			|| Zymurgy::$config["PagesDontLoadParams"] !== "on")
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

			if (Zymurgy::checkaclbyid($row['acl'], 'Read'))
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
		if (!array_key_exists($tag, $this->inputspeccache) || $this->inputspeccache[$tag] != $type)
		{
			//Input spec has changed.  Update the DB and the cache.
			$this->inputspeccache[$tag] = $type;
			Zymurgy::$db->run("update zcm_templatetext set inputspec='".
				Zymurgy::$db->escape_string($type)."' where (template=".$this->sitepage->template.") and (tag='".
				Zymurgy::$db->escape_string($tag)."')");
		}
	}

	/**
	 * Override the contents of the page text cache. This can be used by 
	 * instances of the IncludeCodeFile plugin to update the contents of 
	 * the page.
	 */
	public function overridepagetextcache($tag, $content)
	{
		$this->pagetextcache[$tag] = $content;
	}

	/**
	 * Return the contents of a body content item.
	 *
	 * @param string $tag The identifier for the body content item.
	 * @param string $type The Input Spec to use to display/edit the body 
	 * content item.
	 * @return string
	 */
	public function pagetext($tag,$type='html.600.400')
	{
		require_once(Zymurgy::$root.'/zymurgy/InputWidget.php');
		$this->populatepagetextcache($tag,$type);
		$w = new InputWidget();
		$w->datacolumn = 'zcm_pagetext.body';
		$w->editkey = $this->pagetextids[$tag];
		$r = $w->Display($type,'{0}',$this->pagetextcache[$tag]);
		$exploded = explode('.',$type,2);
		$realwidget = InputWidget::Get(array_shift($exploded));
		if ($realwidget->SupportsFlavours() && (!is_numeric($this->pagetextcache[$tag])))
		{
			//This is a flavoured item, so $value should be numeric - since this isn't it needs to be converted.
			$r = $this->pagetextcache[$tag];
			Zymurgy::$db->run("INSERT into `zcm_flavourtext` (`default`) VALUES ('".
				Zymurgy::$db->escape_string($r)."')");
			$ftid = Zymurgy::$db->insert_id();
			Zymurgy::$db->run("UPDATE `zcm_pagetext` SET `body`=$ftid WHERE `id`={$w->editkey}");
		}
		return $r;
	}

	/**
	 * Return the contents of a body content item without transforming it 
	 * using the input spec's display method.
	 *
	 * @param string $tag The identifier for the body content item.
	 * @param string $type The Input Spec to use to display/edit the body 
	 * content item.
	 * @return string
	 */
	public function pagetextraw($tag,$type='html.600.400')
	{
		$this->populatepagetextcache($tag,$type);
		return $this->pagetextcache[$tag];
	}

	/**
	 * Return the contents of ai Image body content item.
	 *
	 * @param string $tag The identifier for the body content item.
	 * @param int $width The width of the item, in pixels.
	 * @param int $height The height of the item, in pixels.
	 * @param string $alt The ALT text to apply to the image.
	 * @return string
	 */
	public function pageimage($tag,$width,$height,$alt='')
	{
		$img = $this->pagetext($tag,"image.$width.$height");
		$ipos = strpos($img,"src=\"");
		if ($ipos>0)
			$img = substr($img,0,$ipos)."alt=\"$alt\" ".substr($img,$ipos);
		return $img;
	}

	/**
	 * Return the contents of the gadgets attached to the page.
	 *
	 * @param string $alignFilter If provided, only render the gadgets 
	 * assigned to the specified alignment.
	 * @return string
	 */
	function pagegadgets(
		$alignFilter = "")
	{
		$wrapdiv = $alignFilter;
		if ($alignFilter === false)
		{
			$alignFilter = '';
		}
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
			if (Zymurgy::checkaclbyid($row['acl'], 'Read'))
			{
				$pp = explode('&',$row['plugin']);
				$instance = urldecode($pp[1]);

				if ($instance == "Page Navigation Name")
				{
					$instance = $navpart;
				}
				
				if ($wrapdiv !== false)
				{
					echo "<div align=\"{$row['align']}\">";
				}
				echo Zymurgy::plugin(urldecode($pp[0]),$instance);
				if ($wrapdiv !== false)
				{
					echo "</div>";
				}
			}
		}

		Zymurgy::$db->free_result($ri);
	}

	private $m_pluginCount = array();

	/**
	 * Return the contents of a specific plugin assigned to the template. An 
	 * instance of the plugin will be created for each page that uses this 
	 * template.
	 *
	 * @param string $pluginName The name of the plugin
	 * @param string $configName The name of the plugin's configuraton
	 * @return string
	 */
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

	/**
	 * Track the user's visit to this page.
	 */
	public function Track()
	{
		//Log the pageview
		if (array_key_exists('zcmtracking',$_COOKIE))
		{
			$orphan = 0;
			$userid = $_COOKIE['zcmtracking'];
		}
		else
		{
			$orphan = 1;
			$userid = uniqid(true);
		}
		$sql = "insert into zcm_sitepageview (trackingid, sitepageid, path, orphan, viewtime) values ('".
			$userid.
			"', ".
			$this->sitepage->id.
			", '".
			$this->navpath.
			"', $orphan,now())";
		Zymurgy::$db->query($sql);
		//Send tracking javascript
		$r[] = "<script>";
		if (!empty($_SERVER['HTTP_REFERER']))
		{
			$r[] = "if (!document.referrer) document.referrer = \"".addslashes($_SERVER['HTTP_REFERER'])."\";";
		}
		$r[] = "Zymurgy.track('$userid');
			</script>";

		echo(implode("\n", $r));
	}
}

$do404 = false;
if (file_exists(Zymurgy::$root."/zymurgy/custom/controller.php"))
{
	require_once Zymurgy::$root."/zymurgy/custom/controller.php";
}

Zymurgy::$router->route();

if (!array_key_exists('p', $_GET) && (!array_key_exists('pageid', $_GET)))
{
	//No page from mod_rewrite, and no completion from router.  404.
	ZymurgyTemplate::DisplayFileNotFound($_SERVER['REQUEST_URI'], 'controller', '');
}

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

if (array_key_exists('tracking',Zymurgy::$config) && (Zymurgy::$config['tracking']))
{
	Zymurgy::$template->Track();
}

$path = Zymurgy::$template->template['path'];
if (file_exists(Zymurgy::$root.$path))
	require_once(Zymurgy::$root.$path);
else
	echo "This page is trying to use a template from $path, but no such file exists.";
?>
