<?
$ZymurgyConfig = array();

//Select database server type.
$ZymurgyConfig['database'] = 'mysql';

//MySQL Server login info:
$ZymurgyConfig['mysqlhost'] = 'localhost';
$ZymurgyConfig['mysqluser'] = 'caseocms';
$ZymurgyConfig['mysqlpass'] = 'xyzzy';
$ZymurgyConfig['mysqldb'] = 'caseocms';

//Mailer info
$ZymurgyConfig['Mailer Type'] = 'mail'; // Can be mail, sendmail or smtp
$ZymurgyConfig['Mailer SMTP Hosts'] = 'localhost';

//Membership
$ZymurgyConfig['MemberLoginPage'] = '/members/login.php';
$ZymurgyConfig['MemberDefaultPage'] = '/members/';
$ZymurgyConfig['MembershipUsernameForm'] = "<form class=\"MemberForm\" method=\"post\"><table>
	<tr><td align=\"right\">Email Address:</td><td><input type=\"text\" name=\"email\"></td></tr>
	<tr><td align=\"right\">Password:</td><td><input type=\"password\" name=\"pass\"></td></tr>
	<tr><td align=\"right\">Confirm Password:</td><td><input type=\"password\" name=\"pass2\"></td></tr>
	<tr><td align=\"center\" colspan=\"2\"><input type=\"Submit\" value=\"Signup\"></td></tr>
	</table></form>";
$ZymurgyConfig['MembershipLoginForm'] = "<form class=\"MemberLogin\" method=\"post\"><table>
	<tr><td align=\"right\">Email Address:</td><td><input type=\"text\" name=\"email\"></td></tr>
	<tr><td align=\"right\">Password:</td><td><input type=\"password\" name=\"pass\"></td></tr>
	<tr><td align=\"center\" colspan=\"2\"><input type=\"Submit\" value=\"Login\"></td></tr>
	</table></form>";

//Site information
$ZymurgyConfig['sitehome'] = 'www.zymurgy.ca';
$ZymurgyConfig['defaulttitle'] = 'Zymurgy:CM Content Management';
$ZymurgyConfig['defaultdescription'] = 'Content Authoring & Search Engine Optimization tool for web designers to create content management systems for their clients.';
$ZymurgyConfig['defaultkeywords'] = 'content management, search engine optimization, seo, cms, content management system';
$ZymurgyConfig['FixSlashes'] = true;

//Branding Information
$ZymurgyConfig['headerbackground'] = '#666698';
$ZymurgyConfig['headercolor'] = '#FFFFFF';
$ZymurgyConfig['gridcss'] = "table.DataGrid {
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
";

//Image handling information
$ZymurgyConfig['ConvertPath'] = ''; //Path to ImageMagick's convert binary NOT including the trailing slash
?>