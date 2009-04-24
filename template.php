<?
require_once('cmo.php');
class ZymurgyTemplate
{
	public $sitepage;
	public $navpath;
	public $template;
	private $pagetextcache = array();
	private $inputspeccache = array();
	private $pagetextids = array();
	
	function __construct($navpath)
	{
		if (empty($navpath))
		{
			$this->sitepage = Zymurgy::$db->get("select id,template from zcm_sitepage where parent=0 order by disporder limit 1");
		}
		else
		{
			$np = explode('/',$navpath);
			$parent = 0;
			foreach ($np as $navpart)
			{
				$navpart = Zymurgy::$db->escape_string(str_replace('_',' ',$navpart));
				$row = Zymurgy::$db->get("select id,template from zcm_sitepage where parent=$parent and linktext='$navpart'");
				if ($row === false)
				{
					// How to handle 404 like response?
					echo "$navpart couldn't be found from $navpath.";
					return;
				}
				$parent = $row['id'];
			}
			$this->sitepage = $row;
		}
		$this->template = Zymurgy::$db->get("select * from zcm_template where id={$this->sitepage['template']}");
		$this->navpath = $navpath;
		$this->LoadPageText();
	}
	
	private function LoadPageText()
	{
		$ri = Zymurgy::$db->run("select * from zcm_pagetext where sitepage=".$this->sitepage['id']);
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$this->pagetextcache[$row['tag']] = $row['body'];
			$this->inputspeccache[$row['tag']] = $row['inputspec'];
			$this->pagetextids[$row['tag']] = $row['id'];
		}
		//print_r($this->pagetextids); exit;
		Zymurgy::$db->free_result($ri);
	}
	
	public function pagetext($tag,$type='html.600.400')
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
		require_once(Zymurgy::$root.'/zymurgy/InputWidget.php');
		$w = new InputWidget();
		$w->datacolumn = 'zcm_pagetext.body';
		$w->editkey = $this->pagetextids[$tag];
		return $w->Display($type,'{0}',$this->pagetextcache[$tag]);
	}
	
	public function pageimage($tag,$width,$height,$alt='')
	{
		$img = $this->pagetext($tag,"image.$width.$height");
		$ipos = strpos($img,"src=\"");
		if ($ipos>0)
			$img = substr($img,0,$ipos)."alt=\"$alt\" ".substr($img,$ipos);
		return $img;
	}
	
	function pagegadgets()
	{
		$ri = Zymurgy::$db->run("select * from zcm_sitepageplugin where zcm_sitepage=".
			$this->sitepage['id']." order by disporder");
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
Zymurgy::$template = new ZymurgyTemplate((array_key_exists('p',$_GET)) ? $_GET['p'] : '');
if (file_exists(Zymurgy::$root.Zymurgy::$template->template['path']))
	require_once(Zymurgy::$root.Zymurgy::$template->template['path']);
else 
	echo "This page is trying to use a template from ".Zymurgy::$template->template['path'].", but no such file exists.";
?>