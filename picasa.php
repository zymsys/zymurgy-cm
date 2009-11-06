<?php
	function uuid()
  	{
    	$chars = md5(uniqid(rand()));
    	$uuid  = substr($chars,0,8) . '-';
    	$uuid .= substr($chars,8,4) . '-';
    	$uuid .= substr($chars,12,4) . '-';
    	$uuid .= substr($chars,16,4) . '-';
    	$uuid .= substr($chars,20,12);

    	return $uuid;
  	}

  	function CreatePicasaButton(
  		$pluginName,
  		$friendlyName)
  	{
		$guid = uuid();

$xml = <<<ENDXML
<?xml version="1.0" encoding="utf-8"?>
<buttons format='1' version='3'>
	<button id="zymurgy/{{$guid}}" type="dynamic">
		<icon name="{{$guid}}/Icon" src="pbz"/>
		<label>{0}</label>
		<tooltip>Upload the selected images to a {0} $friendlyName.</tooltip>
		<action verb="hybrid">
			<param name="url" value="http://{$_SERVER['HTTP_HOST']}/zymurgy/plugins/$pluginName.php?DocType=picasa"/>
		</action>
	</button>
</buttons>
ENDXML;

		$xml = str_replace("{0}", Zymurgy::GetLocaleString("Common.ProductName"), $xml);

		$zip = new ZipArchive();
		$filename = "../temp/{".$guid."}.pbz";

		if($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE)
		{
			exit("Cannot open <$filename>\n");
		}

		$zip->addFromString(
			"{".$guid."}.pbf",
			$xml);
		$zip->addFile(
			"../images/icon.psd",
			"{".$guid."}.psd");

  		return $guid;
  	}

	$flashRelief = CreatePicasaButton(
		"FlashReliefThumbGallery",
		"Flash Relief image gallery");
	$yuiGallery = CreatePicasaButton(
		"ImageGallery",
		"Image Gallery");
	$jwvideo = CreatePicasaButton(
		"JWVideo",
		"Video Gallery");

	require_once("header.php");
?>

<p><b>Downloads:</b></p>

<ul>
	<li><a href="http://picasa.google.com/">Google Picasa</a></li>
	<li><a href="picasa://importbutton/?url=http://<?= $_SERVER['HTTP_HOST'] ?>/temp/{<?= $flashRelief ?>}.pbz">Button for Flash Relief image galleries</a></li>
	<li><a href="picasa://importbutton/?url=http://<?= $_SERVER['HTTP_HOST'] ?>/temp/{<?= $yuiGallery ?>}.pbz">Button for YUI image galleries</a></li>
	<li><a href="picasa://importbutton/?url=http://<?= $_SERVER['HTTP_HOST'] ?>/temp/{<?= $jwvideo ?>}.pbz">Button for video galleries</a></li>
</ul>



<?php
	include("footer.php");
?>