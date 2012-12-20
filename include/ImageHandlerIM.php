<?
require_once(Zymurgy::getFilePath("~include/ImageHandler.php"));
/**
 * $cmd = "{Zymurgy::$config['ConvertPath']}convert -resize {$swidth}x{$sheight} -crop {$dw}x{$dh}+{$sxs}+{$sys} $srcfile $destfile";
 * $cmd = "{Zymurgy::$config['ConvertPath']}convert -geometry $w x $h $srcfile $destfile";
 * system(Zymurgy::$config['ConvertPath']."convert -modulate 75 $thumbdest/{$id}aspectcropNormal.$ext $thumbdest/{$id}aspectcropDark.$ext");
 *
 */
class ZymurgyImageHandlerImageMagick extends ZymurgyImageHandler 
{
	function ResizeWithCrop($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile)
	{
		list($width, $height, $type, $attr) = getimagesize($srcfile);
		
		//Determine ratio of change
		$wrat = $dw/$sw;
		$hrat = $dh/$sh;
		//Determine x,y offset on resized image
		$sxs = floor($sx*$wrat);
		$sys = floor($sy*$hrat);
		//Determine resized full width and height
		$swidth = floor($width * $wrat);
		$sheight =floor($height* $hrat);
		
		$cmd = Zymurgy::$config['ConvertPath']."convert -resize {$swidth}x{$sheight} -crop {$dw}x{$dh}+{$sxs}+{$sys} $srcfile $destfile";
		//echo "<div>$cmd</div>";
		$out = system($cmd,$r);
		return $r;
	}
	
	function Resize($width,$height,$srcfile,$dstfile)
	{
		$cmd = Zymurgy::$config['ConvertPath']."convert -geometry $width x $height $srcfile $dstfile";
		$out = system($cmd,$r);
		if ($out === false)
		{
			die("Unable to execute GD ($cmd)");
		}
		return $r;
	}
	
	function Darken($amount,$srcfile,$dstfile)
	{
		$cmd = Zymurgy::$config['ConvertPath']."convert -modulate $amount $srcfile $dstfile";
		$out = system($cmd,$r);
		return $r;
	}
}
?>