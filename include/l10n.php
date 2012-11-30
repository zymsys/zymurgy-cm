<?php
/**
 * referenced from include/media.php (orphanned) through cmo.php
 *
 * @package Zymurgy
 * @access private
 */

	// ini_set("display_errors", 1);

	/**
	 * Zymurgy:CM
	 * Localization Module
	 *
	 * This class exposes the methods used by a single locale, as defined by a locale
	 * XML file using the following format:
	 *
	 * <?xml version="1.0"?>
	 * <locale>
	 *     <string id="key">value</string>
	 * </locale>
	 */
	class ZymurgyLocale
	{
		private $m_cache = array();
		private $m_languageCode;
		private $m_defaultLocale;

		private $m_localeFilepath;
		private $m_customFilepath;

		public function ZymurgyLocale(
			$languageCode,
			$filepath,
			$customfilepath,
			$defaultLocale)
		{
			$this->m_languageCode = $languageCode;
			$this->m_localeFilepath = $filepath;
			$this->m_customFilepath = $customfilepath;
			$this->m_defaultLocale = $defaultLocale;
		}

		/**
		 * Get the string from the locale.code.xml file for the given key. If the key
		 * has already been retrieved in the last 24h, get it from the cache.
		 *
		 * @param string $key
		 * @return string
		 */
		public function GetString($key)
		{
			$value = "n/a";

			if(key_exists($key, $this->m_cache)
				&& $this->m_cache[$key]->TimeAdded < time() + (60 * 60 * 24 * 1000))
			{
				//echo("\r\n<!--From cache ($key). -->\r\n");

				$value = $this->m_cache[$key]->Value;
			}
			else
			{
//				echo("\r\n<!--Looking for {$this->m_customFilepath} -->\r\n");
				if(file_exists($this->m_customFilepath))
				{
					//echo("\r\n<!--From custom ($key) -->\r\n");
					$value = $this->GetStringFromFile($key, $this->m_customFilepath);
				}

				if($value == "n/a")
				{
					//echo("\r\n<!--From standard locale ($key) -->\r\n");
					$value = $this->GetStringFromFile($key, $this->m_localeFilepath);
				}
			}

			if($value == "n/a" && $this->m_defaultLocale !== null)
			{
				//echo("\r\n<!--From default locale ($key) -->\r\n");

				$value = $this->m_defaultLocale->GetString($key);
			}

			return $value;
		}

		private function GetStringFromFile($key, $filename)
		{
			$value = "n/a";

			$xmlString = file_get_contents($filename);

			// $xpath = "//string[@id='$key']";
			$xpath = "/";
			$pathArray = explode(".", $key);

			foreach($pathArray as $pathItem)
			{
				$xpath .= "/*[@name='$pathItem']";
			}

			$xml = new SimpleXMLElement($xmlString);
			$snippit = $xml->xpath($xpath);

			//echo "<pre>"; print_r($snippit); echo "</pre>";
			// echo("<br>");

			if(is_array($snippit) && count($snippit) > 0)
			{
				foreach($snippit as $item)
				{
					$item->children();
					//echo "<pre>"; print_r($item->children()); echo "</pre>";
					//echo("Processing {$item}.<br>");

					$localeItem = new LocaleItem();
					$localeItem->Value = $item;
					$localeItem->TimeAdded = time();
					$this->m_cache[$key] = $localeItem;

					$value = $item;
				}
			}

			return $value;
		}
	}

	/**
	 * The locale item class used to store a string definition in the cache.
	 *
	 */
	class LocaleItem
	{
		public $Value;
		public $TimeAdded;
	}

	/**
	 * Locale Factory.
	 *
	 * Used on Zymurgy:CM init to create the locale objects used when calling
	 * Zymurgy::GetLocaleString()
	 */
	class LocaleFactory
	{
		/**
		 * Get the locales from their default location. Used to populate the
		 * locales object in the Zymurgy CMO.
		 *
		 * @return Locale[]
		 */
		public static function GetLocales()
		{
			$locales = array();

			$defaultLocale = new ZymurgyLocale(
				"en",
				Zymurgy::getFilePath("~include/locale.en.xml"),
				Zymurgy::getFilePath("~custom/locale.en.xml"),
				null);
			$locales["en"] = $defaultLocale;

			$di = opendir(Zymurgy::getFilePath("~include/"));
			while (($entry = readdir($di)) !== false)
			{
				if(preg_match("/locale.[A-z][A-z].xml/", $entry))
				{
					$languageCode = str_replace("locale.", "", str_replace(".xml", "", $entry));

					if($languageCode !== "en")
					{
						$locale = new ZymurgyLocale(
							$languageCode,
							Zymurgy::getFilePath("~include/".$entry),
							Zymurgy::getFilePath("~custom/".$entry),
							$defaultLocale);

						$locales[$languageCode] = $locale;
					}
				}
			}
			closedir($di);

			return $locales;
		}
	}
?>