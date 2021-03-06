<?
/**
 * Zymurgy class and initialization code
 *
 * @package Zymurgy
 * @subpackage base
 */

// ==========
// ZK: 2009.10.20
//
// PHP Version check.
// ==========

if (version_compare('5.3.0',PHP_VERSION) > 0)
{
    die("Zymurgy:CM requires PHP 5.3.0 or better.  Your server is running PHP ".
        PHP_VERSION.
        " which is not compatible.  Please upgrade your PHP software, or ".
        "upgrade your hosting.");
}

require_once("include/payment.php");
require_once("include/l10n.php");

// include guard
if (!class_exists('Zymurgy'))
{
    class ZymurgyRouter
    {
        private $gets = array();
        private $posts = array();
        private $puts = array();
        private $deletes = array();
        
        private function addroute(&$to,$uri,$action)
        {
            if (strlen($uri) == 0)
            {
                throw new Exception("Route URI can't be empty.", 0);
            }
            $uri = '/^'.preg_quote($uri,'/').'$/';
            $to[$uri] = $action;
        }
        
        private function addrouteregex(&$to,$pattern,$action)
        {
            if (strlen($pattern) == 0)
            {
                throw new Exception("Route regex can't be empty.", 0);
            }
            $to[$pattern] = $action;
        }
        
        /**
         * Register a new action for GET requests.
         * Actions are only taken when directed through template.php via mod_rewrite, and may
         * supercede flavour/page content that would otherwise be delivered.
         * 
         * @param string $uri
         * @param callback $action
         */
        public function get($uri,$action) { $this->addroute($this->gets, $uri, $action); }
        
        /**
         * Register a new action for POST requests.
         * Actions are only taken when directed through template.php via mod_rewrite, and may
         * supercede flavour/page content that would otherwise be delivered.
         * 
         * @param string $uri
         * @param callback $action
         */
        public function post($uri,$action) { $this->addroute($this->posts, $uri, $action); }
        
        /**
         * Register a new action for PUT requests.
         * Actions are only taken when directed through template.php via mod_rewrite, and may
         * supercede flavour/page content that would otherwise be delivered.
         * 
         * @param string $uri
         * @param callback $action
         */
        public function put($uri,$action) { $this->addroute($this->puts, $uri, $action); }
        
        /**
         * Register a new action for DELETE requests.
         * Actions are only taken when directed through template.php via mod_rewrite, and may
         * supercede flavour/page content that would otherwise be delivered.
         * 
         * @param string $uri
         * @param callback $action
         */
        public function delete($uri,$action) { $this->addroute($this->deletes, $uri, $action); }
        
        /**
         * Like get but takes a regex pattern
         * 
         * @param string $pattern
         * @param callback $action
         */
        public function getregex($pattern,$action) { $this->addrouteregex($this->gets, $pattern, $action); }
        
        /**
         * Like post but takes a regex pattern
         * 
         * @param string $pattern
         * @param callback $action
         */
        public function postregex($pattern,$action) { $this->addrouteregex($this->posts, $pattern, $action); }
        
        /**
         * Like put but takes a regex pattern
         * 
         * @param string $pattern
         * @param callback $action
         */
        public function putregex($pattern,$action) { $this->addrouteregex($this->puts, $pattern, $action); }
        
        /**
         * Like delete but takes a regex pattern
         * 
         * @param string $pattern
         * @param callback $action
         */
        public function deleteregex($pattern,$action) { $this->addrouteregex($this->deletes, $pattern, $action); }
        
        /**
         * Search known routes for the current request and take action if an action
         * is specified.  Returns true no futher processing of the URI is required.
         */
        function route()
        {
            switch ($_SERVER['REQUEST_METHOD'])
            {
                case 'POST':
                    $routes = $this->posts;
                    break;
                case 'PUT':
                    $routes = $this->puts;
                    break;
                case 'DELETE':
                    $routes = $this->deletes;
                    break;
                default:
                    $routes = $this->gets;
            }
            $uri = $_SERVER['REQUEST_URI'];
            $exploded = explode('?', $uri, 2);
            $uirnq = array_shift($exploded);
            foreach ($routes as $pattern=>$action)
            {
                if (preg_match($pattern, $uri) || preg_match($pattern, $uirnq))
                {
                    call_user_func($action);
                }
            }
        }
    }
    
    /**
     * This class provides the basic entry point for integrating Zymurgy:CM
      * functionality into your application.
      *
      * Example: To display Simple Content on your otherwise static PHP page,
      * use the following line:
      *
      * ZymurgyBase::sitetext("Content Name", "input.50.200");
     *
     * @package Zymurgy
     * @subpackage base
     */
    class ZymurgyBase
    {
        /**
         * Physical path to the site's root directory on the server.
         *
         * This is automatically initialized when this file is included.
         *
         * @var string
         */
        public static $root;

        /**
         * Path from the document root of the site to the Zymurgy:CM installation directory.
         *
         * This is automatically initialized when this file is included.
         *
         * @var string
         */
        public static $home;

        /**
         * A connection instance for database access.
         *
         * This is automatically initialized when this file is included. The
         * individual database provider classes are in the ~db folder.
         * The class loaded is set in the "database" parameter in
         * {@link config/config.php}.
         *
         * @var Zymurgy_DB
         * @link config/config.php
         */
        public static $db;
        
        /**
         * Instance of ZymurgyRouter used to redirect requests for MVC architectures
         * 
         * @var ZymurgyRouter
         */
        public static $router;
        
        /**
         * Cache for bookmark information
         * 
         * @var array
         */
        private static $bookmarks = array();

        /**
         * Array of config values from the config/config.php file.
         *
         * This is automatically initialized when this file is included. The
         * values in this array are determined by the contents of
         * {@link config/config.php}.
         *
         * @var array
         * @link config/config.php
         */
        public static $config;

        /**
         * User supplied site config values.
         *
         * This is automatically initialized when this file is included. The
         * values in this array are determined by the contents of the
         * zcm_config database table, and can be modified using the
         * Appearance and Webmaster > Appearance Items sections in Zymurgy:CM.
         *
         * @var array
         * @see Config
         */
        public static $userconfig;

        /**
         * List of keys set in the user supplied site config values.
         *
         * This is automatically initialized when this file is included. The
         * values in this array are determined by the contents of the
         * zcm_config database table, and can be modified using the
         * Appearance and Webmaster > Appearance Items sections in Zymurgy:CM.
         *
         * @var array
         */
        public static $userconfigid;

        /**
         * Instance of the ZymurgyTemplate class being used to render the page.
         * Only used if the page is contained within the Pages system.
         *
         * @var ZymurgyTemplate
         * @link template.php
         */
        public static $template;

        /**
         * Site Color Theme cache.
         *
         * @var unknown_type
         */
        public static $colorThemes = array();

        /**
         * Zymurgy:CM release number
         *
         * @var int
         * @access private
         */
        public static $build = 1987;

        /**
         * Instance of the ZymurgyMember class describing the member that is
         * currently logged in.
         *
         * @var array
         * @link member.php
         */
        public static $member;

        /**
         * Image handler for resizing images
         *
         * @var ZymurgyImageHandler
         */
        public static $imagehandler;

        /**
         * Flag used to include the debug versions of the Yahoo! User Interface
         * javascript files, instead of the smaller "minimized" versions.
         *
         * If set to true, included YUI assets will be stripped of '-min' to
         * facilitate debugging.
         *
         * @var boolean
         */
        public static $yuitest = false;

        /**
         * Array of loaded YUI Javascript & CSS files.
         *
         * Used by the ZymurgyBase::YUI method to ensure that multiple copies of
         * the YUI components are not loaded more than once on a page.
         *
         * @var array
         * @link ZymurgyBase::YUI
         */
        private static $yuiloaded = array();

        /**
         * Array of loaded non-YUI Javascript & CSS files.
         *
         * Used by the ZymurgyBase::RequireOnceCore method to ensure that multiple
         * copies of the requested component(s) are not loaded more than once
         * on a page.
         *
         * @var array
         * @link ZymurgyBase::RequireOnceCore
         */
        private static $otherloaded = array();

        /**
         * Instance of the ZymurgyMember class that functions as the member
         * provider for the site.
         *
         * This variable is set in the ZymurgyBase::initializemembership() method,
         * and the specific class loaded depends on the value of the
         * "MemberProvider" key in the {@link config/config.php} file.
         *
         * @var ZymurgyMember
         * @link config/config.php
         * @link ZymurgyBase::initializemembership
         */
        private static $MemberProvider = null;

        /**
         * Cache of ACL permissions for authenticated user
         * 
         * @var array
         */
        private static $acl;

        /**
         * Instance of the custom table tool for handling custom tables
         *
         * @var CustomTableTool
         */
        private static $_customTableTool;
        
        /**
         * ID of the page in the zcm_meta table.
         *
         * Member made public to allow ZymurgyBase::sitetext() to be called without
         * calling ZymurgyBase::headtags() first. This allows pages based on
         * AJAX calls (that contain incomplete HTML) to work properly.
         *
         * @var int
         * @link ZymurgyBase::sitetext
         * @link ZymurgyBase::headtags
         */
        public static $pageid;

        /**
         * Allows controllers to poke values into sitetext() and pagetext()
         * by calling settext()
         * 
         * @var array
         */
        protected static $controllertext = array();
        
        /**
         * Allows controllers to add fake gadget output
         * 
         * @var array
         */
        protected static $gadgettext = array('left'=>array(), 'center'=>array(), 'right'=>array());

        /**
         * List of user friendly names for the various color indices in the
         * theme input widget.
         *
         * @var array
         */
        public static $ThemeColor = array(
            "Header Background" => 1,
            "Menu Background" => 2,
            "Menu Highlight" => 3,
            "Page Background" => 4,
            "Text Color" => 5,
            "Link Color" => 6
        );

        /**
         * The default version of the theme input widget being used by the site.
         *
         * @var unknown_type
         */
        public static $defaulttheme = null;

        /**
         * Array of the locale objects used for localization support.
         *
         * @var Locale[]
         */
        public static $Locales = array();

        /**
         * Container for catalogues of data which may be stored within the Zymurgy container.
         * Two container arrays are created under $catalogue as defaults:
         *  - Vendors who wish to store generic catalogues here should so so under $catalogue['vendors']
         *  - Implementation specific catalogues should be placed under $catalogue['implementation']
         *
         * @var array
         */
        public static $catalogue = array();
        
        /**
         * Cache of remote lookup items. Used to avoid having to make multiple
         * requests for the same information to the same remote source.
         *
         * @var unknown_type
         */
        private static $remotelookupcache = array();

        /**
         * Array of available flavours
         * @var array
         */
        private static $m_flavours = array();

        /**
         * Same as m_flavours but indexed by code for easy lookup
         * @var array
         */
        private static $m_flavoursbycode = array();

        /**
         * The currently active flavour code
         * @var string
         */
        private static $m_activeFlavour;

        /**
         * Executes the supplied command with the USE_PATH environment variable as the path for execution.
         * Returns the output and other results as an associative array with keys command, output, return and result.
         *
         * @param $command
         * @return array
         */
        public static function exec($command)
        {
            if (getenv('USE_PATH'))
            {
                $command = 'PATH=' . getenv('USE_PATH') . ' ' . $command;
            }
            $out = array();
            $return = 0;
            $result = exec($command, $out, $return);
            return array(
                'command'=>$command,
                'output'=>$out,
                'return'=>$return,
                'result'=>$result,
            );
        }

        /**
         * Common functionality used by the RequireOnce() and YUI() methods
         * to include a file no more than once into the HTML source.
         *
         * @param boolean $isYUI True, if called by ZymurgyBase::YUI. Otherwise,
         * false.
         * @param string $src The location of the file to include.
         * @return string The complete <script> or <link> tag required to
         * include the file into the HTML source.
         */
        protected static function RequireOnceCore($isYUI,$src)
        {
            if ($isYUI)
                $included = (array_key_exists($src,self::$yuiloaded));
            else
                $included = (array_key_exists($src,self::$otherloaded));
            if ($included)
                return '';
                //return "<!-- $src is already loaded. -->"; //Already loaded
            if ($isYUI)
            {
                self::$yuiloaded[$src]='';
                $baseurl = Zymurgy::YUIBaseURL();
            }
            else
            {
                self::$otherloaded[$src]='';
                $baseurl = '';
            }
            $sp = explode('.',$src);
            $ext = strtolower(array_pop($sp));
            switch($ext)
            {
                case 'js':
                    if ($isYUI && Zymurgy::$yuitest)
                        $src = str_replace('-min.js','-debug.js',$src); //Scrub -min for testing YUI
                    return "    <script src=\"".$baseurl."$src\"></script>\r\n";
                case 'ts':
                    $destPath = implode(DIRECTORY_SEPARATOR, array(Zymurgy::$root, 'UserFiles', 'js',
                        substr($src, 0, -2))) . "js";
                    $srcPath = implode(DIRECTORY_SEPARATOR, array(Zymurgy::$root, $src));
                    if (!file_exists($destPath) || (filemtime($destPath) < filemtime($srcPath)))
                    {
                        if (file_exists($destPath))
                        {
                            unlink($destPath);
                        }
                        @mkdir(dirname($destPath),0777,true);
                        $command = "/usr/local/bin/tsc -c --out {$destPath} {$srcPath}";
                        Zymurgy::exec($command);
                    }
                    return "\t<script src=\"/UserFiles/js/" . substr($src, 0, -2) . "js\"></script>\r\n";
                case 'css':
                    return "    <link rel=\"stylesheet\" type=\"text/css\" href=\"".$baseurl."$src\" />\r\n";
                case 'less':
                    return "    <link rel=\"stylesheet\" type=\"text/css\" href=\"" .
                        Zymurgy::getUrlPath("~lesscss.php?src=") . urlencode($baseurl.$src)."\" />\r\n";
                default:
                    return "    <!-- Request for non supported resource: $src -->\r\n";
            }
        }

        /**
         * Return javascript or CSS tags to load the supplied YUI source file
         * if it has not already been loaded by this method.
         *
         * Adds "http://yui.yahooapis.com/{version}/build/" to the start of src
         * to keep the YUI version consistant.  The version number loaded by
         * YUI will be updated in future releases.
         *
         * @param string $src The path of the YUI component to include,
         * relative to the YUI base path (with version number).
         * @return string The complete <script> or <link> tag required to
         * include the file into the HTML source.
         */
        public static function YUI($src)
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
         * Create an instance of the YUI LogReader widget. Used when running
         * YUI in debug mode.
         */
        public static function YUILogger()
        {
            if (Zymurgy::$yuitest)
            {
                ?>
                <script>
                var myLogReader = new YAHOO.widget.LogReader();
                </script>
                <?
            }
        }

        /**
         * Return javascript or CSS tags to load the supplied source file if it
         * has not already been loaded by this method.
         *
         * @param string $src
         * @return string
         */
        public static function RequireOnce($src)
        {
            return Zymurgy::RequireOnceCore(false,$src);
        }

        /**
         * Defines the base URL for the YUI framework.
         *
         * @return The base URL for the YUI framework.
         */
        public static function YUIBaseURL()
        {
            if (array_key_exists('yuibaseurl',Zymurgy::$config) && !empty(Zymurgy::$config['yuibaseurl']))
                return Zymurgy::$config['yuibaseurl'];
            else
                return "http://yui.yahooapis.com/2.8.0r4/build/";
        }
        
        /**
         * Includes jQuery from the Google CDN
         * @param string $version jQuery version requested 
         */
        public static function jQuery($version = "1.5")
        {
            return Zymurgy::RequireOnceCore(false,"http://ajax.googleapis.com/ajax/libs/jquery/$version/jquery.min.js");
        }

        /**
         * Includes jQueryUI from the Google CDN
         * @param string $version jQueryUI version requested 
         */
        public static function jQueryUI($version = "1.8")
        {
            return
                Zymurgy::RequireOnceCore(false, "http://ajax.googleapis.com/ajax/libs/jqueryui/$version/themes/base/jquery-ui.css"). 
                Zymurgy::RequireOnceCore(false,"http://ajax.googleapis.com/ajax/libs/jqueryui/$version/jquery-ui.min.js");
        }

        public static function getFilePath($path)
        {
            if ($path[0] == '~')
            {
                $path = __DIR__ . DIRECTORY_SEPARATOR . substr($path, 1);
            }
            return $path;
        }

        public static function getUrlPath($path)
        {
            if ($path[0] == '~')
            {
                if (!isset(Zymurgy::$home))
                {
                    $rootPath = isset($_SERVER['APPL_PHYSICAL_PATH']) ?
                        $_SERVER['APPL_PHYSICAL_PATH'] : $_SERVER['DOCUMENT_ROOT'];
                    $docRoot = explode(DIRECTORY_SEPARATOR, $rootPath);
                    $myPath = explode(DIRECTORY_SEPARATOR, __DIR__);
                    while ($docRoot && $myPath && ($docRoot[0] == $myPath[0]))
                    {
                        array_shift($docRoot);
                        array_shift($myPath);
                    }
                    Zymurgy::$home = '/' . implode('/', $myPath) . '/';
                }
                $path = Zymurgy::$home . substr($path, 1);
            }
            return $path;
        }

        /**
         * Look up a bookmark and return the URI to it.  Bookmarks are so that if your users change the names of
         * links (and the URI with them) you're app can still get the current valid URI for that page.  Because
         * the flavour is part of the URI the calling script must know its flavour to use this function.
         * 
         * @param string $name
         */
        public static function bookmark($name, $forFlavour = null)
        {
            if (is_null($forFlavour))
            {
                $forFlavour = self::GetActiveFlavourCode();
            }
            if (!isset(self::$bookmarks[$forFlavour])) self::$bookmarks[$forFlavour] = array();
            if (!isset(self::$bookmarks[$forFlavour][$name]))
            {
                self::$bookmarks[$forFlavour][$name] = false;
                $page = Zymurgy::$db->get("SELECT * FROM `zcm_sitepage` WHERE `bookmark`='".
                    Zymurgy::$db->escape_string($name)."'");
                if ($page !== false)
                {
                    $parts = array(ZIW_Base::GetFlavouredValue($page['linkurl'], $forFlavour));
                    while ($page['parent'] > 0) 
                    {
                        $page = Zymurgy::$db->get("SELECT * FROM `zcm_sitepage` WHERE `id`=".$page['parent']);
                        if ($page === false)
                        { //Orphaned bookmark!  Shouldn't happen.  Return false as if the bookmark doesn't exist.
                            return false;
                        }
                        array_unshift($parts, ZIW_Base::GetFlavouredValue($page['linkurl'], $forFlavour));
                    }
                    self::$bookmarks[$forFlavour][$name] = '/'. $forFlavour .'/'.implode('/', $parts);
                }
            }
            return self::$bookmarks[$forFlavour][$name];
        }

        /**
         * Given a table name from Custom Tables, output XML for the table's
         * contents and all detail tables.
         *
         * Used primarily to provide the table contents, with properly
         * associated child contents, to an Adobe Flash file for processing.
         *
         * @param string $rootName Name of root XML node
         * @param string $tableName Name of the custom table to render to XML
         */
        public static function easyXML($rootName,$tableName)
        {
            header('Content-type: text/xml');
            echo "<$rootName>\r\n";
            if (!is_array($tableName)) $tableName = array($tableName);
            foreach($tableName as $tname)
            {
                Zymurgy::buildEasyXML($tname);
            }
            echo "</$rootName>\r\n";
        }

        /**
         * Converts the specified value into properly formatted XML text. If
         * the content cannot be converted to XML text (HTML, etc.), this
         * method also properly wraps the content in a CDATA block.
         *
         * @param string $value
         * @return string
         */
        protected static function XMLvalue($value)
        {
            $sc = htmlspecialchars($value);
            if ($sc == $value)
                return $value;
            else
                return "<![CDATA[$value]]>";
        }

        /**
         * Creates a properly formatted XML block for the specified table,
         * returning all records associated with the given parent ID.
         *
         * @param string $tableName Name of the parent table.
         * @param string $detailTable Name of the detail table. If not
         * provided, all of the records in the table specified in $tableName
         * will be returned instead.
         * @param int $parentID The ID of the parent record associated with the
         * detail records to return. If not provided, or if provided as 0, all
         * records will be returned instead.
         * @param int $level The number of times to indent the returned XML
         * content.
         */
        protected static function buildEasyXML(
            $tableName,
            $detailTable = '',
            $parentID = 0,
            $level = 1)
        {
            //Get meta data about this table, especially detail tables.
            $sql = "select id,hasdisporder from zcm_customtable where tname='".
                Zymurgy::$db->escape_string($tableName)."'";
            $ri = Zymurgy::$db->query($sql) or die("Can't get table info ($sql): ".Zymurgy::$db->error());
            $row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC) or die("Table $tableName doesn't exist.");
            $tid = $row['id'];
            $hasdisporder = ($row['hasdisporder'] == 1);
            Zymurgy::$db->free_result($ri);
            $sql = "select * from zcm_customtable where detailfor=$tid";
            $ri = Zymurgy::$db->query($sql) or die("Can't get table detail info ($sql): ".Zymurgy::$db->error());
            $details = array();
            while(($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
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
            if ($hasdisporder)
            {
                $sql .= " order by disporder";
            }
            $ri = Zymurgy::$db->query($sql) or die("Can't get XML data ($sql): ".Zymurgy::$db->error());
            while(($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
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

        /**
         * Get an instance of the CustomTableTool for working with custom tables
         * @static
         * @return CustomTableTool
         */
        public static function customTableTool()
        {
            require_once self::getFilePath("~customlib.php");
            if (!isset(self::$_customTableTool))
            {
                self::$_customTableTool = new CustomTableTool();
            }
            return self::$_customTableTool;
        }

        /**
         * Properly strip the slashes from the provided text. Some PHP
         * implementations implement stripslashes() differently, and this
         * function behaves consistently across all of these implementations.
         *
         * @param string $value
         * @return string
         */
        public static function stripslashes_deep($value)
        {
            $value = is_array($value) ?
                array_map('Zymurgy::stripslashes_deep', $value) :
                stripslashes($value);

            return $value;
        }
        
        /**
         * Check the ACL for a permission for the logged in user.  Return the provided
         * default permission if the user doesn't have the ACL at all, or if the user is
         * not logged in.
         * 
         * @param string $bywhat (id or name)
         * @param mixed $acl (int if bywhat is id, string if bywhat is name)
         * @param string $permission (Read, Write or Delete)
         * @param boolean $default
         */
        protected static function checkaclby($bywhat, $acl, $permission, $default = null)
        {
            if ($default === null)
            { //Set default default for permission type
                $default = ($permission == 'Read'); //Read permission defaults to true, everything else defaults to false.
            }
            if (($acl === 0) || empty($acl)) 
            { //Either we have no ACL or no authenticated member, so just return the default permission for this request.
                return $default;
            }
            //Ensure $member is initialized if possible
            Zymurgy::memberauthenticate();
            Zymurgy::memberauthorize("");
            if (!isset(Zymurgy::$member))
            { //An ACL was specified but the user is not logged in.  Always return false.
                return false;
            }
            if (!isset(self::$acl))
            { //Build ACL cache for this user
                self::$acl = array(
                    'byname' => array(),
                    'byid' => array()
                );
                $ri = Zymurgy::$db->run("SELECT `zcm_acl`.`id` AS `aclid`, `zcm_acl`.`name`, `zcm_aclitem`.`permission` FROM `zcm_acl` LEFT JOIN `zcm_aclitem` ON `zcm_acl`.`id` = `zcm_aclitem`.`zcm_acl` WHERE `group` IN (".
                    implode(',', array_keys(Zymurgy::$member['groups'])).")");
                while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
                {
                    if (!array_key_exists($row['aclid'], self::$acl['byid']))
                    {
                        self::$acl['byname'][$row['name']] = array();
                        self::$acl['byid'][$row['aclid']] = array();
                    }
                    self::$acl['byname'][$row['name']][$row['permission']] = true;
                    self::$acl['byid'][$row['aclid']][$row['permission']] = true;
                }
                Zymurgy::$db->free_result($ri);
            }
            if (array_key_exists($acl, self::$acl["by$bywhat"]))
            { //We have an entry for this ACL
                if (array_key_exists($permission, self::$acl["by$bywhat"][$acl]))
                    return true; //We have the requested permission
            }
            return $default;
        }
        
        
        /**
         * Read from a table, honoring any ACL checks that have been defined for that table.
         * If the table doesn't have an ACL then no rights are allowed. 
         * 
         * @param string $table
         * @param mixed $rowid
         * @return array (or false on error)
         */
        public static function table_read($table,$rowid = false)
        {
            require_once Zymurgy::getFilePath("~model.php");
            $m = ZymurgyModel::factory($table);
            return $m->read($rowid);
        }
        
        public static function table_read_filtered($table,$filter)
        {
            require_once Zymurgy::getFilePath("~model.php");
            $m = ZymurgyModel::factory($table);
            $m->addFilter($filter);
            return $m->read(false);
        }
        
        /**
         * Write to a $rowdata row honoring the table's ACL.  If no ACL is defined then access
         * will be denied.  $rowdata is an array of column names and values.
         * 
         * @param string $table
         * @param array $rowdata
         * @return boolean
         */
        public static function table_write($table,$rowdata)
        {
            require_once Zymurgy::getFilePath("~model.php");
            $m = ZymurgyModel::factory($table);
            return $m->write($rowdata);
        }
        
        /**
         * Delete a row from a table, honoring the table's ACL.  If the table has no ACL then access
         * is always denied.
         * 
         * @param string $table
         * @param mixed $rowid
         * @return boolean
         */
        public static function table_delete($table,$rowid)
        {
            require_once Zymurgy::getFilePath("~model.php");
            $m = ZymurgyModel::factory($table);
            return $m->delete($rowid);
        }

        /**
         * View a row in a form/table with captions and edit controls.  Wires to data.php to handle posts.
         * 
         * @param string $table
         * @param int $rowid
         * @param string $returnURL
         * @param string $submitValue
         */
        public static function table_view($table,$rowid = 0,$returnURL,$submitValue='Save')
        {
            require_once Zymurgy::getFilePath("~view.php");
            require_once Zymurgy::getFilePath("~model.php");
            $m = ZymurgyModel::factory($table);
            $v = ZymurgyView::factory($m);
            $params = array('table'=>$table,'rurl'=>$returnURL);
            if ($rowid)
            {
                $rows = $m->read($rowid);
                if (array_key_exists($rowid, $rows))
                {
                    $data = $rows[$rowid];
                    $params['id'] = $rowid;
                }
            }
            if (!isset($data))
            {
                $data = array();
            }
            $v->showform(Zymurgy::getUrlPath('~data.php'),$params,$submitValue,$data);
        }
        
        /**
         * Check the ACL for a permission for the logged in user.  Return the provided
         * default permission if the user doesn't have the ACL at all, or if the user is
         * not logged in.
         * 
         * @param int $aclname
         * @param string $permission (Read, Write or Delete)
         * @param boolean $default
         */
        public static function checkaclbyid($aclid, $permission, $default = null)
        {
            return Zymurgy::checkaclby('id', intval($aclid), $permission, $default);
        }
        
        /**
         * Check the ACL for a permission for the logged in user.  Return the provided
         * default permission if the user doesn't have the ACL at all, or if the user is
         * not logged in.
         * 
         * @param string $aclname
         * @param string $permission (Read, Write or Delete)
         * @param boolean $default
         */
        public static function checkaclbyname($aclname, $permission, $default = null)
        {
            return Zymurgy::checkaclby('name', $aclname, $permission, $default);
        }

        /**
         * Get the ID for an ACL by name; 0 if no ACL found
         *
         * @static
         * @param $aclname string
         * @return int
         */
        public static function getaclbyname($aclname)
        {
            return intval(Zymurgy::$db->getParam("SELECT `id` FROM `zcm_acl` WHERE `name`={0}",array($aclname)));
        }

        /**
         * Get the ID for a group by name; 0 of no group found
         *
         * @static
         * @param $groupname
         * @return int
         */
        public static function getgroupbyname($groupname)
        {
            return intval(Zymurgy::$db->getParam("SELECT `id` FROM `zcm_groups` WHERE `name`={0}",array($groupname)));
        }

        /**
         * Sets the
         *
         * @static
         * @param $aclId int
         * @param $groupId int
         * @param $permissions array
         */
        public static function setaclperms($aclId, $groupId, $permissions)
        {
            Zymurgy::$db->runParam("DELETE FROM `zcm_aclitem` WHERE (`zcm_acl`={0}) AND (`group`={1})",
                array($aclId, $groupId));
            foreach ($permissions as $permission) {
                Zymurgy::$db->insert('zcm_aclitem', array(
                    'zcm_acl' => $aclId,
                    'group' => $groupId,
                    'permission' => $permission,
                    'disporder' => 0,
                ));
            }
            Zymurgy::$db->setDispOrder('zcm_aclitem');
        }

        /**
         * Create a new ACL and get its ID
         *
         * @static
         * @param $aclName string
         * @return int
         */
        public static function createacl($aclName)
        {
            Zymurgy::$db->insert('zcm_acl', array('name'=>$aclName));
            return Zymurgy::$db->insert_id();
        }

        /**
         * Get general site content.  Create new tag if this one doesn't exist.
         *
         * @param string $tag The name of the tag to display, as set in the
         * zcm_sitetext table.
         * @param string $type The inputspec of the tag to display. If not set,
         * "html.600.400" is used.
         * @param boolean $adminui Display the "Edit this site text" tooltip
         * when the user is viewing the text and is logged into Zymurgy:CM.
         *
         * @return string The site text, as stored in the zcm_sitetext table.
         */
        static function sitetext(
            $tag,
            $type='html.600.400',
            $adminui = true)
        {
            if (array_key_exists($tag, self::$controllertext))
            {
                return self::$controllertext[$tag];
            }
            
            if (strlen($tag)>35)
            {
                die("Unable to create new site text.  Tag names must be 35 characters or less.");
            }
            $sql = "select body,id,inputspec,acl from zcm_sitetext where tag='".Zymurgy::$db->escape_string($tag)."'";
            $ri = Zymurgy::$db->query($sql);
            if (!$ri)
                die("Unable to read metaid. ($sql): ".Zymurgy::$db->error());
            if (Zymurgy::$db->num_rows($ri)==0)
            {
                $body = 'Please edit the general content called <b>"'.$tag.'"</b> in '.Zymurgy::GetLocaleString("Common.ProductName").'.';

                $widget = InputWidget::GetFromInputSpec($type);

                if($widget->SupportsFlavours())
                {
                    $flavourID = $widget->StoreFlavouredValue(null, $body, array());

                    Zymurgy::$db->query("insert into zcm_sitetext (tag,inputspec,body) values ('".
                        Zymurgy::$db->escape_string($tag).
                        "','".
                        Zymurgy::$db->escape_string($type).
                        "','".
                        Zymurgy::$db->escape_string($flavourID)."')");
                    Zymurgy::$db->query("insert into zcm_textpage(metaid,sitetextid) values (".Zymurgy::$pageid.",".Zymurgy::$db->insert_id().")");
                    $t = $body;
                }
                else
                {
                    //Create new sitetext entry
                    Zymurgy::$db->query("insert into zcm_sitetext (tag,inputspec,body) values ('".Zymurgy::$db->escape_string($tag)."','".
                        Zymurgy::$db->escape_string($type)."','".Zymurgy::$db->escape_string($body)."')");
                    Zymurgy::$db->query("insert into zcm_textpage(metaid,sitetextid) values (".Zymurgy::$pageid.",".Zymurgy::$db->insert_id().")");
                    $t = $body;
                }
            }
            else
            {
                $row = Zymurgy::$db->fetch_array($ri);
                $mayView = true;

                // -----
                // Check to see if the user has access to this block of site text
                if (!Zymurgy::checkaclbyid($row['acl'], 'Read', true))
                {
                    $t = '';
                }
                else
                {
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
                        require_once(Zymurgy::getFilePath("~include/Thumb.php"));
                        $ext = Thumb::mime2ext($row['body']);
                        $thumbName = Zymurgy::$root."/UserFiles/DataGrid/zcm_sitetext.body/{$row['id']}thumb$requestedSize.$ext";
                        if (!file_exists($thumbName))
                        {
                            $dimensions = explode('x',$requestedSize);
                            $rawimage = Zymurgy::$root."/UserFiles/DataGrid/zcm_sitetext.body/{$row['id']}raw.$ext";
                            Thumb::MakeFixedThumb($dimensions[0],$dimensions[1],$rawimage,$thumbName);
                        }
                    }
                    else if ($type!=$row['inputspec'])
                    {
                        Zymurgy::$db->query("update zcm_sitetext set inputspec='".Zymurgy::$db->escape_string($type)."' where id={$row['id']}");
                    }
                    $widget = new InputWidget();

                    $_GET['editkey'] = $widget->editkey = $row['id'];
                    $widget->datacolumn = 'zcm_sitetext.body';
                    $t = $widget->Display("$type","{0}",$row['body']);
                    if ($adminui && ((!array_key_exists('inlineeditor',Zymurgy::$config)) || (array_key_exists('inlineeditor',Zymurgy::$config) && Zymurgy::$config['inlineeditor'])) &&
                        (array_key_exists('zymurgy',$_COOKIE)))
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
                        $tag = htmlspecialchars($tag);
                        $link = Zymurgy::getUrlPath("~sitetextdlg.php?&st=$urltag&extra=".urlencode($extra));
                        $t = "<span id=\"ST$tag\">$t</span><script>
            YAHOO.Zymurgy.container.tt$Zymurgy_tooltipcount = new YAHOO.widget.Tooltip(\"tt$Zymurgy_tooltipcount\",
                                                    { context:\"ST$jstag\",
                                                      hidedelay: 10000,
                                                      autodismissdelay: 10000,
                                                      text:\"<a href='javascript:ShowEditWindow(\\\"$link\\\")'>Edit &quot;$tag&quot; with ".Zymurgy::GetLocaleString("Common.ProductName")."</a>\" });
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
            if (file_exists(Zymurgy::getFilePath("~custom/render.php")))
                include_once(Zymurgy::getFilePath("~custom/render.php"));
            $s = Zymurgy::$db->escape_string($_SERVER['PHP_SELF']);
            if (($s=='') || ($s=='/'))
                $s = '/index.php';

            if(Zymurgy::$template instanceof ZymurgyTemplate)
            {
                // print_r(Zymurgy::$template->sitepage->id);
                // die();

                $sql = "SELECT `zcm_sitepage` AS `id`, `title`, `keywords`, `description` FROM `zcm_sitepageseo` WHERE `zcm_sitepage` = '".
                    Zymurgy::$db->escape_string(Zymurgy::$template->sitepage->id).
                    "' LIMIT 0, 1";
                $ri = Zymurgy::$db->query($sql)
                    or die("Could not retrieve page's SEO information: ".Zymurgy::$db->error().", $sql");

                if(Zymurgy::$db->num_rows($ri) > 0)
                {
                    $row = Zymurgy::$db->fetch_array($ri);
                    $row['title'] = ZIW_Base::GetFlavouredValue($row['title']);
                    $row['description'] = ZIW_Base::GetFlavouredValue($row['description']);
                    $row['keywords'] = ZIW_Base::GetFlavouredValue($row['keywords']);
                }
                else
                {
                    $row = array(
                        "id" => Zymurgy::$template->sitepage->id,
                        "title" => Zymurgy::$config['defaulttitle'],
                        "description" => Zymurgy::$config['defaultdescription'],
                        "keywords" => Zymurgy::$config['defaultkeywords']);
                }

                Zymurgy::$db->free_result($ri);
            }
            else
            {
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
            }
            Zymurgy::$pageid = $row['id'];
            $r = array();
            if ($row['title'] != '')
                $r[] = "\t<title>".htmlspecialchars($row['title'])."</title>";
            if ($row['description']!='')
                $r[] = "\t<meta name=\"description\" content=\"".htmlspecialchars($row['description'])."\" />";
            if ($row['keywords']!='')
                $r[] = "\t<meta name=\"keywords\" content=\"".htmlspecialchars($row['keywords'])."\" />";
            if (array_key_exists('zymurgy',$_COOKIE))
                $r[] = Zymurgy::adminhead();
            $r[] = trim(Zymurgy::RequireOnce(Zymurgy::getUrlPath('~include/cmo.js')));
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
            $r = implode("\n",$r)."\n";
            return $r;
        }
        
        /**
         * Returns HTML/JavaScript to initialize the site text cache used for javascript sitetext.
         * Pass each tag needed for the page.
         * 
         * @param string $varargs
         */
        static function cachesitetext($varargs)
        {
            $args = func_get_args();
            $in = array();
            foreach ($args as $arg)
            {
                $in[] = Zymurgy::$db->escape_string($arg);
            }
            $cache = array();
            $ri = Zymurgy::$db->run("SELECT * FROM `zcm_sitetext` WHERE `tag` IN ('".
                implode("','", $in)."')");
            $widget = new InputWidget();
            while (($row = Zymurgy::$db->fetch_array($ri,ZYMURGY_FETCH_ASSOC))!==false)
            {
                $_GET['editkey'] = $widget->editkey = $row['id'];
                $widget->datacolumn = 'zcm_sitetext.body';
                $t = $widget->Display($row['inputspec'],"{0}",$row['body']);
                $cache[$row['tag']] = $t;
            }
            return "<script>\r\nZymurgy.sitetextcache = ".json_encode($cache).";\r\n</script>\r\n";
        }

        /**
         * Render an image at the specified resolution, as set in a Simple
         * Content item.
         *
         * @deprecated
         * @param string $tag
         * @param int $width
         * @param int $height
         * @param string $alt
         * @return string
         */
        public static function siteimage($tag,$width,$height,$alt='')
        {
            $img = Zymurgy::sitetext($tag,"image.$width.$height");
            $ipos = strpos($img,"src=\"");
            if ($ipos>0)
                $img = substr($img,0,$ipos)."alt=\"$alt\" ".substr($img,$ipos);
            return $img;
        }

        /**
         * Generate an XML document that is compliant with the Sitemaps
         * protocol.
         *
         */
        public static function sitemap()
        {
            include_once(Zymurgy::getFilePath("~sitemapsclass.php"));

            $sm = new Zymurgy_SiteMap(Zymurgy::$config['sitehome']);

            $ri = Zymurgy::$db->query("select * from zcm_meta");
            while (($row = Zymurgy::$db->fetch_array($ri))!==false)
            {
                $sm->AddUrl($row['document'],$row['mtime'],$row['changefreq'],($row['priority']/10));
            }

            $sitenav = Zymurgy::getsitenav();

//            print_r($sitenav);
//            die();

            Zymurgy::AppendToSiteMap($sm, $sitenav, $sitenav->items[0], "");

            $sm->Render();
        }

        /**
         * Append the specified section of the sitenav to the sitemap. Used
         * primarily by the sitemap() method to generate the sitemap.
         *
         * @param Zymurgy_SiteMap $sm Zymurgy Sitemap object to append to
         * @param ZymurgySiteNav $sitenav Zymurgy SiteNav object to pull data from
         * @param ZymurgySiteNavItem $item The item to append to the site map
         * @param string $path The base path for the item
         */
        public static function AppendToSiteMap(&$sm, &$sitenav, $item, $path)
        {
            $myPath = $path;

            if($item->id > 0)
            {
                $myPath = $path."/".$sitenav->linktext2linkpart($item->linktext);

                $sql = "SELECT `mtime`, `changefreq`, `priority` FROM `zcm_sitepageseo` WHERE `zcm_sitepage` = '".
                    Zymurgy::$db->escape_string($item->id).
                    "'";
                $seo = Zymurgy::$db->get($sql);

                $sm->AddUrl($myPath, $seo["mtime"], $seo["changefreq"],  $seo["priority"]/10);
            }

            foreach($item->children as $child)
            {
                Zymurgy::AppendToSiteMap($sm, $sitenav, $sitenav->items[$child], $myPath);
            }
        }

        //@}

        public function userErrorHandler ($errno, $errmsg, $filename, $linenum,  $vars)
        {
                $time=date("d M Y H:i:s");
                // Get the error type from the error number
                $errortype = array (1    => "Error",
                                  2    => "Warning",
                                  4    => "Parsing Error",
                                     8    => "Notice",
                                     16   => "Core Error",
                              32   => "Core Warning",
                                     64   => "Compile Error",
                                     128  => "Compile Warning",
                                  256  => "User Error",
                                  512  => "User Warning",
                                  1024 => "User Notice",
                                  2048 => "Run Time Notice",
                                  4096 => "Catchable Fatal Error");
                $errlevel=$errortype[$errno];
                if (empty($errlevel)) $errlevel = $errno;

                echo "<div>[$errlevel: $errmsg in $filename on line $linenum]</div>\n";
        }

        public function enableErrorHandler()
        {
            set_error_handler(array('ZymurgyBase','userErrorHandler'));
            error_reporting(0);
        }

        /**
         * Load the configuration for the specified plugin from the database.
         *
         * @param PluginBase $pi
         */
        public static function LoadPluginConfig(&$pi)
        {
            $iid = 0 + $pi->iid;
//            $sql = "select `key`,`value` from zcm_pluginconfig where (plugin={$pi->pid}) and (instance=$iid)";

            $sql = "SELECT `key`, `value` FROM `zcm_pluginconfigitem` WHERE `config` = '".
                Zymurgy::$db->escape_string($pi->configid).
                "'";

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
         * Create a plugin object for the named plugin (same as the file name
         * without the extension) and instance name.  Extra is used to pass
         * extra plugin-specific stuff to a plugin, and private is used to flag
         * an instance that shouldn't be listed with regular instances because
         * it is created and maintained by something else (for example a
         * collection of image galleries).
         *
         * @param string $plugin The class name of the plugin to initialize.
         * @param string $instance The name of the instance of the plugin
         * to initialize.
         * @param mixed $extra List of plugin-specific data for the instance of
         * the plugin to initialize
         * @param boolean $private This instance of the  plugin is not to be
         * listed in the Webwaster > Plugin Management section of Zymurgy:CM.
         * @return PluginBase
         */
        public static function mkplugin($plugin,$instance,$extra='',$private=0)
        {
            require_once(Zymurgy::getFilePath("~PluginBase.php"));
            $pluginsrc_core=Zymurgy::getFilePath("~plugins/$plugin.php");
            if (!file_exists($pluginsrc_core))
            {
                $pluginsrc_custom = Zymurgy::getFilePath("~custom/plugins/$plugin.php");
                if (!file_exists($pluginsrc_custom))
                    die("No such plugin: $pluginsrc_core or $pluginsrc_custom");
                $pluginsrc = $pluginsrc_custom;
            }
            else 
                $pluginsrc = $pluginsrc_core;
            require_once($pluginsrc);
            $private = 0 + $private;
            $pif = "{$plugin}Factory";
            $pi = $pif();
            $pi->config = $pi->GetDefaultConfig();
            //Now load the config from the database.  First we have to figure out our instance.  If this is
            //a new instance then create it and populate it with default values.
            $sql = "select zcm_plugin.id as pid, zcm_plugininstance.id as pii,
                COALESCE(`zcm_plugininstance`.`config`, `zcm_plugin`.`defaultconfig`) AS `configid`,
                `release` from zcm_plugin left join zcm_plugininstance on (zcm_plugin.id=zcm_plugininstance.plugin)
                where (zcm_plugin.name='".
                Zymurgy::$db->escape_string($plugin)."') and (zcm_plugininstance.name='".
                Zymurgy::$db->escape_string($instance)."')";
//            die($sql);
            $ri = Zymurgy::$db->query($sql);
            if (!$ri)
            {
                die ("Error loading [plugin: $plugin] [instance $instance]: ".Zymurgy::$db->error()."<br>$sql");
            }
            $row = Zymurgy::$db->fetch_array($ri);
            $pi->extra = $extra;
            $pi->InstanceName = $instance;

            if ($row !== false)
            {
                $pi->pid = $row['pid'];
                $pi->iid = $row['pii'];
                $pi->configid = $row["configid"];
                Zymurgy::LoadPluginConfig($pi);
            }
            else
            {
                //New instance...  Load 'er up!
                $pi->CreateInstance($pi, $plugin, $instance, $private);
            }
            return $pi;
        }

        /**
         * Render the specified instance of the plugin.
         *
         * @param string $plugin The class name of the plugin to render.
         * @param string $instance The name of the instance of the plugin to
         * render.
         * @param mixed $extra List of plugin-specific data for the instance of
         * the plugin to render
         * @return PluginBase
         */
        static function plugin($plugin,$instance,$extra='')
        {
            $pi = Zymurgy::mkplugin($plugin,$instance,$extra,0);
            if (!is_object($pi))
            {
                die("Unable to create plugin: $plugin");
            }
            return $pi->Render();
        }

        /**
         * Render the components required by the tool-tip displayed by
         * Zymurgy::sitetext when logged into Zymurgy:CM.
         *
         * @return string
         */
        static function adminhead()
        {
            return Zymurgy::YUI("container/assets/container.css").
                Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js").
                Zymurgy::YUI("animation/animation-min.js").
                Zymurgy::YUI("container/container-min.js")."
        <script>
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
         * Emit javascript to redirect the user to the supplied URL.
         * Aborts the running script/page.
         *
         * @param string $url
         */
        static function JSRedirect($url)
        {
            //Redirect and die.
            echo "<script>
            <!--
            window.location.href=\"$url\";
            //-->
            </script></head><body><noscript>Javascript is required to view this page.</noscript></body></html>";
            exit;
        }

        /**
         * Emit javascript to set the innerHTML of the privided element (by ID)
         * to the supplied HTML content.
         *
         * @param string $id The ID of the element to modify.
         * @param string $html The new content for the element.
         */
        static function JSInnerHtml($id,$html)
        {
            echo "<script>
            var Zymurgy_InnerHTML = document.getElementById('".addslashes($id)."');
            Zymurgy_InnerHTML.innerHTML = '".addslashes($html)."';
            </script>";
            flush();
        }

        /**
         * Load membership subsystem and any configured membership provider
         *
         */
        static public function initializemembership()
        {
            if (self::$MemberProvider == null)
            {
                //Initialize membership provider
                require_once 'member.php';
                if (empty(Zymurgy::$config['MemberProvider']) || Zymurgy::$config["MemberProvider"] == "(none)")
                {
                    self::$MemberProvider = new ZymurgyMember();
                }
                else
                {
                    require_once(Zymurgy::getFilePath("~memberp/".Zymurgy::$config['MemberProvider'].".php"));
                    self::$MemberProvider = new Zymurgy::$config['MemberProvider'];
                }
            }

            return self::$MemberProvider;
        }

        /**
         * Is member authenticated?  If yes then loads auth info into global $member array.
         *
         * @return boolean
         */
        static function memberauthenticate()
        {
            Zymurgy::initializemembership();
            return self::$MemberProvider->memberauthenticate();
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
            return self::$MemberProvider->memberauthorize($groupname);
        }
        
        /**
         * Authenticate for Z:CM features as Zymurgy:CM - User (1), Zymurgy:CM - Administrator (2) or
         * Zymurgy:CM - Webmaster (3).  Corresponding authlevels in parenthesis.
         * 
         * If the user doesn't have the required priveledge then redirect to ~login.php
         * 
         * @param int $level
         */
        public static function memberrequirezcmauth($level)
        {
            Zymurgy::initializemembership();
            return self::$MemberProvider->memberrequirezcmauth($level);
        }        

        /**
         * Authenticate for Z:CM features as Zymurgy:CM - User (1), Zymurgy:CM - Administrator (2) or
         * Zymurgy:CM - Webmaster (3).  Corresponding authlevels in parenthesis.
         * 
         * If the required level is supplied then true/false is returned to indicate whether or not the
         * user has that level or better.  If it is not supplied then the user's auth level is returned.
         * 
         * @param int $level
         * @return boolean or int
         */
        public static function memberzcmauth($level = false)
        {
            Zymurgy::initializemembership();
            return self::$MemberProvider->memberzcmauth($level);
        }
                
        /**
         * Log member activity
         *
         * @param unknown_type $activity
         */
        static function memberaudit($activity)
        {
            Zymurgy::initializemembership();
            self::$MemberProvider->memberaudit($activity);
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
            self::$MemberProvider->memberpage($groupname);
        }

        /**
         * Attempt to log into the membership system with the provided user ID and password.  Returns true
         * if the login was successful or false if it was not.
         *
         * @param string $userid
         * @param string $password
         * @return boolean
         */
        static function memberdologin($userid, $password, $writeCookie = true)
        {
            Zymurgy::initializemembership();
            return self::$MemberProvider->memberdologin(
                $userid,
                $password,
                $writeCookie);
        }

        /**
         * Clear existing credentials and go to the supplied URL.
         *
         * @param string $logoutpage
         */
        static function memberlogout($logoutpage)
        {
            Zymurgy::initializemembership();
            self::$MemberProvider->memberlogout($logoutpage);
        }

        static public function memberCheckPassword($userName, $password)
        {
            Zymurgy::initializemembership();
            $stored = Zymurgy::$db->getParam("SELECT `password` FROM `zcm_member` WHERE `username`={0}",
                array($userName));
            return self::$MemberProvider->checkPassword($password, $stored);
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

            return self::$MemberProvider->membersignup($formname,$useridfield,$passwordfield,$confirmfield,$redirect);
        }

        /**
         * Render login interface.
         *
         * Uses reg GET variable, which can be:
         *     - username: create a new username/account
         *     - extra: get extra info from the user using a client defined form
         * If the reg GET variable isn't supplied it just tries to log the user in.
         *
         * @return string HTML for login process
         */
        static function memberlogin()
        {
            // die("memberlogin: ".gettype(Zymurgy::$MemberProvider));
            // die(var_dump(debug_backtrace()));

            Zymurgy::initializemembership();
            return self::$MemberProvider->memberlogin();
        }

        /**
         * Render data entry form for user data using the navigation name for
         * the Custom Table used for user data.
         *
         * @param string $navname
         * @param string $exitpage
         */
        static function memberform($navname, $exitpage)
        {
            Zymurgy::initializemembership();
            return self::$MemberProvider->memberform($navname,$exitpage);
        }

        /**
         * Perform a remote lookup through the Member Provider to find the ID
         * of a record in the specified table, based on the value stored in the
         * specified field
         *
         * @param string $table The name of the table on the remote source
         * @param string $field The name of the field to search on the remote
         * source
         * @param string $value The value of the field to search on the remote
         * source
         * @param boolean $exact When true, only return on an exact match of the
         * value parameter.  Otherwise, return any records that contain values
         * starting with the value parameter.
         * @return array
         */
        static function memberremotelookup($table,$field,$value,$exact=false)
        {
            Zymurgy::initializemembership();
            if (method_exists(self::$MemberProvider,'remotelookup'))
                return self::$MemberProvider->remotelookup($table,$field,$value,$exact);
            else
                return array();
        }

        /**
         * Perform a remote lookup through the Member Provider to find the ID
         * of a record in the specified table, based on the ID.
         *
         * @param string $table The name of the table on the remote source
         * @param string $field The name of the field to search on the remote
         * source
         * @param string $value The value of the field to search on the remote
         * source
         * @return array
         */
        static function memberremotelookupbyid($table,$field,$value)
        {
            if (array_key_exists($table,self::$remotelookupcache))
            {
                if (array_key_exists($field,self::$remotelookupcache[$table]))
                {
                    if (array_key_exists($value,self::$remotelookupcache[$table][$field]))
                    {
                        return self::$remotelookupcache[$table][$field][$value];
                    }
                }
                else
                {
                    self::$remotelookupcache[$table][$field] = array();
                }
            }
            else
            {
                self::$remotelookupcache[$table] = array($field=>array());
            }
            Zymurgy::initializemembership();
            if (method_exists(self::$MemberProvider,'remotelookupbyid'))
                $r = self::$MemberProvider->remotelookupbyid($table,$field,$value);
            else
                $r = false;
            self::$remotelookupcache[$table][$field][$value] = $r;
            return $r;
        }
        
        /**
         * Get the client's IP address from either REMOTE_ADDR or X_FORWARDED_FOR
         */
        static function RemoteHost()
        {
            $ip = $_SERVER['REMOTE_ADDR'];
            if (array_key_exists('X_FORWARDED_FOR',$_SERVER))
            {
                $ip .= " forwarding for ".$_SERVER['X_FORWARDED_FOR'];
            }
            return $ip;
        }    

        /**
         * Get PHPMailer object pre-configured with settings from the Zymurgy:CM config file.
         *
         * @return PHPMailer
         */
        static function GetPHPMailer()
        {
            require_once(Zymurgy::getFilePath("~phpmailer/class.phpmailer.php"));
            $mail = new PHPMailer();
            $mail->Mailer = array_key_exists('Mailer Type',Zymurgy::$config) ? Zymurgy::$config['Mailer Type'] : 'mail';
            if ($mail->Mailer == 'smtp')
            {
                $mail->Host = Zymurgy::$config['Mailer SMTP Hosts'];
            }
            $ip = Zymurgy::RemoteHost();
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
            require_once(Zymurgy::getFilePath("~include/Thumb.php"));
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
         * @param string|int $index The index of the color within the theme, either as a
         * number, or as a text value set in the $ThemeColor static array.
         *
         * @param string $theme The theme definition, either as a comma-delimited list
         * of color values, or as a reference to an item in the site config.
         *
         * @return string The color value.
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

        /**
         * The navigation structure of the site; this is an array with flavour codes as indexes,
         * so that more than once flavour's nav can be cached at the same time.
         *
         * @var ZymurgySiteNav[]
         * @see getsitenav
         */
        public static $sitenav = array();

        /**
         * Old function to render site navigation.
         *
         * Please create an instance of {@link ZymurgySitenavRenderer_YUI} instead.
         *
         * @deprecated Create a {@link ZymurgySiteNavRenderer_YUI} instead.
         *
         * @param $ishorizontal
         * @param $currentleveonly
         * @param $childlevelsonly
         * @param $startpath
         * @param $baseurl
         * @return unknown_type
         */
        public static function sitenav(
            $ishorizontal = true,
            $currentleveonly = false,
            $childlevelsonly = false,
            $startpath = '',
            $baseurl = 'pages')
        {

            Zymurgy::getsitenav()->render(
                $ishorizontal,
                $currentleveonly,
                $childlevelsonly,
                $startpath,
                $baseurl);
        }

        /**
         * Get site's navigation structure.
         *
         * generates (if necessary) and returns {@link $sitenav}
         *
         * @return ZymurgySiteNav
         */
        public static function getsitenav($forFlavour = null){
            require_once('sitenav.php');

            if (is_null($forFlavour))
            {
                $forFlavour = Zymurgy::GetActiveFlavourCode();
            }
            if(!isset(Zymurgy::$sitenav[$forFlavour]))
            {
                Zymurgy::$sitenav[$forFlavour] = new ZymurgySiteNav($forFlavour);
            }

            return Zymurgy::$sitenav[$forFlavour];
        }

        /**
         * Tag values can be set using this function, and later calls to sitetext()
         * or pagetext() will return these instead of those from the content editor.
         * 
         * @param string $tag
         * @param string $value
         */
        public static function settext($tag,$value)
        {
            self::$controllertext[$tag] = $value;
        }
        
        /**
         * Render the page content, based on its tag within the Pages system.
         *
         * @param string $tag The tag/ID of the page content to display
         * @param string $type The inputspec to apply to the tag
         * @return string The rendered content
         */
        public static function pagetext($tag,$type='html.600.400')
        {
            if (array_key_exists($tag, self::$controllertext))
            {
                return self::$controllertext[$tag];
            }
            if (isset(Zymurgy::$template))
            {
                return Zymurgy::$template->pagetext($tag,$type);
            }
            else
            {
                return "<div>This page is not linked to a template, so pagetext() can't be used here.</div>";
            }
        }

        /**
         * Render the raw page content, based on its tag within the Pages system.
         *
         * @param string $tag The tag/ID of the page content to display
         * @param string $type The inputspec to apply to the tag
         * @return string The raw page content
         */
        public static function pagetextraw($tag,$type='html.600.400')
        {
            if (isset(Zymurgy::$template))
            {
                return Zymurgy::$template->pagetextraw($tag,$type);
            }
            else
            {
                return "<div>This page is not linked to a template, so pagetextraw() can't be used here.</div>";
            }
        }

        /**
         * Render a page image.
         *
         * @deprecated
         * @param string $tag
         * @param int $width
         * @param int $height
         * @param string $alt
         * @return string
         */
        public static function pageimage($tag,$width,$height,$alt='')
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
        
        /**
         * Add text to the output of the pagegadgets() area(s)
         * $align should be one of 'left', 'center' or 'right'
         * 
         * @param string $align
         * @param string $text
         */
        public static function addgadgettext($align,$text)
        {
            self::$gadgettext[$align][] = $text;
        }

        /**
         * Render the plugins assigned to the page. If the alignFilter parameter
         * is provided, only render the plugins assigned to that alignment.
         *
         * @param string $alignFilter Optional. If provided, must be one of "left", "center", "right" or false to suppress the wrapper DIV.
         * @return The rendered plugin output.
         */
        public static function pagegadgets(
            $alignFilter = "")
        {
            foreach (self::$gadgettext as $align=>$values)
            {
                if (($alignFilter == '') || ($alignFilter === false) || ($alignFilter == $align))
                {
                    foreach ($values as $text)
                    {
                        if ($alignFilter !== false)
                        {
                            echo "<div align=\"$align\">";
                        }
                        echo $text;
                        if ($alignFilter !== false)
                        {
                            echo "</div>";
                        }
                    }
                }
            }
            if (isset(Zymurgy::$template))
            {
                return Zymurgy::$template->pagegadgets($alignFilter);
            }
            else
            {
                return "<div>This page is not linked to a template, so pagegadgets() can't be used here.</div>";
            }
        }

        /**
         * Render a plugin on the page using the specified configuration. A
         * separate instance of the plugin will be created for each page using
         * a template that calls this method, but all of those instances will
         * use the same configuration.
         *
         * @param string $pluginName The name of the plugin
         * @param string $configName The name of the plugin's configuration
         * @return string The rendered plugin output.
         */
        function pagegadget($pluginName, $configName)
        {
            return Zymurgy::$template->pagegadget($pluginName, $configName);
        }

        /**
         * Return a key form the User Config table.
         *
         * If the required key doesn't exist, create it with the given default value and imputspec.
         *
         * @param string $keyname The name of the Appearance Item
         * @param string $defaultvalue The default value to set the entry to if
         * it does not exist.
         * @param string $inputspec The type to set the entry to if it deos not
         * exist.
         * @return mixed
         */
        public static function Config($keyname, $defaultvalue, $inputspec='input.30.30')
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
         * @access private
         */
        public static function GetLocaleString($key)
        {
            // ZK: The locale is hard-coded to English for now, as it's the only
            // language Zymurgy:CM currently supports on the back-end.
            // TODO Read the locale from the session.

            return Zymurgy::$Locales["en"]->GetString($key);
        }
        
        protected static function IsDebugHost()
        {
            return (!array_key_exists('DebugHost',Zymurgy::$config) || (Zymurgy::RemoteHost() == Zymurgy::$config['DebugHost']));
        }

        /**
         * Echo debug arguments.  Format arrays and objects with print_r.
         * Uses variable arguments to support listing as many items as needed
         *
         * @param varargs $args
         */
        static function Dbg()
        {
            if (Zymurgy::IsDebugHost())
            {
                $args = func_get_args();
                echo "<hr />\r\n";
                $n = 1;
                foreach($args as $arg)
                {
                    echo "<div class=\"ZymurgyDebug\">$n: ";
                    $n++;
                    if (is_array($arg) || is_object($arg))
                    {
                        echo "<pre>"; print_r($arg); echo "</pre>";
                    }
                    elseif (is_bool($arg))
                    {
                        echo $arg ? 'TRUE' : 'FALSE';
                    }
                    else
                    {
                        echo $arg;
                    }
                    echo "</div>\r\n";
                }
                echo "<hr />\r\n";
            }
        }
        
        static function DbgLog()
        {
            if (Zymurgy::IsDebugHost())
            {
                $fd = fopen(Zymurgy::$root."/UserFiles/zcmdebug.log",'a+');
                fwrite($fd,"----- ".date('r')." -----\n");
                $args = func_get_args();
                $n = 1;
                $out = '';
                foreach($args as $arg)
                {
                    $out .= "Argument $n: ";
                    $n++;
                    if (is_array($arg) || is_object($arg))
                    {
                        $out .= print_r($arg,true);
                    }
                    elseif (is_bool($arg))
                    {
                        $out .= $arg ? 'TRUE' : 'FALSE';
                    }
                    else
                    {
                        $out .= $arg;
                    }
                    $out .= "\n";
                }
                fwrite($fd,$out);
            fclose($fd);
            }
        }

        /**
         * Echo debug arguments and then exit.  Format arrays and objects with print_r.
         * Uses variable arguments to support listing as many items as needed
         *
         * @param varargs $args
         */
        static function DbgAndDie()
        {
            if (Zymurgy::IsDebugHost())
            {
                $args = func_get_args();
                call_user_func_array(array('Zymurgy','Dbg'),$args);
                exit;
            }
        }

        /**
         * Get an array of associative arrays describing all configured flavours
         *
         * @return array
         */
        static function GetAllFlavoursByCode()
        {
            Zymurgy::GetAllFlavours();
            return self::$m_flavoursbycode;
        }

        /**
         * Get an array of associative arrays describing all configured flavours
         *
         * @return array
         */
        static function GetAllFlavours()
        {
            if(count(self::$m_flavours) <= 0)
            {
                $providescontent = array();
                $providestemplate = array();
                $sql = "SELECT * FROM `zcm_flavour` ORDER BY `disporder`";
                $ri = Zymurgy::$db->query($sql)
                    or die("Could not get list of flavours: ".Zymurgy::$db->error().", $sql");

                while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
                {
                    $row['providescontent'] = false;
                    $row['providestemplate'] = false;
                    self::$m_flavours[$row['id']] = $row;
                    self::$m_flavoursbycode[$row['code']] = $row;
                    if ($row['contentprovider']) $providescontent[$row['contentprovider']] = '';
                    if ($row['templateprovider']) $providestemplate[$row['templateprovider']] = '';
                }

                Zymurgy::$db->free_result($ri);

                foreach($providescontent as $key=>$throwaway)
                {
                    self::$m_flavours[$key]['providescontent'] = true;
                    self::$m_flavoursbycode[self::$m_flavours[$key]['code']]['providescontent'] = true;
                }
                foreach($providestemplate as $key=>$throwaway)
                {
                    self::$m_flavours[$key]['providestemplate'] = true;
                    self::$m_flavoursbycode[self::$m_flavours[$key]['code']]['providestemplate'] = true;
                }
            }

            return self::$m_flavours;
        }

        /**
         * Change flavours so that those which provide templates now provide content.  Done in memory only, so that we can use the
         * content mechanisms to maintain template paths in templatemgr.php.  This method is not likely to be useful outside of this
         * particular case.
         *
         */
        static function MapTemplateToContentFlavours()
        {
            Zymurgy::GetAllFlavours();
            foreach (self::$m_flavours as $key=>$flavour)
            {
                self::$m_flavours[$key]['providescontent'] = self::$m_flavours[$key]['providestemplate'];
            }
            foreach (self::$m_flavoursbycode as $key=>$flavour)
            {
                self::$m_flavoursbycode[$key]['providescontent'] = self::$m_flavoursbycode[$key]['providestemplate'];
            }
        }

        /**
         * Convert regular column content to flavoured content
         *
         * @param string $table
         * @param string $column
         * @access private
         */
        static function ConvertVanillaToFlavoured($table,$column)
        {
            //Write existing content values to the zcm_flavourtext table, and store the mappings
            $ri = Zymurgy::$db->run("SELECT id,`$column` FROM `$table`");
            $map = array();
            while (($row = Zymurgy::$db->fetch_array($ri))!==false)
            {
                Zymurgy::$db->run("INSERT into `zcm_flavourtext` (`default`) VALUES ('".
                    Zymurgy::$db->escape_string($row[$column])."')");
                $map[$row['id']] = Zymurgy::$db->insert_id();
            }
            Zymurgy::$db->free_result($ri);
            //Use the stored mappings to map the old values to the new flavoured content
            foreach ($map as $id=>$fid)
            {
                Zymurgy::$db->run("UPDATE `$table` SET `$column`=$fid WHERE `id`=$id");
            }
            //Finally, convert the old column to BIGINT
            Zymurgy::$db->run("ALTER TABLE `$table` CHANGE `$column` `$column` BIGINT");
        }

        /**
         * Convert the specified "flavoured" column to a standard non-flavoured
         * column.
         *
         * @param string $table The name of the table containing the column to
         * convert
         * @param string $column The name of the column to convert
         * @param string $inputspec The inputspec to apply to the converted column
         */
        public static function ConvertFlavouredToVanilla($table,$column,$inputspec)
        {
            //Determine the type this column should be converted to, and make the change
            $columntype = InputWidget::inputspec2sqltype($inputspec);
            Zymurgy::$db->run("ALTER TABLE `$table` CHANGE `$column` `$column` $columntype");
            //Get a list of zcm_flavourtext keys we need to convert
            $ri = Zymurgy::$db->run("SELECT `id`, `$column` FROM `$table`");
            $map = array();
            while (($row = Zymurgy::$db->fetch_array($ri))!==false)
            {
                $map[$row['id']] = $row[$column];
            }
            Zymurgy::$db->free_result($ri);
            //Read values from zcm_flavourtext and put them into the target column
            //Delete flavour data while we're at it.
            foreach ($map as $id=>$fid)
            {
                $fid = intval($fid); //Zero out empty/null values
                $content = Zymurgy::$db->get("SELECT `default` from `zcm_flavourtext` where `id`=$fid");
                Zymurgy::$db->run("UPDATE `$table` set `$column` = '".
                    Zymurgy::$db->escape_string($content)."' where `id`=$id");
                Zymurgy::$db->run("DELETE FROM zcm_flavourtextitem WHERE `zcm_flavourtext`=$fid");
                Zymurgy::$db->run("DELETE FROM zcm_flavourtext WHERE `id`=$fid");
            }
        }

        /**
         * Returns the SQL type for the given inputspec
         *
         * @param string $inputspec
         * @return string
         */
        public static function inputspec2sqltype($inputspec)
        {
            return InputWidget::inputspec2sqltype($inputspec);
        }

        static function GetActiveFlavourCode()
        {
            if (isset(self::$m_activeFlavour))
                return self::$m_activeFlavour;
            else 
                return 'pages';
        }
        
        static function flavourMyLink($link)
        {
            $lp = explode('?', $link);
            if (count($lp) == 2)
            {
                $link = $lp[0];
                $query = $lp[1];
            }
            $lp = explode('/',substr($link,1)); //Drop first char - should be / always
            $flavour = array_shift($lp); //Get flavour to convert from
            if ($flavour == Zymurgy::GetActiveFlavourCode()) return $link; //No conversion required
            $l = array(Zymurgy::GetActiveFlavourCode());
            $targetNav = Zymurgy::getsitenav();
            $sourceNav = Zymurgy::getsitenav($flavour);
            $sourceNode = $sourceNav->items[0];
            while ($lp)
            {
                $linkpart = array_shift($lp);
                //Change flavour here
                $sourceNode = $sourceNav->items[$sourceNode->childrenbynavname[$linkpart]];
                $targetNode = $targetNav->items[$sourceNode->id];
                $linkpart = $targetNode->linktext;
                $l[] = $linkpart;
            }
            $result = '/'.implode('/',$l);
            if (isset($query))
            {
                $result .= '?' . $query;
            }
            return $result;
        }

        static function GetActiveFlavour()
        {
            Zymurgy::GetAllFlavours();
            return array_key_exists(self::$m_activeFlavour,self::$m_flavoursbycode) ?
                self::$m_flavoursbycode[self::$m_activeFlavour] : false;
        }

        static function GetFlavourById($id)
        {
            return array_key_exists($id,self::$m_flavours) ? self::$m_flavours[$id] : false;
        }

        static function GetFlavourByCode($code)
        {
            Zymurgy::GetAllFlavours();
            return array_key_exists($code,self::$m_flavoursbycode) ? self::$m_flavoursbycode[$code] : false;
        }

        static function SetActiveFlavour($flavour)
        {
            Zymurgy::GetAllFlavours();
            if (array_key_exists($flavour,self::$m_flavoursbycode))
            {
                self::$m_activeFlavour = $flavour;
                return true;
            }
            return false;
        }
        
        static function getAppRoot() 
        {
            return $_SERVER['DOCUMENT_ROOT'];
            //The following used to work when z:cm was always in the /zymurgy folder, but with composer support
            //that is no longer true.  Hopefully other web servers set similar environment variables.
            /*
            $r = dirname(__FILE__);
            $rp = explode(DIRECTORY_SEPARATOR, $r);
            array_pop($rp);
            return implode(DIRECTORY_SEPARATOR, $rp);
            */
        }
        
        static function longcache_write($key, $value)
        {
            Zymurgy::$db->run("INSERT INTO `zcm_longcache` (`ckey`,`value`,`created`) VALUES ('".
                Zymurgy::$db->escape_string($key)."', '".Zymurgy::$db->escape_string($value).
                "',NOW()) ON DUPLICATE KEY UPDATE `value`='".Zymurgy::$db->escape_string($value)."'");
        }
        
        static function longcache_read($key,$maxage = 0)
        {
            $sql = "SELECT `value` FROM `zcm_longcache` WHERE `ckey`='".
                Zymurgy::$db->escape_string($key)."'";
            if ($maxage)
            {
                $sql .= " AND (DATE_SUB(NOW(),'SECONDS ".
                    intval($maxage)."') > `created`";
            }
            $result = Zymurgy::$db->get($sql);
            if (is_null($result))
            {
                $result = false;
            }
            return $result;
        }
    } // End ZymurgyBase Class definition

    //The following runs only the first time cmo.php is included...

    ZymurgyBase::$root = ZymurgyBase::getAppRoot();
    $customCMO = ZymurgyBase::getFilePath("~custom/cmo.php");
    if (file_exists($customCMO))
    {
        require_once $customCMO;
    }
    else
    {
        class Zymurgy extends ZymurgyBase
        {
        }
    }
    Zymurgy::$build = 1987; //Historical; no longer used.

    if (ini_get('date.timezone') == '')
    {
        date_default_timezone_set('America/New_York');
    }
    Zymurgy::$config = include(Zymurgy::getFilePath("~config/config.php"));
    if (!Zymurgy::$config)
    { //Legacy config file support
        Zymurgy::$config = $ZymurgyConfig;
        unset($ZymurgyConfig);
    }
    if (array_key_exists('Default Timezone', Zymurgy::$config))
    {
        date_default_timezone_set(Zymurgy::$config['Default Timezone']);
    }
    
    if (!array_key_exists('Default Timezone',Zymurgy::$config))
    { //See http://ca.php.net/manual/en/timezones.php for supported values
        Zymurgy::$config['Default Timezone'] = 'America/New_York';
    }
    if (date_default_timezone_set(Zymurgy::$config['Default Timezone']) === false)
    {
        die("Invalid default time zone: ".Zymurgy::$config['Default Timezone']);
    }

    Zymurgy::$catalogue['vendors'] = array();
    Zymurgy::$catalogue['implementation'] = array();

    if ((array_key_exists('FixSlashes',Zymurgy::$config)) && (Zymurgy::$config['FixSlashes']) && (get_magic_quotes_gpc())) {
       $_POST = array_map('Zymurgy::stripslashes_deep', $_POST);
       $_GET = array_map('Zymurgy::stripslashes_deep', $_GET);
       $_COOKIE = array_map('Zymurgy::stripslashes_deep', $_COOKIE);
    }

    //Initialize database provider
    if (empty(Zymurgy::$config['database']))
    {
        Zymurgy::$config['database'] = 'mysql';
    }
    require_once(Zymurgy::getFilePath("~db/".Zymurgy::$config['database'].".php"));
    Zymurgy::$db = new Zymurgy_DB();
    if (isset(Zymurgy::$config['characterset']))
    {
        mysql_set_charset(Zymurgy::$config['characterset']);
    }

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
            require_once(Zymurgy::getFilePath("~include/ImageHandlerGD.php"));
            Zymurgy::$imagehandler = new ZymurgyImageHandlerGD();
            break;
        default:
            require_once(Zymurgy::getFilePath("~include/ImageHandlerIM.php"));
            Zymurgy::$imagehandler = new ZymurgyImageHandlerImageMagick();
            break;
    }

    if (array_key_exists('Debug',Zymurgy::$config) && (Zymurgy::$config['Debug'] > 0))
    {
        error_reporting(Zymurgy::$config['Debug']);
    }

    require_once("InputWidget.php");
    Zymurgy::$Locales = LocaleFactory::GetLocales();
    Zymurgy::$router = new ZymurgyRouter();

// end include guard
}
?>
