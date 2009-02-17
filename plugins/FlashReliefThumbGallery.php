<?
//ini_set('display_errors', 1);

if (!class_exists('PluginBase'))
{
	require_once('../cms.php');
	require_once('../PluginBase.php');
	require_once('../include/Thumb.php');
}

class FlashReliefThumbGallery extends PluginBase 
{
	function GetTitle()
	{
		return 'Flash Relief Thumb Gallery Plugin';
	}
	
	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE `zcm_fr_galleryimage` (
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
		return 3; //Renamed table to zcm_fr_galleryimage; rename corresponding files as well.
		//return 2; //Added link to zcm_fr_galleryimage table
	}
	
	function Upgrade()
	{
		require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');
		$newfrgalleryimage = array('link'=>'alter table zcm_fr_galleryimage add link varchar(200)');
		CheckColumns('zcm_fr_galleryimage',$newfrgalleryimage);
		$this->CompleteUpgrade();
	}

	function GetUninstallSQL()
	{
		return "drop table zcm_fr_galleryimage";
	}
	
	function RemoveInstance()
	{
		$sql = "select id from zcm_fr_galleryimage where instance={$this->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Unable to remove gallery images ($sql): ".Zymurgy::$db->error());
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			DataGrid::DeleteThumbs('zcm_fr_galleryimage.image',$row['id']);
		}
		Zymurgy::$db->free_result($ri);
		$sql = "delete from zcm_fr_galleryimage where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove gallery images ($sql): ".Zymurgy::$db->error());
		parent::RemoveInstance();
	}
	
	function GetDefaultConfig()
	{
		$r = array();
		$this->BuildConfig($r,'SWF Location','/swf/gallery.swf','input.30.30',2);
		$this->BuildConfig($r,'Generate Gallery Image','false','drop.true,false',2);
		$this->BuildConfig($r,'Gallery Image Width',50,'input.1.3',2);
		$this->BuildConfig($r,'Gallery Image Height',50,'input.1.3',2);
		$this->BuildConfig($r,'Flash Control Width',500,'input.4.5',2);
		$this->BuildConfig($r,'Flash Control Height',400,'input.4.5',2);
		$this->BuildConfig($r,'Flash Control Background','#ffffff','colour',2);
		$this->BuildConfig($r,'Main Image Area Width',400,'input.4.5',2);
		$this->BuildConfig($r,'Main Image Area Height',300,'input.4.5',2);
		$this->BuildConfig($r,'Thumb Width',50,'input.1.3',2);
		$this->BuildConfig($r,'Thumb Height',50,'input.1.3',2);
		$this->BuildConfig($r,'Transition Type','wipe','drop.fade,zoom,squeeze,pixeldissolve,blinds,wipe,iris,photo,fly');
		$this->BuildConfig($r,'Transition Speed',2,'input.1.3');
		$this->BuildConfig($r,'Thumbnail Rows',1,'input.1.2',2);
		$this->BuildConfig($r,'Show Caption and Controls','true','drop.true,false',2);
		$this->BuildConfig($r,'Show Loader','true','drop.true,false',2);
		$this->BuildConfig($r,'Caption Position','top','drop.top,bottom',2);
		$this->BuildConfig($r,'Seconds per Image',5,'input.1.3');
		$this->BuildConfig($r,'Border Thickness',5,'input.1.3',2);
		$this->BuildConfig($r,'Background Colour','#888888','colour',2);
		$this->BuildConfig($r,'Thumb Outline Thickness','1','input.1.2',2);
		$this->BuildConfig($r,'Thumb Outline Colour','#eeeeee','colour',2);
		$this->BuildConfig($r,'Thumb Outline Colour (selected)','#ff9999','colour',2);
		$this->BuildConfig($r,'Background Colour','#333333','colour',2);
		$this->BuildConfig($r,'Background Corner Size','5','input.1.2',2);
		$this->BuildConfig($r,'Large Image Corner Size','5','input.1.2',2);
		$this->BuildConfig($r,'Thumb Corner Size','5','input.1.2',2);
		$this->BuildConfig($r,'Thumb Spacing','3','input.1.2',2);
		$this->BuildConfig($r,'Main Image to Thumb Spacing','3','input.1.2',2);
		$this->BuildConfig($r,'Scroll Speed',8,'input.1.3');
		$this->BuildConfig($r,'Thumb Transparancy (not selected)',50,'input.1.3',2);
		$this->BuildConfig($r,'Thumb Position','bottom','drop.left,right,top,bottom',2);
		$this->BuildConfig($r,'Play Rollover Sound','true','drop.true,false');
		$this->BuildConfig($r,'Play Click Sound','true','drop.true,false');
		return $r;
	}
			
	function GetCommandMenuItems()
	{
		$r = array();
		
		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);		
		
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
			$sql = "update zcm_fr_galleryimage set gimage=0 where instance=".$this->iid;
			Zymurgy::$db->query($sql) or die("Can't reset gallery image ($sql): ".Zymurgy::$db->error());
			$gimage = 0 + $_GET['gimage'];
			$sql = "update zcm_fr_galleryimage set gimage=1 where id=$gimage";
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
		$ds = new DataSet('zcm_fr_galleryimage','id');
		$ds->AddColumns('id','instance','image','caption','link','disporder');
		$ds->AddDataFilter('instance',$this->iid);
		$dg = new DataGrid($ds);
		$dg->AddThumbColumn('Image','image',$this->GetConfigValue('Thumb Width'),$this->GetConfigValue('Thumb Height'));
		$dg->AddColumn('Caption','caption');
	 	$dg->AddColumn('Link','link');
		$dg->AddAttachmentEditor('image','Image:');
		$dg->AddInput('caption','Caption:',200,60);
		$dg->AddInput('link','Link:',200,60);
		if ($usegimage)
			$dg->AddButton('Set Gallery Image',"pluginadmin.php?pid={$this->pid}&iid={$this->iid}&gimage={0}");
		$dg->AddColumn('','id',"<a href=\"javascript:void()\" onclick=\"aspectcrop_popup('zcm_fr_galleryimage.image','".$this->GetConfigValue('Main Image Area Width').'x'.$this->GetConfigValue('Main Image Area Height')."',{0},'zcm_fr_galleryimage.image',true)\">Adjust Image</a>");
		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Image';
		$dg->AddConstant('instance',$this->iid);
		$dg->Render();
	}
	
	function RenderHTML()
	{
		if (file_exists(Zymurgy::$root.$this->GetConfigValue('SWF Location')))
		{
			$html = "<script src=\"/zymurgy/plugins/FlashReliefThumbGallery.php?DocType=js&GalleryInstance=".
				urlencode($this->InstanceName)."\" type=\"text/javascript\"></script>";
		}
		else 
		{
			$html = "<b>Can't find the Flash Relief Image Gallery Flash file at: ".
				$this->GetConfigValue('SWF Location')."</b>";
		}
		return $html;
	}
	
	function RenderJS()
	{
		$uea = urlencode('&');
		$html = "<embed allowScriptAccess=\"never\" allowNetworking=\"internal\" 
			enableJSURL=\"false\" enableHREF=\"false\" saveEmbedTags=\"true\" WMode=\"transparent\"
			src=\"".$this->GetConfigValue('SWF Location')."?xmlPath=/zymurgy/plugins/FlashReliefThumbGallery.php?DocType=xml{$uea}GalleryInstance=".
			urlencode($this->InstanceName)."\" quality=\"high\" 
			pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\" 
			width=\"".$this->GetConfigValue('Flash Control Width')."\" height=\"".
			$this->GetConfigValue('Flash Control Height')."\" bgcolor=\"".$this->GetConfigValue('Flash Control Background')."\"></embed>";
		$html = str_replace(array("\r","\n"),'',$html);
		$js = "document.write('$html');";
		return $js;
	}
	
	function RenderXML()
	{
		global $ZymurgyRoot;
		
		$xml = array("<gallery>
    	   <setup path=\"/UserFiles/\" >");
		$xml[] = "<imgWidth>".$this->GetConfigValue('Main Image Area Width')."</imgWidth>
              <imgHeight>".$this->GetConfigValue('Main Image Area Height')."</imgHeight>
              <thumbWidth>".$this->GetConfigValue('Thumb Width')."</thumbWidth>
              <thumbHeight>".$this->GetConfigValue('Thumb Height')."</thumbHeight>
              <transitionType>".$this->GetConfigValue('Transition Type')."</transitionType>
              <thumbnailRows>".$this->GetConfigValue('Thumbnail Rows')."</thumbnailRows>
              <captionPosition>".$this->GetConfigValue('Caption Position')."</captionPosition>
              <bgBorderThickness>".$this->GetConfigValue('Border Thickness')."</bgBorderThickness>
              <backgroundColor>".$this->GetConfigValue('Background Colour')."</backgroundColor>
              <thumbColor>".$this->GetConfigValue('Thumb Outline Colour')."</thumbColor>
              <thumbActiveColor>".$this->GetConfigValue('Thumb Outline Colour (selected)')."</thumbActiveColor>
              <scrollSpeed>".$this->GetConfigValue('Scroll Speed')."</scrollSpeed>
              <thumbAlpha>".$this->GetConfigValue('Thumb Transparancy (not selected)')."</thumbAlpha>
			<imgBgColor >".$this->GetConfigValue('Background Colour')."</imgBgColor > 
			<backgroundCorner >".$this->GetConfigValue('Background Corner Size')."</backgroundCorner >
			<imgCorner>".$this->GetConfigValue('Large Image Corner Size')."</imgCorner>
			<thumbCorner>".$this->GetConfigValue('Thumb Corner Size')."</thumbCorner>
			<thumbMaskCorner>".$this->GetConfigValue('Thumb Corner Size')."</thumbMaskCorner>
			<thumbSpace>".$this->GetConfigValue('Thumb Spacing')."</thumbSpace>
			<thumbOutlineThick>".$this->GetConfigValue('Thumb Outline Thickness')."</thumbOutlineThick>
			<thumbToImgSpace>".$this->GetConfigValue('Main Image to Thumb Spacing')."</thumbToImgSpace>
			<transitionSpeed>".$this->GetConfigValue('Transition Speed')."</transitionSpeed>
			<showControls>".$this->GetConfigValue('Show Caption and Controls')."</showControls>
			<showLoader>".$this->GetConfigValue('Show Loader')."</showLoader>
              <thumbPosition>".$this->GetConfigValue('Thumb Position')."</thumbPosition>
              ";
		if ($this->GetConfigValue('Play Rollover Sound')=='false')
			$xml[] = "<soundThumbRoll></soundThumbRoll>";
		if ($this->GetConfigValue('Play Click Sound')=='false')
			$xml[] = "<soundThumbClick></soundThumbClick>";
        $xml[] = "<backgroundAlpha>100</backgroundAlpha>
              <autoPlay>".$this->GetConfigValue('Seconds per Image')."</autoPlay>";
	    $xml [] = "</setup>";
		$sql = "select id,caption,link from zcm_fr_galleryimage where instance={$this->iid} order by disporder";
		$ri = Zymurgy::$db->query($sql) or die("Unable to load images ($sql): ".Zymurgy::$db->error());
		if (!$ri)
			die("Unable to read gallery images ($sql): ".Zymurgy::$db->error());
		$imgpath = "DataGrid/zcm_fr_galleryimage.image";
		chdir("$ZymurgyRoot/UserFiles");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$raw = "$imgpath/{$row['id']}raw.jpg";
			$thumb = "$imgpath/{$row['id']}thumb".$this->GetConfigValue('Thumb Width')."x".$this->GetConfigValue('Thumb Height').".jpg";
			$image = "$imgpath/{$row['id']}thumb".$this->GetConfigValue('Main Image Area Width')."x".$this->GetConfigValue('Main Image Area Height').".jpg";
			$extra = '';
			if (!file_exists($raw))
			{
				//If the source file doesn't exist we're screwed...  Maybe this should barf but I'd like to be graceful if there's any hope.
				continue;
			}
			if (!file_exists($thumb))
			{
				Thumb::MakeFixedThumb($this->GetConfigValue('Thumb Width'),
					$this->GetConfigValue('Thumb Height'),$raw,$thumb);
			}
			if (!file_exists($image))
			{
				Thumb::MakeFixedThumb($this->GetConfigValue('Main Image Area Width'),
					$this->GetConfigValue('Main Image Area Height'),$raw,$image);
			}
			if ($row['caption']!='')
				$extra = "\r\n<caption>{$row['caption']}</caption>\r\n";
			if (empty($row['link']))
				$link = "http://{$_SERVER['HTTP_HOST']}/$raw";
			else 
				$link = $row['link'];
			$xml[] = "<item>
              <thumb>$thumb</thumb> 
              <imgLink>$link</imgLink>
              <img>$image</img> $extra
        </item>";
		}
		$xml[] = "</gallery> ";
		return implode("\r\n",$xml);
	}
	
	function GetGalleryImageTag()
	{
		global $ZymurgyRoot;
		
		$sql = "select id from zcm_fr_galleryimage where (instance={$this->iid}) and (gimage=1)";
		$ri = Zymurgy::$db->query($sql);
		if (!$ri)
			die("Can't load default gallery image ($sql): ".Zymurgy::$db->error());
		if (($row = Zymurgy::$db->fetch_array($ri))===false)
		{
			return "";
		}
		$id = $row["id"];
		$w = $this->GetConfigValue('Gallery Image Width');
		$h = $this->GetConfigValue('Gallery Image Height');
		//echo "[$ZymurgyRoot/UserFiles]"; return;
		chdir("$ZymurgyRoot/UserFiles");
		$thumb = "DataGrid/zcm_fr_galleryimage.image/{$id}thumb{$w}x{$h}.jpg";
		if (!file_exists($thumb))
		{
			require_once('../zymurgy/include/Thumb.php');
			$raw = "DataGrid/zcm_fr_galleryimage.image/{$id}raw.jpg";
			Thumb::MakeFixedThumb($w,$h,$raw,$thumb);
		}
		return "<img width=\"$w\" height=\"$h\" src=\"/UserFiles/$thumb\" />";
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
		
		$pluginSQL = "SELECT `id` FROM zcm_plugin WHERE name = 'FlashReliefThumbGallery'";
		$pid = Zymurgy::$db->get($pluginSQL) or die("Cannot retrieve plugin information");
				
		$instanceSQL = "SELECT `id`, `name` FROM zcm_plugininstance WHERE plugin = '".
			$pid.
			"' AND `name` <> '0' ORDER BY `name`";
		$instanceRI = Zymurgy::$db->query($instanceSQL) or die("Cannot retrieve list of instances");

		// fake an auth token
		$_SESSION['AUTH'] = array();
		
		include("../header_html.php");
		
		echo("<p>The Zymurgy:CM&trade; Picasa Upload Utility allows you to upload your images directly from Google Picasa into your Flash Relief Image Gallery. It also takes care of re-sizing your images for the Web, so you don't have to wait a long time to upload images taken with your digital camera.</p>");
		
		echo("<form name=\"f\" method=\"POST\" action=\"FlashReliefThumbGallery.php\">");
		echo("<input type=\"hidden\" name=\"process\" value=\"true\">");
		echo("<input type=\"hidden\" name=\"DocType\" value=\"picasa\">");
		echo("<p><b>Web Site:</b> ".Zymurgy::$config['defaulttitle']." (".Zymurgy::$config['sitehome'].")");
		echo("<br><b>Upload to Gallery:</b> <select name=\"cmbInstance\">");
		
		while (($instanceRow = mysql_fetch_array($instanceRI))!==false)
		{
			echo("<option value=\"".$instanceRow["id"]."\">".$instanceRow["name"]."</option>");
		}		
		
		echo("</select></p>");
		echo("<p>The following images will be uploaded:</p>");
		
		$xh = new xmlHandler();
		$nodeNames = array("PHOTO:THUMBNAIL", "PHOTO:IMGSRC", "TITLE");
		$xh->setElementNames($nodeNames);
		$xh->setStartTag("ITEM");
		$xh->setVarsDefault();
		$xh->setXmlParser();
		$xh->setXmlData(stripslashes($_POST['rss']));
		$pData = $xh->xmlParse();
		$br = 0;
		
		$cntr = 0;
		
		echo("<table border='0' cellspacing='10' cellpadding='0'><tr>");		
	
		foreach($pData as $e) {
			if($cntr % 7 == 0)
			{
				echo("</tr><tr>");
			}
			echo "<td bgcolor='#C0C0C0' width='120' align='center' valign='middle'><img src='".$e['photo:thumbnail']."?size=120'></td>";
			$large = $e['photo:imgsrc'];		
			echo "<input type=hidden name='".$large."'></td>\r\n";
			
			$cntr++;
		}
		
		echo("</tr></table>");
		
		echo("<p>");
		echo("<input type=\"submit\" value=\"Publish\">&nbsp;");
		echo("<input type=\"button\" value=\"Cancel\" onclick=\"location.href='minibrowser:close'\">");
		echo("</p>");
		
		echo("</form></body></html>");
	}
	
	function UploadFromPicasa()
	{
		$instanceID = $_POST["cmbInstance"];		
		
		// echo $instanceID;
		// die;
	
		$dispOrderSQL = "SELECT MAX(`disporder`) FROM zcm_fr_galleryimage WHERE `instance` = '$instanceID'";
		$dispOrder = Zymurgy::$db->get($dispOrderSQL) or $dispOrder = 0;
		
		if($dispOrder == null)
		{
			$dispOrder = 0;
		}
		
		foreach($_FILES as $key => $file)
		{
			$tmpfile = $file['tmp_name'];		
			$dispOrder++;
			
			$insertSQL = "INSERT INTO zcm_fr_galleryimage ( instance, image, link, ".
			"caption, disporder ) VALUES ( '$instanceID', '".$file['type']."', '', '".$file["name"]."', '$dispOrder')";
			
			Zymurgy::$db->query($insertSQL) or die("Could not insert record for image.");
			
			$idSQL = "SELECT id FROM zcm_fr_galleryimage WHERE instance = '$instanceID' ".
				"AND disporder = '$dispOrder'";
			$id = Zymurgy::$db->get($idSQL) or die("Could not get ID of new image record.");
			
			$localfn = "../../UserFiles/DataGrid/zcm_fr_galleryimage.image/".$id."raw.jpg";
			
			if(move_uploaded_file($tmpfile, $localfn))
			{
				// chmod($localfn, 0644);
			}
		}
		
		// echo("http://".$_SERVER['SERVER_NAME']."/zymurgy/login.php");
	}
}

function FlashReliefThumbGalleryFactory()
{
	return new FlashReliefThumbGallery();
}

if(array_key_exists('DocType', $_GET) && $_GET["DocType"] == 'picasa')
{
	header("Content-type: text/html");	
		
	echo plugin('FlashReliefThumbGallery', 0, 'picasa');
}
else if(array_key_exists('DocType', $_POST) && $_POST["DocType"] == 'picasa')
{
	header("Content-type: text/html");	
		
	echo plugin('FlashReliefThumbGallery', 0, 'picasa');
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
	
	echo plugin('FlashReliefThumbGallery',$_GET['GalleryInstance'],$doctype);
}
?>