<?
/**
 * 
 * @package Zymurgy
 * @subpackage installer
 */
if (!file_exists('../config/license.txt'))
{
	@rename('../config/license.install','../config/license.txt'); //Silently fail if the demo license isn't there.  This could be the dev environment where nothing is encrypted and no license exists.  Otherwise let ionCube issue an error for the missing license file.
}
include('installcore.php');
?>
