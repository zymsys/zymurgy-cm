<?
/**
 * Utility classes for building a Google Sitemap.
 *
 * @package Zymurgy
 * @subpackage backend-modules
 */

/**
 * Class for defining a single URL within a Google Sitemap.
 */
class Zymurgy_SiteMapUrl
{
	/**
	 * The URL
	 */
	var $loc;

	/**
	 * The Last Modified date of the page.
	 */
	var $lastmod;

	/**
	 * How often the content is changed (i.e. how often Google should spider
	 * this page
	 */
	var $changefreq;

	/**
	 * The importance of this page, relative to the other pages on the site.
	 */
	var $priority;

	/**
	 * The sitemap object the URL is being added to.
	 */
	var $SiteMap;

	/**
	 * Constructor.
	 *
	 * @param Zymrugy_SiteMap $SiteMap
	 * @param string $loc
	 * @param string $lastmod
	 * @param string $changefreq
	 * @param string $priority
	 */
	function Zymurgy_SiteMapUrl($SiteMap,$loc,$lastmod='',$changefreq='',$priority='')
	{
		$this->SiteMap = &$SiteMap;

		if (substr($loc,0,1) == '/')
		{
			$loc = substr($loc,1); //Strip leading slash
		}
		if ($lastmod == '') {
			$lp = explode('?',$loc);
			$fi = @stat($_SERVER['DOCUMENT_ROOT'].'/'.$lp[0]);
			$lastmod = $this->get_iso_8601_date($fi['mtime']);
		}
		else if (is_numeric($lastmod))
		{
			$lastmod = $this->get_iso_8601_date($lastmod);
		}
		if ($changefreq=='') $changefreq = $SiteMap->DefaultFrequency;
		if ($priority=='') $priority = $SiteMap->DefaultPriority;
		if (substr($loc,0,7)!='http://')
			$loc = $SiteMap->DefaultHome."/".$loc;
		$loc = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');
		$this->loc = $loc;
		$this->lastmod = $lastmod;
		$this->changefreq = $changefreq;
		$this->priority = $priority;
	}

	/**
	 * Get the ISO 8601 formatted date, given a UNIX Timestamp.
	 *
	 * Thanks to ungu at terong dot com for this.
	 *
	 * @param int $int_date The UNIX Timestamp
	 * @return string
	 */
	function get_iso_8601_date($int_date) {
	   //$int_date: current date in UNIX timestamp
	   $date_mod = date('Y-m-d\TH:i:s', $int_date);
	   $pre_timezone = date('O', $int_date);
	   $time_zone = substr($pre_timezone, 0, 3).":".substr($pre_timezone, 3, 2);
	   $date_mod .= $time_zone;
	   return $date_mod;
	}
}

/**
 * Class for defining a Google Sitemap.
 */
class Zymurgy_SiteMap
{
	/**
	 * The home page of the site. Example: http://www.example.com/
	 * @depcrecated
	 */
	var $DefaultHome;

	/**
	 * The default page priority. Floating point number between 0 and 1.
	 */
	var $DefaultPriority;

	/**
	 * The defaulte page change frequency.
	 * One of always hourly, daily, weekly, monthly, yearly, or never. 
	 */
	var $DefaultFrequency;

	/**
	 * The list of URLs to include in the sitemap.
	 */
	var $urls;

	/**
	 * Constructor.
	 *
	 * @param string $DefaultHome The home page of the site.
	 * @param float $DefaultPriority The default page priority.
	 * @param string $DefaultFrequency The default page change frequency.
	 */
	function Zymurgy_SiteMap($DefaultHome,$DefaultPriority = 0.5,$DefaultFrequency='monthly')
	{
		$this->DefaultHome = $DefaultHome;
		$this->DefaultPriority = $DefaultPriority;
		$this->DefaultFrequency = $DefaultFrequency;
		$this->urls = array();
	}

	/**
	 * Add a URL to the sitemap.
	 *
	 * @param string $loc
	 * @param string $lastmod
	 * @param string $changefreq
	 * @param string $priority
	 */
	function AddUrl($loc,$lastmod='',$changefreq='',$priority='')
	{
		$url = new Zymurgy_SiteMapUrl($this,$loc,$lastmod,$changefreq,$priority);
		$this->urls[$url->loc] = $url;
	}

	/**
	 * Render the Google Sitemap XML doument.
	 */
	function Render()
	{
		header('Content-type: application/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\r\n";
		foreach($this->urls as $loc=>$url)
		{
			echo "<url>\r\n".
//				"<loc>http://{$this->DefaultHome}/$loc</loc>\r\n".
				"<loc>http://$loc</loc>\r\n".
				"<lastmod>{$url->lastmod}</lastmod>\r\n".
				"<changefreq>{$url->changefreq}</changefreq>\r\n".
				"<priority>{$url->priority}</priority>\r\n".
				"</url>\r\n";
		}
		echo "</urlset>\r\n";
	}
}
?>
