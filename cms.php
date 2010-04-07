<?
/**
 * Old globals, use cmo.php instead:
 *
 * $ZymurgyRoot: Physical path to the site's root directory on the server
 * $ZymurgyDB: MySQL link identifier to the Zymurgy:CM database
 * $ZymurgyConfig: Config values from the config/config.php file
 * $CONFIG: User supplied site config values from within the Zymurgy:CM control panel front-end
 *
 * @package Zymurgy
 * @subpackage base
 * @deprecated
 */

if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
	$ZymurgyRoot = $_SERVER["APPL_PHYSICAL_PATH"];
else
	$ZymurgyRoot = $_SERVER['DOCUMENT_ROOT'];

include("{$ZymurgyRoot}/zymurgy/cmo.php");
$build = Zymurgy::$build;
$ZymurgyDB = Zymurgy::$db->link;
$CONFIG = Zymurgy::$userconfig;
$ZymurgyConfig = Zymurgy::$config;

function sitetext($tag,$type='html.600.400')
{
	return Zymurgy::sitetext($tag,$type);
}

function siteimage($tag,$width,$height,$alt='')
{
	return Zymurgy::siteimage($tag,$width,$height,$alt);
}

function sitemap()
{
	Zymurgy::sitemap();
}

/**
 * Create a plugin object for the named plugin (same as the file name without the extension) and
 * instance name.
 *
 * Extra is used to pass extra plugin-specific stuff to a plugin, and private
 * is used to flag an instance that shouldn't be listed with regular instances because it is
 * created and maintained by something else (for example a collection of image galleries).
 *
 * @param string $plugin
 * @param string $instance
 * @param mixed $extra
 * @param boolean $private
 * @return Plugin
 */
function mkplugin($plugin,$instance,$extra='',$private=0)
{
	return Zymurgy::mkplugin($plugin,$instance,$extra,$private);
}

function plugin($plugin,$instance,$extra='')
{
	return Zymurgy::plugin($plugin,$instance,$extra);
}

function adminhead()
{
	return Zymurgy::adminhead();
}

function metatags()
{
	return Zymurgy::headtags();
}

function headtags()
{
	return Zymurgy::headtags();
}

/**
 * Is member authenticated?  If yes then loads auth info into global $member array.
 *
 * @return boolean
 */
function memberauthenticate()
{
	global $member;
	$r = Zymurgy::memberauthenticate();
	$member = Zymurgy::$member;
	return $r;
}

/**
 * Is member authorized (by group name) to view this page?
 *
 * @param unknown_type $groupname
 * @return boolean
 */
function memberauthorize($groupname)
{
	return Zymurgy::memberauthorize($groupname);
}

/**
 * Log member activity
 *
 * @param unknown_type $activity
 */
function memberaudit($activity)
{
	Zymurgy::memberaudit($activity);
}

function JSRedirect($url)
{
	Zymurgy::JSRedirect($url);
}

function JSInnerHtml($id,$html)
{
	Zymurgy::JSInnerHtml($id,$html);
}

/**
 * Use in the header with the include and metatags().
 * Verify that the user is a member of the required group to view this page.
 * If not, redirect to the login page.
 *
 * @param string $groupname
 */
function memberpage($groupname='Registered User')
{
	global $member;
	Zymurgy::memberpage($groupname);
	$member = Zymurgy::$member;
}

function memberdologin($userid, $password)
{
	return Zymurgy::memberdologin($userid,$password);
}

function memberlogout($logoutpage)
{
	Zymurgy::memberlogout($logoutpage);
}

function membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect)
{
	return Zymurgy::membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect);
}

/**
 * Render login interface.  Uses reg GET variable, which can be:
 * 	- username: create a new username/account
 * 	- extra: get extra info from the user using a client defined form
 * If the reg GET variable isn't supplied it just tries to log the user in.
 *
 * @return string HTML for login process
 */
function memberlogin()
{
	return Zymurgy::memberlogin();
}

/**
 * Get PHPMailer object pre-configured with settings from the Zymurgy:CM config file.
 *
 * @return PHPMailer
 */
function GetPHPMailer()
{
	return Zymurgy::GetPHPMailer();
}
?>
