<?
//If the config file already exists then exit
if (file_exists('../config/config.php')) header('Location: upgrade.php');

//Issue warnings for config folder issues
$errors = array();
if (!is_dir('../config'))
	$errors[] = 'The zymurgy/config folder does not exist.  Please create it and set the permissions to read/write/execute for everyone (0777).';
else 
{
	if (!is_writable('../config/'))
	{
		$errors[] = 'The zymurgy/config folder is not writable.  Please set the permissions to read/write/execute for everyone (0777).';
	}
}

//Initialize default values
function InitializeVariable($key,$default='')
{
	if (!key_exists($key,$_POST)) $_POST[$key]=$default;
}
InitializeVariable('mysqlServer','localhost');
InitializeVariable('mysqlUser');
InitializeVariable('mysqlPassword');
InitializeVariable('mysqlDatabase');
InitializeVariable('siteURL',$_SERVER['HTTP_HOST']);
InitializeVariable('siteTitle');
InitializeVariable('siteDesc');
InitializeVariable('siteKeywords');
InitializeVariable('userID');
InitializeVariable('userPassword');
InitializeVariable('userName');
InitializeVariable('userAddress');

//If this is a postback, check that everything is ok.
function CheckRequired($field,$name)
{
	global $errors;
	
	if ($_POST[$field]=='')
		$errors[] = "The field '$name' is required.";
}

if ($_SERVER['REQUEST_METHOD']=='POST')
{
	//Test mysql login
	CheckRequired('mysqlServer','MySQL Server');
	CheckRequired('mysqlUser','MySQL User');
	CheckRequired('mysqlPassword','MySQL Password');
	CheckRequired('mysqlDatabase','MySQL Database');
	if (count($errors)==0)
	{
		$cn = @mysql_connect($_POST['mysqlServer'],$_POST['mysqlUser'],$_POST['mysqlPassword']);
		if (!$cn)
		{
			switch (mysql_errno())
			{
				case 1045: $errors[] = "MySQL connected but the User ID or Password was rejected."; break;
				case 2005: $errors[] = "A MySQL connection to ".htmlentities($_POST['mysqlServer'])." could not be established."; break;
				default: $errors[] = "An error (".mysql_errno().": ".mysql_error().") occured when trying to connect to the MySQL server.";
			}
		}
		else 
		{
			$dbselected = @mysql_select_db($_POST['mysqlDatabase']);
			if (!$dbselected)
				$errors[] = "The MySQL login was successful, but the database ".htmlentities($_POST['mysqlDatabase']).
					" (if it exists) is not available to the user ".htmlentities($_POST['mysqlUser']).".";
			else 
			{
				$ri = mysql_query("show tables");
				if (mysql_num_rows($ri)>0)
					$errors[] = "This MySQL database is not empty.  Please use an empty database, or create a new database.";
			}
		}
	}
	//Check for other manditory fields
	CheckRequired('siteURL','Site URL');
	CheckRequired('siteTitle','Site Title');
	CheckRequired('userID','User ID');
	CheckRequired('userPassword','Password');

	//If there are no errors then go ahead and configure the site.
	if (count($errors)==0)
	{
		include('worker.php');
		CreateSQL($_POST['userID'],$_POST['userPassword'],$_POST['userName'],$_POST['userAddress']);
		CreateConfigFile($_POST['mysqlServer'],$_POST['mysqlUser'],$_POST['mysqlPassword'],
			$_POST['mysqlDatabase'],$_POST['siteURL'],$_POST['siteTitle'],$_POST['siteDesc'],
			$_POST['siteKeywords']);
		if (count($errors)==0)
		{ //Still no trouble?
			require_once('../ZymurgyAuth.php');
			$zauth = new ZymurgyAuth();
			$zauth->SetAuth(0,$_POST['userID'],$_POST['userPassword'],"{$_POST['userID']},{$_POST['userAddress']},{$_POST['userName']},2,1,0","../index.php");
			exit;
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css">
<!--
body {
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size:small;
}
.ZymurgyHeader {
	background-color: #666698;
	color: #FFFFFF;
	height:85px;
	position: relative;
	width: 100%;
}
.ZymurgyHeader .ZymurgyLogo {
	float: left;
	font-size: 40px;
	margin-top:35px;
	margin-left:10px;
	padding: 0px;
}
th {
	font-size: x-small;
	font-weight: normal;
	text-align: right;
}
.errormsg {
	color: #FF0000;
	font-size: x-small;
}
ul {
	font-size: x-small;
}
-->
</style>
<title>Zymurgy:CM Configuration</title>
</head>

<body onload="document.getElementById('mysqlUser').focus()">
<div class="ZymurgyHeader">
	<div class="ZymurgyLogo" title="Content Authoring and Search Engine Optimization">
		Zymurgy:CM
	</div>
</div>
<form method="post">
<div align="center">
  <table border="0">
<?
if (count($errors)>0)
{
	?>
  <tr>
    <td colspan="2"><p class="errormsg">The following problems were fcund when trying to configure your site:</p>
<?
	foreach($errors as $error)
	{
		echo "<ul><li>$error</ul>\r\n";
	}
?>
  </td></tr>
<?
}
?>
  <tr>
    <td colspan="2"><b>MySQL Setup</b></td>    </tr>
  <tr>
    <th scope="row"><label for="mysqlServer">MySQL Server:</label></th>
    <td><input type="text" name="mysqlServer" id="mysqlServer" value="<?=$_POST['mysqlServer']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="mysqlUser">MySQL User:</label></th>
    <td><input type="text" name="mysqlUser" id="mysqlUser" value="<?=$_POST['mysqlUser']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="mysqlPassword">MySQL Password:</label></th>
    <td><input type="password" name="mysqlPassword" id="mysqlPassword" value="<?=$_POST['mysqlPassword']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="mysqlDatabase">MySQL Database:</label></th>
    <td><input type="text" name="mysqlDatabase" id="mysqlDatabase" value="<?=$_POST['mysqlDatabase']?>" /></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;<br /><b>Default Site Info:</b></td>
    </tr>
  <tr>
    <th scope="row"><label for="siteURL">Home Page URL:</label></th>
    <td>http://<input type="text" name="siteURL" id="siteURL" size="30" value="<?=$_POST['siteURL']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="siteTitle">Default Page Title:</label></th>
    <td><input name="siteTitle" type="text" id="siteTitle" size="40" value="<?=$_POST['siteTitle']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="siteDesc">Default Description:</label></th>
    <td><input name="siteDesc" type="text" id="siteDesc" size="50" value="<?=$_POST['siteDesc']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="siteKeywords">Default Keywords:</label></th>
    <td><input name="siteKeywords" type="text" id="siteKeywords" size="50" value="<?=$_POST['siteKeywords']?>" /></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;<br /><b>First User Info:</b></td>
    </tr>
  <tr>
    <th scope="row"><label for="userID">User ID:</label></th>
    <td><input type="text" name="userID" id="userID" value="<?=$_POST['userID']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="userPassword">Password:</label></th>
    <td><input type="password" name="userPassword" id="userPassword" value="<?=$_POST['userPassword']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="userName">Full Name:</label></th>
    <td><input name="userName" type="text" id="userName" size="30" value="<?=$_POST['userName']?>" /></td>
  </tr>
  <tr>
    <th scope="row"><label for="userAddress">Email Address:</label></th>
    <td><input name="userAddress" type="text" id="userAddress" size="30" value="<?=$_POST['userAddress']?>" /></td>
  </tr>
  <tr>
    <th colspan="2" scope="row">&nbsp;<br />
        <input type="submit" name="Submit" value="Save Site Configuration &amp; Log In" id="Submit" />      </th>
    </tr>
</table>
</div>
</form>
</body>
</html>
