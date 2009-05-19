<?php
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
	class Locale
	{
		private $m_cache = array();
		private $m_languageCode;
		private $m_defaultLocale;

		private $m_localeFilepath;

		public function Locale(
			$languageCode,
			$filepath,
			$defaultLocale)
		{
			$this->m_languageCode = $languageCode;
			$this->m_localeFilepath = $filepath;
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
				// echo("From cache.<br>");

				$value = $this->m_cache[$key]->Value;
			}
			else
			{
				$xmlString = file_get_contents(
					$this->m_localeFilepath);

				$xml = new SimpleXMLElement($xmlString);

				foreach($xml->xpath("//string[@id='$key']") as $item)
				{
					// print_r($item);

					$localeItem = new LocaleItem();
					$localeItem->Value = $item;
					$localeItem->TimeAdded = time();
					$this->m_cache[$key] = $localeItem;

					$value = $item[0];
				}
			}

			if($value == "n/a" && $this->m_defaultLocale !== null)
			{
				// echo("From default locale");

				$value = $this->m_defaultLocale->GetString($key);
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

			$defaultLocale = new Locale(
				"en",
				Zymurgy::$root."/zymurgy/include/locale.en.xml",
				null);
			$locales["en"] = $defaultLocale;

			$di = opendir(Zymurgy::$root."/zymurgy/include/");
			while (($entry = readdir($di)) !== false)
			{
				if(preg_match("/locale.[A-z][A-z].xml/", $entry))
				{
					$languageCode = str_replace("locale.", "", str_replace(".xml", "", $entry));

					if($languageCode !== "en")
					{
						$locale = new Locale(
							$languageCode,
							Zymurgy::$root."/zymurgy/include/".$entry,
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