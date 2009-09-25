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
	private $structureparts = array();

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
	 * Replaces ISO-8859-1 special characthers with dashes and decapitates any accented letters.
	 *
	 * @param $linktext
	 * @return string
	 */
	public static function linktext2linkpart($linktext)
	{
		$linktext=strtr($linktext,"()!$'?:,&+-/.ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ",
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

		if($this->items[$key]->parent > 0)
		{
			$anscestor[$this->items[$key]->parent] = $this->items[$this->items[$key]->parent]->linktext;
			$anscestor = array_merge($anscestor, $this->getanscestors($this->items[$key]->parent));
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
	protected $sitenav;

	protected $trimmedtree;
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

	protected function trimtree(){
		$navtree = $this->sitenav->structure;
		$anscestors = array();
		if (!empty($this->startpath)){
			$sp = explode('/',$this->startpath);
			$anscestors = $sp;
			while ($sp){
				$partname = array_shift($sp);
				foreach ($navtree as $key => $subtree){
					$correctedkey = ZymurgySiteNav::linktext2linkpart($this->items[$key]->linktext);
					if ($correctedkey == $partname){
						$navtree = $subtree;
						break;
					}
				}
			}
		}

		$hrefprefix = '/'.$this->hrefroot.'/';
		foreach ($anscestors as $ancestor)
			$hrefprefix .= ZymurgySiteNav::linktext2linkpart($ancestor).'/';
		$this->hrefprefix = $hrefprefix;

		if ($this->showrecursive){
			$this->trimmedtree = $navtree;
		}else{
			$this->trimmedtree = array();
			foreach ($navtree as $key => $subtree)
				$this->trimmedtree[$key] = array();
		}

		if($this->hideACLfailure)
			$this->ACLtrim($this->trimmedtree);
	}

	private function ACLtrim(&$tree){
		foreach ($tree as $key => &$subtree){
			if (!$this->sitenav->haspermission($key, null, false))
				unset($tree[$key]);
			else
				$this->ACLtrim($subtree);
		}
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

		$this->trimtree();
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
<? $this->renderpart($this->trimmedtree, $this->hrefprefix, 0, $ishorizontal); ?>
		</div>
	</div>
</div>
<?
	}

	private function renderpart($tree, $prefix, $depth, $horizontal){
		$dtabs = str_repeat("\t",$depth+3);

		if ($depth > 0) echo "$dtabs<div class=\"yuimenu\"><div class=\"bd\">\r\n";
		echo "$dtabs<ul";
		if ($horizontal)
		{
			echo " class=\"first-of-type";
			if ($depth == 0) echo " zymurgy-horizontal-menu";
			echo "\"";
			$fot = " first-of-type";
		}
		else
		{
			if ($depth > 0) echo " class=\"first-of-type\"";
			$fot = '';
		}
		echo ">\n";
		foreach ($tree as $key=>$children)
		{
			$name = $this->getname($key);
			$href = '#';
			if ($this->sitenav->haspermission($key, null)){
				$href = $prefix.$this->getlinkname($key);
				$disabled = '';
			}else{
				$href = '#';
				$disabled = '-disabled';
			}

			echo "$dtabs\t<li class=\"yuimenuitem$fot\"><a class=\"yuimenuitemlabel$disabled\" href=\"$href\">$name</a>";
			if ($children)
			{
				echo "\n";
				$this->renderpart($children, $href.'/', $depth+1, $horizontal);
				echo "\r\n$dtabs";
			}
			echo "</li>\n";
			$fot='';
		}
		echo "$dtabs</ul>\n";
		if ($depth>0) echo "$dtabs</div></div>";
	}
}

######################################################################

class ZymurgySiteNavRender_TXT extends ZymurgySiteNavRenderer{
	public function headtags(){

	}

	public function render(){
		$this->trimtree();
		$this->renderpart($this->trimmedtree, $this->hrefprefix, 0);
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
