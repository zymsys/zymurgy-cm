<?
class CASEO
{
	/**
	 * Physical path to the site's root directory on the server
	 *
	 * @var string
	 */
	static $root;
	
	/**
	 * A CASEO_DB instance for database access
	 *
	 * @var CASEO_DB
	 */
	static $db; 
	
	/**
	 * Config values from the config/config.php file
	 *
	 * @var array
	 */
	static $config; 
	
	/**
	 * User supplied site config values from within the CASEO control panel front-end
	 *
	 * @var array
	 */
	static $userconfig;
	
	/**
	 * CASEO release number
	 *
	 * @var int
	 */
	static $build = 1987;
	
	/**
	 * Member info available if the user is logged in.
	 *
	 * @var array
	 */
	static $member;
	
	/**
	 * Array of loaded YUI javascript files
	 *
	 * @var array
	 */
	private static $yuiloaded = array();
	
	private static $pageid;
	private static $title;
		
	/**
	 * Return javascript or CSS tags to load the supplied YUI source file if it has not already been loaded by this method.
	 * Adds "http://yui.yahooapis.com/2.4.1/build/" to the start of src to keep the YUI version consistant.  The version
	 * number loaded by YUI will be updated in future releases.
	 *
	 * @param string $src
	 * @return string
	 */
	static function YUI($src)
	{
		if (array_key_exists($src,CASEO::$yuiloaded))
			return ''; //Already loaded
		CASEO::$yuiloaded[$src]='';
		$sp = explode('.',$src);
		$ext = strtolower(array_pop($sp));
		switch($ext)
		{
			case 'js':
				return "<script src=\"http://yui.yahooapis.com/2.4.1/build/$src\"></script>\r\n";
			case 'css':
				return "<link rel=\"stylesheet\" type=\"text/css\" href=\"http://yui.yahooapis.com/2.4.1/build/$src\" />";
		}
	}
	
	/**
	 * Given a table name from Custom Tables, output XML for the table's contents and all detail tables.
	 *
	 * @param string $rootName Name of root XML node
	 * @param string $tableName Name of the custom table to render to XML
	 */
	static function easyXML($rootName,$tableName)
	{
		header('Content-type: text/xml');
		echo "<$rootName>\r\n";
		CASEO::buildEasyXML($tableName);
		echo "</$rootName>\r\n";
	}
	
	static private function XMLvalue($value)
	{
		$sc = htmlentities($value);
		if ($sc == $value)
			return $value;
		else
			return "<![CDATA[$value]]>"; 
	}
	
	static private function buildEasyXML($tableName,$detailTable='',$parentID=0,$level = 1)
	{
		//Get meta data about this table, especially detail tables.
		$sql = "select id from customtable where tname='".
			CASEO::$db->escape_string($tableName)."'";
		$ri = CASEO::$db->query($sql) or die("Can't get table info ($sql): ".CASEO::$db->error());
		$row = CASEO::$db->fetch_array($ri,MYSQL_ASSOC) or die("Table $tableName doesn't exist.");
		$tid = $row['id'];
		CASEO::$db->free_result($ri);
		$sql = "select * from customtable where detailfor=$tid";
		$ri = CASEO::$db->query($sql) or die("Can't get table detail info ($sql): ".CASEO::$db->error());
		$details = array();
		while(($row = CASEO::$db->fetch_array($ri,MYSQL_ASSOC))!==false)
		{
			$details[$row['id']] = $row['tname'];
		}
		CASEO::$db->free_result($ri);
		//Get our rows...
		$myrows = array();
		$sql = "select * from $tableName";
		if (!empty($detailTable))
		{
			$sql .= " where $detailTable=$parentID";
		}
		$ri = CASEO::$db->query($sql) or die("Can't get XML data ($sql): ".CASEO::$db->error());
		while(($row = CASEO::$db->fetch_array($ri,MYSQL_ASSOC))!==false)
		{
			$myrows[] = $row;
		}
		CASEO::$db->free_result($ri);
		//Spit 'em out...
		$mytabs = str_repeat("\t",$level);
		foreach ($myrows as $row)
		{
			echo "{$mytabs}<$tableName id=\"{$row['id']}\">\r\n";
			foreach ($row as $key=>$value)
			{
				if (($key == 'id') || ($key == $detailTable)) continue;
				$value = CASEO::XMLvalue($value);
				echo "{$mytabs}\t<$key>$value</$key>\r\n";
			}
			//Now spit out details for this row
			foreach($details as $id=>$detailName)
			{
				CASEO::buildEasyXML($detailName,$tableName,$row['id'],$level+1);
			}
			echo "{$mytabs}</$tableName>\r\n";
		}
	}
	
	static function stripslashes_deep($value)
	{
		$value = is_array($value) ?
			array_map('CASEO::stripslashes_deep', $value) :
			stripslashes($value);
		
		return $value;
	}
	
	/**
	 * Get general site content.  Create new tag if this one doesn't exist.
	 *
	 * @param string $tag
	 * @param string $type
	 * @param boolean $adminui
	 * @return string
	 */
	static function sitetext($tag,$type='html.600.400',$adminui = true)
	{
		if (strlen($tag)>35)
		{
			die("Unable to create new site text.  Tag names must be 35 characters or less.");
		}
		$sql = "select body,id,inputspec from sitetext where tag='".CASEO::$db->escape_string($tag)."'";
		$ri = CASEO::$db->query($sql);
		if (!$ri)
			die("Unable to read metaid. ($sql): ".CASEO::$db->error());
		if (CASEO::$db->num_rows($ri)==0)
		{
			//Create new sitetext entry
			$body = 'Please edit the general content called <b>"'.$tag.'"</b> in CASEO.';
			CASEO::$db->query("insert into sitetext (tag,inputspec,body) values ('".CASEO::$db->escape_string($tag)."','".
				CASEO::$db->escape_string($type)."','".CASEO::$db->escape_string($body)."')");
			CASEO::$db->query("insert into textpage(metaid,sitetextid) values (".CASEO::$pageid.",".CASEO::$db->insert_id().")");
		}
		else
		{
			$row = CASEO::$db->fetch_array($ri);
			//Has the inputspec changed?
			$isp = explode('.',$row['inputspec'],2);
			$isImage = ($isp[0] == 'image');
			if ($isImage)
			{
				//Create width/height list and see if this one is in it.  If not then add it.
				$whl = explode(',',$isp[1]);
				$isp = explode('.',$type,2);
				$requestedSize = $isp[1];
				if (array_search($requestedSize,$whl)===false)
				{
					//Size isn't yet supported.  Add it to the list.
					$whl[] = $requestedSize;
					CASEO::$db->query("update sitetext set inputspec='image.".implode(',',$whl)."' where id={$row['id']}");
				}
				//Make sure we have the requested thumb.
				$requestedSize = str_replace('.','x',$requestedSize);
				$thumbName = "CASEO::$root/UserFiles/DataGrid/sitetext.body/{$row['id']}thumb$requestedSize.jpg";
				if (!file_exists($thumbName))
				{
					require_once("CASEO::$root/caseo/include/Thumb.php");
					$dimensions = explode('x',$requestedSize);
					$rawimage = "CASEO::$root/UserFiles/DataGrid/sitetext.body/{$row['id']}raw.jpg";
					Thumb::MakeFixedThumb($dimensions[0],$dimensions[1],$rawimage,$thumbName);
				}
			}
			else if ($type!=$row['inputspec'])
			{
				CASEO::$db->query("update sitetext set inputspec='".CASEO::$db->escape_string($type)."' where id={$row['id']}");
			}
			$widget = new InputWidget();
			
			$_GET['editkey'] = $row['id'];
			$t = $widget->Display("$type","{0}",$row['body']);
			if (array_key_exists('caseo',$_COOKIE) && $adminui)
			{
				//Render extra goop to allow in place editing.
				global $CASEO_tooltipcount;
				
				$CASEO_tooltipcount++;
				if ($isImage)
					$extra = $requestedSize;
				else 
					$extra = '';
				$jstag = str_replace('"','\"',$tag);
				$urltag = urlencode($jstag);
				$tag = htmlentities($tag);
				$link = "/caseo/sitetextdlg.php?&st=$urltag&extra=".urlencode($extra);
				$t = "<span id=\"ST$tag\">$t</span><script type=\"text/javascript\">
	YAHOO.CASEO.container.tt$CASEO_tooltipcount = new YAHOO.widget.Tooltip(\"tt$CASEO_tooltipcount\", 
											{ context:\"ST$jstag\", 
											  hidedelay: 10000,
											  autodismissdelay: 10000,
											  text:\"<a href='javascript:ShowEditWindow(\\\"$link\\\")'>Edit &quot;$tag&quot; with CASEO&trade;</a>\" });
					YAHOO.CASEO.container.tt$CASEO_tooltipcount.onClick = undefined;
				</script>";
				/*$jstag = str_replace("'","''",$tag);
				if ($isImage)
					$extra = ",'$requestedSize'";
				else 
					$extra = ",''";
				$t = "<span id=\"ST$tag\" onClick=\"cmsContentClick('$jstag'$extra)\" onMouseOver=\"cmsHighlightContent('$jstag')\" onMouseOut=\"cmsRestoreContent('$jstag')\">$t</span>";*/
			}
			//Ok, the site text exists, but is it linked to this document?
			$sql = "select metaid from textpage where sitetextid={$row['id']} and metaid=".CASEO::$pageid;
			$lri = CASEO::$db->query($sql);
			if (!$lri)
				die("Unable to read metaid. ($sql): ".CASEO::$db->error());
			if (CASEO::$db->num_rows($lri)==0)
			{
				//Nope, add the link.
				CASEO::$db->query("insert into textpage(metaid,sitetextid) values (".CASEO::$pageid.",{$row['id']})");
			}
			CASEO::$db->free_result($lri);
		}
		CASEO::$db->free_result($ri);
		return $t;
	}

	/**
	 * Get header tags such as title, meta tags, and admin javascript for in-place editing.
	 *
	 * @return string
	 */
	static function headtags()
	{
		$s = CASEO::$db->escape_string($_SERVER['PHP_SELF']);
		if (($s=='') || ($s=='/'))
			$s = '/index.php';
		$ri = CASEO::$db->query("select id,title,keywords,description from meta where document='$s'");
		if (($row = CASEO::$db->fetch_array($ri))===false)
		{
			$ri = CASEO::$db->query("insert into meta (document,title,description,keywords,mtime) values ('$s','".
				CASEO::$db->escape_string(CASEO::$config['defaulttitle'])."','".
				CASEO::$db->escape_string(CASEO::$config['defaultdescription'])."','".
				CASEO::$db->escape_string(CASEO::$config['defaultkeywords'])."',".time().")");
			if (!$ri)
				return "<!-- SQL Error: ".CASEO::$db->error()." -->\r\n";
			$ri = CASEO::$db->query("select id,title,keywords,description from meta where document='$s'");
			$row = CASEO::$db->fetch_array($ri);
		}
		CASEO::$title = $row['title'];
		CASEO::$pageid = $row['id'];
		$r = array();
		if ($row['title'] != '')
			$r[] = "<title>".htmlentities($row['title'])."</title>";
		if ($row['description']!='')
			$r[] = "<meta name=\"description\" content=\"".htmlentities($row['description'])."\" />";
		if ($row['keywords']!='')
			$r[] = "<meta name=\"keywords\" content=\"".htmlentities($row['keywords'])."\" />";
		$r = implode("\r\n",$r)."\r\n";
		if (array_key_exists('caseo',$_COOKIE))
			$r = CASEO::adminhead().$r;
		return $r;
	}
	
	static function siteimage($tag,$width,$height,$alt='')
	{
		$img = CASEO::sitetext($tag,"image.$width.$height");
		$ipos = strpos($img,"src=\"");
		if ($ipos>0)
			$img = substr($img,0,$ipos)."alt=\"$alt\" ".substr($img,$ipos);
		return $img;
	}
	
	static function sitemap()
	{
		include_once(CASEO::$root."/caseo/sitemapsclass.php");
		
		$sm = new CASEO_SiteMap(CASEO::$config['sitehome']);
		$ri = CASEO::$db->query("select * from meta");
		while (($row = CASEO::$db->fetch_array($ri))!==false)
		{
			$sm->AddUrl($row['document'],$row['mtime'],$row['changefreq'],($row['priority']/10));
		}
		$sm->Render();
	}

	static function LoadPluginConfig(&$pi)
	{
		$iid = 0 + $pi->iid;
		$sql = "select `key`,`value` from pluginconfig where (plugin={$pi->pid}) and (instance=$iid)";
		$ri = CASEO::$db->query($sql);
		if (!$ri)
		{
			die ("Error loading plugin config: ".CASEO::$db->error()."<br>$sql");
		}
		$pi->config = array();
		$pi->userconfig = $pi->GetDefaultConfig();
		while (($row = CASEO::$db->fetch_array($ri)) !== false )
		{
			$pi->SetConfigValue($row['key'],$row['value']);
		}
		CASEO::$db->free_result($ri);
	}
	
	/**
	 * Create a plugin object for the named plugin (same as the file name without the extension) and
	 * instance name.  Extra is used to pass extra plugin-specific stuff to a plugin, and private
	 * is used to flag an instance that shouldn't be listed with regular instances because it is
	 * created and maintained by something else (for example a collection of image galleries).
	 *
	 * @param string $plugin
	 * @param string $instance
	 * @param mixed $extra
	 * @param boolean $private
	 * @return Plugin
	 */
	static function mkplugin($plugin,$instance,$extra='',$private=0)
	{
		require_once(CASEO::$root."/caseo/PluginBase.php");
		$pluginsrc=CASEO::$root."/caseo/plugins/$plugin.php";
		if (!file_exists($pluginsrc))
		{
			die("No such plugin: $pluginsrc");
		}
		require_once($pluginsrc);
		$private = 0 + $private;
		$pif = "{$plugin}Factory";
		$pi = $pif();
		$pi->config = $pi->GetDefaultConfig();
		//Now load the config from the database.  First we have to figure out our instance.  If this is
		//a new instance then create it and populate it with default values.
		$sql = "select plugin.id as pid, plugininstance.id as pii,`release` from plugin left join plugininstance on (plugin.id=plugininstance.plugin) where (plugin.name='".
			CASEO::$db->escape_string($plugin)."') and (plugininstance.name='".
			CASEO::$db->escape_string($instance)."')";
		$ri = CASEO::$db->query($sql);
		if (!$ri)
		{
			die ("Error loading [plugin: $plugin] [instance $instance]: ".CASEO::$db->error()."<br>$sql");
		}
		$row = CASEO::$db->fetch_array($ri);
		$pi->pid = $row['pid'];
		$pi->iid = $row['pii'];
		$pi->dbrelease = $row['release'];
		$pi->extra = $extra;
		$pi->InstanceName = $instance;
		if ($pi->GetRelease() > $pi->dbrelease) $pi->Upgrade();
		if ($row !== false)
		{
			CASEO::LoadPluginConfig($pi);
		}
		else 
		{
			//New instance...  Load 'er up!
			$sql = "select id,enabled from plugin where name='".
				CASEO::$db->escape_string($plugin)."'";
			$ri = CASEO::$db->query($sql);
			if (!$ri)
			{
				die ("Error creating plugin instance for [$plugin]: ".CASEO::$db->error()."<br>$sql");
			}
			$row = CASEO::$db->fetch_array($ri);
			if ($row === false)
			{
				die ("Plugin [$plugin] isn't installed.");
			}
			if ($row['enabled']==0)
				die ("The plugin [$plugin] is not enabled.");
			$pi->pid = $row['id'];
			$ri = CASEO::$db->query("insert into plugininstance (plugin,name,`private`) values ({$pi->pid},'".
				CASEO::$db->escape_string($instance)."',$private)");
			$iid = CASEO::$db->insert_id();
			CASEO::LoadPluginConfig($pi); //Load default config for new instance
			$pi->pii = $pi->iid = $iid;
			foreach($pi->config as $cv)
			{
				$key = $cv->key;
				$value = $cv->value;
				$sql = "insert into pluginconfig (plugin,instance,`key`,value) values ({$pi->pid},$pi->iid,'".
					CASEO::$db->escape_string($key)."','".CASEO::$db->escape_string($value)."')";
				//echo "$sql<br>";
				CASEO::$db->query($sql) or die("Can't create plugin config ($sql): ".CASEO::$db->error());
			}
		}
		return $pi;
	}
	
	static function plugin($plugin,$instance,$extra='')
	{
		$pi = CASEO::mkplugin($plugin,$instance,$extra,0);
		if (!is_object($pi))
		{
			//echo "<pre>"; print_r($pi); echo "</pre>";
			die("Unable to create plugin: $plugin");
		}
		return $pi->Render();
	}
	
	static function adminhead()
	{
		return CASEO::YUI("container/assets/container.css").
			CASEO::YUI("yahoo-dom-event/yahoo-dom-event.js").
			CASEO::YUI("animation/animation-min.js").
			CASEO::YUI("container/container-min.js")."
	<script type=\"text/javascript\">
	function ShowEditWindow(link)
	{
		var editWindow = window.open(link,'cmsEditor','width=630,height=450,dependent');
		if (window.focus) editWindow.focus(); //Focus the new window if it has fallen beneith us.
	}
	YAHOO.namespace(\"CASEO.container\");
	</script>
	";
	}

	/**
	 * Is member authenticated?  If yes then loads auth info into global $member array.
	 *
	 * @return boolean
	 */
	static function memberauthenticate()
	{
		//Are we already authenticated?
		if (isset(CASEO::$member) && is_array(CASEO::$member))
		{
			return true;
		}
		if (array_key_exists('CASEOAuth',$_COOKIE))
		{
			$authkey = $_COOKIE['CASEOAuth'];
			$sql = "select * from member where authkey='".CASEO::$db->escape_string($authkey)."'"; 
			$ri = CASEO::$db->query($sql) or die("Unable to authenticate ($sql): ".CASEO::$db->error());
			if (($row = CASEO::$db->fetch_array($ri))!==false)
			{
				//Create member object
				CASEO::$member = array(
					'id'=>$row['id'],
					'email'=>$row['email'],
					'password'=>$row['password'],
					'formdata'=>$row['formdata'],
					'orgunit'=>$row['orgunit'],
					'groups'=>array('Registered User')
				);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Is member authorized (by group name) to view this page?
	 *
	 * @param string $groupname
	 * @return boolean
	 */
	static function memberauthorize($groupname)
	{
		if (CASEO::memberauthenticate())
		{
			$sql = "select name from groups,membergroup where (membergroup.memberid=".CASEO::$member['id'].") and (membergroup.groupid=groups.id)";
			$ri = CASEO::$db->query($sql) or die("Unable to authorize ($sql): ".CASEO::$db->error());
			while (($row = CASEO::$db->fetch_array($ri))!==false)
			{
				CASEO::$member['groups'][] = $row['name'];
			}
			return in_array($groupname,CASEO::$member['groups']);
		}
		else 
			return false;
	}
	
	/**
	 * Log member activity
	 *
	 * @param unknown_type $activity
	 */
	static function memberaudit($activity)
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if (array_key_exists('X_FORWARDED_FOR',$_SERVER))
			$realip = $_SERVER['X_FORWARDED_FOR'];
		else 
			$realip = $ip;
		if (is_array(CASEO::$member))
			$mid = 0 + CASEO::$member['id'];
		else 
			$mid = 0;
		$sql = "insert into memberaudit (member, audittime, remoteip, realip, audit) values ($mid,".
			"now(),'$ip','".CASEO::$db->escape_string($realip)."','".CASEO::$db->escape_string($activity)."')";
		CASEO::$db->query($sql) or die("Unable to log activity ($sql): ".CASEO::$db->error());
	}
	
	static function JSRedirect($url)
	{
		//Redirect and die.
		echo "<script type=\"text/JavaScript\">
		<!--
		window.location.href=\"$url\";
		//-->
		</script></head><body><noscript>Javascript is required to view this page.</noscript></body></html>";
		exit;
	}
	
	static function JSInnerHtml($id,$html)
	{
		echo "<script type=\"text/JavaScript\">
		<!--
		var CASEO_InnerHTML = document.getElementById('".addslashes($id)."');
		CASEO_InnerHTML.innerHTML = '".addslashes($html)."';
		//-->
		</script></head><body><noscript>Javascript is required to view this page.</noscript></body></html>";
		flush();
	}
	
	/**
	 * Use in the header with the include and metatags().
	 * Verify that the user is a member of the required group to view this page.
	 * If not, redirect to the login page.
	 *
	 * @param string $groupname
	 */
	static function memberpage($groupname='Registered User')
	{
		if (!array_key_exists('MemberLoginPage',CASEO::$config))
		{
			die("Please define \$CASEOConfig['MemberLoginPage'] before using membership functions.");
		}
		if (CASEO::memberauthenticate() && CASEO::memberauthorize($groupname))
		{
			CASEO::memberaudit("Opened page {$_SERVER['REQUEST_URI']}");
		}
		else 
		{
			$rurl = urlencode($_SERVER['REQUEST_URI']);
			CASEO::JSRedirect(CASEO::$config['MemberLoginPage']."?rurl=$rurl");
		}
	}
	
	static function memberdologin($userid, $password)
	{
		$sql = "select * from member where email='".CASEO::$db->escape_string($userid).
			"' and password='".CASEO::$db->escape_string($password)."'";
		$ri = CASEO::$db->query($sql) or die("Unable to login ($sql): ".CASEO::$db->error());
		if (($row = CASEO::$db->fetch_array($ri)) !== false)
		{
			//Set up the authkey and last auth
			$authkey = md5(uniqid(rand(),true));
			$sql = "update member set lastauth=now(), authkey='$authkey' where id={$row['id']}";
			CASEO::$db->query($sql) or die("Unable to set auth info ($sql): ".CASEO::$db->error());
			//Set authkey session cookie
			$_COOKIE['CASEOAuth'] = $authkey;
			echo "<script language=\"javascript\">
		<!--
		document.cookie = \"CASEOAuth=$authkey\";
		//-->
		</script>";
			CASEO::memberaudit("Successful login for [$userid]");
			return true;
		}
		else 
		{
			CASEO::memberaudit("Failed login attempt for [$userid]");
			return false;
		}
	}
	
	static function memberlogout($logoutpage)
	{
		CASEO::memberauthenticate();
		if (is_array(CASEO::$member))
		{
			$sql = "update member set authkey=null where id=".CASEO::$member['id'];
			CASEO::$db->query($sql) or die("Unable to logout ($sql): ".CASEO::$db->error());
			setcookie('CASEOAuth');
		}
		else 
		{
			echo "not logged in.";
			exit;
		}
		CASEO::JSRedirect($logoutpage);
	}
	
	static function membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect)
	{
		$pi = CASEO::mkplugin('Form',$formname);
		$pi->LoadInputData();
		$userid = $password = $confirm = '';
		$authed = CASEO::memberauthenticate();
			
		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			if ($_POST['formname']!=$pi->InstanceName)
			{
				//Another form is posting, just render the form as usual.
				$pi->RenderForm();
				return ;
			}
			//Look for user id, password and password confirmation fields
			$values = array(); //Build a new array of inputs except for password.
			foreach($pi->InputRows as $row)
			{
				$fldname = 'Field'.$row['fid'];
				if (array_key_exists($fldname,$_POST))
					$row['value'] = $_POST[$fldname];
				else 
					$row['value'] = '';
				switch($row['header'])
				{
					case $useridfield:
						$userid = $row['value'];
						$values[$row['header']] = $row['value'];
						break;
					case $passwordfield:
						$password = $row['value'];
						break;
					case $confirmfield:
						$confirm = $row['value'];
						break;
					default:
						$values[$row['header']] = $row['value'];
				}
			}
			//Now we have our rows, is there anything obviously wrong?
			if ($userid == '')
				$pi->ValidationErrors[] = 'Email address is a required field.';
			if ($password != $confirm)
				$pi->ValidationErrors[] = 'Passwords do not match.';
			if (($password == '') && !$authed)
				$pi->ValidationErrors[] = 'Password is a required field.';
			if (!$pi->IsValid())
			{
				$pi->RenderForm();
				return;
			}
			if (array_key_exists('rurl',$_GET))
				$rurl = $_GET['rurl'];
			else 
				$rurl = $redirect;
			if (strpos($rurl,'?')===false)
				$joinchar = '?';
			else
				$joinchar = '&';
			if (!$authed)
			{
				//New registration
				$sql = "insert into member(email,password,regtime) values ('".
					CASEO::$db->escape_string($userid)."','".
					CASEO::$db->escape_string($password)."',now())";
				$ri = CASEO::$db->query($sql);
				if (!$ri)
				{
					if (CASEO::$db->errno() == 1062)
					{
						$pi->ValidationErrors[] = "That user ID is already in use.";
						$pi->RenderForm();
					}
					else 
					{
						die("Unable to create member ($sql): ".CASEO::$db->error());
					}
				}
				else 
				{
					//Created member successfully.  Login and redirect to default member page.
					if (CASEO::memberdologin($userid,$password))
					{
						CASEO::memberauthenticate();
						$pi->StoreCapture($pi->MakeXML($values));
						$iid = CASEO::$db->insert_id();
						if ($iid)
						{
							$sql = "update member set formdata=$iid where id=".CASEO::$member['id'];
							CASEO::$db->query($sql) or die("Can't set form data ($sql): ".CASEO::$db->error());
						}
						//echo $rurl.$joinchar.'memberaction=new'; exit;
						CASEO::JSRedirect($rurl.$joinchar.'memberaction=new');
					}
					else 
					{
						echo "Oddly we couldn't log you in.";
					}
				}
			}
			else 
			{ //Update existing registration
				//Has email changed?
				if (CASEO::$member['email']!==$userid)
				{
					//Is the new user id already in use?
					$sql = "update member set email='".CASEO::$db->escape_string($userid)."' where id=".CASEO::$member['id'];
					$ri = CASEO::$db->query($sql);
					if (!$ri)
					{
						if (CASEO::$db->errno() == 1062)
						{
							$pi->ValidationErrors[] = "That user ID is already in  use.";
							$pi->RenderForm();
						}
						else 
						{
							die("Unable to update member ($sql): ".CASEO::$db->error());
						}
					}
				}
				//Has password changed?
				if (!empty($password))
				{
					$sql = "update member set password='".CASEO::$db->escape_string($password)."' where id=".CASEO::$member['id'];
					CASEO::$db->query($sql) or die("Unable to update member ($sql): ".CASEO::$db->error());
				}
				//Update other user info (XML)
				$sql = "update formcapture set formvalues='".CASEO::$db->escape_string($pi->MakeXML($values))."' where id=".CASEO::$member['formdata'];
				CASEO::$db->query($sql) or die("Unable to update member ($sql): ".CASEO::$db->error());
				CASEO::JSRedirect($rurl.$joinchar.'memberaction=update');
			}
		}
		else 
		{
			if ($authed)
			{
				//We're logged in so update existing info.
				$sql = "select formvalues from formcapture where id=".CASEO::$member['formdata'];
				$ri = CASEO::$db->query($sql) or die("Can't get form data ($sql): ".CASEO::$db->error());
				$xml = CASEO::$db->result($ri,0,0);
				$pi->XmlValues = $xml;
				return $pi->Render();
			}
			else 
				return $pi->Render();
		}
		return '';
	}
	
	/**
	 * Render login interface.  Uses reg GET variable, which can be:
	 * 	- username: create a new username/account
	 * 	- extra: get extra info from the user using a client defined form
	 * If the reg GET variable isn't supplied it just tries to log the user in.
	 *
	 * @return string HTML for login process
	 */
	static function memberlogin()
	{
		$r = array();
		$e = array();
		if ($_SERVER['REQUEST_METHOD']=='POST')
		{
			//Determine where we are and go from there
			if (array_key_exists("reg",$_GET))
			{
				$reg = $_GET['reg'];
				switch ($reg)
				{
					case 'username':
						//If passwords don't match or if email not supplied, ask again with error.
						$email = trim($_POST['email']);
						$pass = $_POST['pass'];
						$pass2 = $_POST['pass2'];
						if ($email == '')
							$e[] = 'Email address is a required field.';
						if ($pass != $pass2)
							$e[] = 'Passwords do not match.';
						if ($pass == '')
							$e[] = 'Password is a required field.';
						if (count($e)==0)
						{
							$sql = "insert into member(email,password,regtime) values ('".
								CASEO::$db->escape_string($email)."','".
								CASEO::$db->escape_string($pass)."',now())";
							$ri = CASEO::$db->query($sql);
							if (!$ri)
							{
								if (CASEO::$db->errno() == 1062)
								{
									$e[] = "That user ID is already  in use.";
								}
								else 
								{
									die("Unable to create member ($sql): ".CASEO::$db->error());
								}
							}
							else 
							{
								//Created member successfully.  Login and redirect to extra info page if set up.
								if (CASEO::memberdologin($email,$pass))
								{
									if (!array_key_exists('MembershipInfoForm',CASEO::$config))
									{
										if (array_key_exists('rurl',$_GET))
											$rurl = $_GET['rurl'];
										else 
										{
											if (array_key_exists('MemberDefaultPage',CASEO::$config))
												$rurl = CASEO::$config['MemberDefaultPage'];
											else 
											{
												$rp = explode('/',$_SERVER['REQUEST_URI']);
												array_pop($rp); //Remove document name;
												$rurl = implode('/',$rp);
											}
										}
										CASEO::JSRedirect($rurl);
									}
									else 
										CASEO::JSRedirect(CASEO::$config['MemberLoginPage']."?reg=extra&rurl=$rurl");
								}
							}
						}
						break;
					case 'extra':
						//May also confirm email from step one.
						CASEO::memberpage();
						//Get it on with the bogus form fields for password and email.
						$pi = CASEO::mkplugin('Form',CASEO::$config['MembershipInfoForm']);
						$pi->SaveID = CASEO::$member['formdata'];
						if (array_key_exists('Fieldemail',$_POST))
						{
							//Try to update the email address
							$sql = "update member set email='".CASEO::$db->escape_string($_POST['Fieldemail']).
								"' where id=".CASEO::$member['id'];
							$ri = CASEO::$db->query($sql);
							if (!$ri)
							{
								if (CASEO::$db->errno()==1062)
									$pi->ValidationErrors[] = "That email address is already   in use.";
								else
									die("Unable to update email address ($sql): ".CASEO::$db->error());
							}
							//Try to update password
							if ($_POST['Fieldoldpass'] != '')
							{
								if (CASEO::$member['password']!=$_POST['Fieldoldpass'])
								{
									$pi->ValidationErrors[] = "The old password you supplied is not correct.";
								}
								if ($_POST['Fieldpass']!=$_POST['Fieldpass2'])
								{
									$pi->ValidationErrors[] = "The new passwords don't match.";
								}
								if (count($pi->ValidationErrors)==0)
								{
									//All good...  Update the password.
									$sql = "update member set password='".CASEO::$db->escape_string($_POST['Fieldpass']).
										"' where id=".CASEO::$member['id'];
									CASEO::$db->query($sql) or die("Unable to update password ($sql): ".CASEO::$db->error());
								}
							}
							//If validation errors try to return record to it's old email.  Password should only set if all is well.
							if (count($pi->ValidationErrors)>0)
							{
								$sql = "update member set email='".CASEO::$db->escape_string(CASEO::$member['email']).
									"' where id=".CASEO::$member['id'];
								CASEO::$db->query($sql) or die("Unable to restore email ($sql): ".CASEO::$db->error());
							}
						}
						$r[] = $pi->Render();
						if (count($pi->ValidationErrors)==0)
						{
							$formid = CASEO::$db->insert_id();
							if ($formid)
							{
								$sql = "update member set formdata=$formid where id=".CASEO::$member['id'];
								CASEO::$db->query($sql) or die("Can't set form data ($sql): ".CASEO::$db->error());
							}
							//return implode("\r\n",$r);
							CASEO::JSRedirect($_GET['rurl']);
						}
						else 
						{
							//Failed validation.  Just return so we can try again.
							return '';
						}
				}
				//None of the above?  Fall through to login.
			}
			if (count($e)==0)
			{
				if (CASEO::memberdologin($_POST['email'],$_POST['pass']))
				{
					//Redirect to source page or root page if none provided.
					if (array_key_exists('rurl',$_GET))
						$rurl = $_GET['rurl'];
					else 
						$rurl = CASEO::$config['MemberDefaultPage'];
					CASEO::JSRedirect($rurl);
				}
				else
				{
					$e[] = "Your email address or password are not correct.";
				}
			}
		}
		if (count($e)>0)
		{
			$r[] = "<div class=\"MemberBadLogin\">".implode("<br />\r\n",$e)."</div>";
		}
		if (array_key_exists("reg",$_GET))
		{
			$reg = $_GET['reg'];
			switch ($reg)
			{
				case 'username':
					$r[] = CASEO::$config['MembershipUsernameForm'];
					return implode("\r\n",$r);
				case 'extra':
					//May also confirm email from step one.
					memberpage();
					$pi = mkplugin('Form',CASEO::$config['MembershipInfoForm']);
					$pi->LoadInputData();
					if ($member['formdata'])
					{
						$sql = "select formvalues from formcapture where id=".CASEO::$member['formdata'];
						$ri = CASEO::$db->query($sql) or die("Unable to load form data ($sql): ".CASEO::$db->error());
						$pi->XmlValues = CASEO::$db->result($ri,0,0);
						array_unshift($pi->InputRows,array(
							"fid"=>"pass2",
							"header"=>"pass2",
							"defaultvalue"=>"",
							"caption"=>"Confirm New Password:",
							"specifier"=>"password.20.32"));
						array_unshift($pi->InputRows,array(
							"fid"=>"pass",
							"header"=>"pass",
							"defaultvalue"=>"",
							"caption"=>"New Password:",
							"specifier"=>"password.20.32"));
						array_unshift($pi->InputRows,array(
							"fid"=>"oldpass",
							"header"=>"oldpass",
							"defaultvalue"=>"",
							"caption"=>"Old Password:",
							"specifier"=>"password.20.32"));
						array_unshift($pi->InputRows,array(
							"fid"=>"email",
							"header"=>"email",
							"defaultvalue"=>$member['email'],
							"caption"=>"E-mail:",
							"specifier"=>"input.20.80"));
					}
					$r[] = $pi->Render();
					return implode("\r\n",$r);
			}
			//None of the above?  Fall through to login.
		}
		if (!array_key_exists('MembershipLoginForm',CASEO::$config))
		{
			die("Please define \CASEOCconfig['MembershipLoginForm'] before using membership features.");
		}
		$r[] = CASEO::$config['MembershipLoginForm'];
		return implode("\r\n",$r);
	}
	
	/**
	 * Get PHPMailer object pre-configured with settings from the CASEO config file.
	 *
	 * @return PHPMailer
	 */
	static function GetPHPMailer()
	{
		require_once(CASEO::$root."/caseo/phpmailer/class.phpmailer.php");
		$mail = new PHPMailer();
		$mail->Mailer = array_key_exists('Mailer Type',CASEO::$config) ? CASEO::$config['Mailer Type'] : 'mail';
		if ($mail->Mailer == 'smtp')
		{
			$mail->Host = CASEO::$config['Mailer SMTP Hosts'];
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		if (array_key_exists('X_FORWARDED_FOR',$_SERVER))
		{
			$ip .= " forwarding for ".$_SERVER['X_FORWARDED_FOR'];
		}
		$mail->AddCustomHeader("X-WebmailSrc: $ip");
		return $mail;
	}
}

if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
	CASEO::$root = $_SERVER["APPL_PHYSICAL_PATH"];
else 
	CASEO::$root = $_SERVER['DOCUMENT_ROOT'];

CASEO::$build = 1986;
include(CASEO::$root."/caseo/config/config.php");
CASEO::$config = $CASEOConfig;
unset($CASEOConfig);

if ((array_key_exists('FixSlashes',CASEO::$config)) && (CASEO::$config['FixSlashes']) && (get_magic_quotes_gpc())) {
   $_POST = array_map('CASEO::stripslashes_deep', $_POST);
   $_GET = array_map('CASEO::stripslashes_deep', $_GET);
   $_COOKIE = array_map('CASEO::stripslashes_deep', $_COOKIE);
}

if (file_exists(CASEO::$root."/caseo/custom/render.php"))
	include_once(CASEO::$root."/caseo/custom/render.php");
require_once(CASEO::$root."/caseo/InputWidget.php");

if (empty(CASEO::$config['database']))
{
	CASEO::$config['database'] = 'mysql';
}

require_once(CASEO::$root."/caseo/db/".CASEO::$config['database'].".php");
CASEO::$db = new CASEO_DB();

CASEO::$userconfig = array();
$ri = CASEO::$db->query("select * from config order by disporder");
if ($ri)
{
	while (($row = CASEO::$db->fetch_array($ri))!==false)
	{
		CASEO::$userconfig[$row['name']] = $row['value'];
	}
	CASEO::$db->free_result($ri);
} //Else this is init so we don't care.
?>