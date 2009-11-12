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

class ImageGallery extends PluginBase
{
	function GetTitle()
	{
		return 'Image Gallery Plugin';
	}

	function Initialize()
	{
		$this->VerifyTableDefinitions();
	}

	private function VerifyTableDefinitions()
	{
		require_once(Zymurgy::$root.'/zymurgy/installer/upgradelib.php');

		$tableDefinitions = array(
			array(
				"name" => "zcm_galleryimage",
				"columns" => array(
					DefineTableField("id", "INT(11)", "NOT NULL AUTO_INCREMENT"),
					DefineTableField("instance", "INT(11)", "DEFAULT NULL"),
					DefineTableField("image", "VARCHAR(60)", "DEFAULT NULL"),
					DefineTableField("link", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("caption", "VARCHAR(200)", "DEFAULT NULL"),
					DefineTableField("disporder", "INT(11)", "DEFAULT NULL")
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

	function Upgrade()
	{
		$this->VerifyTableDefinitions();
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

	function GetConfigItems()
	{
		$configItems = array();

		$configItems[] = array(
			"name" => "Generate Gallery Image",
			"default" => "false",
			"inputspec" => "drop.true,false",
			"authlevel" => 2);
		$configItems[] = array(
			"name" => "Thumbnail width",
			"default" => 80,
			"inputspec" => "input.3.3",
			"authlevel" => 2);
		$configItems[] = array(
			"name" => "Thumbnail height",
			"default" => 60,
			"inputspec" => "input.3.3",
			"authlevel" => 2);
		$configItems[] = array(
			"name" => "Image width",
			"default" => 800,
			"inputspec" => "input.3.3",
			"authlevel" => 2);
		$configItems[] = array(
			"name" => "Image height",
			"default" => 600,
			"inputspec" => "input.3.3",
			"authlevel" => 2);

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
		global $ZymurgyRoot;

		$html = "";

		$html .= Zymurgy::YUI("carousel/assets/skins/sam/carousel.css")."\n";
		$html .= Zymurgy::YUI("yahoo/yahoo-dom-event.js")."\n";
		$html .= Zymurgy::YUI("element/element-beta-min.js")."\n";
		$html .= Zymurgy::YUI("carousel/carousel-beta-min.js")."\n";


		$html .= "<style>\n";
		$html .= "#spotlight { border: 1px solid black; margin: 10px auto; padding: 1px; width: ".$this->GetConfigValue("Image width")."px; height: ".$this->GetConfigValue("Image height")."px; overflow: hidden; text-align: center; vertical-align: middle; }\n";
		$html .= "#container { margin: 0 auto; }\n";
		$html .= ".yui-carousel-element li { height: ".$this->GetConfigValue('Thumbnail height')."px; width: ".$this->GetConfigValue('Thumbnail width')."px; opacity: 0.6; }\n";
		$html .= ".yui-carousel-element .yui-carousel-item-selected { opacity: 1; } \n";
		$html .= ".yui-skin-sam .yui-carousel-nav ul li { margin: 0; }\n";
		$html .= "</style>\n";

		$html .= "<div id=\"spotlight\"></div>\n";

		$html .= "<div id=\"container\"><ol id=\"carousel\">\n";

		$sql = "select id,caption,link from zcm_galleryimage where instance={$this->iid} order by disporder";
		$ri = Zymurgy::$db->query($sql) or die("Unable to load images ($sql): ".Zymurgy::$db->error());
		if (!$ri)
			die("Unable to read gallery images ($sql): ".Zymurgy::$db->error());

		$imgpath = "DataGrid/zcm_galleryimage.image";
		//echo("$ZymurgyRoot/UserFiles");
		// chdir("$ZymurgyRoot/UserFiles");

		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			$html .= "<li>\n";
			$html .= "<img src=\"".
				"/UserFiles/".
				$imgpath.
				"/".
				$row['id'].
				"thumb".
				$this->GetConfigValue('Thumbnail width').
				"x".
				$this->GetConfigValue('Thumbnail height').
				".jpg\">\n";
			$html .= "</li>\n";
		}

		$html .= "</ol></div>\n";

		$html .= "<script>\n";

		$html .= "(function () {\n";
		$html .= "var carousel;\n";

		$html .= "function getImage(parent) {\n";
		$html .= "var el = parent.firstChild;\n";

		$html .= "while (el) {\n";
		$html .= "if (el.nodeName.toUpperCase() == \"IMG\") {\n";
		$html .= "return el.src.replace(/".
			$this->GetConfigValue('Thumbnail width').
			"x".
			$this->GetConfigValue('Thumbnail height').
			"\\.jpg$/, \"".
			$this->GetConfigValue('Image width').
			"x".
			$this->GetConfigValue('Image height').
			".jpg\");\n";
		$html .= "}\n";
		$html .= "el = el.nextSibling;\n";
		$html .= "}\n";

		$html .= "return \"\";\n";
		$html .= "}\n";

		$html .= "YAHOO.util.Event.onDOMReady(function (ev) {\n";
		$html .= "var el, item,\n";
		$html .= "spotlight   = YAHOO.util.Dom.get(\"spotlight\"),\n";
		$html .= "carousel    = new YAHOO.widget.Carousel(\"container\", { animation: { speed: 0.5 }, numVisible: 8, revealAmount: 20 } );\n";

		$html .= "carousel.render();\n";
		$html .= "carousel.show();\n";
		// $html .= "carousel.startAutoPlay();\n";

		$html .= "item = carousel.getElementForItem(carousel.get(\"selectedItem\"));\n";
		$html .= "if (item) {\n";
		$html .= "spotlight.innerHTML = \"<img src=\\\"\" + getImage(item) + \"\\\">\";\n";
		$html .= "}\n";

		$html .= "carousel.on(\"itemSelected\", function (index) {\n";
		$html .= "item = carousel.getElementForItem(index);\n";

		$html .= "if (item) {\n";
		$html .= "spotlight.innerHTML = \"<img src=\\\"\"+getImage(item)+\"\\\">\";\n";
		$html .= "}\n";
		$html .= "});\n";
		$html .= "});\n";
		$html .= "})();\n";

		$html .= "</script>\n";

		// $html = "Content pending";

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

		include("../header_html.php");

		echo("<p>The ".Zymurgy::GetLocaleString("Common.ProductName")." Picasa Upload Utility allows you to upload your images directly from Google Picasa into your Image Gallery. It also takes care of re-sizing your images for the Web, so you don't have to wait a long time to upload images taken with your digital camera.</p>");

		echo("<form name=\"f\" method=\"POST\" action=\"ImageGallery.php\">");
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
			echo "<td bgcolor='#C0C0C0' width='120' align='center' valign='middle'><img src='".$e['photo:thumbnail']."?size=".$this->GetConfigValue('Thumbnail width')."'></td>";
			$large = $e['photo:imgsrc'];
			echo "<input type=hidden name='".$large."?size=1280'>\r\n";

			$cntr++;
		}

		echo("</tr></table>");

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

			$targets = array();

			$targets[] =
				$this->GetConfigValue('Thumbnail width').
				"x".
				$this->GetConfigValue('Thumbnail height').
				",".
				$this->GetConfigValue('Image width').
				"x".
				$this->GetConfigValue('Image height');

			Zymurgy::MakeThumbs(
				"zcm_galleryimage.image",
				$id,
				$targets,
				$tmpfile);

			// if(move_uploaded_file($tmpfile, $localfn))
			// {
				// chmod($localfn, 0644);
			// }
		}

		// echo("http://".$_SERVER['SERVER_NAME']."/zymurgy/login.php");
	}

	function CreateUploadDirectory()
	{
		global $ZymurgyRoot;

		@mkdir("$ZymurgyRoot/UserFiles/DataGrid");
		$thumbdest = "$ZymurgyRoot/UserFiles/DataGrid/zcm_galleryimage.image";
		@mkdir($thumbdest);
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
