<?
function OnBeforeUpdate($dsr)
{
	$dsr['zcm_sitetext.plainbody'] = strip_tags($dsr['zcm_sitetext.body']);
	return $dsr;
}

function OnUpdate($dsr)
{
	//Load list of related pages
	$sql = "select metaid from zcm_textpage where sitetextid={$dsr['zcm_sitetext.id']}";
	$ri = Zymurgy::$db->query($sql);
	if (!$ri) die("Unable to set update time using ($sql): ".Zymurgy::$db->error());
	$updated = array();
	while ((list($id) = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$updated[] = $id;
	}
	Zymurgy::$db->free_result($ri);
	//Update their modification times
	foreach($updated as $id)
	{
		Zymurgy::$db->query("update meta set mtime=".time()." where id=$id");
	}
	if (array_key_exists('dlg',$_GET))
	{
		echo "<script lang='javascript'>window.close();</script>";
		exit;
	}
}

function showcategories()
{
	global $c,$zauth;
	
	$sql = "select * from zcm_stcategory where id>0 order by name";
	$ri = Zymurgy::$db->query($sql) or die("Unable to load site text categories ($sql): ".Zymurgy::$db->error());
	$cats = array(0=>'Uncategorized Content',-1=>'All Content');
	while (($row = Zymurgy::$db->fetch_array($ri))!==false)
	{
		$cats[$row['id']] = $row['name'];
	}
	Zymurgy::$db->free_result($ri);
	if (count($cats)>2)
	{
		echo "<select name=\"category\" id=\"category\" onchange=\"loadCategory(this)\">\r\n";
		foreach($cats as $id=>$name)
		{
			echo "\t<option value=\"$id\"";
			if ($c == $id)
				echo " selected=\"selected\"";
			echo ">$name</option>\r\n";
		}
		echo "</select>";
	}
	if ($zauth->authinfo['admin']>=2)
	{
		echo " <a href=\"stcategory.php\">Edit Categories</a>";
	}
}

function showsearch()
{
	global $c;
	echo "<input type=\"hidden\" value=\"$c\" name=\"c\">";
	echo "<input type=\"text\" name=\"findtext\"";
	if (array_key_exists('findtext',$_GET))
		echo " value=\"".htmlspecialchars($_GET['findtext'])."\"";
	echo "><input type=\"submit\" value=\"search\">";
}

if (array_key_exists('editkey',$_GET))
{
	$breadcrumbTrail = "<a href=\"sitetext.php\">Simple Content</a> &gt; Edit";
}
else 
{
	$breadcrumbTrail = "General Content";	
}

include('header.php');

if (array_key_exists('dlg',$_GET))
	ob_clean();
include('datagrid.php');

//Get our sitetext category
if (array_key_exists('c',$_GET))
	$c = 0 + $_GET['c'];
else 
	$c = 0;
?>
<script language="javascript" type="text/javascript">
function loadCategory(sl) {
	var c = sl.options[sl.selectedIndex].value;
	document.location.href='sitetext.php?c='+c;
}
</script>
<?

if (!array_key_exists('editkey',$_GET))
{
	echo "<form action=\"{$_SERVER['REQUEST_URI']}\">";
	showsearch();
	showcategories();
	echo "</form><br />";
}
$ds = new DataSet('zcm_sitetext','id');
$ds->AddColumns('id','tag','category','body','plainbody','inputspec');
$ds->OnUpdate = "OnUpdate";
$ds->OnBeforeUpdate = "OnBeforeUpdate";
if ($c >= 0)
{
	$ds->AddDataFilter('category',$c);
}
if (array_key_exists('findtext',$_GET))
{
	$ft = trim($_GET['findtext']);
	if (((substr($ft,0,1)=='"') && (substr($ft,-1)=='"')) || ((substr($ft,0,1)=="'") && (substr($ft,-1)=="'")))
	{
		//Remove quotes and do exact match query
		$ft = substr($ft,1,-1);
		$ds->AddDataFilter('plainbody',"%$ft%",'like');
	}
	else 
	{
		//Use full text search facility
		$ds->ExtraSQL = " and match (plainbody) against ('".Zymurgy::$db->escape_string($ft)."')";
	}
}

$dg = new DataGrid($ds);
$dg->AddColumn('Text Block Name','tag');
$dg->AddColumn('','body','');
$dg->AddEditColumn();
if ($zauth->authinfo['admin']>=2)
{
	$dg->AddInput('tag','Text Block Name:',35,35);
	$dg->AddLookup('category','Category','zcm_stcategory','id','name','name');
	$dg->AddDeleteColumn();
} else {
}
$dg->insertlabel = '';
if (array_key_exists('editkey',$_GET))
{
	$id = 0 + $_GET['editkey'];
	$sql = "select id,inputspec from zcm_sitetext where id=$id";
	$ri = Zymurgy::$db->query($sql);
	$row = Zymurgy::$db->fetch_array($ri);
	$inputspec = $row['inputspec'];
	$dg->AddEditor('body','Contents:',$inputspec);
	Zymurgy::$db->free_result($ri);
}

//Try to configure the editor for the site's css look
if (isset($ZymurgyConfig['sitecss']))
	$dg->fckeditorcss = $ZymurgyConfig['sitecss'];
else
{
	$css = "/css/site.css";
	if (file_exists($ZymurgyRoot.$css))
		$dg->fckeditorcss = $css;
}
//echo "CSS: ".$dg->fckeditorcss;
$dg->Render();
if (!array_key_exists('dlg',$_GET))
	include('footer.php');
?>
