<?php 
/*
 * Used by Zymurgy::RequireOnce to load .less files into CSS dynamically.
 * Probably *not* a good idea for high traffic sites, but convenient for dev and low traffic.
 * Caches output in /UserFiles/css/ for better performance
 */

//Load the lessc class:
require_once 'include/lessc.inc.php';

//Get our application root:
if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
	$root = $_SERVER["APPL_PHYSICAL_PATH"];
else
	$root = $_SERVER['DOCUMENT_ROOT'];

//Build input/output file names:
$src = $_GET['src'];
$out = $root."/UserFiles/css$src";

//Change extension of output file to .css if it was .less so we can directly include it in production
if (substr($out,-5)=='.less')
{
	$out = substr($out,0,-5).'.css';
}

//Ensure the output path exists:
@mkdir(dirname($out),0777,true);

//Build CSS if needed:
try {
    lessc::ccompile($root.$src, $out);
} catch (exception $ex) {
    exit('lessc fatal error:<br />'.$ex->getMessage());
}

//Spit out the built CSS:
header('Content-type: text/css');
echo file_get_contents($out);
?>