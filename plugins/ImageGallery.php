<?
ini_set('display_errors', 1);

if (!class_exists('PluginBase'))
{
	require_once('../cms.php');
	require_once('../PluginBase.php');
	require_once('../include/Thumb.php');
}

class ImageGallery extends PluginBase 
{
	function GetTitle()
	{
		return 'Image Gallery Plugin';
	}
	
	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE `zcm_galleryimage` (
		  `id` int(11) NOT NULL auto_increment,
		  `instance` int(11) default NULL,
		  `image` varchar(60) default NULL,
		  `link` varchar(200) default NULL,
		  `caption` varchar(200) default NULL,
		  `disporder` int(11) default NULL,
		  PRIMARY KEY  (`id`),
		  KEY `instance` (`instance`),
		  KEY `disporder` (`disporder`))");
	}
	
	function GetRelease()
	{
		return 4; // ZK: Recreated ImageGallery, based on FlashReliefThumbGallery.
		//return 3; //Renamed table to zcm_fr_galleryimage; rename corresponding files as well.
		//return 2; //Added link to zcm_fr_galleryimage table
	}
	
	function Upgrade()
	{
		require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');
		$newfrgalleryimage = array('link'=>'alter table zcm_galleryimage add link varchar(200)');
		CheckColumns('zcm_galleryimage',$newfrgalleryimage);
		$this->CompleteUpgrade();
	}

	function GetUninstallSQL()
	{
		return "drop table zcm_galleryimage";
	}
	
	function RemoveInstance()
	{
		$sql = "select id from zcm_galleryimage where instance={$this->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Unable to remove gallery images ($sql): ".Zymurgy::$db->error());
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			DataGrid::DeleteThumbs('zcm_galleryimage.image',$row['id']);
		}
		Zymurgy::$db->free_result($ri);
		$sql = "delete from zcm_galleryimage where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove gallery images ($sql): ".Zymurgy::$db->error());
		parent::RemoveInstance();
	}
	
	function GetDefaultConfig()
	{
		$r = array();
		
		$this->BuildConfig(
			$r,
			"Generate Gallery Image",
			"false",
			"drop.true,false",
			2);
		$this->BuildConfig(
			$r,
			"Thumbnail width",
			80,
			"input.3.3",
			2);
		$this->BuildConfig(
			$r,
			"Thumbnail height",
			60,
			"input.3.3",
			2);
		$this->BuildConfig(
			$r,
			"Image width",
			800,
			"input.3.3",
			2);
		$this->BuildConfig(
			$r,
			"Image height",
			600,
			"input.3.3",
			2);
			
		return $r;
	}
	
	function AdminMenuText()
	{
		return "Galleries";
	}
	
	function RenderAdmin()
	{
		$usegimage = ($this->GetConfigValue('Generate Gallery Image')=='true');
		if ($usegimage && (array_key_exists('gimage',$_GET)))
		{
			$sql = "update zcm_galleryimage set gimage=0 where instance=".$this->iid;
			Zymurgy::$db->query($sql) or die("Can't reset gallery image ($sql): ".Zymurgy::$db->error());
			$gimage = 0 + $_GET['gimage'];
			$sql = "update zcm_galleryimage set gimage=1 where id=$gimage";
			Zymurgy::$db->query($sql) or die("Can't set gallery image ($sql): ".Zymurgy::$db->error());
			header("Location: pluginadmin.php?pid={$this->pid}&iid={$this->iid}");
			return;
		}
		if (($usegimage) && (!array_key_exists('editkey',$_GET)))
		{
			$gi = $this->GetGalleryImageTag();
			if ($gi!='')
			{
				echo "<p>Gallery Image: $gi</p>";
			}
		}
		$ds = new DataSet(
			'zcm_galleryimage',
			'id');
		$ds->AddColumns(
			'id',
			'instance',
			'image',
			'caption',
			'link',
			'disporder');
		$ds->AddDataFilter(
			'instance',
			$this->iid);
			
		$dg = new DataGrid($ds);
		$dg->AddThumbColumn(
			'Image',
			'image',
			$this->GetConfigValue('Thumbnail width'),
			$this->GetConfigValue('Thumbnail height'));
		$dg->AddColumn(
			'Caption',
			'caption');
	 	$dg->AddColumn(
	 		'Link',
	 		'link');
		$dg->AddAttachmentEditor(
			'image',
			'Image:');
			
		$dg->AddInput(
			'caption',
			'Caption:',
			200,
			60);
		$dg->AddInput(
			'link',
			'Link:',
			200,
			60);
		
		if ($usegimage)
		{			
			$dg->AddButton(
				'Set Gallery Image',
				"pluginadmin.php?pid={$this->pid}&iid={$this->iid}&gimage={0}");
		}
		$dg->AddColumn(
			'',
			'id',
			"<a href=\"javascript:void()\" onclick=\"aspectcrop_popup('zcm_galleryimage.image','".
				$this->GetConfigValue('Image width').
				'x'.
				$this->GetConfigValue('Image height').
				"',{0},'zcm_galleryimage.image',true)\">Adjust Image</a>");
			
		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Image';
		$dg->AddConstant('instance',$this->iid);
		
		$dg->Render();
	}
	
	function RenderHTML()
	{
		$html = "Content pending";
		
		return $html;
	}
	
	function RenderJS()
	{		
		return "";
	}
	
	function RenderXML()
	{
		return "";
	}
	
	function Render()
	{
		// echo($this->extra);
		// die();
		
		$r='';
		switch($this->extra)
		{
			case 'xml':
				$r = $this->RenderXML();
				break;
			case 'js':
				$r = $this->RenderJS();
				break;
			case 'picasabutton':
				$r = $this->RenderPicasaButton();
				break;
			case 'picasa':
				$r = isset($_POST["process"])
					? $this->UploadFromPicasa()
					: $this->RenderPicasaUploader();
					
				break;
				
			default:
				$r = $this->RenderHTML();
				break;
		}
		return $r;
	}
	
	function RenderPicasaUploader()
	{
		require_once("../include/xmlHandler.php");	
		
		$pluginSQL = "SELECT `id` FROM zcm_plugin WHERE name = 'ImageGallery'";
		$pid = Zymurgy::$db->get($pluginSQL) or die("Cannot retrieve plugin information");
				
		$instanceSQL = "SELECT `id`, `name` FROM zcm_plugininstance WHERE plugin = '".
			$pid.
			"' AND `name` <> '0' ORDER BY `name`";
		$instanceRI = Zymurgy::$db->query($instanceSQL) or die("Cannot retrieve list of instances");
		
		// require_once("../header.php");
		
		echo("<html><head><title>Picasa Upload Tool for Zymurgy:CM</title></head><body>");
		echo("<form name=\"f\" method=\"POST\" action=\"ImageGallery.php\">");
		echo("<input type=\"hidden\" name=\"process\" value=\"true\">");
		echo("<input type=\"hidden\" name=\"DocType\" value=\"picasa\">");
		echo("<p>Instance: <select name=\"cmbInstance\">");
		
		while (($instanceRow = mysql_fetch_array($instanceRI))!==false)
		{
			echo("<option value=\"".$instanceRow["id"]."\">".$instanceRow["name"]."</option>");
		}		
		
		echo("</select></p>");
		echo("<p><b>Selected Images</b></p><p>");
		
		$xh = new xmlHandler();
		$nodeNames = array("PHOTO:THUMBNAIL", "PHOTO:IMGSRC", "TITLE");
		$xh->setElementNames($nodeNames);
		$xh->setStartTag("ITEM");
		$xh->setVarsDefault();
		$xh->setXmlParser();
		$xh->setXmlData(stripslashes($_POST['rss']));
		$pData = $xh->xmlParse();
		$br = 0;
	
		foreach($pData as $e) {
			echo "<img src='".$e['photo:thumbnail']."?size=120'>\r\n";
			$large = $e['photo:imgsrc'];		
			echo "<input type=hidden name='".$large."'>\r\n";
		}

		echo("</p>");
		
		echo("<p>");
		echo("<input type=\"submit\" value=\"Publish\">&nbsp;");
		echo("<input type=\"button\" value=\"Cancel\" onclick=\"location.href='minibrowser:close'\">");
		echo("</p>");
		
		echo("</form></body></html>");

		// include("../footer.php");
	}
	
	function UploadFromPicasa()
	{
		$instanceID = $_POST["cmbInstance"];		
		
		// echo $instanceID;
		// die;
	
		$dispOrderSQL = "SELECT MAX(`disporder`) FROM zcm_galleryimage WHERE `instance` = '$instanceID'";
		$dispOrder = Zymurgy::$db->get($dispOrderSQL) or $dispOrder = 0;
		
		if($dispOrder == null)
		{
			$dispOrder = 0;
		}
		
		foreach($_FILES as $key => $file)
		{
			$tmpfile = $file['tmp_name'];		
			$dispOrder++;
			
			$insertSQL = "INSERT INTO zcm_galleryimage ( instance, image, link, ".
			"caption, disporder ) VALUES ( '$instanceID', '".$file['type']."', '', '".$file["name"]."', '$dispOrder')";
			
			Zymurgy::$db->query($insertSQL) or die("Could not insert record for image.");
			
			$idSQL = "SELECT id FROM zcm_galleryimage WHERE instance = '$instanceID' ".
				"AND disporder = '$dispOrder'";
			$id = Zymurgy::$db->get($idSQL) or die("Could not get ID of new image record.");
			
			$localfn = "../../UserFiles/DataGrid/zcm_galleryimage.image/".$id."raw.jpg";
			
			if(move_uploaded_file($tmpfile, $localfn))
			{
				// chmod($localfn, 0644);
			}
		}
		
		// echo("http://".$_SERVER['SERVER_NAME']."/zymurgy/login.php");
	}
}

function ImageGalleryFactory()
{
	return new ImageGallery();
}

if(array_key_exists('DocType', $_GET) && $_GET["DocType"] == 'picasa')
{
	header("Content-type: text/html");	
		
	echo plugin('ImageGallery', 0, 'picasa');
}
else if(array_key_exists('DocType', $_POST) && $_POST["DocType"] == 'picasa')
{
	header("Content-type: text/html");	
		
	echo plugin('ImageGallery', 0, 'picasa');
}
else if (array_key_exists('GalleryInstance',$_GET))
{
	$doctype = $_GET['DocType'];
	
	// print_r($_GET);
	// echo("<br>".$_GET['DocType']."<br>".$doctype);
	// die;
	
	if ($doctype=='js')
	{
		header("Content-type: text/javascript");		
	}
	else 
	{
		header("Content-type: text/xml");
		$doctype = 'xml';
	}
	
	echo plugin('ImageGallery',$_GET['GalleryInstance'],$doctype);
}
?>