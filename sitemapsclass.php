<?
class Zymurgy_SiteMapUrl
{
	var $loc,$lastmod,$changefreq,$priority; //Child entities of a Url
	var $SiteMap;
	
	function Zymurgy_SiteMapUrl($SiteMap,$loc,$lastmod='',$changefreq='',$priority='')
	{
		$this->SiteMap = &$SiteMap;
		
		if (substr($loc,0,1) == '/')
		{
			$loc = substr($loc,1); //Strip leading slash
		}
		if ($lastmod == '') {
			$lp = explode('?',$loc);
			$fi = stat($_SERVER['DOCUMENT_ROOT'].'/'.$lp[0]);
			$lastmod = $this->get_iso_8601_date($fi['mtime']);
		}
		else if (is_numeric($lastmod))
		{
			$lastmod = $this->get_iso_8601_date($lastmod);
		}
		if ($changefreq=='') $changefreq = $SiteMap->DefaultFrequency;
		if ($priority=='') $priority = $SiteMap->DefaultPriority;
		if (substr($loc,0,7)!='http://')
			$loc = $SiteMap->DefaultHome.$loc;
		$loc = htmlentities($loc, ENT_QUOTES, 'UTF-8'); 
		$this->loc = $loc;
		$this->lastmod = $lastmod;
		$this->changefreq = $changefreq;
		$this->priority = $priority;
	}
	
	//Thanks to ungu at terong dot com for this.
	function get_iso_8601_date($int_date) {
	   //$int_date: current date in UNIX timestamp
	   $date_mod = date('Y-m-d\TH:i:s', $int_date);
	   $pre_timezone = date('O', $int_date);
	   $time_zone = substr($pre_timezone, 0, 3).":".substr($pre_timezone, 3, 2);
	   $date_mod .= $time_zone;
	   return $date_mod;
	}
}

class Zymurgy_SiteMap
{
	var $DefaultHome; // example: http://www.example.com/
	var $DefaultPriority; // 0 to 1, defaults to .5
	var $DefaultFrequency; // One of always hourly daily weekly monthly yearly or never; defaults to monthly.
	var $urls;
	
	function Zymurgy_SiteMap($DefaultHome,$DefaultPriority = 0.5,$DefaultFrequency='monthly')
	{
		$this->DefaultHome = $DefaultHome;
		$this->DefaultPriority = $DefaultPriority;
		$this->DefaultFrequency = $DefaultFrequency;
		$this->urls = array();
	}
	
	function AddUrl($loc,$lastmod='',$changefreq='',$priority='')
	{
		$url = new Zymurgy_SiteMapUrl($this,$loc,$lastmod,$changefreq,$priority);
		$this->urls[$url->loc] = $url;
	}
	
	function Render()
	{
		header('Content-type: application/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\r\n";
		foreach($this->urls as $loc=>$url)
		{
			echo "<url>\r\n".
				"<loc>http://{$this->DefaultHome}/$loc</loc>\r\n".
				"<lastmod>{$url->lastmod}</lastmod>\r\n".
				"<changefreq>{$url->changefreq}</changefreq>\r\n".
				"<priority>{$url->priority}</priority>\r\n".
				"</url>\r\n";
		}
		echo "</urlset>\r\n";
	}
}
?>
