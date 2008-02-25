<?
require_once('cms.php');
require_once("$ZymurgyRoot/zymurgy/ZymurgyAuth.php");
$zauth = new ZymurgyAuth();
$zauth->Authenticate("login.php");

if (array_key_exists('fixedar',$_GET))
	$fixedar = ($_GET['fixedar'] == 1);
else 
	$fixedar = false;
	
$ds = $_GET['ds'];
$d = $_GET['d'];
$id = 0 + $_GET['id'];
$imgdir = "/UserFiles/DataGrid/$ds/";
list ($width,$height) = explode('x',$d,2);
$minwidth = $initwidth = $width;
$minheight = $initheight = $height;

$work = array();
list($work['w'], $work['h'], $type, $attr) = getimagesize("$ZymurgyRoot$imgdir{$id}aspectcropNormal.jpg");
$raw = array();
list($raw['w'], $raw['h'], $type, $attr) = getimagesize("$ZymurgyRoot$imgdir{$id}raw.jpg");

//Adjust min's for raw image size
$minwidth *= $work['w'] / $raw['w'];
$minheight *= $work['h'] / $raw['h'];

if (($width>$work['w']) || ($height>$work['h']))
{
	//Supplied im,age is too small for thumb size.  Shrink selector and relax minimum size requirement.
	$xfactor = $yfactor = 1;
	if ($width>$work['w'])
		$xfactor = $work['w']/$width;
	if ($height>$work['h'])
		$yfactor = $work['h']/$height;
	$factor = min($xfactor,$yfactor);
	$minwidth = $initwidth = round($width * $factor);
	$minheight = $initheight = round($height * $factor);
	//$minwidth = $minheight = 20;
}
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
		
		$x = $selected['x'] * $xfactor;
		$y = $selected['y'] * $yfactor;
		$w = $selected['w'] * $xfactor;
		$h = $selected['h'] * $yfactor;
		
		//echo "Selected [x:$x,y:$y,w:$w,h:$h]<br>";
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
	}
?>
<script type="text/JavaScript">
<!--
var srcimg = window.opener.document.getElementById('<?=$_POST['returnurl'].".$d"?>');
if (srcimg) {
	var newsrc='<?= "{$imgdir}{$id}thumb$d.jpg?" ?>' + Math.random();
	srcimg.src=newsrc;
}
//window.opener.location.href='<?=$_POST['returnurl']?>';
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
 		<?php echo Zymurgy::YUI("yahoo/yahoo-min.js"); ?>
 		<?php echo Zymurgy::YUI("dom/dom-min.js"); ?>
 		<?php echo Zymurgy::YUI("event/event-min.js"); ?>
 		<?php echo Zymurgy::YUI("dragdrop/dragdrop-min.js"); ?>
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
 			
 			// granularity of the drag-drop constraint area
 			var granularity = 1;
 			
 			// the width of the border of the panel used to create the crop
 			// area - some of the calculations are adjusted to take this 
 			// border into account.
 			var borderWidth = 0;
 				
 			function init() {
 				//dd is the resize handle
	            dd = new YAHOO.example.DDResize(
			            "panelDiv", 
			            "handleDiv", 
			            "panelresize");
			            
			    //dd2 is the selected region of the image
	            dd2 = new YAHOO.util.DDProxy(
			            "panelDiv", 
			            "paneldrag");
	            dd2.addInvalidHandleId("handleDiv"); //Don't let the handle drag the image

	            document.getElementById("panelDiv").style.left = 
	            		(initX + offsetX) + "px";
	            document.getElementById("panelDiv").style.top = 
	            		(initY + offsetY) + "px";
	            document.getElementById("panelDiv").style.width =
	            		initWidth + "px";
	            document.getElementById("panelDiv").style.height =
	            		initHeight + "px";
	            
 				//Called when selected thumb area is dragged.
 				updateCropArea = function(e) {
 					var pd = document.getElementById("panelDiv");
 					var imgX = pd.offsetLeft - offsetX + borderWidth;
 					var imgY = pd.offsetTop - offsetY + borderWidth;
 					//setDebug('updating ix: '+initX+' bw: '+borderWidth+' imgX: '+imgX+' imgY: '+imgY);
	  				cropImage('imgCropped', imgX, imgY);
	 				setConstraints(
	 					dd2,
	 					dd,
	 					"panelDiv",
	 					"imgBackground",
	 					initX,
	 					initY,
	 					imgX, 
	 					imgY, 
	 					borderWidth); 	
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
 					value="10">
 				<input
 					type="hidden"
 					name="cropY"
 					value="10">
 				<input
 					type="hidden"
 					name="cropWidth"
 					value="200">
 				<input
 					type="hidden"
 					name="cropHeight"
 					value="100">
 				<input
 					type="hidden"
 					name="cropScale"
 					value="1.0">
 				<input
 					type="hidden"
 					name="returnurl"
 					value="<?=$_GET['returnurl']?>">
 				
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
  			<div id="debug"></div>
 		</div>
 	
 		<img
 			id="imgBackground"
 		 	src="<?= "$imgdir/{$id}aspectcropDark.jpg" ?>">
 			
 		<div id="panelDiv">
	 		<img
	 			id="imgCropped"
	 			src="<?= "$imgdir/{$id}aspectcropNormal.jpg" ?>">
        	<div id="handleDiv"></div>
    	</div> 			
 	</body>
 </html>
