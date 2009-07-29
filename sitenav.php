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

	public function linktext2linkpart($linktext)
	{
		$linktext=strtr($linktext,"()!$'?:,&+-/.ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ",
			"-------------SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
		return str_replace(' ','_',$linktext);
	}

	/**
	 * Recurse through the nav structure to find the chunk under us
	 *
	 * @param int $me
	 * @param array $structpart
	 * @return array or false if not found
	 */
	private function findmyself($me,$structpart)
	{
		foreach ($structpart as $key=>$value)
		{
			if ($key == $me) return $value;
			if ($value)
			{
				$substruct = $this->findmyself($me,$value);
				if($substruct) return $substruct;
			}
		}
		return false;
	}

	public function render($ishorizontal = true, $currentleveonly = false, $childlevelsonly = false, $startpath = '',$hrefroot = 'pages')
	{
		$idpart = empty($startpath) ? uniqid() : ZymurgySiteNav::linktext2linkpart($startpath);
		echo Zymurgy::YUI('fonts/fonts-min.css');
		echo Zymurgy::YUI('menu/assets/skins/sam/menu.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI('container/container_core-min.js');
		echo Zymurgy::YUI('menu/menu-min.js');
		$bar = $ishorizontal ? 'Bar' : '';
?>
<script type="text/javascript">
YAHOO.util.Event.onContentReady("ZymurgyMenu_<?= $idpart ?>", function () {
	var oMenu = new YAHOO.widget.Menu<?= $bar ?>("ZymurgyMenu_<?= $idpart ?>", {
		<?= $ishorizontal? 'autosubmenudisplay: true' : 'position: "static"' ?>,
		hidedelay: 750,
		lazyload: true });
	oMenu.render();
});
</script>
<?
		if ($currentleveonly)
		{
			$structurestart = array();
			foreach ($this->structure as $key=>$value)
			{
				$structurestart[$key] = array();
			}
			$this->structure = $structurestart;
		}
		else
		{
			$structurestart = $this->structure;
		}
		if ($childlevelsonly) {
			$startpath = Zymurgy::$template->navpath;
			//$startpath = str_replace('_',' ',Zymurgy::$template->navpath);
			//echo "<pre>"; print_r(Zymurgy::$template); exit;
		}
		$parent = 0;
		if (!empty($startpath))
		{
			$sp = explode('/',$startpath);
			$anscestors = $sp;
			while ($sp)
			{
				$partname = array_shift($sp);
				foreach ($this->structureparts[$parent] as $key)
				{
					$correctedkey = $this->linktext2linkpart($this->items[$key]->linktext);
					//echo "<div>Testing for [$correctedkey == $partname</div>";
					if ($correctedkey == $partname)
					{
						$parent = $key;
						$structurestart = $structurestart[$key];
						break;
					}
				}
			}
		}
		else
		{
			$anscestors = array();
		}
		echo "<div class=\"yui-skin-sam \">\r\n";
		echo "\t<div id=\"ZymurgyMenu_$idpart\" class=\"yuimenu".strtolower($bar);
		if ($ishorizontal)
			echo " yuimenubarnav";
		echo "\">\r\n";
		echo "\t\t<div class=\"bd\" style=\"border-style: none\">\r\n";
		$this->renderpart($hrefroot,$ishorizontal,0,($parent == 0) ? $this->structure : $this->structure[$parent],$anscestors);
		echo "\t\t</div>\r\n"; //bd
		echo "\t</div>\r\n"; //yuimenubar yuimenubarnav
		echo "</div>\r\n"; //yui-skin-sam
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
				$href .= $this->linktext2linkpart($anscestor).'/';
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
			$hasPermission = $this->haspermission($key, $anscestors);
			$enableItem = true;

			if(!$hasPermission
				&& isset(Zymurgy::$config["Pages.OnACLFailure"])
				&& Zymurgy::$config["Pages.OnACLFailure"] == "hide")
			{
				continue;
			}

			if(!$hasPermission
				&& isset(Zymurgy::$config["Pages.OnACLFailure"])
				&& Zymurgy::$config["Pages.OnACLFailure"] == "disable")
			{
				$enableItem = false;
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
				$this->linktext2linkpart($this->items[$key]->linktext)."\">".
				$this->items[$key]->linktext.
//				" (".($this->haspermission($key, $anscestors) ? "YES" : "NO" ).")".
				"</a>";
			if ($children)
			{
				echo "\r\n";
				$a = $anscestors;
				// array_push($a,$this->items[$key]->linktext);
				$a[$key] = $this->items[$key]->linktext;
				$this->renderpart($hrefroot,$horizontal,$depth+1,$children,$a);
				echo "\r\n$dtabs";
			}
			echo "</li>\r\n";
		}
		echo "$dtabs</ul>\r\n";
		if ($depth>0) echo "$dtabs</div></div>";
	}

	public function haspermission($key, $anscestors)
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
			if(count($this->items[$key]->aclitems) > 0)
			{
				foreach($this->items[$key]->aclitems as $aclitem)
				{
					if($aclitem["permission"] == "Read")
					{
						if(array_key_exists($aclitem["group"], Zymurgy::$member["groups"]))
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
			else
			{
				if(is_null($anscestors))
				{
					$a = $this->getanscestor($key);
				}
				else
				{
					$a = array_reverse($anscestors, true);
				}

				foreach($a as $anscestorID => $anscestorText)
				{
					if(count($this->items[$anscestorID]->aclitems) > 0)
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

	private function getanscestor($key)
	{
		$anscestor = array();

		if($this->items[$key]->parent > 0)
		{
			$anscestor[$this->items[$key]->parent] = $this->items[$this->items[$key]->parent]->linktext;
			$anscestor = array_merge($anscestor, $this->getanscestor($this->items[$key]->parent));
		}

		return $anscestor;
	}
}

/*require_once('cmo.php');
$n = new ZymurgySiteNav();
//echo "<pre>"; print_r($n->structure); echo "</pre>";
$horizontal = false;
if (!$horizontal) echo "<div style=\"width:160px\">";
$n->render($horizontal);
if (!$horizontal) echo "</div>";*/
?>
