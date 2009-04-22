<?
require_once('cms.php');
//Zymurgy::$yuitest = true;
if (!Zymurgy::memberauthenticate())
{
	require_once("$ZymurgyRoot/zymurgy/ZymurgyAuth.php");
	$zauth = new ZymurgyAuth();
	$zauth->Authenticate("login.php");
}

if (array_key_exists('fixedar',$_GET))
	$fixedar = ($_GET['fixedar'] == 1);
else
	$fixedar = false;

$debugmsg = array(); //Debug messages - Comment out output near the bottom of this script for production.
$stats = array(); //Key: name, Value: array(width, height) - used for making stats table for troubleshooting purposes.

$ds = $_GET['ds']; //Dataset - table.field
$d = $_GET['d']; //Dimensions - WIDTHxHEIGHT
$gd = $_GET['gd']; //Dimensions of the image in the grid on the parent window
$id = 0 + $_GET['id']; //Database row ID
$imgdir = "/UserFiles/DataGrid/$ds/";
list ($width,$height) = explode('x',$d,2);
$stats['Requested'] = array($width,$height);
$minwidth = $width;
$minheight = $height;
$stats['Min (Initial)'] = array($minwidth,$minheight);

$work = array();
list($work['w'], $work['h'], $type, $attr) = getimagesize("$ZymurgyRoot$imgdir{$id}aspectcropNormal.jpg");
$stats['Work Image'] = array($work['w'],$work['h']);
$raw = array();
list($raw['w'], $raw['h'], $type, $attr) = getimagesize("$ZymurgyRoot$imgdir{$id}raw.jpg");
$stats['Raw Image'] = array($raw['w'],$raw['h']);

$xfactor = $raw['w'] / $work['w'];
$yfactor = $raw['h'] / $work['h'];

$debugmsg[] = "xfactor/yfactor: $xfactor/$yfactor";

//Adjust min's for raw image size
$minwidth *= $work['w'] / $raw['w'];
$minheight *= $work['h'] / $raw['h'];
$stats['Min (Adj. for Work)'] = array($minwidth,$minheight);

//Here $width is the requested width, and $work['w'] is the working image for the thumber (aspectcropNormal.jpg)
if (($minwidth>$work['w']) || ($minheight>$work['h']))
{
	$debugmsg[] = "Adjusting restrictions for small work image.  ([rw:$width>ww{$work['w']}] || [rh:$height>wh:{$work['h']}])";
	//Working image is too small for thumb size.  Shrink selector and adjust minimum size requirement.
	$xfactor = $yfactor = 1;
	if ($width>$work['w'])
		$xfactor = $work['w']/$width;
	if ($height>$work['h'])
		$yfactor = $work['h']/$height;
	$factor = min($xfactor,$yfactor);
	$minwidth = round($width * $factor);
	$minheight = round($height * $factor);
	$stats['Min (Adj. for Sm. Wrk.)'] = array($minwidth,$minheight);
}

$initheight = $minheight;
$initwidth = $minwidth;

//Do we have room for a nice 10px gap to start, or will we push right to the edge?
//echo "if (min({$work['w']} - $initwidth,{$work['h']} - $initheight) < 0)"; exit;
if (min($work['w'] - $initwidth,$work['h'] - $initheight) < 10)
{
	$initx = 0;
	$inity = 0;
}
else
{
	$initx = 10;
	$inity = 10;
}

/*if ($minwidth >= $work['w']) $minwidth = $initwidth = $work['w'];
if ($minheight >= $work['h']) $minheight = $initheight = $work['h'];*/

	//Try to load previous cropping area
	$shfn = "$ZymurgyRoot$imgdir{$id}thumb$d.jpg.sh";
	if (file_exists($shfn))
	{
		$fc = file_get_contents($shfn);
		$fc = explode("\n",$fc);
		$fc = explode('(',$fc[0]);
		$fc = explode(',',$fc[1]);
		$lastcrop = array();
		for ($n = 0; $n < 6; $n++)
		{
			$lc = explode(':',$fc[$n]);
			$lastcrop[$lc[0]] = $lc[1];
		}
		$initx = round($lastcrop['sx'] / $xfactor);
		$inity = round($lastcrop['sy'] / $yfactor);
		$initwidth = round($lastcrop['sw'] / $xfactor);
		$initheight = round($lastcrop['sh'] / $yfactor);
		$debugmsg[] = "<div>Loaded from previous thumb: initx:$initx, inity:$inity, initwidth:$initwidth, initheight:$initheight</div>";
	}

if ($_SERVER['REQUEST_METHOD']=='POST')
{
	if ($_POST['action']=='clear')
	{
		//Remove cached image files
		$path = "$ZymurgyRoot/UserFiles/DataGrid/$ds";
		@unlink("$path/{$id}aspectcropDark.jpg");
		@unlink("$path/{$id}aspectcropNormal.jpg");
		@unlink("$path/{$id}raw.jpg");
		$thumbs = glob("$path/{$id}thumb*");
		foreach($thumbs as $thumb)
		{
			@unlink($thumb);
		}
	}
	else
	{
		require_once('include/Thumb.php');

		$selected = array(
			'x'=>$_POST['cropX'],
			'y'=>$_POST['cropY'],
			'w'=>$_POST['cropWidth'],
			'h'=>$_POST['cropHeight']);

		//echo "[{$selected['x']},{$selected['y']},{$selected['w']},{$selected['h']}]<br>";
		//echo "raw [{$raw['w']},{$raw['h']}]<br>";

		//Math time...  Take 640x480 work image coordinates and figure out coordinates on full sized image.
		$xfactor = $raw['w'] / $work['w'];
		$yfactor = $raw['h'] / $work['h'];

		//echo "factors: $xfactor $yfactor<br>";

		$x = round($selected['x'] * $xfactor);
		$y = round($selected['y'] * $yfactor);
		$w = round($selected['w'] * $xfactor);
		$h = round($selected['h'] * $yfactor);

		$debugmsg[] = "Selected [x:$x,y:$y,w:$w,h:$h]";
		if (!$fixedar)
		{
			$maxwidth = $width;
			$maxheight = $height;
			if ($w>$width)
			{
				//Fit for max width
				$r = $maxwidth/$w;
				$width = $maxwidth;
				$height = floor($r * $h);
			}
			if ($height>$maxheight)
			{
				//Height still doesn't fit so adjust again.
				$r = $maxheight/$height;
				$height = $maxheight;
				$width = floor($r * $width);
			}
			//echo "Adjusted dest w/h: $width/$height<br>";
		}
		$thumbpath = "$ZymurgyRoot$imgdir{$id}thumb$d.jpg";
		Thumb::MakeThumb($x,$y,$w,$h,$width,$height,"$ZymurgyRoot$imgdir{$id}raw.jpg",$thumbpath);
		
		//Make smaller grid thumb, if required
		if ($gd != $d)
		{
			list($gw,$gh) = explode('x',$gd);
			Thumb::MakeThumb($x,$y,$w,$h,$gw,$gh,"$ZymurgyRoot$imgdir{$id}raw.jpg","$ZymurgyRoot$imgdir{$id}thumb$gd.jpg");
		}
	}
?>
<script type="text/JavaScript">
<!--
var srcimg = window.opener.document.getElementById('<?=$_POST['imgid'].".$d"?>');
if (srcimg) {
	var newsrc='<?= "{$imgdir}{$id}thumb$gd.jpg?" ?>' + Math.random();
	srcimg.src=newsrc;
}
window.close();
//-->
</script>
<?
	exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">

 <html>
 	<head>
 		<title>Zymurgy:CM Thumbnail Selection Tool</title>
 		<?php
 		if (Zymurgy::$yuitest) echo Zymurgy::YUI("logger/assets/skins/sam/logger.css");
 		echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
 		echo Zymurgy::YUI("dragdrop/dragdrop-min.js"); 
 		if (Zymurgy::$yuitest) echo Zymurgy::YUI("logger/logger-min.js");
 		?>
 		<script
	 			language="javascript"
	 			type="text/javascript"
	 			src="include/aspectCrop.js">
 		</script>
 		<script
	 			language="javascript"
	 			type="text/javascript"
	 			src="include/DDResize.js">
 		</script>
<style type="text/css">
<!--
body {
	background-color: #FFFFFF;
	font-family: Arial, Helvetica, Sans-Serif;
	margin: 10px;
	padding: 0px;
}

div#divForm {
	position: absolute;
	left: <?=$work['w']+10?>px;
	top: 10px;
	padding: 10px;
	width: 110px;
	height: 30px;
	overflow: crop;
	z-index:9;
}

img#imgBackground {
	width: <?=$work['w']?>px;
	height: <?=$work['h']?>px;
}

#panelDiv {
	position: absolute;
    /* position:relative;  */
    height: 150px;
    width: 300px;
    top:10px;
    left:10px;
    background-color: #f7f7f7;
    overflow: hidden;
	z-index: 10;
}

#handleDiv {
    position: absolute;
    bottom:0px;
    right: 0px;
    width:16px;
    height:16px;
    background-image: url(images/resizeHandle.gif);
    font-size: 1px;
    z-index: 20;
}

#theimage {
    position:absolute;
    top: 10px;
    left: 10px;
}

img#imgCropped {
	position: absolute;
	left: 0px;
	top: 0px;
	width: <?=$work['w']?>px;
	height: <?=$work['h']?>px;
	/* clip: rect(0px 0px 100px 200px); */
	z-index: 10;
}

-->
</style>
 		<script
 				language="javascript"
 				type="text/javascript">
 			// Yahoo! Drag and Drop objects
 			var dd, dd2;

 			// position of the background image
 			var offsetX = 10;
 			var offsetY = 10;

 			// initial position of the crop box
 			var initX = <?=$initx?>;
 			var initY = <?=$inity?>;

 			// initial width and height of the crop box
  			var initWidth = <?=$initwidth?>;
 			var initHeight = <?=$initheight?>;

 			// minimum width and height of the crop box
 			var minWidth = <?=$minwidth?>;
 			var minHeight = <?=$minheight?>;
 			var aspectRatio = <?= $fixedar ? "parseFloat(minWidth) / parseFloat(minHeight)" : 0 ?>;

 			function init() {
 				//dd is the resize handle
 				var pd = document.getElementById("panelDiv");
	            dd = new YAHOO.example.DDResize("panelDiv", "handleDiv", "panelresize");

			    //dd2 is the selected region of the image
	            dd2 = new YAHOO.util.DDProxy("panelDiv", "paneldrag");
	            dd2.addInvalidHandleId("handleDiv"); //Don't let the handle drag the image

	            pd.style.left = (initX + offsetX) + "px";
	            pd.style.top = (initY + offsetY) + "px";
	            pd.style.width = initWidth + "px";
	            pd.style.height = initHeight + "px";

 				//Called when selected thumb area is dragged.
 				updateCropArea = function(e) {
 					//var pd = document.getElementById("panelDiv");
 					var imgX = pd.offsetLeft - offsetX;
 					var imgY = pd.offsetTop - offsetY;
 					cropImage('imgCropped', imgX, imgY);
	 				setConstraints(
	 					dd2,
	 					dd,
	 					"panelDiv",
	 					"imgBackground",
	 					initX,
	 					initY,
	 					imgX,
	 					imgY);
 				};

 				updateCropArea();

 				document.getElementById("imgBackground").style.width =
 					document.getElementById("imgCropped").style.width = '<?= "{$work['w']}px" ?>';
 				document.getElementById("imgBackground").style.height =
 					document.getElementById("imgCropped").style.height = '<?= "{$work['h']}px" ?>';

 				dd.onMouseUp = updateCropArea;
 				dd2.onMouseUp = updateCropArea;
 			}
 		</script>
 	</head>
 	<body onLoad="init();">
 		<div id="divForm">
 			<form name="frmCrop" method="POST" action="<?=$_SERVER['REQUEST_URI']?>">
 				<input
 					type="hidden"
 					name="action"
 					value="crop">
 				<input
 					type="hidden"
 					name="cropX"
 					value="<?= $initx ?>">
 				<input
 					type="hidden"
 					name="cropY"
 					value="<?= $inity ?>">
 				<input
 					type="hidden"
 					name="cropWidth"
 					value="<?= $initwidth ?>">
 				<input
 					type="hidden"
 					name="cropHeight"
 					value="<?= $initheight ?>">
 				<input
 					type="hidden"
 					name="cropScale"
 					value="1.0">
 				<input
 					type="hidden"
 					name="imgid"
 					value="<?=$_GET['imgid']?>">

 				<input
 					type="button"
 					name="cmdSubmit"
 					value="Save Image"
 					onClick="submitForm();">
 				<input
 					type="button"
 					name="cmdClear"
 					value="Clear Image"
 					onClick="clearImage();">
  			</form>
  			<div id="debug">
  			<?
if (Zymurgy::$yuitest) //Set to false when not debugging
{  			
	echo "<table border=\"1\">";
	foreach ($stats as $caption=>$sz)
	{
		echo "<tr><td>$caption</td><td>{$sz[0]}</td><td>{$sz[1]}</td></tr>";
	}
	echo "</table>";
	if (isset($debugmsg)) 
	{
		echo implode('<hr />',$debugmsg);
	}
	echo '<div id="myLogger"></div>
<script type="text/javascript">
var myLogReader = new YAHOO.widget.LogReader("myLogger");
</script>';
}
			?></div>
 		</div>

 		<img
 			id="imgBackground"
 		 	src="<?= "$imgdir/{$id}aspectcropDark.jpg".'?'.time() ?>">

 		<div id="panelDiv">
	 		<img
	 			id="imgCropped"
	 			src="<?= "$imgdir/{$id}aspectcropNormal.jpg".'?'.time() ?>">
        	<div id="handleDiv"></div>
    	</div>
 	</body>
 </html>
