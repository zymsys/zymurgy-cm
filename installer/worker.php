<?
include('tables.php');

function CreateSQL($webmasterLogin,$webmasterPassword,$webmasterName,$webmasterEmail)
{
	global $tables;
	
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

//Membership
\$ZymurgyConfig['MemberLoginPage'] = '/login.php';
\$ZymurgyConfig['MemberDefaultPage'] = '/members/';
\$ZymurgyConfig['MembershipUsernameForm'] = '<form class=\"MemberForm\" method=\"post\"><table>
        <tr><td align=\"right\">Email Address:</td><td><input type=\"text\" name=\"email\" id=\"email\"></td></tr>
        <tr><td align=\"right\">Password:</td><td><input type=\"password\" name=\"pass\" id=\"pass\"></td></tr>
        <tr><td align=\"right\">Confirm Password:</td><td><input type=\"password\" name=\"pass2\" id=\"pass2\"></td></tr>
        <tr><td align=\"center\" colspan=\"2\"><input type=\"Submit\" value=\"Signup\"></td></tr>
        </table></form>';
\$ZymurgyConfig['MembershipLoginForm'] = '<form class=\"MemberLogin\" method=\"post\"><table>
        <tr><td align=\"right\">Email Address:</td><td><input type=\"text\" name=\"email\" id=\"email\"></td></tr>
        <tr><td align=\"right\">Password:</td><td><input type=\"password\" name=\"pass\" id=\"pass\"></td></tr>
        <tr><td align=\"center\" colspan=\"2\"><input type=\"Submit\" value=\"Login\"></td></tr>
        </table></form>';

//Branding Information
\$ZymurgyConfig['headerbackground'] = '#666698';
\$ZymurgyConfig['headercolor'] = '#FFFFFF';
\$ZymurgyConfig['gridcss'] = \"table.DataGrid {
	background-color:White;
	border-color:#999999;
	border-width:1px;
	border-style:None;
	border-collapse:collapse;
}
tr.DataGridHeader {
	color:#000000;
	background-color:#999999;
	font-weight:bold;
}
tr.DataGridRow {
	color:Black;
	background-color:#FFFFFF;
}
tr.DataGridRowAlternate {
	color:Black;
	background-color:#cccccc;
}
tr.DataGridRow a, tr.DataGridRowAlternate a {
	text-decoration:none;
}
a.DataGrid {
	color:#000000;
	text-decoration:none;
}
\";

//Image handling information
\$ZymurgyConfig['ConvertPath'] = '$imageMagikPath'; //Path to ImageMagick's convert binary NOT including the trailing slash
?>";
	$fd = fopen('../config/config.php','w');
	fwrite($fd,$config);
	fclose($fd);
	chmod('../config/config.php',0777);
}
?>
