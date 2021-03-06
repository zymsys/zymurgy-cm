<?
/**
 * Request a file from the UserFiles folder.  Don't allow .. style attacks.  If the mime type is blank then return a 1x1 transparent gif so that
 * image references show as a blank.
 *
 * GET parameters:
 * 	mime: mime type of the requested file; this should be in the database for the attachment or image
 * 	dataset: source table (for path construction)
 * 	datacolumn: source column (for path construction)
 * 	id: row id (for file name construction)
 * 	fname: file name (for "save as" type functionality)
 * 	w: return an image and specify the required width and hight.  If w (width) is supplied then h (height) must also be supplied.
 * 	h: height of a re-sized image
 *
 * @package Zymurgy
 * @subpackage frontend
 */

if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
	$root = $_SERVER["APPL_PHYSICAL_PATH"];
else
	$root = $_SERVER['DOCUMENT_ROOT'];

require_once("$root/zymurgy/cmo.php");

/**
 * Get the Last Modified date of the specified file in standard HTTP date format.
 *
 * @param string $fname The path to the file
 * @return string
 */
function get_http_mdate($fname)
{
   return gmdate("D, d M Y H:i:s",filemtime($fname))." GMT";
}

/**
 * Check the Apache Request Headers for the "If-Modified-Since" caching
 * header. If it exists, check to see if the file has ben modified after the
 * date in the header. If it has not, send a 304 response instead of the actual
 * file.
 *
 * @param unknown_type $gmtime
 */
function check_modified_header($gmtime)
{
   if (!function_exists('apache_request_headers'))
   {
		return;
   }
   $headers=apache_request_headers();
   if (array_key_exists('If-Modified-Since',$headers))
   {
	   $if_modified_since=preg_replace('/;.*$/', '', $headers['If-Modified-Since']);
	   if(!$if_modified_since)
	       return;

	   if ($if_modified_since == $gmtime) {
	       header("HTTP/1.1 304 Not Modified");
	       exit;
	   }
   }
}

/**
 * Convert the specified parameter into a "safe" parameter by removing
 * file control characters
 *
 * @param string $param The unsafe parameter
 * @return string The safe parameter
 */
function safeparam($param)
{
	return str_replace(array('/','.'),'',$param);
}

/**
 * Return a 1x1 empty GIF file. Typically used if the requested file cannot be found.
 *
 */
function returnblankgif()
{
//	die(print_r(debug_backtrace(), true));

    header("Content-type: image/gif");
    echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x40\x02\x01\x44\x00\x3b";
    exit;
}

$mime = $_GET['mime'];
$dataset = safeparam($_GET['dataset']);
$datacolumn = safeparam($_GET['datacolumn']);
$id = 0 + $_GET['id'];
if ($mime == 'auto')
{
	$mime = getmime("$root/UserFiles/DataGrid/$dataset.$datacolumn/{$id}raw.*");
}
$thumbName = "";

function getmime($fn)
{
	$info = `file -i $fn`;
	$ip = explode(': ',$info);
	return trim(array_pop($ip));
}

if ($mime == '')
{
	returnblankgif();
}
else
{
	if (array_key_exists('w',$_GET))
	{
		$w = 0 + $_GET['w'];
		$h = 0 + $_GET['h'];
		$requestedSize = "{$w}x$h";
		require_once(Zymurgy::$root.'/zymurgy/include/Thumb.php');
		$ext = Thumb::mime2ext($mime);
		$rawimage = "$root/UserFiles/DataGrid/$dataset.$datacolumn/{$id}raw.$ext";
		$thumbName = "$root/UserFiles/DataGrid/$dataset.$datacolumn/{$id}thumb$requestedSize.$ext";

		$makethumb = false;
		if (file_exists($thumbName))
		{
			//Make sure the raw file isn't newer
			$thumbinfo = stat($thumbName);
			$rawinfo = stat($rawimage);
			if ($rawinfo['mtime'] > $thumbinfo['mtime'])
			{
				$makethumb = true;
			}
		}
		else
		{
			//Thumb doesn't exist...  Try to build it.
			$makethumb = true;
		}

		if(!file_exists($rawimage))
		{
			$thumbName = "$root/zymurgy/uploads/$dataset.$datacolumn.{$id}";
			$rawimage = "$root/zymurgy/uploads/$dataset.$datacolumn.{$id}";
			$makethumb = false;
		}

		if ($makethumb)
		{
			if (!file_exists($rawimage))
			{
				returnblankgif();
			}
			$ontheflyok = false; //Default to no on the fly image resizing to avoid expensive DoS attacks
			if (Zymurgy::memberzcmauth(1))
			{
				$ontheflyok = true;
			}
			else
			{
				//We're not authed, but if any other image has been resized to this size before, this image probably needs resizing too - lets do it.
				$sizes = Thumb::GetThumbSizes($dataset.'.'.$datacolumn);
				if (array_search("{$w}x{$h}",$sizes) !== false)
				{
					$ontheflyok = true;
				}
				else 
				{
					echo "noauth";
					exit;
				}
			}
			if ($ontheflyok)
			{
				Thumb::MakeFixedThumb($w,$h,$rawimage,$thumbName);
				$mime = "image/jpeg"; //Resized images are all image/jpeg
			}
		}
	}
    $fn = '';
    $safefn = "{$_GET['dataset']}.{$_GET['datacolumn']}.{$_GET['id']}";
    while ($fn != $safefn)
    {
            $fn = $safefn;
            $safefn = str_replace('..','.',$fn);
    }
    if (array_key_exists('w',$_GET))
    	$safefn = $thumbName;
    else
    	$safefn = "$root/zymurgy/uploads/$safefn";
    if (!file_exists($safefn))
    {
//    	die($safefn);

    	//Maybe this is a thumb...
    	if (file_exists($thumbName))
    		$safefn = $thumbName;
    	else
    	{
    		//Last ditch effort to supply a valid file...  See if there's a raw thumb.
    		$ext = Thumb::mime2ext($mime);
			$thumbName = "$root/UserFiles/DataGrid/$dataset.$datacolumn/{$id}raw.$ext";
	    	if (file_exists($thumbName))
	    		$safefn = $thumbName;
	    	else
    			returnblankgif();
    	}
    }
    $modifydate = get_http_mdate($safefn);
    check_modified_header($modifydate);
    header("Last-Modified: ".$modifydate);
    header("Content-type: {$_GET['mime']}");
    if (array_key_exists('fname',$_GET))
    {
    	if ($_GET['fname']!='') header('Content-Disposition: inline; filename="'.$_GET['fname'].'"');
    }
	readfile($safefn);
}
?>
