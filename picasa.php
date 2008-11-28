<?php
	// ini_set("display_errors", 1);

	require_once("cms.php");

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
		
		$xml = "";
		
		$xml .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$xml .= "<buttons format=\"1\" version=\"3\">";
		$xml .= "<button id=\"zymurgy/{".$guid."}\" type=\"dynamic\">";
		$xml .= "<icon name=\"{".$guid."}/Icon\" src=\"pbz\"/>";
		$xml .= "<label>Zymurgy:CM</label>";
		$xml .= "<tooltip>Upload the selected images to a Zymurgy:CM $friendlyName.</tooltip>";
		$xml .= "<action verb=\"hybrid\">";
	  	$xml .= "<param name=\"url\" value=\"http://".$_SERVER['HTTP_HOST']."/zymurgy/plugins/$pluginName.php?DocType=picasa\"/>";
		$xml .= "</action>";
		$xml .= "</button>";
		$xml .= "</buttons>";
		
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
	
	require_once("header.php");
?>	

<p><b>Downloads:</b></p>

<ul>
	<li><a href="http://picasa.google.com/">Google Picasa</a></li>
	<li><a href="picasa://importbutton/?url=http://<?= $_SERVER['HTTP_HOST'] ?>/temp/{<?= $flashRelief ?>}.pbz">Button for Flash Relief image galleries</a></li>
	<li><a href="picasa://importbutton/?url=http://<?= $_SERVER['HTTP_HOST'] ?>/temp/{<?= $yuiGallery ?>}.pbz">Button for YUI image galleries</a></li>
</ul>



<?php
	include("footer.php");	
?>