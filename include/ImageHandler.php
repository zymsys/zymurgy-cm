<?
abstract class ZymurgyImageHandler
{
	/**
	 * Return the ImageMagick command line for creating this image.  Used even with other image handlers, since this is how resize/crop data is
	 * restored to the front-end when adjustments are made.
	 */
	function ResizeWithCropCmd($swidth,$sheight,$dwidth,$dheight,$dx,$dy,$srcfile,$dstfile)
	{
		return Zymurgy::$config['ConvertPath']."convert -resize {$swidth}x{$sheight} -crop {$dwidth}x{$dheight}+{$dx}+{$dy} $srcfile $dstfile";
	}
	
	//Abstract functions:
	abstract function ResizeWithCrop($sx,$sy,$sw,$sh,$dw,$dh,$srcfile,$destfile);
	abstract function Resize($width,$height,$srcfile,$dstfile);
	abstract function Darken($amount,$srcfile,$dstfile);
}
?>