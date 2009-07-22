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

	function __construct($navpath, $hrefroot = 'pages', $id = 0)
	{
		$this->LoadParams();
		if($id > 0)
		{
			$sql = "SELECT `id`, `template` FROM `zcm_sitepage` WHERE `id` = '".
				Zymurgy::$db->escape_string($id).
				"' LIMIT 0, 1";
			$this->sitepage = Zymurgy::$db->get($sql);
		}
		else if (empty($navpath))
		{
			$this->sitepage = Zymurgy::$db->get("select id,template from zcm_sitepage where parent=0 order by disporder limit 1");
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
				$row = Zymurgy::$db->get("select id,template from zcm_sitepage where parent=$parent and linkurl='$navpart'");
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
				header("HTTP/1.0 404 Not Found");
				echo "$navpart couldn't be found from $navpath. <!--\r\n";
				print_r($newpath);
				echo "-->";
				exit;
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
		$this->template = Zymurgy::$db->get("select * from zcm_template where id={$this->sitepage['template']}");
		$this->navpath = $navpath;
		$this->LoadPageText();
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
			$this->pagetextcache[$row['tag']] = $row['body'];
			$this->pagetextids[$row['tag']] = $row['id'];
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

		$sql = "SELECT `plugin`, `align` FROM `zcm_sitepageplugin` WHERE `zcm_sitepage` = '".
			Zymurgy::$db->escape_string($this->sitepage["id"]).
			"' AND ".
			$alignFilterCriteria.
			" ORDER BY `disporder`";

		$ri = Zymurgy::$db->run($sql);

		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$pp = explode('&',$row['plugin']);
			$instance = urldecode($pp[1]);
			if ($instance == "Page Navigation Name")
				$instance = $navpart;
			echo "<div align=\"{$row['align']}\">";
			echo Zymurgy::plugin(urldecode($pp[0]),$instance);
			echo "</div>";
		}

		Zymurgy::$db->free_result($ri);
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
