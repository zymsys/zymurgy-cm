<?
if (!class_exists('Zymurgy'))
{
	class Zymurgy
	{
		/**
		 * Physical path to the site's root directory on the server
		 *
		 * @var string
		 */
		static $root;
		
		/**
		 * A Zymurgy_DB instance for database access
		 *
		 * @var Zymurgy_DB
		 */
		static $db; 
		
		/**
		 * Config values from the config/config.php file
		 *
		 * @var array
		 */
		static $config; 
		
		/**
		 * User supplied site config values from within the Zymurgy:CM control panel front-end
		 *
		 * @var array
		 */
		static $userconfig;
		
		/**
		 * Zymurgy:CM release number
		 *
		 * @var int
		 */
		static $build = 1987;
		
		/**
		 * member info available if the user is logged in.
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
		 * Adds "http://yui.yahooapis.com/{version}/build/" to the start of src to keep the YUI version consistant.  The version
		 * number loaded by YUI will be updated in future releases.
		 *
		 * @param string $src
		 * @return string
		 */
		static function YUI($src)
		{
			if (array_key_exists($src,Zymurgy::$yuiloaded))
				return ''; //Already loaded
			Zymurgy::$yuiloaded[$src]='';
			$sp = explode('.',$src);
			$ext = strtolower(array_pop($sp));
			switch($ext)
			{
				case 'js':
					return "<script src=\"".Zymurgy::YUIBaseURL()."$src\"></script>\r\n";
				case 'css':
					return "<link rel=\"stylesheet\" type=\"text/css\" href=\"".Zymurgy::YUIBaseURL()."$src\" />";
			}
		}
		
		/**
		 * Defines the base URL for the YUI framework.
		 *
		 * @return The base URL for the YUI framework.
		 */
		
		static function YUIBaseURL()
		{
			return "http://yui.yahooapis.com/2.6.0/build/";
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
			Zymurgy::buildEasyXML($tableName);
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
				Zymurgy::$db->escape_string($tableName)."'";
			$ri = Zymurgy::$db->query($sql) or die("Can't get table info ($sql): ".Zymurgy::$db->error());
			$row = Zymurgy::$db->fetch_array($ri,MYSQL_ASSOC) or die("Table $tableName doesn't exist.");
			$tid = $row['id'];
			Zymurgy::$db->free_result($ri);
			$sql = "select * from customtable where detailfor=$tid";
			$ri = Zymurgy::$db->query($sql) or die("Can't get table detail info ($sql): ".Zymurgy::$db->error());
			$details = array();
			while(($row = Zymurgy::$db->fetch_array($ri,MYSQL_ASSOC))!==false)
			{
				$details[$row['id']] = $row['tname'];
			}
			Zymurgy::$db->free_result($ri);
			//Get our rows...
			$myrows = array();
			$sql = "select * from $tableName";
			if (!empty($detailTable))
			{
				$sql .= " where $detailTable=$parentID";
			}
			$ri = Zymurgy::$db->query($sql) or die("Can't get XML data ($sql): ".Zymurgy::$db->error());
			while(($row = Zymurgy::$db->fetch_array($ri,MYSQL_ASSOC))!==false)
			{
				$myrows[] = $row;
			}
			Zymurgy::$db->free_result($ri);
			//Spit 'em out...
			$mytabs = str_repeat("\t",$level);
			foreach ($myrows as $row)
			{
				echo "{$mytabs}<$tableName id=\"{$row['id']}\">\r\n";
				foreach ($row as $key=>$value)
				{
					if (($key == 'id') || ($key == $detailTable)) continue;
					$value = Zymurgy::XMLvalue($value);
					echo "{$mytabs}\t<$key>$value</$key>\r\n";
				}
				//Now spit out details for this row
				foreach($details as $id=>$detailName)
				{
					Zymurgy::buildEasyXML($detailName,$tableName,$row['id'],$level+1);
				}
				echo "{$mytabs}</$tableName>\r\n";
			}
		}
		
		static function stripslashes_deep($value)
		{
			$value = is_array($value) ?
				array_map('Zymurgy::stripslashes_deep', $value) :
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
			$sql = "select body,id,inputspec from zcm_sitetext where tag='".Zymurgy::$db->escape_string($tag)."'";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
				die("Unable to read metaid. ($sql): ".Zymurgy::$db->error());
			if (Zymurgy::$db->num_rows($ri)==0)
			{
				//Create new sitetext entry
				$body = 'Please edit the general content called <b>"'.$tag.'"</b> in Zymurgy:CM.';
				Zymurgy::$db->query("insert into zcm_sitetext (tag,inputspec,body) values ('".Zymurgy::$db->escape_string($tag)."','".
					Zymurgy::$db->escape_string($type)."','".Zymurgy::$db->escape_string($body)."')");
				Zymurgy::$db->query("insert into zcm_textpage(metaid,sitetextid) values (".Zymurgy::$pageid.",".Zymurgy::$db->insert_id().")");
				$t = $body;
			}
			else
			{
				$row = Zymurgy::$db->fetch_array($ri);
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
						Zymurgy::$db->query("update zcm_sitetext set inputspec='image.".implode(',',$whl)."' where id={$row['id']}");
					}
					//Make sure we have the requested thumb.
					$requestedSize = str_replace('.','x',$requestedSize);
					$thumbName = "Zymurgy::$root/UserFiles/DataGrid/sitetext.body/{$row['id']}thumb$requestedSize.jpg";
					if (!file_exists($thumbName))
					{
						require_once("Zymurgy::$root/zymurgy/include/Thumb.php");
						$dimensions = explode('x',$requestedSize);
						$rawimage = "Zymurgy::$root/UserFiles/DataGrid/sitetext.body/{$row['id']}raw.jpg";
						Thumb::MakeFixedThumb($dimensions[0],$dimensions[1],$rawimage,$thumbName);
					}
				}
				else if ($type!=$row['inputspec'])
				{
					Zymurgy::$db->query("update zcm_sitetext set inputspec='".Zymurgy::$db->escape_string($type)."' where id={$row['id']}");
				}
				$widget = new InputWidget();
				
				$_GET['editkey'] = $row['id'];
				$t = $widget->Display("$type","{0}",$row['body']);
				if (array_key_exists('zymurgy',$_COOKIE) && $adminui)
				{
					//Render extra goop to allow in place editing.
					global $Zymurgy_tooltipcount;
					
					$Zymurgy_tooltipcount++;
					if ($isImage)
						$extra = $requestedSize;
					else 
						$extra = '';
					$jstag = str_replace('"','\"',$tag);
					$urltag = urlencode($jstag);
					$tag = htmlentities($tag);
					$link = "/zymurgy/sitetextdlg.php?&st=$urltag&extra=".urlencode($extra);
					$t = "<span id=\"ST$tag\">$t</span><script type=\"text/javascript\">
		YAHOO.Zymurgy.container.tt$Zymurgy_tooltipcount = new YAHOO.widget.Tooltip(\"tt$Zymurgy_tooltipcount\", 
												{ context:\"ST$jstag\", 
												  hidedelay: 10000,
												  autodismissdelay: 10000,
												  text:\"<a href='javascript:ShowEditWindow(\\\"$link\\\")'>Edit &quot;$tag&quot; with Zymurgy:CM</a>\" });
						YAHOO.Zymurgy.container.tt$Zymurgy_tooltipcount.onClick = undefined;
					</script>";
					/*$jstag = str_replace("'","''",$tag);
					if ($isImage)
						$extra = ",'$requestedSize'";
					else 
						$extra = ",''";
					$t = "<span id=\"ST$tag\" onClick=\"cmsContentClick('$jstag'$extra)\" onMouseOver=\"cmsHighlightContent('$jstag')\" onMouseOut=\"cmsRestoreContent('$jstag')\">$t</span>";*/
				}
				//Ok, the site text exists, but is it linked to this document?
				$sql = "select metaid from zcm_textpage where sitetextid={$row['id']} and metaid=".Zymurgy::$pageid;
				$lri = Zymurgy::$db->query($sql);
				if (!$lri)
					die("Unable to read metaid. ($sql): ".Zymurgy::$db->error());
				if (Zymurgy::$db->num_rows($lri)==0)
				{
					//Nope, add the link.
					Zymurgy::$db->query("insert into zcm_textpage(metaid,sitetextid) values (".Zymurgy::$pageid.",{$row['id']})");
				}
				Zymurgy::$db->free_result($lri);
			}
			Zymurgy::$db->free_result($ri);
			return $t;
		}
	
		/**
		 * Get header tags such as title, meta tags, and admin javascript for in-place editing.
		 *
		 * @return string
		 */
		static function headtags()
		{
			$s = Zymurgy::$db->escape_string($_SERVER['PHP_SELF']);
			if (($s=='') || ($s=='/'))
				$s = '/index.php';
			$ri = Zymurgy::$db->query("select id,title,keywords,description from zcm_meta where document='$s'");
			if (($row = Zymurgy::$db->fetch_array($ri))===false)
			{
				$ri = Zymurgy::$db->query("insert into zcm_meta (document,title,description,keywords,mtime) values ('$s','".
					Zymurgy::$db->escape_string(Zymurgy::$config['defaulttitle'])."','".
					Zymurgy::$db->escape_string(Zymurgy::$config['defaultdescription'])."','".
					Zymurgy::$db->escape_string(Zymurgy::$config['defaultkeywords'])."',".time().")");
				if (!$ri)
					return "<!-- SQL Error: ".Zymurgy::$db->error()." -->\r\n";
				$ri = Zymurgy::$db->query("select id,title,keywords,description from zcm_meta where document='$s'");
				$row = Zymurgy::$db->fetch_array($ri);
			}
			Zymurgy::$title = $row['title'];
			Zymurgy::$pageid = $row['id'];
			$r = array();
			if ($row['title'] != '')
				$r[] = "<title>".htmlentities($row['title'])."</title>";
			if ($row['description']!='')
				$r[] = "<meta name=\"description\" content=\"".htmlentities($row['description'])."\" />";
			if ($row['keywords']!='')
				$r[] = "<meta name=\"keywords\" content=\"".htmlentities($row['keywords'])."\" />";
			$r = implode("\r\n",$r)."\r\n";
			if (array_key_exists('zymurgy',$_COOKIE))
				$r = Zymurgy::adminhead().$r;
			return $r;
		}
		
		static function siteimage($tag,$width,$height,$alt='')
		{
			$img = Zymurgy::sitetext($tag,"image.$width.$height");
			$ipos = strpos($img,"src=\"");
			if ($ipos>0)
				$img = substr($img,0,$ipos)."alt=\"$alt\" ".substr($img,$ipos);
			return $img;
		}
		
		static function sitemap()
		{
			include_once(Zymurgy::$root."/zymurgy/sitemapsclass.php");

			$sm = new Zymurgy_SiteMap(Zymurgy::$config['sitehome']);
			$ri = Zymurgy::$db->query("select * from zcm_meta");
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$sm->AddUrl($row['document'],$row['mtime'],$row['changefreq'],($row['priority']/10));
			}
			$sm->Render();
		}
	
		static function LoadPluginConfig(&$pi)
		{
			$iid = 0 + $pi->iid;
			$sql = "select `key`,`value` from zcm_pluginconfig where (plugin={$pi->pid}) and (instance=$iid)";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
			{
				die ("Error loading plugin config: ".Zymurgy::$db->error()."<br>$sql");
			}
			$pi->config = array();
			$pi->userconfig = $pi->GetDefaultConfig();
			while (($row = Zymurgy::$db->fetch_array($ri)) !== false )
			{
				$pi->SetConfigValue($row['key'],$row['value']);
			}
			Zymurgy::$db->free_result($ri);
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
			require_once(Zymurgy::$root."/zymurgy/PluginBase.php");
			$pluginsrc=Zymurgy::$root."/zymurgy/plugins/$plugin.php";
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
			$sql = "select zcm_plugin.id as pid, zcm_plugininstance.id as pii,`release` from zcm_plugin left join zcm_plugininstance on (zcm_plugin.id=zcm_plugininstance.plugin) where (zcm_plugin.name='".
				Zymurgy::$db->escape_string($plugin)."') and (zcm_plugininstance.name='".
				Zymurgy::$db->escape_string($instance)."')";
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
			{
				die ("Error loading [plugin: $plugin] [instance $instance]: ".Zymurgy::$db->error()."<br>$sql");
			}
			$row = Zymurgy::$db->fetch_array($ri);
			$pi->pid = $row['pid'];
			$pi->iid = $row['pii'];
			$pi->dbrelease = $row['release'];
			$pi->extra = $extra;
			$pi->InstanceName = $instance;
			//echo "[".$pi->GetRelease().",{$pi->dbrelease}]"; exit;
			if ($pi->GetRelease() > $pi->dbrelease) $pi->Upgrade();
			if ($row !== false)
			{
				Zymurgy::LoadPluginConfig($pi);
			}
			else 
			{
				//New instance...  Load 'er up!
				$sql = "select id,enabled from zcm_plugin where name='".
					Zymurgy::$db->escape_string($plugin)."'";
				$ri = Zymurgy::$db->query($sql);
				if (!$ri)
				{
					die ("Error creating plugin instance for [$plugin]: ".Zymurgy::$db->error()."<br>$sql");
				}
				$row = Zymurgy::$db->fetch_array($ri);
				if ($row === false)
				{
					die ("Plugin [$plugin] isn't installed.");
				}
				if ($row['enabled']==0)
					die ("The plugin [$plugin] is not enabled.");
				$pi->pid = $row['id'];
				$ri = Zymurgy::$db->query("insert into zcm_plugininstance (plugin,name,`private`) values ({$pi->pid},'".
					Zymurgy::$db->escape_string($instance)."',$private)");
				$iid = Zymurgy::$db->insert_id();
				Zymurgy::LoadPluginConfig($pi); //Load default config for new instance
				$pi->pii = $pi->iid = $iid;
				foreach($pi->config as $cv)
				{
					$key = $cv->key;
					$value = $cv->value;
					$sql = "insert into zcm_pluginconfig (plugin,instance,`key`,value) values ({$pi->pid},$pi->iid,'".
						Zymurgy::$db->escape_string($key)."','".Zymurgy::$db->escape_string($value)."')";
					//echo "$sql<br>";
					Zymurgy::$db->query($sql) or die("Can't create plugin config ($sql): ".Zymurgy::$db->error());
				}
			}
			return $pi;
		}
		
		static function plugin($plugin,$instance,$extra='')
		{
			$pi = Zymurgy::mkplugin($plugin,$instance,$extra,0);
			if (!is_object($pi))
			{
				die("Unable to create plugin: $plugin");
			}
			return $pi->Render();
		}
		
		static function adminhead()
		{
			return Zymurgy::YUI("container/assets/container.css").
				Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js").
				Zymurgy::YUI("animation/animation-min.js").
				Zymurgy::YUI("container/container-min.js")."
		<script type=\"text/javascript\">
		function ShowEditWindow(link)
		{
			var editWindow = window.open(link,'cmsEditor','width=630,height=450,dependent');
			if (window.focus) editWindow.focus(); //Focus the new window if it has fallen beneith us.
		}
		YAHOO.namespace(\"Zymurgy.container\");
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
			if (isset(Zymurgy::$member) && is_array(Zymurgy::$member))
			{
				return true;
			}
			if (array_key_exists('ZymurgyAuth',$_COOKIE))
			{
				$authkey = $_COOKIE['ZymurgyAuth'];
				$sql = "select * from zcm_member where authkey='".Zymurgy::$db->escape_string($authkey)."'"; 
				$ri = Zymurgy::$db->query($sql) or die("Unable to authenticate ($sql): ".Zymurgy::$db->error());
				if (($row = Zymurgy::$db->fetch_array($ri))!==false)
				{
					//Create member object
					Zymurgy::$member = array(
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
			if (Zymurgy::memberauthenticate())
			{
				$sql = "select name from zcm_groups,zcm_membergroup where (zcm_membergroup.memberid=".Zymurgy::$member['id'].") and (zcm_membergroup.groupid=zcm_groups.id)";
				$ri = Zymurgy::$db->query($sql) or die("Unable to authorize ($sql): ".Zymurgy::$db->error());
				while (($row = Zymurgy::$db->fetch_array($ri))!==false)
				{
					Zymurgy::$member['groups'][] = $row['name'];
				}
				return in_array($groupname,Zymurgy::$member['groups']);
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
			if (is_array(Zymurgy::$member))
				$mid = 0 + Zymurgy::$member['id'];
			else 
				$mid = 0;
			$sql = "insert into zcm_memberaudit (member, audittime, remoteip, realip, audit) values ($mid,".
				"now(),'$ip','".Zymurgy::$db->escape_string($realip)."','".Zymurgy::$db->escape_string($activity)."')";
			Zymurgy::$db->query($sql) or die("Unable to log activity ($sql): ".Zymurgy::$db->error());
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
			var Zymurgy_InnerHTML = document.getElementById('".addslashes($id)."');
			Zymurgy_InnerHTML.innerHTML = '".addslashes($html)."';
			</script>";
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
			if (!array_key_exists('MemberLoginPage',Zymurgy::$config))
			{
				die("Please define \$ZymurgyConfig['MemberLoginPage'] before using membership functions.");
			}
			$matuh8 = Zymurgy::memberauthenticate();
			$matuhz = Zymurgy::memberauthorize($groupname);
			if ($matuh8 && $matuhz)
			{
				Zymurgy::memberaudit("Opened page {$_SERVER['REQUEST_URI']}");
			}
			else 
			{
				$rurl = urlencode($_SERVER['REQUEST_URI']);
				Zymurgy::JSRedirect(Zymurgy::$config['MemberLoginPage']."?rurl=$rurl");
			}
		}
		
		static function memberdologin($userid, $password)
		{
			$sql = "select * from zcm_member where email='".Zymurgy::$db->escape_string($userid).
				"' and password='".Zymurgy::$db->escape_string($password)."'";
			$ri = Zymurgy::$db->query($sql) or die("Unable to login ($sql): ".Zymurgy::$db->error());
			if (($row = Zymurgy::$db->fetch_array($ri)) !== false)
			{
				//Set up the authkey and last auth
				$authkey = md5(uniqid(rand(),true));
				$sql = "update zcm_member set lastauth=now(), authkey='$authkey' where id={$row['id']}";
				Zymurgy::$db->query($sql) or die("Unable to set auth info ($sql): ".Zymurgy::$db->error());
				//Set authkey session cookie
				$_COOKIE['ZymurgyAuth'] = $authkey;
				echo "<script language=\"javascript\">
			<!--
			document.cookie = \"ZymurgyAuth=$authkey\";
			//-->
			</script>";
				Zymurgy::memberaudit("Successful login for [$userid]");
				//echo "Alright, logged in with $authkey, now fuck off.<pre>"; print_r($_COOKIE); exit;
				return true;
			}
			else 
			{
				Zymurgy::memberaudit("Failed login attempt for [$userid]");
				return false;
			}
		}
		
		static function memberlogout($logoutpage)
		{
			Zymurgy::memberauthenticate();
			if (is_array(Zymurgy::$member))
			{
				$sql = "update zcm_member set authkey=null where id=".Zymurgy::$member['id'];
				Zymurgy::$db->query($sql) or die("Unable to logout ($sql): ".Zymurgy::$db->error());
				setcookie('ZymurgyAuth');
			}
			else 
			{
				echo "not logged in.";
				exit;
			}
			Zymurgy::JSRedirect($logoutpage);
		}
		
		static function membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect)
		{
			$pi = Zymurgy::mkplugin('Form',$formname);
			$pi->LoadInputData();
			$userid = $password = $confirm = '';
			$authed = Zymurgy::memberauthenticate();
				
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
					$sql = "insert into zcm_member(email,password,regtime) values ('".
						Zymurgy::$db->escape_string($userid)."','".
						Zymurgy::$db->escape_string($password)."',now())";
					$ri = Zymurgy::$db->query($sql);
					if (!$ri)
					{
						if (Zymurgy::$db->errno() == 1062)
						{
							$pi->ValidationErrors[] = "That user ID is already in use.";
							$pi->RenderForm();
						}
						else 
						{
							die("Unable to create member ($sql): ".Zymurgy::$db->error());
						}
					}
					else 
					{
						//Created member successfully.  Login and redirect to default member page.
						if (Zymurgy::memberdologin($userid,$password))
						{
							Zymurgy::memberauthenticate();
							$pi->StoreCapture($pi->MakeXML($values));
							$pi->SendEmail();
							$iid = Zymurgy::$db->insert_id();
							if ($iid)
							{
								$sql = "update zcm_member set formdata=$iid where id=".Zymurgy::$member['id'];
								Zymurgy::$db->query($sql) or die("Can't set form data ($sql): ".Zymurgy::$db->error());
							}
							Zymurgy::JSRedirect($rurl.$joinchar.'memberaction=new');
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
					if (Zymurgy::$member['email']!==$userid)
					{
						//Is the new user id already in use?
						$sql = "update zcm_member set email='".Zymurgy::$db->escape_string($userid)."' where id=".Zymurgy::$member['id'];
						$ri = Zymurgy::$db->query($sql);
						if (!$ri)
						{
							if (Zymurgy::$db->errno() == 1062)
							{
								$pi->ValidationErrors[] = "That user ID is already in  use.";
								$pi->RenderForm();
							}
							else 
							{
								die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
							}
						}
					}
					//Has password changed?
					if (!empty($password))
					{
						$sql = "update zcm_member set password='".Zymurgy::$db->escape_string($password)."' where id=".Zymurgy::$member['id'];
						Zymurgy::$db->query($sql) or die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
					}
					//Update other user info (XML)
					$sql = "update formcapture set formvalues='".Zymurgy::$db->escape_string($pi->MakeXML($values))."' where id=".Zymurgy::$member['formdata'];
					Zymurgy::$db->query($sql) or die("Unable to update zcm_member ($sql): ".Zymurgy::$db->error());
					Zymurgy::JSRedirect($rurl.$joinchar.'memberaction=update');
				}
			}
			else 
			{
				if ($authed)
				{
					//We're logged in so update existing info.
					$sql = "select formvalues from formcapture where id=".Zymurgy::$member['formdata'];
					$ri = Zymurgy::$db->query($sql) or die("Can't get form data ($sql): ".Zymurgy::$db->error());
					$xml = Zymurgy::$db->result($ri,0,0);
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
								$sql = "insert into zcm_member(email,password,regtime) values ('".
									Zymurgy::$db->escape_string($email)."','".
									Zymurgy::$db->escape_string($pass)."',now())";
								$ri = Zymurgy::$db->query($sql);
								if (!$ri)
								{
									if (Zymurgy::$db->errno() == 1062)
									{
										$e[] = "That user ID is already  in use.";
									}
									else 
									{
										die("Unable to create zcm_member ($sql): ".Zymurgy::$db->error());
									}
								}
								else 
								{
									//Created member successfully.  Login and redirect to extra info page if set up.
									if (Zymurgy::memberdologin($email,$pass))
									{
										if (!array_key_exists('MembershipInfoForm',Zymurgy::$config))
										{
											if (array_key_exists('rurl',$_GET))
												$rurl = $_GET['rurl'];
											else 
											{
												if (array_key_exists('MemberDefaultPage',Zymurgy::$config))
													$rurl = Zymurgy::$config['MemberDefaultPage'];
												else 
												{
													$rp = explode('/',$_SERVER['REQUEST_URI']);
													array_pop($rp); //Remove document name;
													$rurl = implode('/',$rp);
												}
											}
											Zymurgy::JSRedirect($rurl);
										}
										else 
											Zymurgy::JSRedirect(Zymurgy::$config['MemberLoginPage']."?reg=extra&rurl=$rurl");
									}
								}
							}
							break;
						case 'extra':
							//May also confirm email from step one.
							Zymurgy::memberpage();
							//Get it on with the bogus form fields for password and email.
							$pi = Zymurgy::mkplugin('Form',Zymurgy::$config['MembershipInfoForm']);
							$pi->SaveID = Zymurgy::$member['formdata'];
							if (array_key_exists('Fieldemail',$_POST))
							{
								//Try to update the email address
								$sql = "update zcm_member set email='".Zymurgy::$db->escape_string($_POST['Fieldemail']).
									"' where id=".Zymurgy::$member['id'];
								$ri = Zymurgy::$db->query($sql);
								if (!$ri)
								{
									if (Zymurgy::$db->errno()==1062)
										$pi->ValidationErrors[] = "That email address is already   in use.";
									else
										die("Unable to update email address ($sql): ".Zymurgy::$db->error());
								}
								//Try to update password
								if ($_POST['Fieldoldpass'] != '')
								{
									if (Zymurgy::$member['password']!=$_POST['Fieldoldpass'])
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
										$sql = "update zcm_member set password='".Zymurgy::$db->escape_string($_POST['Fieldpass']).
											"' where id=".Zymurgy::$member['id'];
										Zymurgy::$db->query($sql) or die("Unable to update password ($sql): ".Zymurgy::$db->error());
									}
								}
								//If validation errors try to return record to it's old email.  Password should only set if all is well.
								if (count($pi->ValidationErrors)>0)
								{
									$sql = "update zcm_member set email='".Zymurgy::$db->escape_string(Zymurgy::$member['email']).
										"' where id=".Zymurgy::$member['id'];
									Zymurgy::$db->query($sql) or die("Unable to restore email ($sql): ".Zymurgy::$db->error());
								}
							}
							$r[] = $pi->Render();
							if (count($pi->ValidationErrors)==0)
							{
								$formid = Zymurgy::$db->insert_id();
								if ($formid)
								{
									$sql = "update zcm_member set formdata=$formid where id=".Zymurgy::$member['id'];
									Zymurgy::$db->query($sql) or die("Can't set form data ($sql): ".Zymurgy::$db->error());
								}
								//return implode("\r\n",$r);
								Zymurgy::JSRedirect($_GET['rurl']);
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
					if (Zymurgy::memberdologin($_POST['email'],$_POST['pass']))
					{
						//Redirect to source page or root page if none provided.
						if (array_key_exists('rurl',$_GET))
							$rurl = $_GET['rurl'];
						else 
							$rurl = Zymurgy::$config['MemberDefaultPage'];
						Zymurgy::JSRedirect($rurl);
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
						$r[] = Zymurgy::$config['MembershipUsernameForm'];
						return implode("\r\n",$r);
					case 'extra':
						//May also confirm email from step one.
						memberpage();
						$pi = mkplugin('Form',Zymurgy::$config['MembershipInfoForm']);
						$pi->LoadInputData();
						if ($zcm_member['formdata'])
						{
							$sql = "select formvalues from formcapture where id=".Zymurgy::$member['formdata'];
							$ri = Zymurgy::$db->query($sql) or die("Unable to load form data ($sql): ".Zymurgy::$db->error());
							$pi->XmlValues = Zymurgy::$db->result($ri,0,0);
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
			if (!array_key_exists('MembershipLoginForm',Zymurgy::$config))
			{
				die("Please define \$ZymurgyConfig['MembershipLoginForm'] before using membership features.");
			}
			$r[] = Zymurgy::$config['MembershipLoginForm'];
			return implode("\r\n",$r);
		}
		
		/**
		 * Get PHPMailer object pre-configured with settings from the Zymurgy:CM config file.
		 *
		 * @return PHPMailer
		 */
		static function GetPHPMailer()
		{
			require_once(Zymurgy::$root."/zymurgy/phpmailer/class.phpmailer.php");
			$mail = new PHPMailer();
			$mail->Mailer = array_key_exists('Mailer Type',Zymurgy::$config) ? Zymurgy::$config['Mailer Type'] : 'mail';
			if ($mail->Mailer == 'smtp')
			{
				$mail->Host = Zymurgy::$config['Mailer SMTP Hosts'];
			}
			$ip = $_SERVER['REMOTE_ADDR'];
			if (array_key_exists('X_FORWARDED_FOR',$_SERVER))
			{
				$ip .= " forwarding for ".$_SERVER['X_FORWARDED_FOR'];
			}
			$mail->AddCustomHeader("X-WebmailSrc: $ip");
			return $mail;
		}
		
		static function MakeThumbs(
			$datacolumn,
			$id,
			$targets,
			$uploadpath = '')
		{
			global $ZymurgyRoot, $ZymurgyConfig;
			
			@mkdir("$ZymurgyRoot/UserFiles/DataGrid");
			$thumbdest = "$ZymurgyRoot/UserFiles/DataGrid/$datacolumn";
			@mkdir($thumbdest);
			
			$rawimage = "$thumbdest/{$id}raw.jpg";
			
			if ($uploadpath!=='')
				move_uploaded_file($uploadpath,$rawimage);
				
			if ((function_exists('mime_content_type')) && (mime_content_type($rawimage)!='image/jpeg'))
			{
				//Supplied image isn't a jpeg.  Convert raw into one (best effort!).
				system("{$ZymurgyConfig['ConvertPath']}convert $rawimage $thumbdest/{$id}jpg.jpg");
				rename("$thumbdest/{$id}jpg.jpg",$rawimage);
			}
			
			require_once("$ZymurgyRoot/zymurgy/include/Thumb.php");
			
			//echo "[Targets: "; print_r($targets); echo "]";
			foreach($targets as $targetsizes)
			{
				$targetsizes = explode(',',$targetsizes);
				
				foreach ($targetsizes as $targetsize)
				{
					$dimensions = explode('x',$targetsize);
					Thumb::MakeFixedThumb($dimensions[0],$dimensions[1],$rawimage,"$thumbdest/{$id}thumb$targetsize.jpg");
				}
			}
			
			Thumb::MakeQuickThumb(640,480,$rawimage,"$thumbdest/{$id}aspectcropNormal.jpg");
			
			system("{$ZymurgyConfig['ConvertPath']}convert -modulate 75 $thumbdest/{$id}aspectcropNormal.jpg $thumbdest/{$id}aspectcropDark.jpg");
		}
		
		
		
		
	}

	if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
		Zymurgy::$root = $_SERVER["APPL_PHYSICAL_PATH"];
	else 
		Zymurgy::$root = $_SERVER['DOCUMENT_ROOT'];
	
	Zymurgy::$build = 1986;
	include(Zymurgy::$root."/zymurgy/config/config.php");
	Zymurgy::$config = $ZymurgyConfig;
	unset($ZymurgyConfig);
	
	if ((array_key_exists('FixSlashes',Zymurgy::$config)) && (Zymurgy::$config['FixSlashes']) && (get_magic_quotes_gpc())) {
	   $_POST = array_map('Zymurgy::stripslashes_deep', $_POST);
	   $_GET = array_map('Zymurgy::stripslashes_deep', $_GET);
	   $_COOKIE = array_map('Zymurgy::stripslashes_deep', $_COOKIE);
	}
	
	if (file_exists(Zymurgy::$root."/zymurgy/custom/render.php"))
		include_once(Zymurgy::$root."/zymurgy/custom/render.php");
	if (file_exists(Zymurgy::$root."/caseo/custom/render.php"))
		include_once(Zymurgy::$root."/caseo/custom/render.php");
	require_once(Zymurgy::$root."/zymurgy/InputWidget.php");
	
	if (empty(Zymurgy::$config['database']))
	{
		Zymurgy::$config['database'] = 'mysql';
	}
	
	require_once(Zymurgy::$root."/zymurgy/db/".Zymurgy::$config['database'].".php");
	Zymurgy::$db = new Zymurgy_DB();
	
	Zymurgy::$userconfig = array();
	$ri = Zymurgy::$db->query("select * from zcm_config order by disporder");
	if ($ri)
	{
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			Zymurgy::$userconfig[$row['name']] = $row['value'];
		}
		Zymurgy::$db->free_result($ri);
	} //Else this is init so we don't care.
}
?>
