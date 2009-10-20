<?
/**
 * Gradient png image for {@link simple.php}
 * 
 * @package Zymurgy
 * @subpackage defaulttemplates
 */

require_once('../cmo.php');
Zymurgy::Config('Color Theme','#275176,#275176,#4085C2,#336B9C,#757575,#FFFFFF,#C29240','theme');
Zymurgy::headtags();

$height = 24;

function color_hex2dec ($color) {
	return array (hexdec (substr ($color, 0, 2)), hexdec (substr ($color, 2, 2)), hexdec (substr ($color, 4, 2)));
}

$bg = imagecreatetruecolor(1,$height);

$top = color_hex2dec(Zymurgy::theme('Menu Background','Color Theme'));
$bottom = $top;
foreach ($bottom as $bk=>$bv)
{
	if (array_key_exists('s',$_GET))
	{
		$top[$bk] = $bv * 1.1;
	}
	else 
	{
		$bottom[$bk] = $bv * 0.6;
	}
}
for ($y = 0; $y < $height; $y++)
{
	$pcent = $y/$height;
	$r = round($top[0] + (($bottom[0] - $top[0]) * $pcent));
	$g = round($top[1] + (($bottom[1] - $top[1]) * $pcent));
	$b = round($top[2] + (($bottom[2] - $top[2]) * $pcent));
	$c = imagecolorallocate($bg,$r,$g,$b);
	imagesetpixel($bg,0,$y,$c);
}
header('Content-type: image/png');
imagepng($bg);
imagedestroy($bg);
?>