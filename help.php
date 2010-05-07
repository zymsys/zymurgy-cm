<?php
/**
 * Zymurgy:CM Help section. Has been replaced by the User and Development wikis at:
 *   http://www.zymurgycm.com/userwiki/
 *   http://www.zymurgycm.com/devwiki/
 *
 * @package Zymurgy
 * @subpackage help
 * @deprecrated
 */

	/**
     * @deprecated
	 */
	class IndexEntry
	{
		public $helpid = array();
		public $phrase;
		
		function IndexEntry($helpid, $phrase)
		{
			$this->helpid[] = $helpid;
			$this->phrase = $phrase;
		}
		
		function addhelp($helpid)
		{
			$this->helpid[] = $helpid;
		}
		
		function getListing()
		{
			$out = array($this->phrase);
			foreach($this->helpid as $idx=>$helpid)
			{
				$out[] = "[<a href=\"help.php?id=$helpid\">".($idx+1)."</a>]";
			}
			return implode(' ',$out);
		}
	}

	if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
		$ZymurgyRoot = $_SERVER["APPL_PHYSICAL_PATH"];
	else 
		$ZymurgyRoot = $_SERVER['DOCUMENT_ROOT'];
	require_once("$ZymurgyRoot/zymurgy/ZymurgyAuth.php");
	$zauth = new ZymurgyAuth();
	$zauth->Authenticate("/zymurgy/login.php");
	$zp = explode(',',$zauth->authinfo['extra']);
	if (count($zp)<6)
	{
		echo $zauth->authinfo['extra']; exit;
		header("Location: logout.php");
		exit;
	}
	$zauth->authinfo['email'] = $zp[1];
	$zauth->authinfo['fullname'] = $zp[2];
	$zauth->authinfo['admin'] = $zp[3];
	$zauth->authinfo['id'] = $zp[4];
	$zauth->authinfo['eula'] = $zp[5];
	
	require_once("$ZymurgyRoot/zymurgy/cmo.php");
	ob_start();
	echo Zymurgy::YUI('treeview/assets/skins/sam/treeview.css');
	echo Zymurgy::YUI('yahoo/yahoo-min.js');
	echo Zymurgy::YUI('dom/dom-min.js');
	echo Zymurgy::YUI('event/event-min.js');
	echo Zymurgy::YUI('treeview/treeview-min.js');


	$topicTitle = "Help topic unavailable";
	$topicBody = "<p>This help topic is not available.</p>";
	
	$seeAlsoTitle = "See also:";
	$seeAlsoBody = "There are no references for this topic.";
	
//	include 'header.php';
	include 'helpheader.php';
	
	function getFirstTopicFromDatabase()
	{
		global $topicTitle, $topicBody, $zauth;
		
		$sql = "SELECT
				`title`,
				`body`
			FROM
				zcm_help
			WHERE
				authlevel <= " . intval($zauth->authinfo["admin"]) . "
			ORDER BY
				parent,
				disporder
			LIMIT
				0, 1";
		
		$rsTopic = Zymurgy::$db->run($sql);
		
		while(($row = Zymurgy::$db->fetch_array($rsTopic)) == true)
		{			
			$topicTitle = $row["title"];
			$topicBody = $row["body"];
		}
		
		Zymurgy::$db->free_result($rsTopic);
	}

	function getCurrentTopicFromDatabase($id)
	{
		global $topicTitle, $topicBody, $zauth;
		
		$sql = "SELECT
				`title`,
				`body`
			FROM
				zcm_help
			WHERE
				id = " . intval($id) . "
			AND
				authlevel <= " . intval($zauth->authinfo["admin"]);
		
		$rsTopic = Zymurgy::$db->run($sql);
		
		while(($row = Zymurgy::$db->fetch_array($rsTopic)) == true)
		{			
			$topicTitle = $row["title"];
			$topicBody = $row["body"];
		}
		
		Zymurgy::$db->free_result($rsTopic);
	}

	function getTopicsFromDatabase()
	{
		global $zauth;
//		global $maxIndex;
		
		$sql = "SELECT
				`id`,
				`parent`,
				`disporder`,
				`authlevel`,
				`title`
			FROM
				zcm_help
			WHERE
				authlevel <= " . intval($zauth->authinfo["admin"]) . "
			ORDER BY
				parent,
				disporder";
		
		// echo($sql);
		
		$rsTopics = Zymurgy::$db->run($sql);
		$topics = array();
//		$maxIndex = 0;
		
		while (($row = Zymurgy::$db->fetch_array($rsTopics))!==false)
		{
			$row_array = array(
				"id" => $row["id"],
				"title" => $row["title"],
				"children" => array());
			
			$topics = addRowToTopicsArray(0, $topics, $row, $row_array);
			
/*			if ($row["id"] > $maxIndex)
			{
				$maxIndex = $row["id"];
			}
*/			
		}	
		
		Zymurgy::$db->free_result($rsTopics);
		
//		$maxIndex++;
		$index_array = array(
//			"id" => $maxIndex,
			"id" => 0,
			"title" => "Index",
			"children" => null);
		array_push($topics, $index_array);

		return $topics;
	}

	function getSeeAlsoFromDatabase($id)
	{
		$sql = "SELECT
				`title`,
				`seealso`
			FROM
				zcm_helpalso ha
			INNER JOIN
				zcm_help h
			ON
				h.id = ha.seealso
			WHERE
				ha.help =" . intval($id) . "
			ORDER BY
				title";
		
		//echo($sql);
		
		$rsSeeAlso = Zymurgy::$db->run($sql);
		$seeAlsoResults = array();
		
		while (($row = Zymurgy::$db->fetch_array($rsSeeAlso))!==false)
		{
			$seeAlso = array(
				"title" => $row["title"],
				"seealso" => $row["seealso"]);
			array_push($seeAlsoResults, $seeAlso);
		}	
		
		Zymurgy::$db->free_result($rsSeeAlso);
		return $seeAlsoResults;
	}

	function addRowToTopicsArray(
		$parentid,
		$topics,
		$row,
		$row_array)
	{
		if($row["parent"] == $parentid)
		{				
			// echo("<p>Adding $row[id] to item $parentid using recursive function.</p>");
			
			array_push($topics, $row_array);
		}
		else 
		{
			foreach($topics as &$topic) 
			{
				$childCount = count($topic["children"]);
				
				$topic["children"] = addRowToTopicsArray(
					$topic["id"],
					$topic["children"],
					$row,
					$row_array);
					
				if($childCount < count($topic["children"]))
					break;
			}
		}
		
		return $topics;
	}
	
	function createTreeNode(
		$topic,
		$parentid)
	{
		echo("nodeDetails = { label: '".addslashes($topic[title])."', id: '$topic[id]' };\r\n");
		echo("node$topic[id] = new YAHOO.widget.TextNode(nodeDetails, node$parentid, true);\r\n");
		
		foreach($topic["children"] as &$child)
		{
			createTreeNode(
				$child,
				$topic["id"]);
		}
	}
	
	function getSearchResults()
	{
		$out = array();
		$ri = Zymurgy::$db->query("SELECT id, title FROM zcm_help WHERE MATCH (title,plain) AGAINST (\"{$_GET['q']}\")");
		if (Zymurgy::$db->num_rows($ri) > 0)
		{
			$out[] = "<p>The following help topics match your search:</p>";
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$out[] = "<a href=\"help.php?id={$row['id']}\">{$row['title']}</a><br />";
			}
		}
		else 
		{
			$out[] = "No help was found matching your search phrase.";
		}
		return implode("\r\n",$out);
	}
	
	function getIndex()
	{
		$sql = "select zcm_helpindex.zcm_help,zcm_helpindexphrase.phrase from zcm_helpindex join zcm_helpindexphrase on zcm_helpindex.phrase = zcm_helpindexphrase.id order by zcm_helpindexphrase.phrase";
		$ri = Zymurgy::$db->run($sql);
		$index = array(); //Array of first letters
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$first = strtoupper(substr($row['phrase'],0,1));
			if (!array_key_exists($first,$index))
			{
				$index[$first] = array(); //Array of phrases for this letter
			}
			if (!array_key_exists($row['phrase'],$index[$first]))
			{ //Add a new phrase to the index
				$index[$first][$row['phrase']] = new IndexEntry($row['help'],$row['phrase']);
			}
			else 
			{ //Add a new help reference to an existing phrase
				$index[$first][$row['phrase']]->addhelp($row['help']);
			}
		}
		$out = array(); //Output buffer to build our full index string
		foreach($index as $first=>$entries)
		{
			$out[] = "<h2>$first</h2>";
			foreach ($entries as $entry)
			{
				$out[] = $entry->getListing().'<br />';
			}
		}
		return implode("\r\n",$out);
	}

	$topics = getTopicsFromDatabase();

	$topicid = array_key_exists('id',$_GET) ? 0 + $_GET['id'] : 0;
	//echo($topicid);
	if ($topicid > 0)
	{
		getCurrentTopicFromDatabase($topicid);
		$seeAlsoResults = getSeeAlsoFromDatabase($topicid);
	}
	else 
	{
		if (array_key_exists('id',$_GET))
		{
			$topicTitle = "Index";
			$topicBody = getIndex();
			
			$seeAlsoTitle = "";
			$seeAlsoBody = "";
/*?>
<script language="javascript" type="text/javascript">
				window.location.href = "helpindex.php?h=0";
</script>							
<?php*/
		}
		else 
		{
			if (array_key_exists('q',$_GET))
			{ //Execute a search
				$topicTitle = "Search Results";
				$topicBody = getSearchResults();
				
				$seeAlsoTitle = "";
				$seeAlsoBody = "";
			}
			else 
			{
				getFirstTopicFromDatabase();	
				$seeAlsoResults = getSeeAlsoFromDatabase(1);
			}
		}
	}

	if (count($seeAlsoResults) > 0)
	{
		$seeAlsoBody = '';
	}
	
	foreach ($seeAlsoResults as $seeAlsoItem)
	{
		$seeAlsoBody .= '<a href="help.php?id=' . $seeAlsoItem["seealso"] . '">' . $seeAlsoItem["title"] . '</a><br>';
	}
	$breadcrumbTrail = "Help &gt; $topicTitle";
	
	// echo(print_r($topics));
?>

<div id="treehelpTopics" style="float: left; clear: left; width: 200px; margin-right: 20px; height: 400px; overflow: auto;">	
</div>

<script language="javascript" type="text/javascript">
	function initializeTopics()
	{
		var tree = new YAHOO.widget.TreeView("treehelpTopics");
		var node0 = tree.getRoot();
		var tmpNode;
		
		<?php
		foreach($topics as $topic)
		{
			createTreeNode($topic, 0);
		}
		?>		

		tree.subscribe("labelClick", function(node) {
			window.location.href = "help.php?id=" + node.data.id;
		
			return false;
		});
		
		sizeTree();
		
		tree.draw();
	}
	
	function sizeTree()
	{
		var pos = YAHOO.util.Dom.getXY('treehelpTopics');
		var height = YAHOO.util.Dom.getViewportHeight();
		var top = pos[1];
		YAHOO.util.Dom.setStyle('treehelpTopics', 'height', (height-top-15)+'px'); 
	}
	
	YAHOO.util.Event.addListener(
		window,
		"load",
		initializeTopics);
	YAHOO.util.Event.addListener(
		window,
		"resize",
		sizeTree);
</script>

<?php
echo "<h1>$topicTitle</h1>$topicBody";
echo "<h1>$seeAlsoTitle</h1>$seeAlsoBody";
?>
