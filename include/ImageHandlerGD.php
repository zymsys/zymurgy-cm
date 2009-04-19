<?
require_once(Zymurgy::$root."/zymurgy/include/ImageHandler.php");
/**
 * $cmd = "{$ZymurgyConfig['ConvertPath']}convert -resize {$swidth}x{$sheight} -crop {$dw}x{$dh}+{$sxs}+{$sys} $srcfile $destfile";
 * $cmd = "{$ZymurgyConfig['ConvertPath']}convert -geometry $w x $h $srcfile $destfile";
 * system(Zymurgy::$config['ConvertPath']."convert -modulate 75 $thumbdest/{$id}aspectcropNormal.$ext $thumbdest/{$id}aspectcropDark.$ext");
 *
 */
class ZymurgyImageHandlerGD extends ZymurgyImageHandler 
{
	private $ext;
	
	private function getImage($src)
	{
		$this->ext = strtolower(array_pop(explode('.',$src)));
		switch ($this->ext)
		{
			case 'gif':
				$im = imagecreatefromgif($src);
				break;
			case 'png':
				$im = imagecreatefrompng($src);
				break;
			default:
				$im = imagecreatefromjpeg($src);
				break;
		}
		return $im;
	}
	
	private function writeImage($img,$fname)
	{
		/*echo "<div>Writing: $fname</div>";
		if (file_exists($fname))
			unlink($fname);*/
		switch ($this->ext)
		{
			case 'gif':
				imagegif($img,$fname);
				break;
			case 'png':
				imagepng($img,$fname);
				break;
			default:
				imagejpeg($img,$fname);
				break;
		}
	}
	
	private function createBlankThumb($w,$h)
	{
		return imagecreatetruecolor($w,$h);
	}
	
	function ResizeWithCrop($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile)
	{
		$src = $this->getImage($srcfile);
		$dst = $this->createBlankThumb($dw,$dh);
		//echo "<div>imagecopyresampled(dst_im,src_im,0,0,src_x:$sx,src_y:$sy,dst_w:$dw,dst_h:$dh,src_w:$sw,src_h:$sh);</div>";
		imagecopyresampled($dst,$src,0,0,$sx,$sy,$dw,$dh,$sw,$sh);
		$this->writeImage($dst,$destfile);
		imagedestroy($img);
		imagedestroy($dst);
		return;
	}
	
	function Resize($width,$height,$srcfile,$dstfile)
	{
		$cmd = Zymurgy::$config['ConvertPath']."convert -geometry $width x $height $srcfile $dstfile";
		$out = system($cmd,$r);
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