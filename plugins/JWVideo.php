<?
ini_set('display_errors', 1);

if (!class_exists('PluginBase'))
{
	require_once('../cms.php');
	require_once('../PluginBase.php');
	require_once('../include/Thumb.php');
}

class JWVideo extends PluginBase
{
	function GetTitle()
	{
		return 'JWPlayer Video Plugin';
	}

	public function GetDescription()
	{
		return "Media player plugin for the JWPlayer";
	}
	
	function Initialize()
	{
		Zymurgy::$db->query("CREATE TABLE IF NOT EXISTS `zcm_jwvideo` (
		  `id` int(11) NOT NULL auto_increment,
		  `instance` int(11) default NULL,
		  `video` varchar(60) default NULL,
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
		// require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');
		// $newfrgalleryimage = array('link'=>'alter table zcm_galleryimage add link varchar(200)');
		// CheckColumns('zcm_galleryimage',$newfrgalleryimage);
		// $this->CompleteUpgrade();
	}

	function GetUninstallSQL()
	{
		return "drop table zcm_jwvideo";
	}

	function RemoveInstance()
	{
		$sql = "select id from zcm_jwvideo where instance={$this->iid}";
		$ri = Zymurgy::$db->query($sql) or die("Unable to remove videos ($sql): ".Zymurgy::$db->error());
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			DataGrid::DeleteThumbs('zcm_jwvideo.image',$row['id']);
		}
		Zymurgy::$db->free_result($ri);
		$sql = "delete from zcm_jwvideo where instance={$this->iid}";
		Zymurgy::$db->query($sql) or die("Unable to remove videos ($sql): ".Zymurgy::$db->error());
		parent::RemoveInstance();
	}
	
	public function GetConfigItems()
	{
		$configItems = array();

		$configItems["Thumbnail width"] = array(
			"name" => "Thumbnail width",
			"default" => "80",
			"inputspec" => "input.3.3",
			"authlevel" => 0);
		$configItems["Thumbnail height"] = array(
			"name" => "Thumbnail height",
			"default" => "60",
			"inputspec" => "input.3.3",
			"authlevel" => 0);
		$configItems["Image width"] = array(
			"name" => "Image width",
			"default" => "800",
			"inputspec" => "input.3.3",
			"authlevel" => 0);
		$configItems["Image height"] = array(
			"name" => "Image height",
			"default" => "600",
			"inputspec" => "input.3.3",
			"authlevel" => 0);

		return $configItems;
	}
	
	function GetDefaultConfig()
	{
		$r = array();

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
		if (($usegimage) && (!array_key_exists('editkey',$_GET)))
		{
			$gi = $this->GetGalleryImageTag();
			if ($gi!='')
			{
				echo "<p>Gallery Image: $gi</p>";
			}
		}
		$ds = new DataSet(
			'zcm_jwvideo',
			'id');
		$ds->AddColumns(
			'id',
			'instance',
			'video',
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
			'video',
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

		$dg->AddUpDownColumn('disporder');
		$dg->AddEditColumn();
		$dg->AddDeleteColumn();
		$dg->insertlabel='Add a new Video';
		$dg->AddConstant('instance',$this->iid);

		$dg->Render();
	}

	function RenderHTML()
	{
		global $ZymurgyRoot;

		$filename = "http://www.easilybuildawebsite.com/Zak3.flv";

		$html = "";

		$html .= "<p id='jw".$this->iid."'>Video placeholder</p>\n";
		$html .= "<script type='text/javascript' src='/video/swfobject.js'></script>\n";
		$html .= "<script type='text/javascript'>\n";
		$html .= "var s1 = new SWFObject('/video/player.swf','player','400','300','9');\n";
		$html .= "s1.addParam('allowfullscreen','true');\n";
		$html .= "s1.addParam('allowscriptaccess','always');\n";
		$html .= "s1.addParam('flashvars','file=../Zak3.flv');\n";
		$html .= "s1.addVariable('type', 'video');\n";
		$html .= "s1.write('jw".$this->iid."');\n";
		$html .= "</script>\n";

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

		$pluginSQL = "SELECT `id` FROM zcm_plugin WHERE name = 'JWVideo'";
		$pid = Zymurgy::$db->get($pluginSQL) or die("Cannot retrieve plugin information");

		$instanceSQL = "SELECT `id`, `name` FROM zcm_plugininstance WHERE plugin = '".
			$pid.
			"' AND `name` <> '0' ORDER BY `name`";
		$instanceRI = Zymurgy::$db->query($instanceSQL) or die("Cannot retrieve list of instances");

		// require_once("../header.php");

		echo("<html><head><title>Picasa Upload Tool for Zymurgy:CM</title></head><body>");
		echo("<form name=\"f\" method=\"POST\" action=\"JWVideo.php\">");
		echo("<input type=\"hidden\" name=\"process\" value=\"true\">");
		echo("<input type=\"hidden\" name=\"DocType\" value=\"picasa\">");
		echo("<p>Instance: <select name=\"cmbInstance\">");

		while (($instanceRow = mysql_fetch_array($instanceRI))!==false)
		{
			echo("<option value=\"".$instanceRow["id"]."\">".$instanceRow["name"]."</option>");
		}

		echo("</select></p>");
		echo("<p><b>Selected Videos</b></p><p>");

		// echo(htmlentities($_POST['rss']));
		// die();

		$xmlText = $_POST['rss'];
		$xmlText = str_replace("xmlns=","a=",$xmlText);
		$xmlText = str_replace("photo:", "", $xmlText);
		$xmlText = str_replace("media:", "", $xmlText);

		$xml = new SimpleXMLElement($xmlText);
		//echo(htmlentities($xml->asXML()));
		//echo("<br><br>");

		foreach($xml->xpath("//item") as $item)
		{
			//echo $item->title;
			//echo "<br>";
			echo htmlentities($item->asXML());
			echo "<br>";

			$content = $item->xpath('group/content[@type!="video*"]');
			$contentAttributes = $content[0]->attributes();
			//print_r($content);
			// echo "<br><br>";

			$thumbnail = $item->xpath('group/thumbnail');
			$thumbnailAttributes = $thumbnail[0]->attributes();
			$thumbnailURL = $thumbnailAttributes["url"];

			echo "<img src='".$thumbnailURL."?size=".$this->GetConfigValue('Thumbnail width')."'>\r\n";
			// $large = $item->imgsrc;
			$large = $contentAttributes["url"];
			echo "<input type='hidden' name='".$large."'>\r\n";

			// echo "<br><br>";
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
		$this->CreateUploadDirectory();

		// print_r($_POST);
		// die();

		$instanceID = $_POST["cmbInstance"];

		// echo $instanceID;
		// die;

		$dispOrderSQL = "SELECT MAX(`disporder`) FROM zcm_jwvideo WHERE `instance` = '$instanceID'";
		$dispOrder = Zymurgy::$db->get($dispOrderSQL) or $dispOrder = 0;

		if($dispOrder == null)
		{
			$dispOrder = 0;
		}

		foreach($_FILES as $key => $file)
		{
			$tmpfile = $file['tmp_name'];
			$dispOrder++;

			$insertSQL = "INSERT INTO zcm_jwvideo ( instance, video, link, ".
			"caption, disporder ) VALUES ( '$instanceID', '".$file['type']."', '', '".$file["name"]."', '$dispOrder')";

			Zymurgy::$db->query($insertSQL) or die("Could not insert record for video.");

			$idSQL = "SELECT id FROM zcm_jwvideo WHERE instance = '$instanceID' ".
				"AND disporder = '$dispOrder'";
			$id = Zymurgy::$db->get($idSQL) or die("Could not get ID of new video record.");

			$extension = "jpg";

			switch($file['type'])
			{
				case "video/mpeg":
					$extension = "mpg";
					break;

				case "video/x-ms-wmv":
					$extension = "wmv";
					break;

				default:
					break;
			}

			$localfn = "../../UserFiles/DataGrid/zcm_jwvideo.video/".$id."raw.".$extension;

			if(move_uploaded_file($tmpfile, $localfn))
			{
				chmod($localfn, 0644);
			}
		}

		// echo("http://".$_SERVER['SERVER_NAME']."/zymurgy/login.php");
	}

	function CreateUploadDirectory()
	{
		global $ZymurgyRoot;

		@mkdir("$ZymurgyRoot/UserFiles/DataGrid");
		$thumbdest = "$ZymurgyRoot/UserFiles/DataGrid/zcm_jwvideo.video";
		@mkdir($thumbdest);
	}
}

function JWVideoFactory()
{
	return new JWVideo();
}

if(array_key_exists('DocType', $_GET) && $_GET["DocType"] == 'picasa')
{
	header("Content-type: text/html");

	echo plugin('JWVideo', 0, 'picasa');
}
else if(array_key_exists('DocType', $_POST) && $_POST["DocType"] == 'picasa')
{
	header("Content-type: text/html");

	echo plugin('JWVideo', 0, 'picasa');
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

	echo plugin('JWVideo',$_GET['GalleryInstance'],$doctype);
}
?>