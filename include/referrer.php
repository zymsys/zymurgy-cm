<?php
/**
 * Takes a URL and works out information from it such as any search strings embedded in the query string part of the address.
 * This information is then exposed through public variables as listed below.
 *
 */ 
class Referrer
{
	/**
	 * The protocol used to deliver the referrer.  Either http or https.
	 *
	 * @var string
	 */
	public $protocol;
	
	/**
	 * The hostname part of the referrer such as www.google.com.
	 *
	 * @var string
	 */
	public $host;
	
	/**
	 * The document part of the referrer such as search.aspx.
	 *
	 * @var string
	 */
	public $document;
	
	/**
	 * The query string part of the URL such as q=zymurgy&lang=en.  Use $getparams for a parsed array of these similar to $_GET.
	 *
	 * @var string
	 */
	public $querystring;
	
	/**
	 * The search string used to find the page if the referrer looks like a search engine result page.  For exmaple, if the
	 * query string was q=zymurgy&lang=en then $searchstring would be zymurgy. 
	 *
	 * @var string
	 */
	public $searchstring;
	
	/**
	 * If $host looks like it belongs to a recognised search engine, the name of that search engine is provided here.
	 *
	 * @var string
	 */
	public $searchengine;
	
	/**
	 * An array of get parameters from the referrer, similar to $_GET.
	 *
	 * @var array
	 */
	public $getparms = array();
	
	/**
	 * Search strings to figure out search engine names from host names.
	 *
	 * @var array
	 */
	private $sehosts = array(
		'google' => 'Google',
		'doubleclick' => 'Google',
		'yahoo' => 'Yahoo!',
		'msn' => 'MSN Live',
		'live' => 'MSN Live',
		'incredimail' => 'MyStart by IncrediMail',
		'ask' => 'Ask.com'
	);
	
	/**
	 * Doubleclick referrers are given special treatment, and this flag is used to turn on that treatment internally.
	 *
	 * @var boolean
	 */
	private $isdoubleclick = FALSE;
	
	/**
	 * Parse the supplied URL as a referrer and return an instance of Referrer which contains the useful information from that URL.
	 *
	 * @param string $referrer
	 */
	function __construct($referrer)
	{
		$this->parseReferrer($referrer);
		if ($this->isdoubleclick)
		{
			//Doubleclick wipes out the normal referrer info we get from adwords, but makes up for it by passing it in a get parameter.
			//Act as though that was the real referrer.
			//doubleclick offers an extra bonus...  The referrers referrer.  If we don't have a search string yet, we can try here.
			$r = new Referrer($this->getparms['ref']);
			$url = $this->getparms['url'];
			$this->protocol = $this->host = $this->document = $this->querystring = $this->searchengine = '';
			$this->getparms = array(); //Clear out the other doubleclick junk
			$this->parseReferrer($url);
			if (empty($this->searchstring))
			{
				$this->searchstring = $r->searchstring;
			}
		}
		unset($this->sehosts); //Clean up print_r output
	}
	
	/**
	 * Actually do the parsing of the URL.
	 *
	 * @param string $referrer
	 */
	private function parseReferrer($referrer)
	{
		list($this->protocol,$s) = $this->getchunk('://',$referrer,2);
		if (($this->protocol!='http') && ($this->protocol!='https'))
			return; //Don't bother parsing anything other than http/https.
		list($this->host,$s) = $this->getchunk('/',$s,2);
		list($this->document,$this->querystring) = $this->getchunk('?',$s,2);
		//Try to get the source search engine by looking for strings in the host name
		foreach($this->sehosts as $host=>$sename)
		{
			if (strpos($this->host,".$host.")!==FALSE)
			{
				$this->searchengine = $sename;
				$this->isdoubleclick = ($host=='doubleclick');
				break;
			}
		}
		if ($this->querystring)
		{
			$qp = explode('&',$this->querystring);
			foreach($qp as $p)
			{
				$pp = explode('=',$p,2);
				$this->getparms[$pp[0]] = urldecode($pp[1]); 
			}
		}
		//Try to get the query string used in a search engine if any by using common query parameter names.
		if (array_key_exists('q',$this->getparms))
			$this->searchstring = $this->getparms['q'];
	}
	
	/**
	 * Returns an array of two strings, the part before $delimiter and the part after it.  Instead of strings it will return FALSE in the
	 * array if there is no string to parse out.
	 *
	 * @param string $delimiter
	 * @param string $string
	 * @return boolean
	 */
	private function getchunk($delimiter,$string)
	{
		$sp = explode($delimiter,$string,2);
		while (count($sp)<2) $sp[] = FALSE;
		return $sp;
	}
}
?>