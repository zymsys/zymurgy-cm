<?
require_once("include/payment.php");
require_once("include/l10n.php");

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
		 * User supplied site config values from within the Zymurgy:CM control panel front-end
		 *
		 * @var array
		 */
		static $userconfigid;

		/**
		 * If this is a template page, this contains info about this template, and template instance.
		 *
		 * @var ZymurgyTemplate
		 */
		static $template;

		/**
		 * Site Color Theme cache.
		 *
		 * @var unknown_type
		 */
		static $colorThemes = array();

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
		 * Image handler for resizing images
		 *
		 * @var ZymurgyImageHandler
		 */
		static $imagehandler;

		/**
		 * If set to true, included YUI assets will be stripped of '-min' to facilitate debugging.
		 *
		 * @var boolean
		 */
		public static $yuitest = false;

		/**
		 * Array of loaded YUI javascript & css files
		 *
		 * @var array
		 */
		private static $yuiloaded = array();

		/**
		 * Array of loaded non-YUI javascript & css files
		 *
		 * @var array
		 */
		private static $otherloaded = array();

		/**
		 * Instance of a ZymurgyMember class, or a decendent which provides membership features
		 *
		 * @var ZymurgyMember
		 */
		private static $MemberProvider = null;

		/**
		 * Member made public to allow Zymurgy::sitetext() to be called without calling
		 * Zymurgy::headtags() first. This allows pages based on AJAX calls (that contain
		 * incomplete HTML) to work properly.
		 *
		 * @var int
		 */
		public static $pageid;

		private static $title;

		public static $ThemeColor = array(
			"Header Background" => 1,
			"Menu Background" => 2,
			"Menu Highlight" => 3,
			"Page Background" => 4,
			"Text Color" => 5,
			"Link Color" => 6
		);

		public static $defaulttheme = null;

		/**
		 * Array of the locale objects used for localization support.
		 *
		 * @var Locale[]
		 */
		public static $Locales = array();

		/**
		 * Provides the base functionality for both RequireOnce() and YUI() methods.
		 *
		 * @param boolean $isYUI
		 * @param string $src
		 * @return string
		 */
		private static function RequireOnceCore($isYUI,$src)
		{
			if ($isYUI)
				$included = (array_key_exists($src,Zymurgy::$yuiloaded));
			else
				$included = (array_key_exists($src,Zymurgy::$otherloaded));
			if ($included)
				return '';
				//return "<!-- $src is already loaded. -->"; //Already loaded
			if ($isYUI)
			{
				Zymurgy::$yuiloaded[$src]='';
				$baseurl = Zymurgy::YUIBaseURL();
			}
			else
			{
				Zymurgy::$otherloaded[$src]='';
				$baseurl = '';
			}
			$sp = explode('.',$src);
			$ext = strtolower(array_pop($sp));
			switch($ext)
			{
				case 'js':
					if ($isYUI && Zymurgy::$yuitest)
						$src = str_replace('-min.js','-debug.js',$src); //Scrub -min for testing YUI
					return "<script type=\"text/javascript\" src=\"".$baseurl."$src\"></script>\r\n";
				case 'css':
					return "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$baseurl."$src\" />\r\n";
				default:
					return "<!-- Request for non supported resource: $src -->\r\n";
			}
		}

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
			$r = array();
			if (Zymurgy::$yuitest)
			{
				//YUI in debug mode requires logger, which in turn requires the rest of this list.
				$r[] = Zymurgy::RequireOnceCore(true,'logger/assets/skins/sam/logger.css');
				$r[] = Zymurgy::RequireOnceCore(true,'yahoo-dom-event/yahoo-dom-event.js');
				$r[] = Zymurgy::RequireOnceCore(true,'dragdrop/dragdrop-min.js');
				$r[] = Zymurgy::RequireOnceCore(true,'logger/logger-min.js');
			}
			$r[] = Zymurgy::RequireOnceCore(true,$src);
			return implode($r);
		}

		/**
		 * Return javascript or CSS tags to load the supplied source file if it has not already been loaded by this method.
		 *
		 * @param string $src
		 * @return string
		 */
		static function RequireOnce($src)
		{
			return Zymurgy::RequireOnceCore(false,$src);
		}

		/**
		 * Defines the base URL for the YUI framework.
		 *
		 * @return The base URL for the YUI framework.
		 */

		static function YUIBaseURL()
		{
			return "http://yui.yahooapis.com/2.7.0/build/";
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
			$sql = "select id from zcm_customtable where tname='".
				Zymurgy::$db->escape_string($tableName)."'";
			$ri = Zymurgy::$db->query($sql) or die("Can't get table info ($sql): ".Zymurgy::$db->error());
			$row = Zymurgy::$db->fetch_array($ri,MYSQL_ASSOC) or die("Table $tableName doesn't exist.");
			$tid = $row['id'];
			Zymurgy::$db->free_result($ri);
			$sql = "select * from zcm_customtable where detailfor=$tid";
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
					$ext = Thumb::mime2ext($row['body']);
					$thumbName = "Zymurgy::$root/UserFiles/DataGrid/sitetext.body/{$row['id']}thumb$requestedSize.$ext";
					if (!file_exists($thumbName))
					{
						require_once("Zymurgy::$root/zymurgy/include/Thumb.php");
						$dimensions = explode('x',$requestedSize);
						$rawimage = "Zymurgy::$root/UserFiles/DataGrid/sitetext.body/{$row['id']}raw.$ext";
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
		 * @param $ispage boolean Is this a page in the context of the site?  CSS for example is not a page.
		 *
		 * @return string
		 */
		static function headtags($ispage = true)
		{
			if (file_exists(Zymurgy::$root."/zymurgy/custom/render.php"))
				include_once(Zymurgy::$root."/zymurgy/custom/render.php");
			if (file_exists(Zymurgy::$root."/caseo/custom/render.php"))
				include_once(Zymurgy::$root."/caseo/custom/render.php");
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
			if (array_key_exists('zymurgy',$_COOKIE))
				$r[] = Zymurgy::adminhead();
			$r[] = trim(Zymurgy::RequireOnce('/zymurgy/include/cmo.js'));
			if (array_key_exists('tracking',Zymurgy::$config) && (Zymurgy::$config['tracking']))
			{
				//Log the pageview
				if (array_key_exists('zcmtracking',$_COOKIE))
				{
					$orphan = 0;
					$userid = $_COOKIE['zcmtracking'];
				}
				else
				{
					$orphan = 1;
					$userid = uniqid(true);
				}
				$sql = "insert into zcm_pageview (trackingid,pageid,orphan,viewtime) values ('$userid',".
					Zymurgy::$pageid.",$orphan,now())";
				Zymurgy::$db->query($sql);
				//Send tracking javascript
				$r[] = "<script>";
				if (!empty($_SERVER['HTTP_REFERER']))
				{
					$r[] = "if (!document.referrer) document.referrer = \"".addslashes($_SERVER['HTTP_REFERER'])."\";";
				}
				$r[] = "Zymurgy.track('$userid');
					</script>";
			}
			$r = implode("\r\n",$r)."\r\n";
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
			//echo $sql;
			$ri = Zymurgy::$db->query($sql);
			if (!$ri)
			{
				die ("Error loading [plugin: $plugin] [instance $instance]: ".Zymurgy::$db->error()."<br>$sql");
			}
			$row = Zymurgy::$db->fetch_array($ri);
			$pi->extra = $extra;
			$pi->InstanceName = $instance;
			//echo "[".$pi->GetRelease().",{$pi->dbrelease}]"; exit;
			if ($row !== false)
			{
				$pi->pid = $row['pid'];
				$pi->iid = $row['pii'];
				$pi->dbrelease = $row['release'];
				if ($pi->GetRelease() > $pi->dbrelease) $pi->Upgrade();
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
		 * Emit javascript to redirect the user to the supplied URL.  Aborts the running script/page.
		 *
		 * @param string $url
		 */
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

		/**
		 * Emit javascript to set the innerHTML of the privided element (by ID) to the supplied HTML content.
		 *
		 * @param string $id
		 * @param string $html
		 */
		static function JSInnerHtml($id,$html)
		{
			echo "<script type=\"text/JavaScript\">
			var Zymurgy_InnerHTML = document.getElementById('".addslashes($id)."');
			Zymurgy_InnerHTML.innerHTML = '".addslashes($html)."';
			</script>";
			flush();
		}

		/**
		 * Load membership subsystem and any configured membership provider
		 *
		 */
		function initializemembership()
		{
			if (Zymurgy::$MemberProvider == null)
			{
				//Initialize membership provider
				require_once 'member.php';
				if (empty(Zymurgy::$config['MemberProvider']))
				{
					Zymurgy::$MemberProvider = new ZymurgyMember();
				}
				else
				{
					require_once(Zymurgy::$root."/zymurgy/memberp/".Zymurgy::$config['MemberProvider'].".php");
					Zymurgy::$MemberProvider = new Zymurgy::$config['MemberProvider'];
				}
			}
		}

		/**
		 * Is member authenticated?  If yes then loads auth info into global $member array.
		 *
		 * @return boolean
		 */
		static function memberauthenticate()
		{
			Zymurgy::initializemembership();
			return Zymurgy::$MemberProvider->memberauthenticate();
		}

		/**
		 * Is member authorized (by group name) to view this page?
		 *
		 * @param string $groupname
		 * @return boolean
		 */
		static function memberauthorize($groupname)
		{
			Zymurgy::initializemembership();
			return Zymurgy::$MemberProvider->memberauthorize($groupname);
		}

		/**
		 * Log member activity
		 *
		 * @param unknown_type $activity
		 */
		static function memberaudit($activity)
		{
			Zymurgy::initializemembership();
			Zymurgy::$MemberProvider->memberaudit($activity);
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
			Zymurgy::initializemembership();
			Zymurgy::$MemberProvider->memberpage($groupname);
		}

		/**
		 * Attempt to log into the membership system with the provided user ID and password.  Returns true
		 * if the login was successful or false if it was not.
		 *
		 * @param string $userid
		 * @param string $password
		 * @return boolean
		 */
		static function memberdologin($userid, $password)
		{
			Zymurgy::initializemembership();
			return Zymurgy::$MemberProvider->memberdologin($userid,$password);
		}

		/**
		 * Clear existing credentials and go to the supplied URL.
		 *
		 * @param string $logoutpage
		 */
		static function memberlogout($logoutpage)
		{
			Zymurgy::initializemembership();
			Zymurgy::$MemberProvider->memberlogout($logoutpage);
		}

		/**
		 * Handle new signups.  Takes a form (from the Form plugin), the field names for the user ID, password and password confirmation,
		 * and the link to send users to after registration.  Returns UI HTML.
		 *
		 * @param string $formname
		 * @param string $useridfield
		 * @param string $passwordfield
		 * @param string $confirmfield
		 * @param string $redirect
		 * @return string
		 */
		static function membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect)
		{
			Zymurgy::initializemembership();

			// die("membersignup: ".gettype(Zymurgy::$MemberProvider));

			return Zymurgy::$MemberProvider->membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect);
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
			// die("memberlogin: ".gettype(Zymurgy::$MemberProvider));
			// die(var_dump(debug_backtrace()));

			Zymurgy::initializemembership();
			return Zymurgy::$MemberProvider->memberlogin();
		}

		/**
		 * Render data entry form for user data using the navigation name for the Custom Table used for user data.
		 *
		 * @param string $navname
		 * @param string $exitpage
		 */
		static function memberform($navname, $exitpage)
		{
			Zymurgy::initializemembership();
			return Zymurgy::$MemberProvider->memberform($navname,$exitpage);
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

		/**
		 * Make thumbnails from an uploaded file.  $targets is an array of csv strings, each of which contains WIDTHxHIEGHT values.
		 *
		 * @param string $datacolumn
		 * @param integer $id
		 * @param array $targets
		 * @param string $uploadpath
		 */
		static function MakeThumbs(
			$datacolumn,
			$id,
			$targets,
			$uploadpath = '',
			$ext = 'jpg')
		{
			require_once(Zymurgy::$root."/zymurgy/include/Thumb.php");
			Thumb::MakeThumbs($datacolumn,$id,$targets,$uploadpath,$ext);
		}

		/**
		 * Return a color value in #RRGGBB format, as set in a color theme.
		 *
		 * The standard theme color indexes are:
		 *  - Header Background
		 *  - Menu Background
		 *  - Menu Highlight
		 *  - Page Background
		 *  - Text Color
		 *  - Link Color
		 *
		 * @param mixed $index The index of the color within the theme, either as a
		 * number, or as a text value set in the $ThemeColor static array.
		 * @param string $theme The theme definition, either as a comma-delimited list
		 * of color values, or as a reference to an item in the site config.
		 * @return The color value.
		 */
		static function theme(
			$index,
			$theme = null)
		{
			if (is_null($theme))
			{
				$theme = Zymurgy::$defaulttheme;
			}
			// If the index was passed in as text, convert it to the numeric index
			// based on the $ThemeColor static array.
			if(!is_numeric($index))
			{
				$index = Zymurgy::$ThemeColor[$index];
			}

			// If the theme was passed in hard-coded, use it as-is. Otherwise,
			// assume that the theme is actually a reference to an item in
			// the site config.
			if(substr($theme, 0, 1) !== "#")
			{
				$theme = Zymurgy::$userconfig[$theme];
			}

			// If the theme is being used for the first time, add it to the
			// look-up cache. This cache is used to prevent the explode()
			// method from running too often.
			if(!array_key_exists($theme, Zymurgy::$colorThemes))
			{
				Zymurgy::$colorThemes[$theme] = explode(",", $theme);
			}

			$color = Zymurgy::$colorThemes[$theme][$index];

			return substr($color, 1);
		}

		function sitenav($ishorizontal = true, $currentleveonly = false, $childlevelsonly = false, $startpath = '',$baseurl = 'pages')
		{
			require_once('sitenav.php');
			$nav = new ZymurgySiteNav();
			$nav->render($ishorizontal, $currentleveonly, $childlevelsonly, $startpath, $baseurl);
		}

		static function pagetext($tag,$type='html.600.400')
		{
			if (isset(Zymurgy::$template))
			{
				return Zymurgy::$template->pagetext($tag,$type);
			}
			else
			{
				return "<div>This page is not linked to a template, so pagetext() can't be used here.</div>";
			}
		}

		static function pageimage($tag,$width,$height,$alt='')
		{
			if (isset(Zymurgy::$template))
			{
				return Zymurgy::$template->pageimage($tag,$width,$height,$alt);
			}
			else
			{
				return "<div>This page is not linked to a template, so pageimage() can't be used here.</div>";
			}
		}

		function pagegadgets()
		{
			if (isset(Zymurgy::$template))
			{
				return Zymurgy::$template->pagegadgets();
			}
			else
			{
				return "<div>This page is not linked to a template, so pagegadgets() can't be used here.</div>";
			}
		}

		function Config($keyname, $defaultvalue, $inputspec='input.30.30')
		{
			if (!array_key_exists($keyname,Zymurgy::$userconfig))
			{
				Zymurgy::$db->run("insert into zcm_config (name,value,inputspec) values ('".
					Zymurgy::$db->escape_string($keyname)."','".
					Zymurgy::$db->escape_string($defaultvalue)."','".
					Zymurgy::$db->escape_string($inputspec)."')");
				$id = Zymurgy::$db->insert_id();
				Zymurgy::$db->run("update zcm_config set disporder=$id where id=$id");
				Zymurgy::$userconfig[$keyname] = $defaultvalue;
				Zymurgy::$userconfigid[$keyname] = $id;
				if (($inputspec=='theme') && (is_null(Zymurgy::$defaulttheme)))
					Zymurgy::$defaulttheme = $keyname;
			}
			return Zymurgy::$userconfig[$keyname];
		}

		/**
		 * Get the string from the locale XML file.
		 *
		 * @param string $key
		 * @return string
		 */
		function GetLocaleString($key)
		{
			// ZK: The locale is hard-coded to English for now, as it's the only
			// language Zymurgy:CM currently supports on the back-end.
			// TODO Read the locale from the session.

			return Zymurgy::$Locales["en"]->GetString($key);
		}
	}

	if (array_key_exists("APPL_PHYSICAL_PATH",$_SERVER))
		Zymurgy::$root = $_SERVER["APPL_PHYSICAL_PATH"];
	else
		Zymurgy::$root = $_SERVER['DOCUMENT_ROOT'];

	Zymurgy::$build = 1987;
	include(Zymurgy::$root."/zymurgy/config/config.php");
	Zymurgy::$config = $ZymurgyConfig;
	unset($ZymurgyConfig);

	if ((array_key_exists('FixSlashes',Zymurgy::$config)) && (Zymurgy::$config['FixSlashes']) && (get_magic_quotes_gpc())) {
	   $_POST = array_map('Zymurgy::stripslashes_deep', $_POST);
	   $_GET = array_map('Zymurgy::stripslashes_deep', $_GET);
	   $_COOKIE = array_map('Zymurgy::stripslashes_deep', $_COOKIE);
	}

	require_once(Zymurgy::$root."/zymurgy/InputWidget.php");

	//Initialize database provider
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
			Zymurgy::$userconfigid[$row['name']] = $row['id'];
			if (($row['inputspec']=='theme') && (is_null(Zymurgy::$defaulttheme)))
				Zymurgy::$defaulttheme = $row['name'];
		}
		Zymurgy::$db->free_result($ri);
	} //Else this is init so we don't care.

	switch (array_key_exists('ConvertToolset',Zymurgy::$config) ? Zymurgy::$config['ConvertToolset'] : 'ImageMagick')
	{
		case 'GD':
			require_once(Zymurgy::$root."/zymurgy/include/ImageHandlerGD.php");
			Zymurgy::$imagehandler = new ZymurgyImageHandlerGD();
			break;
		default:
			require_once(Zymurgy::$root."/zymurgy/include/ImageHandlerIM.php");
			Zymurgy::$imagehandler = new ZymurgyImageHandlerImageMagick();
			break;
	}

	if (array_key_exists('Debug',Zymurgy::$config) && (Zymurgy::$config['Debug'] > 0))
	{
		error_reporting(Zymurgy::$config['Debug']);
	}

	Zymurgy::$Locales = LocaleFactory::GetLocales();
}
?>
