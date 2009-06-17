<?
class ZIW_Base
{
	public $xlatehtmlentities = true;
	public $extra = array();
	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		if (array_key_exists($postname,$_POST))
			return $_POST[$postname];
		else
			return '';
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		return $display;
	}

	/**
	 * Get output needed before any instances of this widget are rendered.
	 *
	 * @param array $tp Input-spec exploded parts, broken up by .'s
	 * @return string
	 */
	function GetPretext($tp)
	{
		return '';
	}

	/**
	 * Return javascript code that should appear above the use of this widget as part of it's initialization.
	 * Similar to GetPretext, except this is placed inside <script> tags.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	function JSRender($ep,$name,$value)
	{
		return '';
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "Don't know how to render a '{$ep[0]}'.";
	}

	/**
	 * Determine if the data entered into this widget is valid. Used by form
	 * validation routines on widgets containing dates.
	 *
	 * The base class always returns True. It is up to the derived classes
	 * to override this and provide true validation.
	 *
	 * @param string $value
	 * @return boolean True, if the data is valide
	 */
	function IsValid($value)
	{
		return true;
	}
}

class ZIW_Input extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "<input type=\"text\" size=\"{$ep[1]}\" maxlength=\"{$ep[2]}\" id=\"$name\" name=\"".
			"$name\" value=\"$value\" />";
	}
}

class ZIW_TextArea extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "<textarea id=\"$name\" name=\"".
			"$name\" rows=\"{$ep[2]}\" cols=\"{$ep[1]}\">$value</textarea>";
	}
}

class ZIW_Hidden extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$value\" />";
	}
}

class ZIW_Password extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "<input type=\"password\" size=\"{$ep[1]}\" maxlength=\"{$ep[2]}\" id=\"$name\" name=\"".
			"$name\" value=\"$value\" />";
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		return "*******";
	}
}

class ZIW_CheckBox extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "<input type=\"checkbox\" id=\"$name\" name=\"$name\"";
		if (isset($ep[1]) && ($ep[1]=='checked') || ($value!='')) echo " checked=\"checked\"";
		echo " />";
	}
}

class ZIW_Money extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$m = $value;
		if ($this->extra['UsePennies'])
			$m = $m / 100;
		$m = '$'.number_format($m,2,'.',',');
		echo "<input type=\"text\" id=\"$name\" name=\"$name\" value=\"$m\" />";
	}

	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$m = parent::PostValue($ep,$postname);
		$m = str_replace(array("$",","),'',$m);
		if ($this->extra['UsePennies'])
			$m = $m * 100;
		return $m;
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		return '$'.number_format($this->extra['UsePennies'] ? ($display / 100) : $display,2,'.',',');
	}
}

class ZIW_InputSpec extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		require_once(Zymurgy::$root."/zymurgy/include/inputspec.php");
		echo "<script>makeInputSpecifier('".
			str_replace("'","\'",$name)."','".
			str_replace("'","\'",$value)."');</script>";
	}
}

class ZIW_Lookup extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo $this->extra['lookups'][$ep[1]]->RenderDropList($name,$value);
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		$values = $this->extra['lookups'][$ep[1]]->values;

		return array_key_exists($display, $values)
			? $values[$display]
			: "";
	}
}

abstract class ZIW_AutoCompleteBase extends ZIW_Base
{
	static public $ZymurgyAutocompleteZIndex = 9000;
	static public $acwidth = 200;
	protected $name;
	protected $jsname;
	protected $textvalue;

	abstract function PreRender($ep,$name,$value); //Must set $textvalue

	function RenderJS()
	{

	}

	function GetHint()
	{
		return ''; //Decendants may override this to put a hint into the autocomplete box
	}

	function Render($ep,$name,$value)
	{
		$this->name = $name;
		$this->jsname = str_replace('.','_',$name);
		$this->PreRender($ep,$name,$value);
		echo "<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"".
			addslashes($this->textvalue)."\"/>";
		/*if ($isplugin)
		{
			echo "<div style=\"float:left; margin-left:6px\">";
		}*/
		echo Zymurgy::YUI('autocomplete/assets/skins/sam/autocomplete.css');
		echo Zymurgy::YUI('yahoo-dom-event/yahoo-dom-event.js');
		echo Zymurgy::YUI('datasource/datasource-min.js');
		echo Zymurgy::YUI('get/get-min.js');
		echo Zymurgy::YUI('connection/connection-min.js');
		echo Zymurgy::YUI('animation/animation-min.js');
		echo Zymurgy::YUI('json/json-min.js');
		echo Zymurgy::YUI('autocomplete/autocomplete-min.js');
		echo Zymurgy::YUI('datasource/datasource-min.js');
		echo Zymurgy::RequireOnce('/zymurgy/include/yui-stretch.js');
		echo Zymurgy::RequireOnce('/zymurgy/include/cmo.js');
		echo "<div id=\"{$name}-autocomplete\" style=\"width: ".ZIW_AutoComplete::$acwidth."px\"><input id=\"{$name}-input\" type=\"text\" ";
		$hint = $this->GetHint();
		if (!empty($hint))
		{
			echo "title=\"$hint\" ";
		}
		echo "value=\"".htmlentities($this->textvalue)."\" onchange=\"{$this->jsname}_update\" />";
		echo "<div id=\"{$name}-container\" style=\"z-index:".ZIW_AutoComplete::$ZymurgyAutocompleteZIndex."\"></div></div>";
		echo "<div style=\"float:left;z-index:".ZIW_AutoComplete::$ZymurgyAutocompleteZIndex.";";
		echo " margin-left:".(ZIW_AutoComplete::$acwidth+5)."px";
		echo "\"><input type=\"button\" value=\"&raquo;\" onclick=\"{$this->jsname}_autocomp.toggleContainer(); Zymurgy.toggleText(this,'&raquo;','&laquo;');\" /></div>";
		echo "</div>";
		echo '<script type="text/javascript">
			'.$this->jsname.'_text = document.getElementById("'.$name.'-input");
			'.$this->jsname.'_plugin = document.getElementById("'.$name.'-plugin");
			'.$this->jsname.'_hidden = document.getElementById("'.$name.'");
			';
		if (!empty($hint))
		{
			echo "Zymurgy.enableHint({$this->jsname}_text);\r\n";
		}
		$this->RenderJS();
		echo "</script>\r\n";
		ZIW_AutoComplete::$ZymurgyAutocompleteZIndex++;
	}
}

class ZIW_Plugin extends ZIW_AutoCompleteBase
{
	function PreRender($ep,$name,$value)
	{
		$ep = explode('&',$value);
		$pluginvalue = urldecode($ep[0]);
		$this->textvalue = urldecode($ep[1]);
		echo "<div style=\"float:left\">";
		echo "<select id=\"{$name}-plugin\" name=\"{$name}-plugin\">\r\n\t<option value=\"\">Choose a Plugin</option>\r\n";
		$ri = Zymurgy::$db->run("select id,name from zcm_plugin order by name");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "\t<option value=\"{$row['id']}\"";
			if ($row['name'] == $pluginvalue)
				echo " selected=\"selected\"";
			echo ">{$row['name']}</option>\r\n";
		}
		echo "</select>";
		echo "</div>";
	}

	function RenderJS()
	{
		if (empty($this->textvalue))
			echo "{$this->jsname}_text.disabled = true;\r\n";
		echo 'function '.$this->jsname.'_update() {
				if ('.$this->jsname.'_text.disabled || '.$this->jsname.'_plugin.value == 0)
					'.$this->jsname.'_hidden.value = "&";
				else
					'.$this->jsname.'_hidden.value = escape('.$this->jsname.'_plugin.options['.$this->jsname.'_plugin.selectedIndex].text) + "&" + escape('.$this->jsname.'_text.value);
			}
			YAHOO.util.Event.addListener("'.$this->name.'-plugin", "change", function () {
			if (this.value == "")
			{
				'.$this->jsname.'_text.disabled = true;
				YAHOO.util.Dom.setAttribute('.$this->jsname.'_text,"title","Choose a Plugin First");
			}
			else
			{
				'.$this->jsname.'_text.disabled = false;
				YAHOO.util.Dom.setAttribute('.$this->jsname.'_text,"title","Select one, or name a new one");
			}
			Zymurgy.refreshHint('.$this->jsname.'_text);
			'.$this->jsname.'_update();
		});
		var '.$this->jsname.'_datasource = new YAHOO.util.XHRDataSource("/zymurgy/include/plugin.php?pg='.$_GET['d'].'&");
			'.$this->jsname.'_datasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSARRAY;
			'.$this->jsname.'_datasource.responseSchema = {fields : ["plugin"]};
			var '.$this->jsname.'_autocomp = new YAHOO.widget.AutoComplete("'.$this->name.'-input","'.$this->name.'-container", '.$this->jsname.'_datasource);
			'.$this->jsname.'_autocomp.textboxChangeEvent.subscribe('.$this->jsname.'_update);
			'.$this->jsname.'_autocomp.generateRequest = function(sQuery) {
				var elSel = document.getElementById("'.$this->name.'-plugin");
				return "/zymurgy/include/plugin.php?pg='.$_GET['d'].'&pi=" + elSel.value + "&q=" + sQuery;
			};

			';
	}

	function GetTitle()
	{
		return 'Choose a Plugin First';
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		$ep = explode('&',$display);
		return urldecode($ep[0]).' ('.urldecode($ep[1]).')';
	}
}

class ZIW_AutoComplete extends ZIW_AutoCompleteBase
{
	private $table;
	private $idcolumn;
	private $column;

	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$table = $ep[1];
		$idcolumn = $ep[2];
		$column = $ep[3];
		$postvalue = $_POST[$postname];
		$autocreate = array_key_exists(4,$ep) ? ($ep[4] == 'true') : false;
		$sql = "select `$idcolumn` from `$table` where `$column`='".
			Zymurgy::$db->escape_string($postvalue)."'";
		$value = Zymurgy::$db->get($sql);
		if (!$value && $autocreate)
		{
			//Autocreate unknown entry from autocomplete widget
			$newrecord = array("$table.$column"=>$postvalue);
			if (!empty($this->extra['OnBeforeAutoInsert']))
				$newrecord = call_user_func($this->extra['OnBeforeAutoInsert'], $table, $newrecord);
			$sql = "insert into $table (".
				implode(',',array_keys($newrecord)).") values ('".
				implode("','",$newrecord)."')";
			Zymurgy::$db->run($sql);
			$value = $newrecord[$idcolumn] = Zymurgy::$db->insert_id();
			if (!empty($this->extra['OnAutoInsert']))
				call_user_func($this->extra['OnAutoInsert'],$table, $newrecord);
		}
		return $value;
	}

	function PreRender($ep,$name,$value)
	{
		$this->table = $ep[1];
		$this->idcolumn = $ep[2];
		$this->column = $ep[3];
		$this->textvalue = $this->Display($ep,$value,null);
	}

	function RenderJS()
	{
		echo 'var '.$this->jsname.'_datasource = new YAHOO.util.XHRDataSource("/zymurgy/include/autocomplete.php");
			'.$this->jsname.'_datasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
			'.$this->jsname.'_datasource.responseSchema = {
				resultsList : "results",
				fields : ["value"]};
			var '.$this->jsname.'_autocomp = new YAHOO.widget.AutoComplete("'.$this->name.'-input","'.$this->name.'-container", '.$this->jsname.'_datasource);
			'.$this->jsname.'_autocomp.generateRequest = function(sQuery) {
				return "/zymurgy/include/autocomplete.php?t='.urlencode($this->table).'&c='.
					urlencode($this->column).'&i='.urlencode($this->idcolumn).'&q=" + sQuery;
			};
			function '.$this->jsname.'_update() {
				'.$this->jsname.'_hidden.value = escape('.$this->jsname.'_text.value);
			}
			'.$this->jsname.'_autocomp.textboxChangeEvent.subscribe('.$this->jsname.'_update);
			';
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		$table = $ep[1];
		$idcolumn = $ep[2];
		$column = $ep[3];
		$autocreate = array_key_exists(4,$ep) ? ($ep[4] == 'true') : false;
		$sql = "select `$column` from `$table` where `$idcolumn`='".
			Zymurgy::$db->escape_string($display)."'";
		return Zymurgy::$db->get($sql);
	}
}

class ZIW_RadioDrop extends ZIW_Base
{
	/**
	 * Unserialize source if it begins a: and ends } for items that take arrays.
	 * For simpler population outside of PHP fall back to CSV with an associative array of values to themselves.
	 *
	 * @param string $source
	 */
	function HackedUnserialize($source)
	{
		if ((substr($source,0,2)=='a:') && (substr($source,-1)=='}'))
			$r = unserialize($source);
		else
		{
			$r = array();
			$sp = explode(',',$source);
			foreach ($sp as $val)
				$r[$val] = $val;
			return $r;
		}
		return $r;
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		$ep = explode('.',implode('.',$ep),2); //Otherwise arrays which contain a . will cause it to barf.
		$ritems = $this->HackedUnserialize($ep[1]);
		return $ritems[$display];
	}
}

class ZIW_Radio extends ZIW_RadioDrop
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$rp = $ep;
		array_shift($rp);
		$radioarray = $this->HackedUnserialize(implode('.',$rp));
		foreach($radioarray as $rkey=>$rcaption)
		{
			echo "<label><input type=\"radio\" id=\"$name-$rkey\" name=\"$name\" value=\"$rkey\"";
			if ($value == $rkey) echo " checked=\"checked\"";
			echo " />$rcaption</label><br />\r\n";
		}
	}
}

class ZIW_Drop extends ZIW_RadioDrop
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$rp = $ep;
		array_shift($rp);
		$radioarray = $this->HackedUnserialize(implode('.',$rp));
		echo "<select id=\"$name\" name=\"$name\">\r\n";
		foreach($radioarray as $rkey=>$rcaption)
		{
			echo "<option value=\"$rkey\"";
			if ($value == $rkey) echo " selected=\"selected\"";
			echo " />$rcaption<br />\r\n";
		}
		echo "</select>\r\n";
	}
}

class ZIW_Time extends ZIW_Base
{
	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$t = strtotime($_POST[$postname]);
		return date('H:i:s',$t);
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		//Convert 24hr clock to hh:mm am/pm
		return date("g:i a",strtotime($display));
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$value = date("g:i a",strtotime($value));
		echo "<input type=\"text\" size=\"8\" maxlength=\"8\" id=\"$name\" name=\"".
			"$name\" value=\"$value\" /> <i>hh:mm am/pm</i>";
	}
}

abstract class ZIW_DateBase extends ZIW_Base
{
	protected $unixdate;
	protected $caloptions = array('firstDay'       => 0, // show Monday first
                 'showOthers'     => true,
                 'timeFormat'     => '12');
    protected $calattributes = array('style' => 'width: 15em; color: #840; background-color: #ff8; border: 1px solid #000; text-align: center');

	abstract function SetCalendarParams($date);

	function GetJSFormat()
	{
		return '%Y-%m-%d';
	}

	function GetFormat()
	{
		return 'Y-m-d';
	}

	function ToUnixTime($tm)
	{
		return $tm;
	}

	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		//echo "[f:".$this->GetFormat().",tu:".$this->ToUnixTime($display).",d:$display]";
		return date($this->GetFormat(),$this->ToUnixTime($display));
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		require_once(Zymurgy::$root."/zymurgy/jscalendar/calendar.php");
		$date = $this->unixdate = $this->ToUnixTime($value);
		if ($date == 0) $date=time();
		$cal = new DHTML_Calendar(
			"/zymurgy/jscalendar/",
			'en',
			'calendar-win2k-2',
			false);

		$cal->SetFieldPrefix($name);
		$cal->SetIncludeID(false);

		$cal->load_files();
		$this->SetCalendarParams($date);
		$this->calattributes['name'] = $name;
		$cal->make_input_field($this->caloptions,$this->calattributes);
	}
}

class ZIW_UnixDate extends ZIW_DateBase
{
	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$dp = explode("-",$_POST[$postname]);
		return mktime(0,0,0,$dp[1],$dp[2],$dp[0]);
	}

	function SetCalendarParams($date)
	{
		$format = $this->GetJSFormat();
		$this->caloptions['ifFormat'] = $format;
		$this->calattributes['value'] = strftime($format, $date);
	}
}

class ZIW_Date extends ZIW_DateBase
{
	function SetCalendarParams($date)
	{
		$format = $this->GetJSFormat();
		$this->caloptions['ifFormat'] = $format;
		$this->calattributes['value'] = strftime($format, $date);
	}

	function ToUnixTime($tm)
	{
		return strtotime($tm);
	}
}

abstract class ZIW_DateTimeBase extends ZIW_DateBase
{
	function GetFormat()
	{
		return 'Y-m-d [g:i A]';
	}

	function GetJSFormat()
	{
		return '%Y-%m-%d [%I:%M %p]';
	}

	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$pp = explode(" ",$_POST[$postname]);
		$dp = explode("-",$pp[0]);
		$tp = explode(":",$pp[1]);
		$tp[0] = substr($tp[0],1);
		if ($pp[2]=='PM]')
		{
			if ($tp[0] < 12)
				$tp[0] += 12;
		}
		else
		{
			if ($tp[0]==12)
				$tp[0] = 0;
		}
		return mktime($tp[0],$tp[1],0,$dp[1],$dp[2],$dp[0]);
	}
}

class ZIW_UnixDateTime extends ZIW_DateTimeBase
{
	function SetCalendarParams($date)
	{
		$format = '%Y-%m-%d [%I:%M %p]';
		$this->caloptions['ifFormat'] = $format;
		$this->caloptions['showsTime'] = 1;
		$this->calattributes['value'] = strftime($format, $date);
	}
}

class ZIW_DateTime extends ZIW_DateTimeBase
{
	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$tm = parent::PostValue($ep,$postname);
		return strftime('%Y-%m-%d %H:%M:%S',$tm);
	}

	function ToUnixTime($tm)
	{
		return strtotime($tm);
	}

	function SetCalendarParams($date)
	{
		$format = '%Y-%m-%d [%I:%M %p]';
		$this->caloptions['ifFormat'] = $format;
		$this->caloptions['showsTime'] = 1;
		$this->calattributes['value'] = strftime($format, $date);
	}
}

class ZIW_Image extends ZIW_Base
{
	/**
	 * Take a value as it comes from the database, and make it suitable for display
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $display
	 * @param InputWidget $shell
	 */
	function Display($ep,$display,$shell)
	{
		if (isset($this->targetsize))
			$targetsize = $this->targetsize;
		else
			$targetsize = "{$ep[1]}x{$ep[2]}";
		require_once(Zymurgy::$root.'/zymurgy/include/Thumb.php');
		$ext = Thumb::mime2ext($display);
		list($dataset,$datacolumn) = explode('.',$this->extra['datacolumn']);
		$imgsrc = "/zymurgy/file.php?mime=$display&dataset=$dataset&datacolumn=$datacolumn&id={$this->extra['editkey']}&w={$ep[1]}&h={$ep[2]}";
		//$imgsrc = "/UserFiles/DataGrid/{$this->datacolumn}/{$this->editkey}thumb$targetsize.$ext?".rand(0,99999);
		return "<img id=\"sitetext.body.$targetsize\" src=\"$imgsrc\" /></a>";
	}

	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$file = $_FILES[$postname];
		if ($file['type']!='')
			return $file['type'];
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		array_shift($ep); //Remove type
		$ep = explode(',',implode('.',$ep)); //Re-explode on ,
		$thumbs = array();
		if (($this->extra['editkey']==0) && array_key_exists('editkey',$_GET))
			$this->extra['editkey'] = 0 + $_GET['editkey'];
		foreach($ep as $targetsize)
		{
			$targetsize = str_replace('.','x',$targetsize);
			if ($this->extra['editkey'] > 0)
			{
				require_once(Zymurgy::$root."/zymurgy/include/Thumb.php");
				$ext = Thumb::mime2ext($value);
				$imgsrc = "/UserFiles/DataGrid/".$this->extra['datacolumn']."/".$this->extra['editkey']."thumb$targetsize.$ext";
				if (!file_exists(Zymurgy::$root.$imgsrc))
					$imgsrc = "/zymurgy/file.php?dataset=&datacolumn=&id=&mime="; //Creates blank gif file.
				$thumbs[] = "<a onclick=\"aspectcrop_popup('".$this->extra['datacolumn']."','$targetsize','".$this->extra['editkey']."','".$this->extra['datacolumn'].".$targetsize',true)\">".
					"<img id=\"".$this->extra['datacolumn'].".$targetsize\" src=\"$imgsrc?".rand(0,99999)."\" style=\"cursor: pointer\" /></a> ";
			}
		}
		echo "<table><tr><td valign=\"center\"><input type=\"file\" id=\"$name\" name=\"$name\" /></td><td>".implode($thumbs,"</td><td>")."</td></tr></table>";
	}
}

class ZIW_Attachment extends ZIW_Base
{
	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$output = "";

		if(strlen($value) > 0)
		{
			$dsItems = explode(".", $name);
			$tableName = $dsItems[0];
			$fieldName = $dsItems[1];

			$output .= "<input type=\"hidden\" name=\"clear$name\" value=\"0\">";
			$output .= "Currently: <a href=\"file.php?dataset=$tableName&amp;datacolumn=$fieldName&amp;mime=$value&amp;id={$_GET["editkey"]}\">$value</a>";
			$output .= " <input type=\"button\" id=\"btnClear$name\" name=\"btnClear$name\" value=\"Clear\" ";
			$output .= "onclick=\"document.datagridform['clear$name'].value=1;document.datagridform.submit();\">";
			$output .= " Change to:";
		}

		$output .= "<input type=\"file\" id=\"$name\" name=\"$name\" />";

		if(strlen($value) > 0)
		{
		}

		echo $output;
	}

	/**
	 * Take posted value(s) and return the value to be stored in the database
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $postname Posted value name
	 * @return string
	 */
	function PostValue($ep,$postname)
	{
		$file = $_FILES[$postname];
		if ($file['type']!='')
			return $file['type'];
	}
}

class ZIW_YUIHtml extends ZIW_Base
{
	/**
	 * Get output needed before any instances of this widget are rendered.
	 *
	 * @param array $tp Input-spec exploded parts, broken up by .'s
	 * @return string
	 */
	function GetPretext($tp)
	{
		$output  = Zymurgy::YUI("assets/skins/sam/skin.css");
		$output .= Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
		$output .= Zymurgy::YUI("element/element-min.js");
		$output .= Zymurgy::YUI("connection/connection-min.js");
		$output .= Zymurgy::YUI("container/container-min.js");
		$output .= Zymurgy::YUI("button/button-min.js");
		$output .= Zymurgy::YUI("dragdrop/dragdrop-min.js");
		$output .= Zymurgy::YUI("editor/editor-min.js");

		require_once("include/media.php");
		MediaFileView::RenderThumberJavascript(false);
		$output .= PageImageLibraryView::RenderJavascript();
		return $output;
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$dialogName = $this->extra['dialogName'];
		$tabsetName = $this->extra['tabsetName'];
		$tabName = $this->extra['tabName'];
		if($dialogName !== "")
		{
			echo("<div id=\"".
				str_replace(".", "_", $name).
				"_div\"></div>");
		}
		echo("<div id=\"".
			str_replace(".", "_", $name).
			"_dlg\"><div class=\"hd\">Insert Image from Library</div><div id=\"".
			str_replace(".", "_", $name).
			"_dlgBody\" class=\"bd\"></div></div>");

		echo("<textarea id=\"".
			str_replace(".", "_", $name).
			"\" name=\"$name\" cols=\"60\" rows=\"10\">$value</textarea>\r\n");

		?>
			<script type="text/javascript">
				var Display<?= str_replace(".", "_", $name) ?>Exists = true;
				var <?= str_replace(".", "_", $name) ?>Editor;
				var <?= str_replace(".", "_", $name) ?>Dialog;

				function Display<?= str_replace(".", "_", $name) ?>() {
					var myConfig = {
						height: '<?= $ep[2] ?>px',
						width: '<?= $ep[1] ?>px',
						dompath: true,
						focusAtStart: false
					};

					<?= str_replace(".", "_", $name) ?>Editor = new YAHOO.widget.Editor(
						'<?= str_replace(".", "_", $name) ?>',
						myConfig);

					<? if($dialogName !== "") { ?>
					<?= str_replace(".", "_", $name) ?>Editor.on('windowRender', function() {
						document.getElementById('<?= $name ?>_div').appendChild(this.get('panel').element);
					});

					if(typeof <?= $dialogName ?> == "Dialog")
					{
						Link<?= str_replace(".", "_", $name) ?>ToDialog();
					}
					<? } ?>

					<?= str_replace(".", "_", $name) ?>Editor.on("toolbarLoaded", function()
					{
						// alert("toolbarLoaded Start");

						<?= str_replace(".", "_", $name) ?>Dialog = new YAHOO.widget.Dialog(
							"<?= str_replace(".", "_", $name) ?>_dlg",
							{
								width: "400px",
								fixedcenter: true,
								visible: false,
								constraintoviewport: true,
								buttons: [
									{ text: "OK", handler: function() {
										//alert("OK pressed");
										InsertMediaFileInPage(<?= str_replace(".", "_", $name) ?>Editor);
										//alert("media inserted");
										this.cancel();
									}, isDefault: true },
									{ text: "Cancel", handler: function() { this.cancel(); } }
								]
							});

						// alert("-- mediaFileDialog defined");

						var mediaFileImageConfig = {
							type: "push",
							label: "Insert Image from Library",
							value: "mediafile"
						};

						<?= str_replace(".", "_", $name) ?>Editor.toolbar.addButtonToGroup(
							mediaFileImageConfig,
							"insertitem");

						<?= str_replace(".", "_", $name) ?>Editor.toolbar.on(
							"mediafileClick",
							function(ev)
							{
								// alert("mediafileClick Start");
								this._focusWindow();

								if(ev && ev.img)
								{
									// alert("img declared");

									var html = "<img src=\"" + ev.img + "\" alt=\"" + ev.alt + "\">";
									this.execCommand("inserthtml", html);

									<?= str_replace(".", "_", $name) ?>Dialog.hide();
								}
								else
								{
									var load<?= str_replace(".", "_", $name) ?>Object = {
										targetElement: "<?= str_replace(".", "_", $name) ?>_dlgBody",
										url: "/zymurgy/media.php?action=insert_image_into_yuihtml" +
											"&editor_id=<?= str_replace(".", "_", $name) ?>Editor",
										handleSuccess:function(o)
										{
											// alert("Success");
											document.getElementById(this.targetElement).innerHTML = o.responseText;
										},
										handleFailure:function(o)
										{
											// alert("Failure");
											document.getElementById(this.targetElement).innerHTML =
												o.status + ": " + o.responseText;
										},
										startRequest:function()
										{
											document.getElementById(this.targetElement).innerHTML = "Updating...";

											YAHOO.util.Connect.asyncRequest(
												"GET",
												this.url,
												load<?= str_replace(".", "_", $name) ?>Callback,
												null);
										}
									};

									// alert("-- AJAX connection declared");

									var load<?= str_replace(".", "_", $name) ?>Callback =
									{
										success: load<?= str_replace(".", "_", $name) ?>Object.handleSuccess,
										failure: load<?= str_replace(".", "_", $name) ?>Object.handleFailure,
										scope: load<?= str_replace(".", "_", $name) ?>Object
									};

									// alert("-- Callback declared");

									load<?= str_replace(".", "_", $name) ?>Object.startRequest();

									// alert("-- AJAX connection request started");

									<?= str_replace(".", "_", $name) ?>Dialog.show();
								}

								// alert("mediafileClick End");
							},
							<?= str_replace(".", "_", $name) ?>Editor,
							true);

						<?= str_replace(".", "_", $name) ?>Dialog.render();

						// alert("toolbarLoaded Fin");
					});

					<?= str_replace(".", "_", $name) ?>Editor.render();
				}

				function Link<?= str_replace(".", "_", $name) ?>ToDialog()
				{
					<? if($dialogName !== '') { ?>
					<?= $dialogName ?>.showEvent.subscribe(
						<?= str_replace(".", "_", $name) ?>Editor.show,
						<?= str_replace(".", "_", $name) ?>Editor,
						true);
					<?= $dialogName ?>.hideEvent.subscribe(
						<?= str_replace(".", "_", $name) ?>Editor.hide,
						<?= str_replace(".", "_", $name) ?>Editor,
						true);
					<? } ?>
				}

				<? if($dialogName == '') { ?>
					YAHOO.util.Event.onDOMReady(Display<?= str_replace(".", "_", $name) ?>);
				<? } ?>
			</script>
		<?
	}
}

class ZIW_Html extends ZIW_Base
{
	function __construct()
	{
		$this->xlatehtmlentities = false;
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		require_once(Zymurgy::$root."/zymurgy/fckeditor/fckeditor.php");
		$fck = new FCKeditor($name);
		$fck->BasePath = "/zymurgy/fckeditor/";
		$fck->ToolbarSet = 'Zymurgy';
		$fck->Width = $ep[1];
		$fck->Height = $ep[2];
		$fck->Value = $value;
		$fck->Config['EditorAreaCSS'] = $this->extra['fckeditorcss'];
		$fck->Create();
	}
}

class ZIW_Color extends ZIW_Base
{
	/**
	 * Get output needed before any instances of this widget are rendered.
	 *
	 * @param array $tp Input-spec exploded parts, broken up by .'s
	 * @return string
	 */
	function GetPretext($tp)
	{
		require_once(Zymurgy::$root.'/zymurgy/include/colorpicker.php');
		$output  = Zymurgy::YUI("fonts/fonts-min.css");
		$output .= Zymurgy::YUI("container/assets/skins/sam/container.css");
		$output .= Zymurgy::YUI("colorpicker/assets/skins/sam/colorpicker.css");
		$output .= Zymurgy::YUI("utilities/utilities.js");
		$output .= Zymurgy::YUI("container/container-min.js");
		$output .= Zymurgy::YUI("slider/slider-min.js");
		$output .= Zymurgy::YUI("colorpicker/colorpicker-min.js");
		$output .= ColorPicker_JavaScript();
		$output .= ColorPicker_DialogHTML();
		return $output;
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		$matchJS = "";

		echo "#<input type=\"text\" name=\"$name\" id=\"$name\" value=\"$value\" maxlength=\"6\" size=\"6\" onChange=\"updateSwatch('swatch$name', this.value); if(typeof UpdatePreview == 'function') { UpdatePreview(); }; if(document.getElementById('{$name}locked')) {document.getElementById('{$name}locked').checked = true;}; $matchJS\">&nbsp;";
		echo "<span id=\"swatch$name\" onclick=\"showColourPicker('$name','swatch$name')\" style=\"width:15px; height:15px; background-color:#$value; border: #000000 solid 1px; cursor:pointer;\">&nbsp;&nbsp;&nbsp;</span>";
	}
}

class ZIW_Theme extends ZIW_Base
{
	/**
	 * Get output needed before any instances of this widget are rendered.
	 *
	 * @param array $tp Input-spec exploded parts, broken up by .'s
	 * @return string
	 */
	function GetPretext($tp)
	{
		require_once(Zymurgy::$root.'/zymurgy/include/colorpicker.php');
		$output  = Zymurgy::YUI("container/assets/container.css");
		$output .= Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
		$output .= Zymurgy::YUI("animation/animation-min.js");
		$output .= Zymurgy::YUI("container/container-min.js");
		$output .= Theme_JavaScript();
		return $output;
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		echo "<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\">";

		$colors = explode(",", $value);

		echo "<span style=\"border: 1px solid black; padding-top: 1px; padding-bottom: 1px;\">";

		for($cntr = 1; $cntr < count($colors); $cntr++)
		{
			$hex = substr($colors[$cntr], 1);
			$lockedStyle = " border: 1px solid #$hex;";

			if(substr($colors[$cntr], 0, 1) == "L")
			{
				$lockedStyle = " border: 1px inset black;";
			}

			echo "<span id=\"swatch{$name}{$cntr}\" style=\"width:15px; height:15px; background-color:#$hex; cursor:pointer;$lockedStyle\" onClick=\"OpenThemeWindow('$name');\">&nbsp;&nbsp;&nbsp;</span>";
		}

		for($cntr = count($colors); $cntr <= 6; $cntr++)
		{
			echo "<span id=\"swatch{$name}{$cntr}\" style=\"width:15px; height:15px; background-color:#FFFFFF; cursor:pointer;\">&nbsp;&nbsp;&nbsp;</span>";
		}

		echo "</span>&nbsp;<input type=\"button\" onClick=\"OpenThemeWindow('$name');\" value=\"Edit theme...\">";

		$themenames = array();
		foreach(Zymurgy::$ThemeColor as $cname=>$index)
		{
			$themenames[$index] = $cname;
		}
		echo "<script type=\"text/javascript\">\r\n";
		for($cntr = 1; $cntr < count($colors); $cntr++)
		{
			echo "swatchTT{$name}{$cntr} = new YAHOO.widget.Tooltip(\"swatchTT{$name}{$cntr}\", {
				context:\"swatch{$name}{$cntr}\",
				text:\"".addslashes($themenames[$cntr])."\",
				showDelay:0,
				hidedelay:0 } );\r\n";
		}
		echo "</script>\r\n";
	}
}

class ZIW_HIP extends ZIW_Base
{
	/**
	 * Return javascript code that should appear above the use of this widget as part of it's initialization.
	 * Similar to GetPretext, except this is placed inside <script> tags.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	function JSRender($ep,$name,$value)
	{
		switch ($ep[1])
		{
			case "asirra":
				echo "<script type=\"text/javascript\">
<!--
passThroughFormSubmit = false;
-->
</script>";
				return "if (passThroughFormSubmit) {
  						return true;
						}
					Asirra_CheckIfHuman(function (isHuman) {
						if (isHuman) {
							passThroughFormSubmit = true;
							me.submit();
						} else {
							alert('Please correctly identify the cats.');
						}
					});
					ok = false;";
		}
	}

	/**
	 * Render the actual input interface to the user.
	 *
	 * @param array $ep Input-spec exploded parts, broken up by .'s
	 * @param string $name
	 * @param string $value
	 */
	function Render($ep,$name,$value)
	{
		switch ($ep[1])
		{
			case "asirra":
				$position = array_key_exists(2,$ep) ? $ep[2] : 'top';
				$perrow = array_key_exists(3,$ep) ? $ep[3] : '6';
				echo '<script type="text/javascript" src="//challenge.asirra.com/js/AsirraClientSide.js"></script>
<script type="text/javascript">
	// You can control where the big version of the photos appear by
	// changing this to top, bottom, left, or right
	asirraState.SetEnlargedPosition("'.$position.'");

	// You can control the aspect ratio of the box by changing this constant
	asirraState.SetCellsPerRow('.$perrow.');
</script>';
				break;
		}
	}
}

class InputWidget
{
	static $widgets = array();

	// Stuff caried over from the old php3 world of InputWidget:
	public $fck = array();
	public $mypath;
	public $fckeditorpath;
	public $UsePennies = true;
	public $lookups = array();
	public $editkey = 0;
	public $datacolumn = 'sitetext.body';
	public $OnBeforeAutoInsert;
	public $OnAutoInsert;
	public $fckeditorcss = '';

	/**
	 * Register an InputWidget object for use by the InputWidget shell.
	 *
	 * @param string $type
	 * @param ZIW_Base $widget
	 */
	function Register($type,$widget)
	{
		InputWidget::$widgets[$type] = $widget;
	}

	/**
	 * Find the Input Widget object for this type and return it.
	 *
	 * @param string $type
	 * @return ZIW_Base
	 */
	function Get($type)
	{
		if (!array_key_exists($type, InputWidget::$widgets))
			$type = 'default';
		return InputWidget::$widgets[$type];
	}

	function PostValue($type,$postname)
	{
		$postname = str_replace(' ','_',$postname);
		$ep = explode('.',$type);
		$widget = InputWidget::Get($ep[0]);
		$this->SetExtras($widget);
		return $widget->PostValue($ep,$postname);
	}

	function Display($type,$template,$value,$masterkey='')
	{
		if ($template == '') return '';
		$ep = explode('.',$type);
		$widget = InputWidget::Get($ep[0]);
		$this->SetExtras($widget);
		$value = $widget->Display($ep,$value,$this);
		return str_replace(array("{0}","{ID}"),array($value,$masterkey),$template);
	}

	static function GetPretext($type)
	{
		$tp = explode('.',$type,2);
		$widget = InputWidget::Get($tp[0]);
		return $widget->GetPretext($tp);
	}

	function JSRender($type,$name,$value)
	{
		$ep = explode('.',$type);
		$widget = InputWidget::Get($ep[0]);
		if ($widget->xlatehtmlentities)
			$value = htmlentities($value);
		return $widget->JSRender($ep,$name,$value);
	}

	function SetExtras($widget)
	{
		$widget->extra['UsePennies'] = $this->UsePennies;
		$widget->extra['fck'] = $this->fck;
		$widget->extra['mypath'] = $this->mypath;
		$widget->extra['fckeditorpath'] = $this->fckeditorpath;
		$widget->extra['lookups'] = $this->lookups;
		$widget->extra['editkey'] = $this->editkey;
		$widget->extra['datacolumn'] = $this->datacolumn;
		$widget->extra['OnBeforeAutoInsert'] = $this->OnBeforeAutoInsert;
		$widget->extra['OnAutoInsert'] = $this->OnAutoInsert;
		if (isset($this->fckeditorcss))
			$widget->extra['fckeditorcss'] = $this->fckeditorcss;
	}

	function Render(
		$type,
		$name,
		$value,
		$dialogName = "",
		$tabsetName = "",
		$tabName = "")
	{
		$ep = explode('.',$type);
		$widget = InputWidget::Get($ep[0]);
		$widget->extra['dialogName'] = $dialogName;
		$widget->extra['tabsetName'] = $tabsetName;
		$widget->extra['tabName'] = $tabName;
		$this->SetExtras($widget);
		if ($widget->xlatehtmlentities)
			$value = htmlentities($value);
		$widget->Render($ep,$name,$value);
	}

	function IsValid($type, $value)
	{
		$ep = explode('.',$type);
		$widget = InputWidget::Get($ep[0]);

		return $widget->IsValid($value);
	}
}

class DataGridLookup
{
	public $values;
	public $keys; //In the correct display order

	function DataGridLookup($table,$idcolumn,$valcolumn,$ordercolumn = '')
	{
		$sql = "select $idcolumn,$valcolumn from $table";
		if ($ordercolumn != '')
			$sql .= " order by $ordercolumn";
		$ri = mysql_query($sql);
		if (!$ri)
		{
			echo "Error loading lookup: ".mysql_error()." [$sql]";
			exit;
		}
		while (($row = mysql_fetch_array($ri)) !== false)
		{
			$this->values[$row[$idcolumn]] = $row[$valcolumn];
			$this->keys[] = $row[$idcolumn];
		}
		mysql_free_result($ri);
	}

	function RenderDropList($name,$selected)
	{
		$r = array();
		$r[] = "<select id='$name' name='$name'>";
		foreach ($this->keys as $key)
		{
			$o = "<option value=\"$key\"";
			if ($key == $selected) $o .= " selected=\"selected\"";
			$r[] = "$o>{$this->values[$key]}";
		}
		$r[] = "</select>";
		return implode("\r\n",$r);
	}
}

InputWidget::Register('input',new ZIW_Input());
InputWidget::Register('lookup',new ZIW_Lookup());
InputWidget::Register('textarea',new ZIW_TextArea());
InputWidget::Register('unixdatetime',new ZIW_UnixDateTime());
InputWidget::Register('autocomplete',new ZIW_AutoComplete());
InputWidget::Register('drop',new ZIW_Drop());
InputWidget::Register('time',new ZIW_Time());
InputWidget::Register('unixdate',new ZIW_UnixDate());
InputWidget::Register('date',new ZIW_Date());
InputWidget::Register('datetime',new ZIW_DateTime());
InputWidget::Register('theme',new ZIW_Theme());
InputWidget::Register('inputspec',new ZIW_InputSpec());
InputWidget::Register('image',new ZIW_Image());
InputWidget::Register('html',new ZIW_Html());
InputWidget::Register('attachment',new ZIW_Attachment());
InputWidget::Register('plugin',new ZIW_Plugin()); //Ugly, needs tweaking
InputWidget::Register('hip',new ZIW_HIP());
InputWidget::Register('default',new ZIW_Base());
InputWidget::Register('password',new ZIW_Password());
InputWidget::Register('numeric',new ZIW_Input());
InputWidget::Register('float',new ZIW_Input());
InputWidget::Register('checkbox',new ZIW_CheckBox());
InputWidget::Register('money',new ZIW_Money()); //Rounding problem (3.14 -> 3.00!)
InputWidget::Register('radio',new ZIW_Radio());
InputWidget::Register('yuihtml',new ZIW_YUIHtml());
InputWidget::Register('color',new ZIW_Color());
InputWidget::Register('colour',new ZIW_Color());
InputWidget::Register('hidden',new ZIW_Hidden());
//InputWidget::Register('',new ZIW_);

if (file_exists(Zymurgy::$root.'/zymurgy/custom/CustomWidgets.php'))
	require_once(Zymurgy::$root.'/zymurgy/custom/CustomWidgets.php');
?>