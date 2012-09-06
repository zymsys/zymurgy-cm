<?php
/**
 * Helper methods for rendering a web site's navigation.
 *
 * @package Zymurgy
 * @subpackage frontend
 */

class ZymurgySiteNavItem
{
	/**
	 * This item's ID in the database
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The page title to display in the nav nemu.
	 * @var string
	 */
	public $linktext;
	/**
	 * The filename of the page in the url (page title with special characters and spaces replaced).
	 * @var string
	 */
	public $linkurl;

	/**
	 * The golive date
	 * @var timestamp
	 */
	public $livedate;
	/**
	 * The softlaunch date
	 * @var timestamp
	 */
	public $softlaunchdate;
	/**
	 * The retire  date
	 * @var timestamp
	 */
	public $retiredate;

	/**
	 * The ACL database ID
	 * @var int
	 */
	public $acl;

	/**
	 * The template database ID
	 *
	 * @var int
	 */
	public $template;
	/**
	 * The ACL entriers for the page
	 * @var array
	 */
	public $aclitems = array();

	/**
	 * The database ID of the page's parent (root is 0)
	 * @var int
	 */
	public $parent;

	/**
	 * The database IDs of the page's children
	 * @var array of integers
	 */
	public $children = array();

	/**
	 * The database IDs of the page's children indexed by the nav name of the child
	 * @var array of integers
	 */
	public $childrenbynavname = array();

	function __construct(
		$id,
		$linktext,
		$linkurl,
		$parent,
		$livedate,
		$softlaunchdate,
		$retiredate,
		$acl,
		$template)
	{
		$this->id = $id;
		$this->linktext = $linktext;
		$this->linkurl = $linkurl;
		$this->parent = $parent;
		$this->livedate = $livedate;
		$this->softlaunchdate = $softlaunchdate;
		$this->retiredate = $retiredate;
		$this->acl =  $acl;
		$this->template = $template;
	}
}

/**
 * A class to hold the site navigation tree.  Will fill itself when constructed.
 *
 * @author George
 *
 */
class ZymurgySiteNav
{
	/**
	 * The nav items by database ID.
	 *
	 * @var array of ZymurgySiteNavItem objects
	 */
	public $items = array();

	/**
	 * Create a new navigation tree and fill it from the database.
	 *
	 */
	function __construct($forflavour = NULL)
	{
		Zymurgy::memberauthenticate();
		Zymurgy::memberauthorize("");

		// temporary holding point for navigation tree structure
		$structureparts = array();

		// check if user can see softlaunch pages
		$cansoftlaunch = false;
		$softlaunch_ACL = array('Zymurgy:CM - User', 'Zymurgy:CM - Administrator', 'Zymurgy:CM - Webmaster');
		if (is_array(Zymurgy::$member["groups"]))
		{
			foreach(Zymurgy::$member["groups"] as $group)
			{
//				echo("<!-- Searching for $group -->\n");

				if(in_array($group, $softlaunch_ACL))
				{
//					echo("<!-- Found -->\n");

					$cansoftlaunch = true;
				}
			}
		}

		$ri = Zymurgy::$db->run("select id,linktext,linkurl,parent,unix_timestamp(golive) as golive,unix_timestamp(softlaunch) as softlaunch,unix_timestamp(retire) as retire, acl, template from zcm_sitepage order by disporder");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			if (!is_null($row['golive']) || !is_null($row['softlaunch']) || !is_null($row['retire']))
			{
				//Is this page retired?
				if ($row['retire'] && (time() > $row['retire']))
					continue;

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
					if (!$cansoftlaunch)
					{
						//User isn't a Z:CM user, so not allowed to see soft launch pages.
						continue;
					}
				}
			}

			$this->items[$row['id']] = new ZymurgySiteNavItem(
				$row['id'],
				ZIW_Base::GetFlavouredValue($row['linktext'],$forflavour),
				ZIW_Base::GetFlavouredValue($row['linkurl'],$forflavour),
				$row['parent'],
				$row['golive'],
				$row['softlaunch'],
				$row['retire'],
				$row['acl'],
				$row['template']
			);

			// add item as child of parent node
			$structureparts[$row['parent']][] = $row['id'];

		}

		Zymurgy::$db->free_result($ri);

		// dummy node for root to assint in tree trversal
			$this->items[0] = new ZymurgySiteNavItem(0,'Root','',0,null,null,null,null,0);

		// copy the structure into the nav items children attributes.
		foreach ($structureparts as $key => $children)
		{
            if (!isset($this->items[$key])) $this->items[$key] = new stdClass();
			$this->items[$key]->children = $children;
			//Zymurgy::DbgAndDie($this->items[$key]);
			foreach ($children as $childkey)
			{
				$this->items[$key]->childrenbynavname[$this->items[$childkey]->linkurl] = $childkey;
			}
		}

		$sql = "SELECT `zcm_acl`, `group` FROM `zcm_aclitem`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve list of ACLs: ".Zymrugy::$db->error().", $sql");

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			foreach($this->items as $key => &$item)
			{
//				echo("<pre>".print_r($item, true)."</pre>");

				if($item instanceof ZymurgySiteNavItem)
				{
					if($item->acl == $row["zcm_acl"])
						$item->aclitems[] = array(
							"group" => $row["group"]
						);
				}
			}
		}

		Zymurgy::$db->free_result($ri);
	}

	/**
	 * escape a page title for use in URLs
	 *
	 * @param string $text utf-8
	 * @return string urlencoded
	 */
	public static function linktext2linkpart($text){
		return rawurlencode(strtr($text,' \/','_||'));
	}

	/**
	 * Deprecated, renders a YUI menu bar.  Use ZymurgySiteNavRenderer_YUI instead.
	 *
	 * @deprecated
	 *
	 * @param $ishorizontal bool
	 * @param $currentlevelonly bool
	 * @param $childlevelsonly bool
	 * @param $startpath
	 * @param $hrefroot
	 */
	public function render(
		$ishorizontal = true,
		$currentlevelonly = false,
		$childlevelsonly = false,
		$startpath = '',
		$hrefroot = 'pages')
	{
		$yuinavbar = new ZymurgySiteNavRender_YUI($startpath);

		if ($childlevelsonly) $yuinavbar->startat_thispage();
		if ($currentlevelonly){
			$yuinavbar->maxdepth = 1;
			$yuinavbar->startat_parent();
		}

		$yuinavbar->headtags();
		$yuinavbar->render($ishorizontal);
	}

	/**
	 * Check if the user has permission to view the page with the ID $key.
	 *
	 * @param int $key
	 * @return bool
	 */
	public function haspermission($key)
	{
		//Zymurgy::DbgAndDie($key,$this->items[$key]->aclitems,Zymurgy::$member["groups"]);
		while ($key > 0)
		{
			if(array_key_exists($key, $this->items)	&& count($this->items[$key]->aclitems) > 0)
			{ //This node has an ACL.  If it doesn't allow access then reject this request / return false.
				if (!Zymurgy::memberauthenticate())
				{ //If the user has not authenticated and this page has an ACL we're automatically not allowed.
					return false;
				}
				$allowed = false;
				foreach ($this->items[$key]->aclitems as $aclitem)
				{
					if (array_key_exists($aclitem['group'],Zymurgy::$member['groups']))
					{ //We've got this group, allow access.
						$allowed = true;
						break;
					}
				}
				if (!$allowed)
				{
					return false;
				}
			}
			$key = $this->items[$key]->parent;
		}
		return true; //If we've worked our way back to the root and found no ACL denying read access, then provide read access.
	}
	
	public function x_haspermission($key){
		// is this a page (not root)
		//Zymurgy::DbgAndDie($key,$this->items[$key]->aclitems,Zymurgy::$member["groups"]);
		while ($key > 0){
			// does the current node have an ACL
			if(array_key_exists($key, $this->items)	&& count($this->items[$key]->aclitems) > 0){
				if ($this->items[$key]->aclitems) return true;
				/*foreach($this->items[$key]->aclitems as $aclitem){
					if($aclitem["permission"] == "Read"){
						if(is_array(Zymurgy::$member["groups"]) &&
								array_key_exists($aclitem["group"], Zymurgy::$member["groups"])){
							// user's group is in ACL, has permission
							return true;
						}
					}
				}*/
				// user's groups are not in ACL, no permission
				return false;
			}

			// no ACL here, check parent page
			$key = $this->items[$key]->parent;
		}

		// root is always readable
		return true;
	}

	/**
	 * Get the list of anscestors of the page with id $key.
	 *
	 * @param int $key
	 * @return array in the form id=>title
	 */
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

/**
 * Base class to create navigation renderers.
 *
 * Extend this class to create navigation renderers.
 * This provides common utility functions to select the start and depth of the navigation tree shown.
 * Your renderer inpmlementation will orerride headtags() and render() which are called to display the navigation.
 *
 * @author George
 *
 */
abstract class ZymurgySiteNavRenderer{

	/**
	 * The number of levels to show in the navigation tree. 0 for unlimited.
	 *
	 * @var int
	 */
	public $maxdepth = 0;

	/**
	 * The navigation URL part of the root page.  Blank for the site root.
	 * @var string
	 */
	public $startpath = '';

	/**
	 * The string to add to the start of the URL path.  This is usually "pages" (default).
	 * @var string
	 */
	public $hrefroot = 'pages';

	/**
	 * For absolute links in menues, for example http://example.com would make all links absolute to example.com.
	 * Do not include the trailing slash.
	 *
	 * @var string
	 */
	public $baseurl = '';

	########################################

	/**
	 * A reference to the navigation tree.  Set by constructor.
	 * @var Zymurgy_SiteNav
	 */
	protected $sitenav;

	########################################

	/**
	 * The foot key of the displayed navigation tree.  Filled dy initialize_data().
	 * @var int
	 */
	protected $rootnode;
	/**
	 * A list of keys of the asceestors to the displayed navigation tree.  Filled dy initialize_data().
	 * @var array of int
	 */
	protected $anscestors;
	/**
	 * The text to prepend to make the navigation URLs from the displayed tree.  Filled dy initialize_data().
	 * @var string
	 */
	protected $hrefprefix;
	/**
	 * IDs of the current page and its anscestors.
	 * @var array
	 */
	protected $crumbs = array();

	########################################

	/**
	 * Creates a new navigation renderer that shows navigation from $startpath.
	 *
	 * @param string $startpath
	 */
	public function __construct(
		$startpath = '')
	{
		$this->startpath = $startpath;

		$this->sitenav = Zymurgy::getsitenav();
		$this->hrefroot = Zymurgy::$template->hrefroot;
	}

	########################################
	// start control

	/**
	 * Start the navigation at the current page.  Displays children.
	 */
	public function startat_thispage(){
		$this->startpath = Zymurgy::$template->navpath;
	}
	/**
	 * Start the navigation at the parent of the current page.  Displays siblings.
	 */
	public function startat_parent(){
		$this->startpath = implode('/',explode('/', Zymurgy::$template->navpath, -1));
	}
	/**
	 * Start the navigation $depth levels towards the current page.
	 */
	public function startat_depth($depth){
		$this->startpath = implode('/',array_slice(explode('/', Zymurgy::$template->navpath), 0, $depth));
	}

	########################################
	/**
	 * Get the text for the page with id $key to show in the menu.
	 * @param int$key
	 * @return string
	 */
	protected function getname($key){
		return $this->sitenav->items[$key]->linktext;
	}

	/**
	 * get the URL to likg to the page with id $key.
	 * @param $key
	 * @return string
	 */
	protected function geturl($key){
		$up = array();
		while ($key > 0)
		{
			array_unshift($up, $this->sitenav->items[$key]->linkurl);
			$key = $this->sitenav->items[$key]->parent;
		}
		return '/'.$this->hrefroot.'/'.implode('/', $up);
	}

	/**
	 * Call this at the start your render function.
	 * @return unknown_type
	 */
	protected function initialize_data(){
		$this->anscestors = array();
		$this->hrefprefix = '/'.$this->hrefroot;
		$this->rootnode = 0;

		foreach (explode('/', $this->startpath) as $pathpart){
			foreach ($this->sitenav->items[$this->rootnode]->children as $child){
				if ($this->sitenav->items[$child]->linkurl == $pathpart){
					$this->rootnode =  $child;
					$this->anscestors[] = $child;
					$this->hrefprefix .= '/'.$pathpart;
				}
			}
		}

		$this->hrefprefix .= '/';

		$this->crumbs = $this->sitenav->getanscestors(Zymurgy::$pageid);
	}
	
	/**
	 * Given a key returns either 'show', 'hide' or 'disable'
	 * 
	 * @param int $key
	 */
	protected function getVisibility($key)
	{
		$hasPermission = $this->sitenav->haspermission($key);
		$enableItem = true;

		if(!$hasPermission)
		{
			if (isset(Zymurgy::$config["PagesOnACLFailure"]))
			{
				if (Zymurgy::$config["PagesOnACLFailure"] == "hide")
					return 'hide';
				if (Zymurgy::$config["PagesOnACLFailure"] == "disable")
					return 'disable';
			}
		}
		return 'show';
	}

	########################################

	/**
	 * emit the head tags required by the renderer.
	 */
	abstract public function headtags();
	/**
	 * actually show the navigation
	 */
	abstract public function render();
}

######################################################################

/**
 * Renderer to show YUI sitenav menus.
 *
 * @author George
 *
 */
class ZymurgySiteNavRender_YUI extends ZymurgySiteNavRenderer{

	/**
	 * Include the required YUI css and javascript for the menus.
	 */
	public function headtags(){
		// only needeed once
		static $included = 0;
		if ($included) return;
		$included = 1;

		echo Zymurgy::YUI('fonts/fonts-min.css');
		echo Zymurgy::YUI('menu/assets/skins/sam/menu.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI("selector/selector-min.js");
		echo Zymurgy::YUI('container/container_core-min.js');
		echo Zymurgy::YUI('menu/menu-min.js');

?>
<script type="text/javascript">// <![CDATA[
YAHOO.util.Event.onDOMReady(function(){
	YAHOO.util.Dom.batch(YAHOO.util.Selector.query('.ZymurgyMenu_YUI_H'), function(item){
		var oMenu = new YAHOO.widget.MenuBar(item, {
			autosubmenudisplay: true,
			hidedelay: 750,
			lazyload: true });
		oMenu.render();
	});
	YAHOO.util.Dom.batch(YAHOO.util.Selector.query('.ZymurgyMenu_YUI_V'), function(item){
		var oMenu = new YAHOO.widget.Menu("ZymurgyMenu_item", {
			position: "static",
			hidedelay: 750,
			lazyload: true });
		oMenu.render();
	});
});
// ]]></script>
<?php
	}

	/**
	 * Actually show the menu.
	 *
	 * @param bool $ishorizontal if ture dray a horizontal bar, if false draw a vertical menu.
	 */
	public function render($ishorizontal = true){
		$this->initialize_data();

		echo '<div class="yui-skin-sam">'."\n";
		if ($ishorizontal){
			echo '  <div class="yuimenubar yuimenubarnav ZymurgyMenu_YUI_H">'."\n";
		}else{
			echo '  <div class="yuimenu ZymurgyMenu_YUI_V">'."\n";
		}
        echo '    <div class="bd" style="border-style: none">'."\n";
		$this->renderpart($this->rootnode, 0, $ishorizontal);
		echo "    </div>\n  </div>\n</div>\n";
	}

	/**
	 * Render part of the site's navigation
	 *
	 * @param string $hrefroot Root of the navigation sections as used by mod_rewrite
	 * @param int $depth What is our current depth?
	 * @param array $sp Structure Part; the part of the nav to be rendered
	 * @param array $anscestors Ancestor nav names
	 */
	private function renderpart($node, $depth, $horizontal)//$hrefroot,$horizontal,$depth,$sp,$anscestors)
	{
		$tabs = str_repeat("    ",$depth+3);

		if ($depth > 0) echo "$tabs<div class=\"yuimenu\"><div class=\"bd\">\n";
		echo "$tabs<ul";
		if ($horizontal)
		{
			echo ' class="first-of-type';
			if ($depth == 0) echo ' zymurgy-horizontal-menu';
			echo '"';
			$fot = true;
		}
		else
		{
			if ($depth > 0) echo ' class="first-of-type"';
			$fot = false;
		}
		echo ">\n";
		foreach ($this->sitenav->items[$node]->children as $key)
		{
			$visibility = $this->getVisibility($key);
			if ($visibility == 'hide') continue;
			$enableItem = ($visibility != 'disable');

			echo "$tabs    <li class=\"yuimenuitem";
			if ($fot)
			{
				$fot = false;
				echo " first-of-type";
			}
			echo '">';

			echo '<a class="yuimenuitemlabel'.
				($enableItem ? '' : '-disabled').
				'" href="'.$this->baseurl.
				($enableItem ? $this->geturl($key) : '#').'">'.
				htmlspecialchars($this->getname($key)).'</a>';
			if ($this->maxdepth - $depth != 1 && $this->sitenav->items[$key]->children)
			{
				$this->renderpart($key,$depth+1,$horizontal);
				echo "\n$tabs";
			}
			echo "</li>\n";
		}
		echo "$tabs</ul>\n";
		if ($depth>0) echo "$tabs</div></div>";
	}
}

######################################################################

/**
 * The simplest possible nav renderer.  Shows an ascii list.
 *
 * Use this as an example of how to write your own renderers.
 *
 * @author George
 *
 */
class ZymurgySiteNavRender_TXT extends ZymurgySiteNavRenderer{

	// no headtags necessary
	public function headtags(){

	}

	// show it
	public function render(){
		// this does some setup: call it at the start of your renderer.
		$this->initialize_data();

		// actually start rendering.
		$this->renderpart($this->rootnode, 0);
	}

	/**
	 * Render a part of the navigation menu
	 *
	 * @param int $node The id of the node to start from.
	 * @param int $depth we are currently this many levels deep.
	 */
	private function renderpart($node, $depth){
		// we need some tabs to show structure
		$tabs = str_repeat("    ",$depth);

		// for each child of the current node
		foreach ($this->sitenav->items[$node]->children as $key){
			// if the user can see it
			if ($this->sitenav->haspermission($key)){
				// display list entry
				echo "$tabs* ".$this->getname($key).': '.$this->geturl($key)."\n";

				// if we want to show children and the node has any, recurse
				if ($this->maxdepth - $depth != 1 && $this->sitenav->items[$key]->children)
					$this->renderpart($key, $depth+1);
			}
		}
	}
}

class ZymurgySiteNavRender_UL extends ZymurgySiteNavRenderer
{

	// no headtags necessary
	public function headtags()
	{

	}

	// show it
	public function render()
	{
		// this does some setup: call it at the start of your renderer.
		$this->initialize_data();

		// actually start rendering.
		$this->renderpart($this->rootnode, 0);
	}

	/**
	 * Render a part of the navigation menu
	 *
	 * @param int $node The id of the node to start from.
	 * @param int $depth we are currently this many levels deep.
	 */
	private function renderpart($node, $depth)
	{
		echo "<ul>";
		// for each child of the current node
		foreach ($this->sitenav->items[$node]->children as $key)
		{
			$visibility = $this->getVisibility($key);
			if ($visibility == 'hide') continue;
			$enableItem = ($visibility != 'disable');

			// display list entry
			echo "<li>";
			if ($enableItem)
			{
				echo "<a href=\"".$this->geturl($key)."\">";
			}
			echo $this->getname($key);
			if ($enableItem)
			{
				echo "</a>";
			}
			
			// if we want to show children and the node has any, recurse
			if ($this->maxdepth - $depth != 1 && $this->sitenav->items[$key]->children)
				$this->renderpart($key, $depth+1);
			echo "</li>";
		}
		echo "</ul>";
	}
}
?>
