<?
require_once('cmo.php');
class ZymurgyTemplate
{
	public $sitepage;
	public $navpath;
	public $template;
	private $pagetextcache = array();
	
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
		//Now $row is our page info.  Get the template info.
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
		}
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
			}
			$this->pagetextcache[$tag] = null;
		}
		return $this->pagetextcache[$tag];
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