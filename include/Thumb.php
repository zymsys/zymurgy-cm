<?
class Thumb
{
	function ThumbFolder($galleryname)
	{
		global $ZymurgyRoot;
		$thumbs = $ZymurgyRoot.'/UserFiles/DataGrid';
		@mkdir($thumbs);
		$thumbs .= '/'.str_replace(
			array('/','.','$','\\',' '),
			array('', '', '', '',  '_'),$$galleryname);
		@mkdir($thumbs);
		return $thumbs;
	}

	function MakeThumb($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile)
	{
		global $ZymurgyConfig;
		
		$fd = fopen("$destfile.sh","w");
		fwrite($fd,"# MakeThumb(sx:$sx,sy:$sy,sw:$sw,sh:$sh,dw:$dw,dh:$dh,srcfile:$srcfile,destfile:$destfile)\n");

		list($width, $height, $type, $attr) = getimagesize($srcfile);
		fwrite($fd,"# source image size: $width x $height");
		//Now run ImageMagick.  Need to figure out options for thumbs that extract only a portion of the original.
		//Resize first and then crop resized image:
		//convert -crop 100x100+50+60 030101024026.JPG[400x200] out2.jpg
		
		//Determine ratio of change
		$wrat = $dw/$sw;
		$hrat = $dh/$sh;
		//Determine x,y offset on resized image
		$sxs = floor($sx*$wrat);
		$sys = floor($sy*$hrat);
		//Determine resized full width and height
		$swidth = floor($width * $wrat);
		$sheight =floor($height* $hrat);
		fwrite($fd,"# Calculated: wrat: $wrat hrat: $hrat sxs: $sxs sys: $sys swidth: $swidth sheight: $sheight\n");
		$cmd = "{$ZymurgyConfig['ConvertPath']}convert -resize {$swidth}x{$sheight} -crop {$dw}x{$dh}+{$sxs}+{$sys} $srcfile $destfile";
		fwrite($fd,"$cmd\n");
		fclose($fd);
		$out = system($cmd,$r);
		return $r;
	}

	function MakeFixedThumb($w,$h,$srcfile,$destfile)
	{
		list($width, $height, $type, $attr) = getimagesize($srcfile);
		$tratio = ($w/$h); //Target ratio of width to height
		$sratio = ($width/$height); //Source ratio of width to height
		if ($sratio > $tratio)
		{
			//Clip sides.  How much?
			$ratio = ($height/$h); 
			$sw = floor($w * $ratio);
			$sh = $height;
			$sx = floor(($width-$sw)/2);
			$sy = 0;
		}
		if ($sratio < $tratio)
		{
			//Clip top and bottom
			$ratio = ($width/$w);
			$sw = $width;
			$sh = floor($h * $ratio);
			$sx = 0;
			$sy = floor(($height-$sh)/2);
		}
		if ($sratio == $tratio)
		{
			return Thumb::MakeQuickThumb($w,$h,$srcfile,$destfile);
		}
		return Thumb::MakeThumb($sx,$sy,$sw,$sh,$w,$h,$srcfile,$destfile);
	}
	
	function MakeThumbNoBiggerThan($maxw,$maxh,$srcfile,$destfile)
	{
		list($width, $height, $type, $attr) = getimagesize($srcfile);
		$sw = $width;
		$sh = $height;
		if ($width > $maxw)
		{
			$ratio = $maxw / $width;
			$height = floor($height * $ratio);
			$width = $maxw;
		}
		if ($height > $maxh)
		{
			$ratio = $maxh / $height;
			$width = floor($width * $ratio);
			$height = $maxh;
		}
		return Thumb::MakeThumb(0,0,$sw,$sh,$width,$height,$srcfile,$destfile);
	}
	
	function MakeQuickThumb($maxw,$maxh,$srcfile,$destfile)
	{
		global $ZymurgyConfig;
		
		list($width, $height, $type, $attr) = getimagesize($srcfile);
		$w = $width;
		$h = $height;
		if ($maxw < $w)
		{
			//Scale to fit within the max width
			$ratio = $maxw / $w;
			$w = $maxw;
			$h = floor($h * $ratio);
		}
		if ($maxh < $h)
		{
			//Scale to fit within max height
			$ratio = $maxh /$h;
			$h = $maxh;
			$w = floor($w * $ratio);
		}
		//Now run ImageMagick
		$cmd = "{$ZymurgyConfig['ConvertPath']}convert -geometry $w x $h $srcfile $destfile";
		//echo "$cmd<br>";
		$out = system($cmd,$r);
		//echo "[$out,$r]<br>";
		return $r;
	}
}
?>