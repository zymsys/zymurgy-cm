<?
require_once(Zymurgy::$root."/zymurgy/include/ImageHandler.php");
/**
 * $cmd = "{Zymurgy::$config['ConvertPath']}convert -resize {$swidth}x{$sheight} -crop {$dw}x{$dh}+{$sxs}+{$sys} $srcfile $destfile";
 * $cmd = "{Zymurgy::$config['ConvertPath']}convert -geometry $w x $h $srcfile $destfile";
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
		imagecopyresampled($dst,$src,0,0,$sx,$sy,$dw,$dh,$sw,$sh);
		$this->writeImage($dst,$destfile);
		imagedestroy($img);
		imagedestroy($dst);
		return;
	}
	
	function Resize($width,$height,$srcfile,$dstfile)
	{
		$src = $this->getImage($srcfile);
		$dst = $this->createBlankThumb($width,$height);
		$sw = imagesx($src);
		$sh = imagesy($src);
		imagecopyresampled($dst,$src,0,0,0,0,$width,$height,$sw,$sh);
		$this->writeImage($dst,$dstfile);
		imagedestroy($img);
		imagedestroy($dst);
		return;
	}
	
	function Darken($amount,$srcfile,$dstfile)
	{
		$src = $this->getImage($srcfile);
		$sw = imagesx($src);
		$sh = imagesy($src);
		imagealphablending($src,true);
		imagefilledrectangle($src,0,0,$sw,$sh,imagecolorallocatealpha($src,0,0,0,$amount)); //Make drk image all black, same size as source
		$this->writeImage($src,$dstfile);
		imagedestroy($img);
		return;
	}
}
?>