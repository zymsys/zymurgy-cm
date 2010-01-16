<?
class ZymurgyRSSFeed
{
	//Required RSS
	public $title;
	public $link;
	public $description;
	
	//Optional RSS
	public $language;
	public $copyright;
	public $managingEditor;
	public $webMaster;
	public $pubDate;
	public $lastBuildDate;
	public $category;
	public $generator;
	public $docs;
	public $cloud;
	public $ttl;
	public $image;
	public $rating;
	public $textInput;
	public $skipHours;
	public $skipDays;
	
	//Child Elements
	public $items = array();
	
	/**
	 * Current item being parsed
	 *
	 * @var ZymurgyRSSFeedItem
	 */
	private $item;

	public function startElementHandler($parser, $name, $attribs, $depth)
	{
		switch ($depth) {
			case 3:
				if ($name == 'item')
				{
					$this->item = new ZymurgyRSSFeedItem();
				}
				break;
			default:
				if (isset($this->item))
				{
					$this->item->startElementHandler($parser, $name, $attribs, $depth);
				}
				break;
		}
	}
	
	public function endElementHandler($parser, $name, $depth, $chardata)
	{
		switch ($depth) {
			case 3:
				if ($name == 'item')
				{
					$this->items[] = $this->item;
					unset($this->item);
				}
				else if (property_exists($this,$name))
				{
					$this->$name = trim($chardata);
				}
				break;
			default:
				if (isset($this->item))
				{
					$this->item->endElementHandler($parser, $name, $depth, $chardata);
				}
				break;
		}
	}
}

class ZymurgyRSSFeedItem
{
	public $title;
	public $link;
	public $description;
	public $author;
	public $category;
	public $comments;
	public $enclosure;
	public $guid;
	public $pubDate;
	public $source;
	
	public function startElementHandler($parser, $name, $attribs, $depth)
	{
		//Don't care, wait for the end of the element.
	}
	
	public function endElementHandler($parser, $name, $depth, $chardata)
	{
		switch ($depth) {
			case 4:
				if (property_exists($this, $name))
				{
					$this->$name = trim($chardata);
				}
				break;
			default:
				//Don't know any elements below item, so do nothing.
				break;
		}
	}
}

class ZymurgyRSSFeedReader
{
	private $depth = 0;
	private $chardata = '';
	
	/**
	 * Current channel being built
	 *
	 * @var ZymurgyRSSFeed
	 */
	private $channel;
	
	public $channels = array();
	
	public function startElementHandler($parser, $name  , array $attribs)
	{
		$this->depth++;
		switch ($this->depth)
		{
			case 1:
				switch ($name)
				{
					case 'rss':
						//Good, we were expecting an RSS feed.
						break;
					default:
						throw new Exception("Unexpected XML type: $name",0);
						break;
				}
				break;
			case 2:
				switch ($name)
				{
					case 'channel':
						$this->channel = new ZymurgyRSSFeed();
						break;
					default:
						//Ignore any other elements
						unset($this->channel);
						break;
				}
				break;
			default:
				if (isset($this->channel))
				{
					//Let the channel handle it.
					$this->channel->startElementHandler($parser, $name, $attribs, $this->depth);
				}
				else
				{
					//We don't know the element we're in; ignore this.
				}
				break;
		}
	}
	
	public function endElementHandler($parser, $name)
	{
		switch ($this->depth)
		{
			case 1:
				//Done reading RSS feed.  Do nothing.
				break;
			case 2:
				switch ($name)
				{
					case 'channel':
						$this->channels[] = $this->channel;
						unset($this->channel);
						break;
					default:
						//Ignore any other elements
						break;
				}
				break;
			default:
				$this->channel->endElementHandler($parser, $name, $this->depth, $this->chardata);
				break;				
		}
		$this->chardata = '';
		$this->depth--;
	}
	
	public function characterDataHandler($parser, $data)
	{
		$this->chardata .= $data;
	}
	
	public function __construct($rssurl)
	{
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($parser,$this);
		xml_set_element_handler($parser, "startElementHandler", "endElementHandler");
		xml_set_character_data_handler($parser, "characterDataHandler");
  		if(!xml_parse($parser, file_get_contents($rssurl)))
		{
			xmlHandler::setErr("Couldn't read XML");
  		}
		xml_parser_free($parser);
	}
}

/*
$r = new ZymurgyRSSFeedReader('http://rss.news.yahoo.com/rss/topstories');
echo "<pre>"; print_r($r);
*/
?>