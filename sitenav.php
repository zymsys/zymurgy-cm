<?php
class ZymurgySiteNavItem
{
	public $linktext;
	public $parent;
	public $livedate;
	public $softlaunchdate;
	public $retiredate;
	
	function __construct($linktext,$parent,$livedate,$softlaunchdate,$retiredate)
	{
		$this->linktext = $linktext;
		$this->parent = $parent;
		$this->livedate = $livedate;
		$this->softlaunchdate = $softlaunchdate;
		$this->retiredate = $retiredate;
	}
}

class ZymurgySiteNav
{
	public $items = array();
	public $structure = array();
	private $structureparts = array();
	
	function __construct($navinfo='')
	{
		$ri = Zymurgy::$db->run("select id,linktext,parent,golive,softlaunch,retire from zcm_sitepage order by disporder");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$this->items[$row['id']] = new ZymurgySiteNavItem($row['linktext'],$row['parent'],$row['golive'],$row['softlaunch'],$row['retire']);
			if (array_key_exists($row['parent'],$this->structureparts))
				$this->structureparts[$row['parent']][] = $row['id'];
			else 
				$this->structureparts[$row['parent']] = array($row['id']);
		}
		Zymurgy::$db->free_result($ri);
		$this->structure = $this->buildnav(0);
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
		return str_replace(' ','_',$linktext);
	}
	
	public function render($horizontal = true, $hrefroot = 'pages')
	{
		echo Zymurgy::YUI('fonts/fonts-min.css');
		echo Zymurgy::YUI('menu/assets/skins/sam/menu.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI('container/container_core-min.js');
		echo Zymurgy::YUI('menu/menu-min.js');
		$bar = $horizontal ? 'Bar' : '';
?>
<script type="text/javascript">
YAHOO.util.Event.onContentReady("ZymurgyMenu_<?= $hrefroot ?>", function () {
	var oMenu = new YAHOO.widget.Menu<?= $bar ?>("ZymurgyMenu_<?= $hrefroot ?>", { 
		<?= $horizontal? 'autosubmenudisplay: true' : 'position: "static"' ?>, 
		hidedelay: 750, 
		lazyload: true });
	oMenu.render();
});
</script>
<?
		echo "<div class=\"yui-skin-sam \">\r\n";
		echo "\t<div id=\"ZymurgyMenu_$hrefroot\" class=\"yuimenu".strtolower($bar);
		if ($horizontal)
			echo " yuimenubarnav";
		echo "\">\r\n";
		echo "\t\t<div class=\"bd\">\r\n";
		$this->renderpart($hrefroot,$horizontal,0,$this->structure,array());
		echo "\t\t</div>\r\n"; //bd
		echo "\t</div>\r\n"; //yuimenubar yuimenubarnav
		echo "</div>\r\n"; //yui-skin-sam
	}
	
	private function renderpart($hrefroot,$horizontal,$depth,$sp,$anscestors)
	{
		$dtabs = str_repeat("\t",$depth+3);
		$href = "/$hrefroot/";
		if ($anscestors) $href .= $this->linktext2linkpart(implode('/',$anscestors)).'/';
		if ($depth > 0) echo "$dtabs<div class=\"yuimenu\"><div class=\"bd\">\r\n";
		echo "$dtabs<ul";
		if ($horizontal)
		{
			echo " class=\"first-of-type\"";
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
			echo "$dtabs\t<li class=\"";
			echo "yuimenuitem";
			if ($fot)
			{
				$fot = false;
				echo " first-of-type";
			}
			echo "\"><a class=\"yuimenuitemlabel\" href=\"$href".$this->linktext2linkpart($this->items[$key]->linktext)."\">".$this->items[$key]->linktext."</a>";
			if ($children) 
			{
				echo "\r\n";
				$a = $anscestors;
				array_push($a,$this->items[$key]->linktext);
				$this->renderpart($hrefroot,$horizontal,$depth+1,$children,$a);
				echo "\r\n$dtabs";
			}
			echo "</li>\r\n";
		}
		echo "$dtabs</ul>\r\n";
		if ($depth>0) echo "$dtabs</div></div>";
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