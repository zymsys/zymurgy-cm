<?
/**
 * 
 * @package Zymurgy
 * @subpackage installer
 */
include("upgradelib.php");
include('tables.php');

function CreateSQL($webmasterLogin,$webmasterPassword,$webmasterName,$webmasterEmail)
{
	global $baseTableDefinitions;
	ProcessTableDefinitions($baseTableDefinitions);

	$tables = array();
	$tables['ipasswd'] = "insert into zcm_passwd (username,password,fullname,email,admin) values ('".
		mysql_escape_string($webmasterLogin)."','".
		mysql_escape_string($webmasterPassword)."','".
		mysql_escape_string($webmasterName)."','".
		mysql_escape_string($webmasterEmail)."'".
		",2)";
	$tables['istcategory'] = "insert into zcm_stcategory (id,name) values (0,'Uncategorized Content')";

	foreach($tables as $sql)
	{
		$ri = mysql_query($sql);
		if (!$ri)
		{
			global $errors;
			$errors[] = "An unexpected error (".mysql_errno().' '.mysql_error().": $sql) occured while configuring your site.  Please contact support.";
		}
	}
}


function CreateConfigFile($mysqlServer,$mysqlUser,$mysqlPassword,$mysqlDatabase,$siteURL,$siteTitle,
	$siteDescription,$siteKeywords,$imageMagikPath='')
{
	//Fix slashes
	$siteTitle = str_replace("'","\\'",$siteTitle);
	$siteKeywords = str_replace("'","\\'",$siteKeywords);
	$siteDescription = str_replace("'","\\'",$siteDescription);

	//Remove trailing slash if it is included.
	if (substr($imageMagikPath,-1)=='/')
	{
		$imageMagikPath = substr($imageMagikPath,0,-1);
	}
	$config = "<?
\$ZymurgyConfig = array();

//MySQL Server login info:
\$ZymurgyConfig['mysqlhost'] = '$mysqlServer';
\$ZymurgyConfig['mysqluser'] = '$mysqlUser';
\$ZymurgyConfig['mysqlpass'] = '$mysqlPassword';
\$ZymurgyConfig['mysqldb'] = '$mysqlDatabase';

//Mailer info
\$ZymurgyConfig['Mailer Type'] = 'mail'; // Can be mail, sendmail or smtp
\$ZymurgyConfig['Mailer SMTP Hosts'] = 'localhost';

//Site information
\$ZymurgyConfig['sitehome'] = '$siteURL';
\$ZymurgyConfig['defaulttitle'] = '$siteTitle';
\$ZymurgyConfig['defaultdescription'] = '$siteDescription';
\$ZymurgyConfig['defaultkeywords'] = '$siteKeywords';

//Tracking and privacy
//Check your local privacy laws before turning on this feature.
\$ZymurgyConfig['tracking'] = FALSE;

//Membership
\$ZymurgyConfig['MemberLoginPage'] = '/login.php';
\$ZymurgyConfig['MemberDefaultPage'] = '/members/';

//Branding Information
\$ZymurgyConfig['headerbackground'] = '#666698';
\$ZymurgyConfig['headercolor'] = '#FFFFFF';
\$ZymurgyConfig['gridcss'] = '/zymurgy/include/datagrid.css';

//Image handling information
\$ZymurgyConfig['ConvertPath'] = '$imageMagikPath'; //Path to ImageMagick's convert binary NOT including the trailing slash
?>";
	$fd = fopen('../config/config.php','w');
	fwrite($fd,$config);
	fclose($fd);
	chmod('../config/config.php',0777);
}
?>
