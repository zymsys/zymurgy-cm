<?
/**
 *
 * @package Zymurgy_Plugins
 */
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
		$this->VerifyTableDefinitions();
	}

	function Upgrade()
	{
		$this->VerifyTableDefinitions();
	}

	function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');

		$tableDefinitions = array(
			array(
				"name" => "zcm_fr_galleryimage",
				"columns" => array(
					DefineTableField("id", "INTEGER", "UNSIGNED NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INTEGER", "UNSIGNED NOT NULL"),
					DefineTableField("image", "VARCHAR(60)", "DEFAULT NULL"),
					DefineTableField("link", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("caption", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("disporder", "INTEGER", "UNSIGNED DEFAULT NULL")
				),
				"indexes" => array(
					array("columns" => "instance", "unique" => "false", "type" => ""),
					array("columns" => "disporder", "unique" => "false", "type" => "")
				),
				"primarykey" => "id",
				"engine" => "InnoDB"
			)
		);

		ProcessTableDefinitions($tableDefinitions);
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

	function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => 'SWF Location',
			"default" => '/swf/gallery.swf',
			"inputspec" => 'input.30.30',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Generate Gallery Image',
			"default" => 'false',
			"inputspec" => 'drop.true,false',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Gallery Image Width',
			"default" => 50,
			"inputspec" => 'input.1.3',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Gallery Image Height',
			"default" => 50,
			"inputspec" => 'input.1.3',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Flash Control Width',
			"default" => 500,
			"inputspec" => 'input.4.5',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Flash Control Height',
			"default" => 400,
			"inputspec" => 'input.4.5',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Flash Control Background',
			"default" => '#ffffff',
			"inputspec" => 'colour',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Main Image Area Width',
			"default" => 400,
			"inputspec" => 'input.4.5',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Main Image Area Height',
			"default" => 300,
			"inputspec" => 'input.4.5',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Width',
			"default" => 50,
			"inputspec" => 'input.1.3',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Height',
			"default" => 50,
			"inputspec" => 'input.1.3',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Transition Type',
			"default" => 'wipe',
			"inputspec" => 'drop.fade,zoom,squeeze,pixeldissolve,blinds,wipe,iris,photo,fly',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Transition Speed',
			"default" => 2,
			"inputspec" => 'input.1.3',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Thumbnail Rows',
			"default" => 1,
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Show Caption and Controls',
			"default" => 'true',
			"inputspec" => 'drop.true,false',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Show Loader',
			"default" => 'true',
			"inputspec" => 'drop.true,false',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Caption Position',
			"default" => 'top',
			"inputspec" => 'drop.top,bottom',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Seconds per Image',
			"default" => 5,
			"inputspec" => 'input.1.3',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Border Thickness',
			"default" => 5,
			"inputspec" => 'input.1.3',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Background Colour',
			"default" => '#888888',
			"inputspec" => 'colour',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Outline Thickness',
			"default" => '1',
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Outline Colour',
			"default" => '#eeeeee',
			"inputspec" => 'colour',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Outline Colour (selected)',
			"default" => '#ff9999',
			"inputspec" => 'colour',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Background Colour',
			"default" => '#333333',
			"inputspec" => 'colour',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Background Corner Size',
			"default" => '5',
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Large Image Corner Size',
			"default" => '5',
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Corner Size',
			"default" => '5',
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Spacing',
			"default" => '3',
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Main Image to Thumb Spacing',
			"default" => '3',
			"inputspec" => 'input.1.2',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Scroll Speed',
			"default" => 8,
			"inputspec" => 'input.1.3',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Thumb Transparancy (not selected)',
			"default" => 50,
			"inputspec" => 'input.1.3',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Thumb Position',
			"default" => 'bottom',
			"inputspec" => 'drop.left,right,top,bottom',
			"authlevel" => 2);
		$configItems[] = array(
			"name" => 'Play Rollover Sound',
			"default" => 'true',
			"inputspec" => 'drop.true,false',
			"authlevel" => 0);
		$configItems[] = array(
			"name" => 'Play Click Sound',
			"default" => 'true',
			"inputspec" => 'drop.true,false',
			"authlevel" => 0);

		return $configItems;
	}

	function GetDefaultConfig()
	{
		$r = array();

		$configItems = $this->GetConfigItems();

		foreach($configItems as $configItem)
		{
			$this->BuildConfig(
				$r,
				$configItem["name"],
				$configItem["default"],
				$configItem["inputspec"],
				$configItem["authlevel"]);
		}

		$this->BuildExtensionConfig($r);

		return $r;
	}

	function GetCommandMenuItems()
	{
		$r = array();

		$this->BuildSettingsMenuItem($r);
		$this->BuildDeleteMenuItem($r);

		return $r;
	}

	function RenderAdmin()
	{
		$usegimage = ($this->GetConfigValue('Generate Gallery Image')=='true');
		if ($usegimage && (array_key_exists('gimage',$_GET)))
		{
			$sql = "update zcm_fr_galleryimage set gimage=0 where instance=".$this->iid;
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
//		print_r($this);
//		echo($this->GetConfigValue("SWF Location"));
//		die();

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

	function isOlder($isthis,$olderthanthis)
	{
		$a = stat($isthis);
		$b = stat($olderthanthis);
		return ($a['mtime'] < $b['mtime']);
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
			if (!file_exists($thumb) || $this->isOlder($thumb,$raw))
			{
				Thumb::MakeFixedThumb($this->GetConfigValue('Thumb Width'),
					$this->GetConfigValue('Thumb Height'),$raw,$thumb);
			}
			if (!file_exists($image) || $this->isOlder($image,$raw))
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

		echo("<p>The ".Zymurgy::GetLocaleString("Common.ProductName")." Picasa Upload Utility allows you to upload your images directly from Google Picasa into your Flash Relief Image Gallery. It also takes care of re-sizing your images for the Web, so you don't have to wait a long time to upload images taken with your digital camera.</p>");

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
