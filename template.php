<?
ob_start();
require_once('cmo.php');
require_once('sitenav.php');
class ZymurgyTemplate
{
	public $sitepage;
	public $navpath;
	public $template;

	private $pagetextcache = array();
	private $inputspeccache = array();
	private $pagetextids = array();
	private $pagetextacls = array();

	function __construct($navpath, $hrefroot = 'pages', $id = 0)
	{
		$this->LoadParams();
		if($id > 0)
		{
			$this->sitepage = $this->GetSitePage("`id` = '".
				Zymurgy::$db->escape_string($id).
				"'");
		}
		else if (empty($navpath))
		{
			$this->sitepage = $this->GetSitePage("`parent` = 0");
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
				$navpart = Zymurgy::$db->escape_string(ZymurgySiteNav::linktext2linkpart($navpart));

				$row = $this->GetSitePage("`parent` = '".
					Zymurgy::$db->escape_string($parent).
					"' AND `linkurl` = '".
					Zymurgy::$db->escape_string($navpart).
					"'");

				if ($row === false)
				{
					// Is there a redirect available for this navpart?
					$redirect = Zymurgy::$db->get("select * from zcm_sitepageredirect where parent=$parent and linkurl='$navpart'");
					if ($redirect)
					{
						//Yes, this page has a new home.  Find it.
						$newpart = Zymurgy::$db->get("select linkurl from zcm_sitepage where id = {$redirect['sitepage']}");
						if ($newpart)
						{
							$newpath[] = $newpart;
							$parent = $redirect['sitepage'];
							$doredirect = true;
							continue;
						}
						else
						{
							$do404 = true;
							break;
						}
					}
					else
					{
						$do404 = true;
						break;
					}
				}
				else
				{
					$newpath[] = $navpart;
				}
				$parent = $row['id'];
			}
			if ($do404)
			{
				$this->DisplayFileNotFound($navpart, $navpath, $newpath);
			}
			if ($doredirect)
			{
				header("Location: /$hrefroot/".implode('/',$newpath));
				/*echo "Redirect: <pre>\r\n";
				print_r($newpath);
				echo "</pre>";*/
				exit;
			}

			$this->sitepage = $row;
		}

		// -----
		// Check the page to make sure the user actually has permission to view it
		if(!Zymurgy::getsitenav()->haspermission($this->sitepage["id"], null))
		{
			$this->DisplayForbidden($navpart, $navpath, $newpath, "Failed ACL Check");
		}

		// -----
		// Check the page to make sure it within the published range

		$retire = strtotime($this->sitepage["retire"]);
		$golive = strtotime($this->sitepage["golive"]);
		$softlaunch = strtotime($this->sitepage["softlaunch"]);

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

		$this->template = Zymurgy::$db->get("select * from zcm_template where id={$this->sitepage['template']}");
		$this->navpath = $navpath;
		$this->LoadPageText();
	}

	private function GetSitePage($criteria)
	{
		$sql = "SELECT `id`, `template`, `retire`, `golive`, `softlaunch` ".
			"FROM `zcm_sitepage` WHERE $criteria ORDER BY `disporder` LIMIT 0, 1";

		return Zymurgy::$db->get($sql);
	}

	private function DisplayFileNotFound($navpart, $navpath, $newpath, $msg = "")
	{
		header("HTTP/1.0 404 Not Found");

		if(array_key_exists("Pages.Error.404", Zymurgy::$config))
		{
			header("Location: ".Zymurgy::$config["Pages.Error.404"]);
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
		// echo(Zymurgy::$config["Pages.OnACLFailure"]);
		// die();

		if(!Zymurgy::memberauthenticate()
			&& isset(Zymurgy::$config["Pages.OnACLFailure"])
			&& !(Zymurgy::$config["Pages.OnACLFailure"] == "disable")
			|| (Zymurgy::$config["Pages.OnACLFailure"] == "hide"))
		{
			header("Location: ".Zymurgy::$config["Pages.OnACLFailure"]);
		}

		if(array_key_exists("Pages.Error.403", Zymurgy::$config))
		{
			header("Location: ".Zymurgy::$config["Pages.Error.403"]);
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
	private function LoadParams()
	{
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

	private function LoadPageText()
	{
		//Load content types
		$ri = Zymurgy::$db->run("select * from zcm_templatetext where template=".$this->sitepage['template']);
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$this->inputspeccache[$row['tag']] = $row['inputspec'];
		}
		Zymurgy::$db->free_result($ri);
		$ri = Zymurgy::$db->run("select * from zcm_pagetext where sitepage=".$this->sitepage['id']);
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
				$this->sitepage['template']." and tag='".
				Zymurgy::$db->escape_string($tag)."'");
			if ($row === false)
			{
				//Add it
				Zymurgy::$db->run("insert into zcm_templatetext (template,tag,inputspec) values (".
					$this->sitepage['template'].",'".
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
				Zymurgy::$db->escape_string($type)."' where (template=".$this->sitepage['template'].") and (tag='".
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
			Zymurgy::$db->escape_string($this->sitepage["id"]).
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
}

ob_start();

if(array_key_exists("f", $_GET))
{
	$flavours = explode(".", $_GET["f"]);

	foreach($flavours as $flavour)
	{
		Zymurgy::AddActiveFlavour($flavour);;
	}
}

Zymurgy::$template = new ZymurgyTemplate(
	(array_key_exists('p',$_GET)) ? $_GET['p'] : '',
	'pages',
	(array_key_exists('pageid', $_GET)) ? $_GET["pageid"] : 0);

if (file_exists(Zymurgy::$root.Zymurgy::$template->template['path']))
	require_once(Zymurgy::$root.Zymurgy::$template->template['path']);
else
	echo "This page is trying to use a template from ".Zymurgy::$template->template['path'].", but no such file exists.";
?>
