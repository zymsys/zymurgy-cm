<?
function get_http_mdate($fname)
{
   return gmdate("D, d M Y H:i:s",filemtime($fname))." GMT";
}

function check_modified_header($gmtime)
{
   // This function is based on code from http://ontosys.com/php/cache.html

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
if ($_GET['mime'] == '')
{
        header("Content-type: image/gif");
        echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x
00\x00\x00\x01\x00\x01\x00\x40\x02\x01\x44\x00\x3b";
        exit;
}
else
{
        if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
                $ZymurgyRoot = $_SERVER["APPL_PHYSICAL_PATH"];
        else
                $ZymurgyRoot = $_SERVER['DOCUMENT_ROOT'];
    $fn = '';
    $safefn = "{$_GET['dataset']}.{$_GET['datacolumn']}.{$_GET['id']}";
    while ($fn != $safefn)
    {
            $fn = $safefn;
            $safefn = str_replace('..','.',$fn);
    }
    $safefn = "$ZymurgyRoot/zymurgy/uploads/$safefn";
    $modifydate = get_http_mdate($safefn);
    check_modified_header($modifydate);
    header("Last-Modified: ".$safefn);
    header("Content-type: {$_GET['mime']}");
    if ((array_key_exists('fname',$_GET)) && ($_GET['fname']!=''))
    {
    	header('Content-Disposition: inline; filename="'.$_GET['fname'].'"');
    }
    if ((array_key_exists('thumb',$_GET)) && ($_GET['thumb']=='yes'))
    {
    	$safefn = "$safefn.thumb";
    }
    header("Content-Length: ".filesize($safefn));
    //echo "[$safefn]";
    echo file_get_contents($safefn);
    exit;
}
?>