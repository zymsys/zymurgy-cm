<?php
class ZymurgySiteNavItem
{
	public $linktext;
	public $parent;
	public $livedate;
	public $softlaunchdate;
	public $retiredate;
	public $acl;

	public $aclitems = array();

	function __construct(
		$linktext,
		$parent,
		$livedate,
		$softlaunchdate,
		$retiredate,
		$acl)
	{
		$this->linktext = $linktext;
		$this->parent = $parent;
		$this->livedate = $livedate;
		$this->softlaunchdate = $softlaunchdate;
		$this->retiredate = $retiredate;
		$this->acl =  $acl;
	}
}

class ZymurgySiteNav
{
	public $items = array();
	public $structure = array();
	public $structureparts = array();

	function __construct($navinfo='')
	{
		Zymurgy::memberauthenticate();
		Zymurgy::memberauthorize("");

		$ri = Zymurgy::$db->run("select id,linktext,parent,unix_timestamp(golive) as golive,unix_timestamp(softlaunch) as softlaunch,unix_timestamp(retire) as retire, acl from zcm_sitepage order by disporder");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			if (!is_null($row['golive']) || !is_null($row['softlaunch']) || !is_null($row['retire']))
			{
				//Is this page retired?
				if ($row['retire'] > 0 && (time() > $row['retire']))
				{
					//This page is retired
					continue;
				}
				//Is this before the go live date?
				if (!is_null($row['golive']) && (time() < $row['golive']))
				{
					//This is before the go live date.
					//Is this after the soft launch date?
					if (!is_null($row['softlaunch']) && (time() < $row['softlaunch']))
					{
						//Yeah, we haven't even soft-launched yet.  Bail.
						continue;
					}
					//We've soft-launched, but not gone live.  Is the user allowed to view soft launch pages?
					if (!array_key_exists('zymurgy',$_COOKIE))
					{
						//User isn't a Z:CM user, so not allowed to see soft launch pages.
						continue;
					}
				}
			}
			$this->items[$row['id']] = new ZymurgySiteNavItem(
				$row['linktext'],
				$row['parent'],
				$row['golive'],
				$row['softlaunch'],
				$row['retire'],
				$row["acl"]);
			if (array_key_exists($row['parent'],$this->structureparts))
				$this->structureparts[$row['parent']][] = $row['id'];
			else
				$this->structureparts[$row['parent']] = array($row['id']);
		}

		Zymurgy::$db->free_result($ri);

		$this->structure = $this->buildnav(0);

		$sql = "SELECT `zcm_acl`, `group`, `permission` FROM `zcm_aclitem`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of ACLs: ".Zymrugy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			foreach($this->items as $key => $item)
			{
				if($item->acl == $row["zcm_acl"])
				{
					$item->aclitems[] = array(
						"group" => $row["group"],
						"permission" => $row["permission"]);
					$this->items[$key] = $item;
				}
			}
		}

		Zymurgy::$db->free_result($ri);
	}

	private function buildnav($parent)
	{
		if (!array_key_exists($parent,$this->structureparts)) return array();
		$r = array();
		foreach ($this->structureparts[$parent] as $key)
		{
			$r[$key] = $this->buildnav($key);
		}
		return $r;
	}

	/**
	 * Inject a nav item into the navigation structure. This is used primarily
	 * in custom/render.php to modify the menu based on business logic not
	 * supported directly by the nav system.
	 *
	 * @param unknown_type $linktext
	 * @param unknown_type $parent
	 * @param unknown_type $livedate
	 * @param unknown_type $softlaunchdate
	 * @param unknown_type $retiredate
	 * @param unknown_type $acl
	 */
	public function InjectNavItem(
		$linktext,
		$parent,
		$livedate = null,
		$softlaunchdate = null,
		$retiredate = null,
		$acl = 0)
	{
		$this->items[] = new ZymurgySiteNavItem(
			$linktext,
			$parent,
			$livedate,
			$softlaunchdate,
			$retiredate,
			0);
		$navItemID = max(array_keys($this->items));
		$this->structure[$navItemID] = array();
		$this->structureparts[$parent][] = $navItemID;
	}

	/**
	 * Replaces ISO-8859-1 special characthers with dashes and decapitates any accented letters.
	 *
	 * @param $linktext
	 * @return string
	 */
	public static function linktext2linkpart($linktext)
	{
		$linktext=strtr($linktext,"()!$'?:,&+-/.���������������������������������������������������������������������",
			"-------------SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
		return str_replace(' ','_',$linktext);
	}

	public function render(
		$ishorizontal = true,
		$currentlevelonly = false,
		$childlevelsonly = false,
		$startpath = '',
		$hrefroot = 'pages')
	{
		$yuinavbar = new ZymurgySiteNavRender_YUI(
			true,
			$startpath,
			$hrefroot);

		$yuinavbar->childlevelsonly($childlevelsonly);
		$yuinavbar->currentlevelonly($currentlevelonly);

		$yuinavbar->headtags();
		$yuinavbar->render($ishorizontal);
	}

	public function haspermission($key, $anscestors, $recursive = true)
	{
//		echo("<pre>\n");
//		echo("Key: $key\nAnscestors: ");
//		print_r($anscestors);
//		echo("</pre>\n");

		if($key <= 0)
		{
			return true;
		}
		else
		{
			if(array_key_exists($key, $this->items)
				&& count($this->items[$key]->aclitems) > 0)
			{
				foreach($this->items[$key]->aclitems as $aclitem)
				{
					if($aclitem["permission"] == "Read")
					{
						if(is_array(Zymurgy::$member["groups"]) &&
							array_key_exists($aclitem["group"], Zymurgy::$member["groups"]))
						{
							return true;
							break;
						}
					}
				}

				// if we get this far, then the user does not have
				// permission to view this resource
				return false;
			}
			else if ($recursive)
			{
				if(is_null($anscestors))
				{
					$a = $this->getanscestors($key);
				}
				else
				{
					$a = array_reverse($anscestors, true);
				}

				foreach($a as $anscestorID => $anscestorText)
				{
					if(array_key_exists($anscestorID, $this->items)
						&& count($this->items[$anscestorID]->aclitems) > 0)
					{
						foreach($this->items[$anscestorID]->aclitems as $aclitem)
						{
							if($aclitem["permission"] == "Read")
							{
								if(array_key_exists($aclitem["group"], Zymurgy::$member["groups"]))
								{
									return true;
								}
							}
						}

						// if we get this far, then the user does not have
						// permission to view this resource
						return false;
					}
				}
			}
		}

		// If we get this far, then there are no ACLs throughout the
		// entire tree - by default, all users get to view this item
		return true;
	}

	public function getanscestors($key)
	{
		$anscestor = array();
		while ($key)
		{
			$me = $this->items[$key];
			$anscestor[$key] = $me->linktext;
			$key = $me->parent;
		}
		return $anscestor;
	}

}

######################################################################

abstract class ZymurgySiteNavRenderer{
	public $showrecursive;
	public $startpath;
	public $hrefroot;
	public $hideACLfailure;
	
	/**
	 * ZymurgySiteNav reference for this renderer
	 *
	 * @var ZymurgySiteNav
	 */
	protected $sitenav;
	protected $parent;
	protected $anscestors = array();
	protected $hrefprefix;

	private $m_originalStartpath;

	public function __construct(
		$showrecursive = true,
		$startpath = '',
		$hrefroot = 'pages',
		$childlevelsonly = false)
	{
		$this->showrecursive = $showrecursive;
		$this->startpath = $startpath;
		$this->m_originalStartpath = $startpath;
		$this->hrefroot = $hrefroot;
		$this->hideACLfailure = true;
		$this->parent = 0;
		if (!empty($startpath))
		{
			$sp = explode('/',$startpath);
			$anscestors = $sp;
			while ($sp)
			{
				$partname = array_shift($sp);
				foreach ($this->sitenav->structureparts[$this->parent] as $key)
				{
					$correctedkey = $this->linktext2linkpart($this->items[$key]->linktext);
					//echo "<div>Testing for [$correctedkey == $partname</div>";
					if ($correctedkey == $partname)
					{
						$this->parent = $key;
						$structurestart = $structurestart[$key];
						break;
					}
				}
			}
		}
		
		$this->childlevelsonly($childlevelsonly);

		$this->sitenav = Zymurgy::getsitenav();
	}

	public function childlevelsonly($newValue)
	{
		if($newValue)
		{
			$this->startpath = Zymurgy::$template->navpath;
		}
		else
		{
			$this->startpath = $this->m_originalStartpath;
		}
	}

	public function currentlevelonly($newValue)
	{
		if ($newValue){
			$yuinavbar->showrecursive = false;
			$currentpath = explode('/', Zymurgy::$template->navpath);
			array_pop($currentpath);
			if (!empty($currentpath))
				$yuinavbar->startpath = implode('/', $currentpath);
			else
				$yuinavbar->startpath = '';
		}
		else
		{
			$yuinavbar->showrecursive = true;
			$this->startpath = $this->m_originalStartpath;
		}
	}

	public function startatdepth($depth){
		$this->startpath = implode('/',array_slice(explode('/',Zymurgy::$template->navpath),0,$depth));
	}
	
	protected function getname($key){
		return $this->sitenav->items[$key]->linktext;
	}
	protected function getlinkname($key){
		return ZymurgySiteNav::linktext2linkpart($this->sitenav->items[$key]->linktext);
	}

	abstract public function headtags();
	abstract public function render();
}

######################################################################

class ZymurgySiteNavRender_YUI extends ZymurgySiteNavRenderer{

	public function headtags(){
		echo "\t".Zymurgy::YUI('fonts/fonts-min.css');
		echo "\t".Zymurgy::YUI('menu/assets/skins/sam/menu.css');
		echo "\t".Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo "\t".Zymurgy::YUI('container/container_core-min.js');
		echo "\t".Zymurgy::YUI('menu/menu-min.js');
	}

	public function render($ishorizontal = true){
		$idpart = uniqid().ZymurgySiteNav::linktext2linkpart($this->startpath);
		$bar = $ishorizontal ? 'Bar' : '';
?>
<script type="text/javascript">
  // <![CDATA[
	YAHOO.util.Event.onContentReady("ZymurgyMenu_<?= $idpart ?>", function () {
		var oMenu = new YAHOO.widget.Menu<?= $bar ?>("ZymurgyMenu_<?= $idpart ?>", {
			<?= $ishorizontal? 'autosubmenudisplay: true' : 'position: "static"' ?>,
			hidedelay: 750,
			lazyload: true });
		oMenu.render();
	});
  // ]]>
</script>
<div class="yui-skin-sam ">
	<div id="ZymurgyMenu_<?= $idpart ?>" class="yuimenu<? if($ishorizontal) echo "bar yuimenubarnav"?>" >
		<div class="bd" style="border-style: none">
<? $this->renderpart($this->hrefroot,$ishorizontal,0,($this->parent == 0) ? $this->sitenav->structure : $this->sitenav->structure[$this->parent],$this->anscestors); ?>
		</div>
	</div>
</div>
<?
	}
	
	/**
	 * Render part of the site's navigation
	 *
	 * @param string $hrefroot Root of the navigation sections as used by mod_rewrite
	 * @param boolean $horizontal Is this a horizontal nav?  If false, this is a vertical nav.
	 * @param int $depth What is our current depth?
	 * @param array $sp Structure Part; the part of the nav to be rendered
	 * @param array $anscestors Ancestor nav names
	 */
	private function renderpart($hrefroot,$horizontal,$depth,$sp,$anscestors)
	{
		$dtabs = str_repeat("\t",$depth+3);
		$href = "/$hrefroot/";
		if ($anscestors)
		{
			foreach($anscestors as $anscestor)
			{
				$href .= $this->sitenav->linktext2linkpart($anscestor).'/';
			}
		}

		if ($depth > 0) echo "$dtabs<div class=\"yuimenu\"><div class=\"bd\">\r\n";
		echo "$dtabs<ul";
		if ($horizontal)
		{
			echo " class=\"first-of-type";
			if ($depth == 0) echo " zymurgy-horizontal-menu";
			echo "\"";
			$fot = true;
		}
		else
		{
			if ($depth > 0) echo " class=\"first-of-type\"";
			$fot = false;
		}
		echo ">\r\n";
		foreach ($sp as $key=>$children)
		{
			$hasPermission = $this->sitenav->haspermission($key, null);
			//echo "<div>$key: $hasPermission</div>";
			$enableItem = true;

			if(!$hasPermission)
			{
				if (isset(Zymurgy::$config["PagesOnACLFailure"]))
				{
					if (Zymurgy::$config["PagesOnACLFailure"] == "hide")
						continue;
					if (Zymurgy::$config["PagesOnACLFailure"] == "disable")
						$enableItem = false;
					// Default to fail
				}
				else 
				{
					// Default to fail
				}
			}

			echo "$dtabs\t<li class=\"";
			echo "yuimenuitem";
			if ($fot)
			{
				$fot = false;
				echo " first-of-type";
			}
			echo "\">";

			echo "<a class=\"yuimenuitemlabel".
				($enableItem ? "" : "-disabled").
				"\" href=\"".
				($enableItem ? $href : "javascript:;").
				$this->sitenav->linktext2linkpart($this->sitenav->items[$key]->linktext)."\">".
				$this->sitenav->items[$key]->linktext.
//				" (".($this->haspermission($key, $anscestors) ? "YES" : "NO" ).")".
				"</a>";
			if ($children)
			{
				echo "\r\n";
				$a = $anscestors;
				// array_push($a,$this->items[$key]->linktext);
				$a[$key] = $this->sitenav->items[$key]->linktext;
				$this->renderpart($hrefroot,$horizontal,$depth+1,$children,$a);
				echo "\r\n$dtabs";
			}
			echo "</li>\r\n";
		}
		echo "$dtabs</ul>\r\n";
		if ($depth>0) echo "$dtabs</div></div>";
	}
}

######################################################################

class ZymurgySiteNavRender_TXT extends ZymurgySiteNavRenderer{
	public function headtags(){

	}

	public function render(){
		//$this->trimtree();
		$this->renderpart($this->sitenav->structure, $this->hrefprefix, 0);
	}

	private function renderpart($tree, $prefix, $depth){
		$tabs = str_repeat("    ",$depth);
		foreach ($tree as $key=>$children){
			$href = $prefix.$this->getlinkname($key);
			echo "$tabs* ".$this->getname($key).": $href\n";
			if ($children)
				$this->renderpart($children, $href.'/', $depth+1);
		}
	}
}
?>
