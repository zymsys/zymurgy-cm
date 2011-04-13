<?php 
interface ZymurgyGeoCoder
{
	function geocode($address);
}

class ZymurgyGeoCoderPoint
{
	protected $lat;
	protected $lng;
	
	function __construct($lat,$lng)
	{
		$this->lat = $lat;
		$this->lng = $lng;
	}
	
	function getlat()
	{
		return $this->lat;
	}
	
	function getlng()
	{
		return $this->lng;
	}
}

class ZymurgyGoogleGeoCoder implements ZymurgyGeoCoder
{
	protected $googlekey;
	
	function __construct($googlekey)
	{
		$this->googlekey = $googlekey;
	}
	
	function geocode($address)
	{
		$key = 'GOOGLE_GEOCODE: '.$address;
		$json = Zymurgy::longcache_read($key);
		if ($json === false)
		{
			$url = "http://maps.google.com/maps/geo?q=".urlencode($address)."&output=json&key=".$this->googlekey;
			$json = file_get_contents($url);
			Zymurgy::longcache_write($key, $json);
		}
		$o = json_decode($json);
		if (!is_object($o))
		{
			throw new Exception("JSON Response expected but something else was returned: ".$json, 0);
		}
		$result = false;
		if (property_exists($o, 'Placemark'))
		{
			$firstmark = array_shift($o->Placemark);
			if (property_exists($firstmark, 'Point'))
			{
				$point = $firstmark->Point;
				if (property_exists($point, 'coordinates'))
				{
					$coords = $point->coordinates;
					$result = new ZymurgyGeoCoderPoint($coords[1],$coords[0]);
				}
			}
		}
		return $result;
	}
}
?>