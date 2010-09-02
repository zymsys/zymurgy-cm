<?
class Thumb
{
	static function mime2ext($mime)
	{
		switch($mime)
		{
			case 'image/gif':
				return 'gif';
			case 'image/png':
			case 'image/x-png':
				return 'png';
			default:
				return 'jpg';
		}
	}
	
	function ThumbFolder($galleryname,$createifnew=true)
	{
		$thumbs = Zymurgy::$root.'/UserFiles/DataGrid';
		if ($createifnew)
		{
			@mkdir($thumbs);
		}
		$oldgn = false;
		while ($galleryname != $oldgn)
		{
			$oldgn = $galleryname;
			$galleryname = str_replace(
				array('/','..','$','\\',' '),
				array('', '.', '', '',  '_'),$galleryname);
		}
		$thumbs .= '/'.$galleryname;
		if ($createifnew)
		{
			@mkdir($thumbs);
		}
		return $thumbs;
	}
	
	function GetThumbSizes($galleryname)
	{
		$sizes = array();
		$folder = Thumb::ThumbFolder($galleryname,false);
		//Zymurgy::Dbg($folder);
		if (file_exists($folder))
		{
			$files = glob("$folder/*thumb*.sh");
			//Zymurgy::Dbg($files);
			foreach ($files as $filename)
			{
				$matches = array();
				if (preg_match('/([\d]*)thumb([\d]*)x([\d]*)[\.]([a-z]*)/',$filename,$matches))
				{
					//Zymurgy::Dbg($matches);
					$sizes[] = $matches[2]."x".$matches[3];
				}
			}
		}
		return $sizes;
	}

	function MakeThumb($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile)
	{
		//Save parameters so we can restore the thumber for the next run.
		$fd = fopen("$destfile.sh","w");
		fwrite($fd,"MakeThumb(sx:$sx,sy:$sy,sw:$sw,sh:$sh,dw:$dw,dh:$dh,srcfile:$srcfile,destfile:$destfile)\n");
		//echo "<div>MakeThumb(sx:$sx,sy:$sy,sw:$sw,sh:$sh,dw:$dw,dh:$dh,srcfile:$srcfile,destfile:$destfile);</div>";
		fclose($fd);

		return Zymurgy::$imagehandler->ResizeWithCrop($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile);
		return Zymurgy::$imagehandler->ResizeWithCrop($swidth,$sheight,$dw,$dh,$sxs,$sys,$srcfile,$destfile);
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
		return Zymurgy::$imagehandler->Resize($w,$h,$srcfile,$destfile);
	}
	
	/**
	 * Make thumbnails from an uploaded file.  $targets is an array of csv strings, each of which contains WIDTHxHIEGHT values.
	 *
	 * @param string $datacolumn
	 * @param integer $id
	 * @param array $targets
	 * @param string $uploadpath
	 */
	static function MakeThumbs(
		$datacolumn,
		$id,
		$targets,
		$uploadpath = '',
		$ext = 'jpg')
	{
		$thumbdest = Thumb::ThumbFolder($datacolumn);
		$rawimage = "$thumbdest/{$id}raw.$ext";
		if ($uploadpath!=='')
			move_uploaded_file($uploadpath,$rawimage);

		foreach($targets as $targetsizes)
		{
			$targetsizes = explode(',',$targetsizes);

			foreach ($targetsizes as $targetsize)
			{
				$dimensions = explode('x',$targetsize);
				Thumb::MakeFixedThumb($dimensions[0],$dimensions[1],$rawimage,"$thumbdest/{$id}thumb$targetsize.$ext");
			}
		}

		Thumb::MakeQuickThumb(640,480,$rawimage,"$thumbdest/{$id}aspectcropNormal.$ext");
		Zymurgy::$imagehandler->Darken(75,"$thumbdest/{$id}aspectcropNormal.$ext","$thumbdest/{$id}aspectcropDark.$ext");
	}
}
?>