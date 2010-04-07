<?
/**
 * Input widget classes
 *
 * @package Zymurgy
 */

/**
 * Input widget base class
 *
 * @package Zymurgy
 */

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

	/**
	 * Determine if the InputWidget supports the Flavours system
	 *
	 * @return unknown
	 */
	function SupportsFlavours()
	{
		return false;
	}

	static function StoreFlavouredValueFromPost($postname,$flavourID)
	{
		$values = array();
		$flavours = Zymurgy::GetAllFlavours();
		foreach ($flavours as $flavour)
		{
			if ($flavour['providescontent'])
			{
				$values[$flavour['code']] = $_POST[$postname.'_'.$flavour['code']];
			}
		}
		return ZIW_Base::StoreFlavouredValue($flavourID,$_POST[$postname.'_default'],$values);
	}

	/**
	 * Store flavoured values.  Returns flavour ID of this stored flavour.
	 *
	 * @param integer $flavourID
	 * @param string $default
	 * @param array $values
	 *
	 * @return integer
	 */
	static function StoreFlavouredValue($flavourID,$default,$values)
	{
		if ($flavourID)
		{
			Zymurgy::$db->run("update zcm_flavourtext set `default`='".
				Zymurgy::$db->escape_string($default)."' where id=$flavourID");
		}
		else
		{
			Zymurgy::$db->run("insert into zcm_flavourtext (`default`) values ('".
				Zymurgy::$db->escape_string($default)."')");
			$flavourID = Zymurgy::$db->insert_id();
		}
		$codes = Zymurgy::GetAllFlavoursByCode();
		foreach ($values as $code=>$value)
		{
			$flavour = $codes[$code];
			$value = Zymurgy::$db->escape_string($value);

			$sql = "SELECT `id` FROM `zcm_flavourtextitem` WHERE `flavour` = '".
				Zymurgy::$db->escape_string($flavour["id"]).
				"' AND `zcm_flavourtext` = '".
				Zymurgy::$db->escape_string($flavourID).
				"'";
			$id = Zymurgy::$db->get($sql);

			if($id <= 0)
			{
				Zymurgy::$db->run("insert into zcm_flavourtextitem (zcm_flavourtext,flavour,`text`) values (".
					"$flavourID,{$flavour['id']},'$value')");
			}
			else
			{
				Zymurgy::$db->run("UPDATE `zcm_flavourtextitem` SET `text` = '".
					$value. // Already escaped - Zymurgy::$db->escape_string($value).
					"' WHERE `flavour` = '".
					Zymurgy::$db->escape_string($flavour["id"]).
					"' AND `zcm_flavourtext` = '".
					Zymurgy::$db->escape_string($flavourID).
					"'");
			}
		}
		return $flavourID;
	}

	static function GetFlavouredValue($value,$forflavour = NULL,$fortemplate = false)
	{
		$value = intval($value);
		if (is_null($forflavour))
			$forflavour = Zymurgy::GetActiveFlavourCode();
		$flavour = Zymurgy::GetFlavourByCode($forflavour);

		//echo "<div>Getting flavoured value ($value) for ($forflavour)</div>";

		$text = Zymurgy::$db->get("SELECT `default` FROM `zcm_flavourtext` WHERE `id` = $value");
		if ($flavour)
		{
			if ($fortemplate)
				$contentflavour = Zymurgy::GetFlavourById($flavour['templateprovider']);
			else
				$contentflavour = Zymurgy::GetFlavourById($flavour['contentprovider']);
			if ($contentflavour)
			{
				$flavouredtext = Zymurgy::$db->get("SELECT `text` FROM `zcm_flavourtextitem` WHERE (zcm_flavourtext=$value) and (flavour={$contentflavour['id']})");
				if ($flavouredtext) $text = $flavouredtext;
			}
		}
		return $text;
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Input(inputspecName) {\n";
		$output .= " var description = \"n/a\"\n";

		$output .= " switch(inputspecName) {\n";
		$output .= "  case \"input\": description = \"Text - one line\"; break;\n";
		$output .= "  case \"float\": description = \"Numeric (with decimals)\"; break;\n";
		$output .= "  case \"numeric\": description = \"Numeric (no decimals)\"; break;\n";
		$output .= "  default: description = inputspecName;\n";
		$output .= " }\n";

		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = description;\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Size\", 3, 5, 20));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Maximum Length\", 3, 5, 50));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		switch($inputspecName)
		{
			case "numeric":
				return "BIGINT";
				break;
			case "float":
				return "FLOAT";
				break;
			case "input":
				return "VARCHAR(".$parameters[1].")";
				break;
			default:
				return "TEXT";
				break;
		}
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_InputFlavoured extends ZIW_Base
{
	function Display($ep,$display,$shell)
	{
		return $this->GetFlavouredValue($display);
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
?>
		<input type="hidden" id="<?= $name ?>" name="<?= $name ?>" value="<?= $value ?>">
		<table>
			<tr>
				<td>Default:</td>
				<td><input type="text" size="<?= $ep[1] ?>" maxlength="<?= $ep[2] ?>" id="<?= $name ?>_default" name="<?= $name ?>_default" value='<?= $this->GetFlavouredValue($value, '') ?>'></td>
			</tr>
<?
		$flavours = Zymurgy::GetAllFlavours();
		foreach($flavours as $flavour)
		{
			if (!$flavour['providescontent']) continue;
?>
			<tr>
				<td><?= $flavour['label'] ?>:</td>
				<td><input type="text" size="<?= $ep[1] ?>" maxlength="<?= $ep[2] ?>" id="<?= $name ?>_<?= $flavour['code'] ?>" name="<?= $name ?>_<?= $flavour['code'] ?>" value='<?= $this->GetFlavouredValue($value, $flavour['code']) ?>'></td>
			</tr>
<?
		}
?>
		</table>
<?
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_InputFlavoured(inputspecName) {\n";
		$output .= " var description = \"n/a\"\n";

		$output .= " switch(inputspecName) {\n";
		$output .= "  case \"inputf\": description = \"Flavoured Text - one line\"; break;\n";
		$output .= "  case \"float\": description = \"Numeric (with decimals)\"; break;\n";
		$output .= "  case \"numeric\": description = \"Numeric (no decimals)\"; break;\n";
		$output .= "  default: description = inputspecName;\n";
		$output .= " }\n";

		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = description;\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Size\", 3, 5, 20));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Maximum Length\", 3, 5, 50));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		//Always bigint to refer to zcm_flavourtext table
		return "BIGINT";
	}

	/**
	 * Determine if the InputWidget supports the Flavours system
	 *
	 * @return unknown
	 */
	function SupportsFlavours()
	{
		return true;
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_TextArea(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Text - multiple lines\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Width (characters)\", 3, 5, 40));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Height (characters)\", 3, 5, 5));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "LONGTEXT";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_TextAreaFlavoured extends ZIW_Base
{
	function Display($ep,$display,$shell)
	{
		return $this->GetFlavouredValue($display);
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
?>
		<input type="hidden" id="<?= $name ?>" name="<?= $name ?>" value="<?= $value ?>">
		<table>
			<tr>
				<td valign="top">Default:</td>
				<td><textarea id="<?= $name ?>_default" name="<?= $name ?>_default" rows="<?= $ep[2] ?>" cols="<?= $ep[1] ?>"><?= $this->GetFlavouredValue($value, '') ?></textarea></td>
			</tr>
<?
		$flavours = Zymurgy::GetAllFlavours();
		foreach($flavours as $flavour)
		{
			if (!$flavour['providescontent']) continue;
?>
			<tr>
				<td valign="top"><?= $flavour['label'] ?>:</td>
				<td><textarea id="<?= $name ?>_<?= $flavour['code'] ?>" name="<?= $name ?>_<?= $flavour['code'] ?>" rows="<?= $ep[2] ?>" cols="<?= $ep[1] ?>"><?= $this->GetFlavouredValue($value, $flavour['code']) ?></textarea></td>
			</tr>
<?
		}
?>
		</table>
<?
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_TextAreaFlavoured(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Flavoured Text - multiple lines\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Width (characters)\", 3, 5, 40));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Height (characters)\", 3, 5, 5));\n";


		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		//Always bigint to refer to zcm_flavourtext table
		return "BIGINT";
	}

	/**
	 * Determine if the InputWidget supports the Flavours system
	 *
	 * @return unknown
	 */
	function SupportsFlavours()
	{
		return true;
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(".$parameters[1].")";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_CheckBox(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Checkbox\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineCheckboxParameter(\"Checked by default\", \"\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(5)";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Money(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Money\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "INT UNSIGNED";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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
		if (!array_key_exists($ep[1],$this->extra['lookups']))
		{
			include_once("datagrid.php");

			$this->extra['lookups'][$ep[1]] = new DataGridLookup($ep[1],$ep[2],$ep[3],$ep[4]);
		}

		$this->PreRender();

		echo $this->extra['lookups'][$ep[1]]->RenderDropList(
			$name,
			$value,
			count($ep) >= 6 && $ep[5] == "checked");
	}

	function PreRender()
	{
		//Stub in case an ancestor needs to tweak the lookup data
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
		if (!array_key_exists($ep[1],$this->extra['lookups']))
		{
			include_once("datagrid.php");

			$this->extra['lookups'][$ep[1]] = new DataGridLookup($ep[1],$ep[2],$ep[3],$ep[4]);
		}

		$values = $this->extra['lookups'][$ep[1]]->values;

		return array_key_exists($display, $values)
			? $values[$display]
			: "";
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Lookup(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Custom Table Lookup\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineSelectParameter(\"Table Name\", 30, 200, \"getTableNames();\", \"getColumnNames();\", \"\"));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineHiddenParameter(\"ID Column\", \"id\"));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineSelectParameter(\"Value Column\", 30, 200, \"\", \"\", \"\"));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineSelectParameter(\"Sort Column\", 30, 200, \"\", \"\", \"\"));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineCheckboxParameter(\"Allow Nulls\", \"\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(20) NULL";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
abstract class ZIW_AutoCompleteBase extends ZIW_Base
{
	static public $ZymurgyAutocompleteZIndex = 9000;
	static public $acwidth = 200;
	protected $name;
	protected $jsname;
	protected $textvalue;

	function __construct()
	{
		$this->xlatehtmlentities = false;
	}

	abstract function PreRender($ep,$name,$value); //Must set $textvalue

	function RenderJS()
	{
		//Use absolute positioning for AC button since YUI makes the input box absolute.
		echo 'function fixbutton_'.$this->jsname.'() {
			var elBtn = document.getElementById("'.$this->name.'-btn");
			var elInp = document.getElementById("'.$this->name.'-input");
			var elCnt = document.getElementById("'.$this->name.'-container");
			var reg = YAHOO.util.Dom.getRegion(elInp);
			YAHOO.util.Dom.setXY(elBtn, [reg.right-1,reg.top-1]);
			YAHOO.util.Dom.setXY(elCnt, [reg.left,reg.bottom]);
		}
		fixbutton_'.$this->jsname.'();
		'.$this->jsname.'_autocomp.dataRequestEvent.subscribe(fixbutton_'.$this->jsname.');
		';
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
		echo "<div id=\"{$name}-autocomplete\"";
		echo " style=\"display:inline-block\"";
		echo "><nobr><input id=\"{$name}-input\" type=\"text\" ";
		$hint = $this->GetHint();
		if (!empty($hint))
		{
			echo "title=\"$hint\" ";
		}
		echo "value=\"".htmlspecialchars($this->textvalue)."\" onchange=\"{$this->jsname}_update\" style=\"width:200px\" />";
		echo "<input id=\"{$name}-btn\" type=\"button\" value=\"...\" onclick=\"{$this->jsname}_autocomp.toggleContainer();\"";
		echo " />";
		echo "<div id=\"{$name}-container\" style=\"width:200px; z-index:".ZIW_AutoComplete::$ZymurgyAutocompleteZIndex."\"></div></nobr></div>";
		echo '<script type="text/javascript">
			'.$this->jsname.'_text = document.getElementById("'.$name.'-input");
			'.$this->jsname.'_plugin = document.getElementById("'.$name.'-plugin");
			'.$this->jsname.'_btn = document.getElementById("'.$name.'-btn");
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
		$this->textvalue = isset($ep[1]) ? urldecode($ep[1]) : "";
		//echo "<div style=\"float:left\">";
		echo "<select id=\"{$name}-plugin\" name=\"{$name}-plugin\">\r\n\t<option value=\"\">Gadget Type</option>\r\n";
		$ri = Zymurgy::$db->run("select id,name from zcm_plugin order by name");
		while (($row = Zymurgy::$db->fetch_array($ri))!==false)
		{
			echo "\t<option value=\"{$row['id']}\"";
			if ($row['name'] == $pluginvalue)
				echo " selected=\"selected\"";
			echo ">{$row['name']}</option>\r\n";
		}
		echo "</select>";
		//echo "</div>";
	}

	function RenderJS()
	{
		$d = isset($_GET["d"]) ? $_GET["d"] : 1;

		if (empty($this->textvalue))
		{
			echo "{$this->jsname}_text.disabled = true;\r\n";
			echo "{$this->jsname}_btn.disabled = true;\r\n";
		}
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
				'.$this->jsname.'_btn.disabled = true;
				YAHOO.util.Dom.setAttribute('.$this->jsname.'_text,"title","Choose a Plugin First");
			}
			else
			{
				'.$this->jsname.'_text.disabled = false;
				'.$this->jsname.'_btn.disabled = false;
				YAHOO.util.Dom.setAttribute('.$this->jsname.'_text,"title","Select one, or name a new one");
			}
			Zymurgy.refreshHint('.$this->jsname.'_text);
			'.$this->jsname.'_update();
		});
		var '.$this->jsname.'_datasource = new YAHOO.util.XHRDataSource("/zymurgy/include/plugin.php?pg='.$d.'&");
			'.$this->jsname.'_datasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSARRAY;
			'.$this->jsname.'_datasource.responseSchema = {fields : ["plugin"]};
			var '.$this->jsname.'_autocomp = new YAHOO.widget.AutoComplete("'.$this->name.'-input","'.$this->name.'-container", '.$this->jsname.'_datasource);
			'.$this->jsname.'_autocomp.textboxChangeEvent.subscribe('.$this->jsname.'_update);
			'.$this->jsname.'_autocomp.generateRequest = function(sQuery) {
				var elSel = document.getElementById("'.$this->name.'-plugin");
				// return "/zymurgy/include/plugin.php?pg='.$d.'&pi=" + elSel.value + "&q=" + sQuery;
				return "pi=" + elSel.value + "&q=" + sQuery;
			};

			';
		parent::RenderJS();
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

class ZIW_RemoteLookup extends ZIW_AutoCompleteBase
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
		$postvalue = urldecode($_POST[$postname]);
		$autocreate = array_key_exists(4,$ep) ? ($ep[4] == 'true') : false;
		$r = Zymurgy::memberremotelookup($table,$column,$postvalue,true);
		$value = 0;
		foreach ($r as $key=>$val)
		{
			$value = $key;
		}
		if (!$value && $autocreate)
		{
			//Autocreate unknown entry from autocomplete widget
			$newrecord = array("$table.$column"=>$postvalue);
			if (!empty($this->extra['OnBeforeAutoInsert']))
				$newrecord = call_user_func($this->extra['OnBeforeAutoInsert'], $table, $newrecord);
			//TODO:  Create remote entry and load ID into $value.
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
		echo 'var '.$this->jsname.'_datasource = new YAHOO.util.XHRDataSource("/zymurgy/include/acremote.php");
			'.$this->jsname.'_datasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
			'.$this->jsname.'_datasource.responseSchema = {
				resultsList : "results",
				fields : ["value"]};
			var '.$this->jsname.'_autocomp = new YAHOO.widget.AutoComplete("'.$this->name.'-input","'.$this->name.'-container", '.$this->jsname.'_datasource);
			'.$this->jsname.'_autocomp.generateRequest = function(sQuery) {
				return "/zymurgy/include/acremote.php?t='.urlencode($this->table).'&c='.
					urlencode($this->column).'&i='.urlencode($this->idcolumn).'&q=" + sQuery;
			};
			function '.$this->jsname.'_update() {
				'.$this->jsname.'_hidden.value = escape('.$this->jsname.'_text.value);
			}
			'.$this->jsname.'_autocomp.textboxChangeEvent.subscribe('.$this->jsname.'_update);
			';
		parent::RenderJS();
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
		$r = Zymurgy::memberremotelookupbyid($table,$column,$display);
		if (!is_array($r))
		{
			echo "<div>Unexpected result from Infusionsoft: <pre>";
			print_r($r);
			echo "</pre></div>";
		}
		foreach ($r as $key=>$value)
		{
			return $value;
		}
		return '';
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
		parent::RenderJS();
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

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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
		return array_key_exists($display,$ritems) ? $ritems[$display] : array_shift($ritems);
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_".get_class($this)."(inputspecName) {\n";
		$output .= " var description = \"n/a\"\n";

		$output .= " switch(inputspecName) {\n";
		$output .= "  case \"radio\": description = \"Radio Buttons\"; break;\n";
		$output .= "  case \"drop\": description = \"Drop-Down List\"; break;\n";
		$output .= "  default: description = inputspecName;\n";
		$output .= " }\n";

		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = description;\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Values (comma seperated)\", 30, 200, \"Value 1,Value 2,Value 3\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		$ritems = ZIW_RadioDrop::HackedUnserialize($parameters[0]);
		$maxsz = 0;
		foreach ($ritems as $value)
		{
			if (strlen($value) > $maxsz)
				$maxsz = strlen($value);
		}
		return "VARCHAR($maxsz)";
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

/**
 * Provides an input widget for selecting an available database table.
 *
 * @package Zymurgy
 * @subpackage inputwidgets
 *
 */
class ZIW_DatabaseTable extends ZIW_Base
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
		$showinternal = (array_key_exists(1,$ep) && ($ep[1] == 'true'));
		$tables = Zymurgy::$db->enumeratetables();
		echo "<select id=\"$name\" name=\"$name\">\r\n";
		foreach ($tables as $tbl)
		{
			if (!$showinternal && (substr($tbl,0,4) == 'zcm_'))
				continue;
			echo "\t<option value=\"$tbl\"";
			if ($tbl == $value)
				echo " selected=\"selected\"";
			echo ">$tbl</option>\r\n";
		}
		echo "</select>\r\n";
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return 'varchar(64)';
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_DatabaseTable(inputspecName) {\n";
		$output .= " var description = \"Database Table Selection\"\n";

		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = description;\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineCheckboxParameter(\"Show Internal Tables\", \"\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
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

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_Time extends ZIW_Base
{
	//Lifted from http://guru-forum.net/showthread.php?t=7, and lovingly improved
	function mysqlToUnix ($datetime) {
	    if ($datetime)
	    {
	    	$parts = explode(' ', $datetime);
		    $datebits = explode('-', $parts[0]);
		    if (3 != count($datebits)) return -1;
		    if (isset($parts[1])) {
		        $timebits = explode(':', $parts[1]);
		        if (3 != count($timebits)) return -1;
			    if (intval($timebits[0]) +
			    	intval($timebits[1]) +
			    	intval($timebits[2]) +
			    	intval($datebits[0]) +
			    	intval($datebits[1]) +
			    	intval($datebits[2]) == 0)
			    	return 0;
		        return mktime($timebits[0], $timebits[1], $timebits[2], $datebits[1], $datebits[2], $datebits[0]);
		    }
		    if (intval($datebits[0]) +
		    	intval($datebits[1]) +
		    	intval($datebits[2]) == 0)
		    	return 0;
		    return mktime (0, 0, 0, $datebits[1], $datebits[2], $datebits[0]);
	    }
	    return 0;
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
		$t = ZIW_Time::mysqlToUnix($_POST[$postname]);
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
		return date("g:i a",ZIW_Time::mysqlToUnix($display));
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
		$value = date("g:i a",ZIW_Time::mysqlToUnix($value));
		echo "<input type=\"text\" size=\"8\" maxlength=\"8\" id=\"$name\" name=\"".
			"$name\" value=\"$value\" /> <i>hh:mm am/pm</i>";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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
		return 0 + $tm;
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

		// ZK: Disabling
		// if ($date == 0) $date=time();

		$cal = new DHTML_Calendar(
			"/zymurgy/jscalendar/",
			'en',
			'calendar-win2k-2',
			false);

		$cal->SetFieldPrefix($name);
		$cal->SetIncludeID(true); // false);

		$cal->load_files();
		$dateval = $date > 0 ? $date : "";
		$this->SetCalendarParams($dateval);
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

		if(count($dp) >= 3)
		{
			return mktime(0,0,0,$dp[1],$dp[2],$dp[0]);
		}
		else
		{
			"";
		}
	}

	function SetCalendarParams($date)
	{
		$format = $this->GetJSFormat();
		$this->caloptions['ifFormat'] = $format;

		if(!is_null($date) && strlen($date) > 0)
			$this->calattributes['value'] = strftime($format, $date);
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_UnixDate(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Date\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "INT UNSIGNED";
	}
}

class ZIW_YuiUnixDate extends ZIW_UnixDate
{
	public function Render($ep,$name,$value)
	{
		$jsName = str_replace(".", "_", $name);

		echo(Zymurgy::YUI("fonts/fonts-min.css"));
		echo(Zymurgy::YUI("container/assets/skins/sam/container.css"));
		echo(Zymurgy::YUI("calendar/assets/skins/sam/calendar.css"));
		echo(Zymurgy::YUI("button/assets/skins/sam/button.css"));
		echo(Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js"));
		echo(Zymurgy::YUI("calendar/calendar-min.js"));
		echo(Zymurgy::YUI("container/container-min.js"));
		echo(Zymurgy::YUI("element/element-min.js"));
		echo(Zymurgy::YUI("button/button-min.js"));

		echo("<input type=\"hidden\" id=\"{$jsName}\" name=\"{$name}\" value=\"".
			(is_numeric($value) ? date("Y-m-d", $value) : "").
			"\">\n");

		echo("<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n");
		echo("<tr>\n");
		echo("<td><input type=\"text\" id=\"{$jsName}year\" name=\"{$name}year\" value=\"".
			(is_numeric($value) ? date("Y", $value) : "").
			"\" size=\"4\" maxlength=\"4\"></td>\n");
		echo("<td>/</td>\n");
		echo("<td><input type=\"text\" id=\"{$jsName}month\" name=\"{$name}month\" value=\"".
			(is_numeric($value) ? date("m", $value) : "").
			"\" size=\"2\" maxlength=\"2\"></td>\n");
		echo("<td>/</td>\n");
		echo("<td><input type=\"text\" id=\"{$jsName}day\" name=\"{$name}day\" value=\"".
			(is_numeric($value) ? date("d", $value) : "").
			"\" size=\"2\" maxlength=\"2\"></td>\n");
		echo("<td class=\"yui-skin-sam\"><div id=\"{$jsName}ButtonContainer\"></div></td>\n");
		echo("<td>");
		echo("</td>");
		echo("</tr>\n");
		echo("<tr>\n");
		echo("<td>year</td>\n");
		echo("<td>&nbsp;</td>\n");
		echo("<td>month</td>\n");
		echo("<td>&nbsp;</td>\n");
		echo("<td>day</td>\n");
		echo("<td>&nbsp;</td>\n");
		echo("</tr>\n");
		echo("</table>\n");
		echo("<script type=\"text/javascript\">\n");
		echo("var {$jsName}CalendarMenu;\n");
		echo("var {$jsName}Calendar;\n");
		echo("var {$jsName}Refreshing = false;\n");

		echo("var {$jsName}ButtonClick = function() {\n");

		echo("function handleSelect(type, args, obj) {\n");
		// echo("alert('handleSelect Start');\n");
		echo("if({$jsName}Refreshing) return;\n");
		echo("if(args) {\n");
		echo("var dates = args[0];\n");
		echo("var date = dates[0];\n");
		echo("var year = date[0], month = date[1], day = date[2];\n");
		// echo("alert(year);\n");
		echo("YAHOO.util.Dom.get(\"{$jsName}year\").value = year;\n");
		echo("YAHOO.util.Dom.get(\"{$jsName}month\").value = month;\n");
		echo("YAHOO.util.Dom.get(\"{$jsName}day\").value = day;\n");
		echo("YAHOO.util.Dom.get(\"{$jsName}\").value = \"\" + year + \"-\" + month + \"-\" + day;\n");
		echo("}\n");
		echo("{$jsName}CalendarMenu.hide();\n");
		echo("}\n");

		echo("function handleKeydown(event) {\n");
		echo("if(YAHOO.util.Event.getCharCode(event) === 27) {\n");
		echo("{$jsName}CalendarMenu.hide();\n");
		echo("this.focus();\n");
		echo("}\n");
		echo("}\n");

		echo("var focusDay = function() {\n");
		// echo("alert('focusDay start');\n");
		echo("var oCalendarTBody = YAHOO.util.Dom.get(\"{$jsName}ButtonContainer\").tBodies[0];\n");
		echo("var aElements = oCalendarTBody.getElementsByTagName(\"a\");\n");
		echo("var oAnchor;\n");
		echo("if(aElements.length > 0) {\n");
		echo("YAHOO.util.Dom.batch(aElements, function (element) {\n");
		echo("if(YAHOO.util.Dom.hasClass(element.parentNode, \"today\")) {\n");
		echo("oAnchor = element;\n");
		echo("}\n");
		echo("});\n");
		echo("if(!oAnchor) {\n");
		echo("oAnchor = aElements[0];\n");
		echo("}\n");
		echo("YAHOO.lang.later(0, oAnchor, function() {\n");
		echo("try {\n");
		echo("oAnchor.focus();\n");
		echo("}\n");
		echo("catch(e) {}\n");
		echo("});\n");
		echo("}\n");
		echo("};\n");

		echo("{$jsName}CalendarMenu.subscribe(\"show\", focusDay);\n");
		// echo("{$jsName}Calendar.renderEvent.subscribe(focusDay, {$jsName}Calendar, true);\n");

		echo("var year = YAHOO.util.Dom.get(\"{$jsName}year\").value;\n");
		echo("var month = YAHOO.util.Dom.get(\"{$jsName}month\").value;\n");
		echo("var day = YAHOO.util.Dom.get(\"{$jsName}day\").value;\n");

		echo("var pageDate = \"\" + month + \"/\" + year;\n");
		echo("var date = \"\" + month + \"/\" + day + \"/\" + year;\n");

		// echo("alert('{$jsName}ButtonClick: ' + date);\n");

		echo("if(date !== \"//\") {\n");

		echo("{$jsName}Calendar = new YAHOO.widget.Calendar(\"{$jsName}Calendar\", ".
			"{$jsName}CalendarMenu.body.id, \n");
		echo("{ pagedate: pageDate,  selected: date } );\n");

		echo("} else {\n");

		echo("{$jsName}Calendar = new YAHOO.widget.Calendar(\"{$jsName}Calendar\", ".
			"{$jsName}CalendarMenu.body.id);\n");

		echo("}\n");

		echo("{$jsName}Calendar.render();\n");

		echo("{$jsName}Calendar.selectEvent.subscribe(handleSelect, {$jsName}Calendar, true);\n");
		echo("YAHOO.util.Event.on({$jsName}CalendarMenu.element, \"keydown\", handleKeydown);\n");

		echo("{$jsName}CalendarMenu.align();\n");
		echo("this.unsubscribe(\"click\", {$jsName}ButtonClick);\n");
		echo("this.subscribe(\"click\", {$jsName}Refresh);\n");
		echo("}\n");

		echo("var {$jsName}Refresh = function() {\n");

		echo("var year = YAHOO.util.Dom.get(\"{$jsName}year\").value;\n");
		echo("var month = YAHOO.util.Dom.get(\"{$jsName}month\").value;\n");
		echo("var day = YAHOO.util.Dom.get(\"{$jsName}day\").value;\n");

		echo("var pageDate = \"\" + month + \"/\" + year;\n");
		echo("var date = \"\" + month + \"/\" + day + \"/\" + year;\n");

		// echo("alert('{$jsName}Refresh: ' + date)\n;");

		echo("if(date !== \"//\") {\n");
		// echo("alert('applying updated date');\n");
		echo("{$jsName}Refreshing = true;\n");
		echo("{$jsName}Calendar.select(date);\n");
		echo("{$jsName}Calendar.cfg.setProperty(\"pagedate\", pageDate);\n");
		echo("{$jsName}Calendar.render();\n");
		echo("{$jsName}Refreshing = false;\n");
		echo("}\n");

		echo("{$jsName}CalendarMenu.align();\n");
		// echo("alert('{$jsName}Refresh fin');\n");

		echo("}\n");

		echo("{$jsName}CalendarMenu = new YAHOO.widget.Overlay(\"{$jsName}CalendarMenu\", { visible: false } );\n");

		echo("var {$jsName}Button = new YAHOO.widget.Button( {\n");
		echo("type: \"menu\",\n");
		echo("id: \"{$jsName}CalendarPicker\",\n");
		echo("label: \"\",\n");
		echo("menu: {$jsName}CalendarMenu,\n");
		echo("container: \"{$jsName}ButtonContainer\"\n");
		echo("} );\n");

		echo("{$jsName}Button.on(\"appendTo\", function() {\n");
		echo("{$jsName}CalendarMenu.setBody(\"&#32;\");\n");
		echo("{$jsName}CalendarMenu.body.id = \"{$jsName}Container\";\n");
		echo("});\n");

		echo("{$jsName}Button.on(\"click\", {$jsName}ButtonClick);\n");

		echo("var {$jsName}UpdateDate = function() {\n");
		echo("var year = YAHOO.util.Dom.get(\"{$jsName}year\").value;\n");
		echo("var month = YAHOO.util.Dom.get(\"{$jsName}month\").value;\n");
		echo("var day = YAHOO.util.Dom.get(\"{$jsName}day\").value;\n");

		echo("var date = \"\" + year + \"-\" + month + \"-\" + day;\n");

		echo("YAHOO.util.Dom.get(\"{$jsName}\").value = date;\n");
		echo("}\n");

		echo("YAHOO.util.Event.addListener(\"{$jsName}year\", \"change\", {$jsName}UpdateDate);\n");
		echo("YAHOO.util.Event.addListener(\"{$jsName}month\", \"change\", {$jsName}UpdateDate);\n");
		echo("YAHOO.util.Event.addListener(\"{$jsName}day\", \"change\", {$jsName}UpdateDate);\n");

		echo("</script>\n");
	}


	function IsValid($value)
	{
		$isValid = true;

		$dateArray = explode("-", $value);

		if(count($dateArray) <> 3)
		{
			$isValid = false;
		}
		else
		{
			if(!is_numeric($dateArray[0]) || !is_numeric($dateArray[1]) || !is_numeric($dateArray[2]))
			{
				$isValid = false;
			}
			else if($dateArray[1] < 1 || $dateArray[1] > 12)
			{
				$isValid = false;
			}
			else
			{
				$maxDays = 31;

				switch($dateArray[1])
				{
					case 4:
					case 6:
					case 9:
					case 11:
						$maxDays = 30;
						break;

					case 2:
						if($dateArray[0] % 400 == 0)
						{
							$maxDays = 29;
						}
						else if($dateArray[0] % 100 == 0)
						{
							$maxDays = 28;
						}
						else if($dateArray[0] % 4 == 0)
						{
							$maxDays = 29;
						}
						else
						{
							$maxDays = 28;
						}

						break;
				}

				if($dateArray[2] < 1 || $dateArray[2] > $maxDays)
				{
					$isValid = false;
				}
			}
		}

		return $isValid;
	}
}

class ZIW_Date extends ZIW_DateBase
{
	function SetCalendarParams($date)
	{
		$format = $this->GetJSFormat();
		$this->caloptions['ifFormat'] = $format;

		if(strlen($date) > 0)
		{
			$this->calattributes['value'] = strftime($format, $date);
		}
		else
		{
			$this->calattributes['value'] = '';
		}
	}

	function ToUnixTime($tm)
	{
		return ZIW_Time::mysqlToUnix($tm);
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "DATE";
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
		$date = trim($_POST[$postname]);
		if (empty($date))
		{
			return '';
		}
		$pp = explode(" ",$date);

		if(count($pp) <= 0)
		{
			return "";
		}
		else
		{
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

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "INT UNSIGNED";
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

		return strlen($tm) > 0
			? strftime('%Y-%m-%d %H:%M:%S',$tm)
			: "";
	}

	function ToUnixTime($tm)
	{
		return ZIW_Time::mysqlToUnix($tm);
	}

	function SetCalendarParams($date)
	{
		$format = '%Y-%m-%d [%I:%M %p]';
		$this->caloptions['ifFormat'] = $format;
		$this->caloptions['showsTime'] = 1;

		if($date > 0)
			$this->calattributes['value'] = strftime($format, $date);
		else
			$this->calattributes['value'] = '';
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "DATETIME";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Image(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Image Attachment\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Width (pixels)\", 3, 5, 100));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Height (pixels)\", 3, 5, 100));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(60)";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Attachment(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Attachment\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(60)";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_RichTextBase extends ZIW_Base
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
		if (array_key_exists($postname,$_POST))
		{
			if (array_key_exists('allowabletags',Zymurgy::$config))
				return strip_tags($_POST[$postname],Zymurgy::$config['allowabletags']);
			else
				return $_POST[$postname];
		}
		else
			return '';
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_".get_class($this)."(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"WYSIWYG HTML Input\";\n";
		$output .= " specifier.type = \"html\";\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Width (pixels)\", 3, 5, 600));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Height (pixels)\", 3, 5, 400));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "LONGTEXT";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_FlavouredRichTextBase extends ZIW_Base
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
		if (array_key_exists($postname,$_POST))
		{
			if (array_key_exists('allowabletags',Zymurgy::$config))
				return strip_tags($_POST[$postname],Zymurgy::$config['allowabletags']);
			else
				return $_POST[$postname];
		}
		else
			return '';
	}

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_".get_class($this)."(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Flavoured HTML Input\";\n";
		$output .= " specifier.type = \"html\";\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Width (pixels)\", 3, 5, 600));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Height (pixels)\", 3, 5, 400));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "BIGINT";
	}

	/**
	 * Determine if the InputWidget supports the Flavours system
	 *
	 * @return unknown
	 */
	function SupportsFlavours()
	{
		return true;
	}
}

class ZIW_YUIHtml extends ZIW_RichTextBase
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
		return "YAHOO.util.Event.on('submitForm', 'click', function() { ".
						str_replace(".", "_", $name).
						"Editor.saveHTML(); });\n";
	}

	/**
	 * Get output needed before any instances of this widget are rendered.
	 *
	 * @param array $tp Input-spec exploded parts, broken up by .'s
	 * @return string
	 */
	function GetPretext($tp)
	{
		$output  = Zymurgy::YUI("assets/skins/sam/skin.css");
		$output .= Zymurgy::YUI("dom/dom-min.js");
		$output .= Zymurgy::YUI("event/event-min.js");
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
		echo Zymurgy::RequireOnce('/zymurgy/include/yui-stretch.js');
		$dialogName = $this->extra['dialogName'];
		$tabsetName = $this->extra['tabsetName'];
		$tabName = $this->extra['tabName'];

		$id = str_replace(".", "_", $name);
		if($dialogName !== "")
		{
			echo( "<div id=\"{$id}_div\"></div>");
		}

echo <<<HTML
<!-- <div id="{$id}_dlg"><div class="hd">Insert Image from Library</div><div id="{$id}_dlgBody" class="bd"></div></div> -->
<textarea id="{$id}" name="$name" cols="60" rows="10">$value</textarea>

<script type="text/javascript">
HTML;

ECHO <<<JAVASCRIPT
	var Display{$id}Exists = true;
	var {$id}Editor;
	var {$id}Dialog;

	function Display{$id}() {
		var myConfig = {
			height: '{$ep[2]}px',
			width: '{$ep[1]}px',
			dompath: true,
			focusAtStart: false
		};

		{$id}Editor = new YAHOO.widget.Editor(
			'$id',
			myConfig);

		{$id}Editor.addZCMImageButton();
		{$id}Editor.addEditCodeButton();

		{$id}Editor.render();
JAVASCRIPT;

		if(false){
			if($dialogName !== "") {

echo <<<JAVASCRIPT
		{$id}Editor.on('windowRender', function() {
			document.getElementById('{$id}_div').appendChild(this.get('panel').element);
		});

		if(typeof $dialogName == "Dialog")
		{
			Link{$id}ToDialog();
		}
JAVASCRIPT;

			}

echo <<<JAVASCRIPT
		{$id}Editor.on("toolbarLoaded", function()
		{
			// alert("toolbarLoaded Start");

			{$id}Dialog = new YAHOO.widget.Dialog(
				"{$id}_dlg",
				{
					width: "400px",
					fixedcenter: true,
					visible: false,
					constraintoviewport: true,
					buttons: [
						{ text: "OK", handler: function() {
							//alert("OK pressed");
							InsertMediaFileInPage({$id}Editor);
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

			{$id}Editor.toolbar.addButtonToGroup(
				mediaFileImageConfig,
				"insertitem");

			{$id}Editor.toolbar.on(
				"mediafileClick",
				function(ev)
				{
					// alert("mediafileClick Start");
					this._focusWindow();

					if(ev && ev.img)
					{
						// alert("img declared");

						var html = "<img src=\\"" + ev.img + "\\" alt=\\"" + ev.alt + "\\">";
						this.execCommand("inserthtml", html);

						{$id}Dialog.hide();
					}
					else
					{
						var load{$id}Object = {
							targetElement: "{$id}_dlgBody",
							url: "/zymurgy/media.php?action=insert_image_into_yuihtml" +
								"&editor_id={$id}Editor",
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
									load{$id}Callback,
									null);
							}
						};

						// alert("-- AJAX connection declared");

						var load{$id}Callback =
						{
							success: load{$id}Object.handleSuccess,
							failure: load{$id}Object.handleFailure,
							scope: load{$id}Object
						};

						// alert("-- Callback declared");

						load{$id}Object.startRequest();

						// alert("-- AJAX connection request started");

						{$id}Dialog.show();
					}

					// alert("mediafileClick End");
				},
				{$id}Editor,
				true);

			{$id}Dialog.render();

			// alert("toolbarLoaded Fin");
		});

		{$id}Editor.render();
	}

	function Link{$id}ToDialog()
	{
JAVASCRIPT;

			if($dialogName !== '') {

echo <<<JAVASCRIPT
		$dialogName.showEvent.subscribe(
			{$id}Editor.show,
			{$id}Editor,
			true);
		$dialogName.hideEvent.subscribe(
			{$id}Editor.hide,
			{$id}Editor,
			true);
JAVASCRIPT;

			}
			if($dialogName == '') {
				echo "\t\tYAHOO.util.Event.onDOMReady(Display{$id});\n";
			}

		} // if(false)

echo <<<JAVASCRIPT
	}
	YAHOO.util.Event.onDOMReady(Display{$id});
</script>
JAVASCRIPT;

	}
}

class ZIW_Html extends ZIW_RichTextBase
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
		if (array_key_exists('fckeditorcss',$this->extra))
			$fck->Config['EditorAreaCSS'] = $this->extra['fckeditorcss'];
		else if (array_key_exists('sitecss',Zymurgy::$config))
			$fck->Config['EditorAreaCSS'] = Zymurgy::$config['sitecss'];
		$fck->Create();
	}

	function GetInputSpecifier()
	{
		// The input specifier should only be available to the base class

		return "";
	}
}

class ZIW_CKHtml extends ZIW_RichTextBase
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
		require_once(Zymurgy::$root."/zymurgy/ckeditor/ckeditor.php");

		$config = array();
		$config["width"] = $ep[1];
		$config["height"] = $ep[2];
		$config["contentsCss"] = array_key_exists("fckeditorcss", $this->extra)
			? $this->extra["fckeditorcss"]
			: Zymurgy::$config["sitecss"];

		$ck = new CKEditor();
		$ck->basePath = "/zymurgy/ckeditor/";
		$ck->editor($name, $value, $config);
	}

	function GetInputSpecifier()
	{
		// The input specifier should only be available to the base class

		return "";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_FlavouredHtml extends ZIW_FlavouredRichTextBase
{
	function __construct()
	{
		$this->xlatehtmlentities = false;
	}

	function Display($ep,$display,$shell)
	{
		return $this->GetFlavouredValue($display);
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

?>
		<input type="hidden" id="<?= $name ?>" name="<?= $name ?>" value="<?= $value ?>">
		<table>
			<tr>
				<td>Default:</td>
				<td>
<?
		$fck = new FCKeditor($name."_default");
		$fck->BasePath = "/zymurgy/fckeditor/";
		$fck->ToolbarSet = 'Zymurgy';
		$fck->Width = $ep[1];
		$fck->Height = $ep[2];
		$fck->Value = $this->GetFlavouredValue($value, '');
		if (array_key_exists('fckeditorcss',$this->extra))
			$fck->Config['EditorAreaCSS'] = $this->extra['fckeditorcss'];
		else if (array_key_exists('sitecss',Zymurgy::$config))
			$fck->Config['EditorAreaCSS'] = Zymurgy::$config['sitecss'];
		$fck->Create();
?>
				</td>
			</tr>
<?
		$flavours = Zymurgy::GetAllFlavours();
		foreach($flavours as $flavour)
		{
			if (!$flavour['providescontent']) continue;
?>
			<tr>
				<td><?= $flavour['label'] ?>:</td>
				<td>
<?
		$fck = new FCKeditor($name."_".$flavour['code']);
		$fck->BasePath = "/zymurgy/fckeditor/";
		$fck->ToolbarSet = 'Zymurgy';
		$fck->Width = $ep[1];
		$fck->Height = $ep[2];
		$fck->Value = $this->GetFlavouredValue($value, $flavour['code']);
		if (array_key_exists('fckeditorcss',$this->extra))
			$fck->Config['EditorAreaCSS'] = $this->extra['fckeditorcss'];
		else if (array_key_exists('sitecss',Zymurgy::$config))
			$fck->Config['EditorAreaCSS'] = Zymurgy::$config['sitecss'];
		$fck->Create();
?>
				</td>
			</tr>
<?
		}
?>
		</table>
<?
	}
}
/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_FlavouredCKHtml extends ZIW_FlavouredRichTextBase
{
	function __construct()
	{
		$this->xlatehtmlentities = false;
	}

	function Display($ep,$display,$shell)
	{
		return $this->GetFlavouredValue($display);
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
		require_once(Zymurgy::$root."/zymurgy/ckeditor/ckeditor.php");

?>
		<input type="hidden" id="<?= $name ?>" name="<?= $name ?>" value="<?= $value ?>">
		<table>
			<tr>
				<td>Default:</td>
				<td>
<?
		$config = array();
		$config["width"] = $ep[1];
		$config["height"] = $ep[2];
		$config["contentsCss"] = array_key_exists("fckeditorcss", $this->extra)
			? $this->extra["fckeditorcss"]
			: Zymurgy::$config["sitecss"];

		$ck = new CKEditor();
		$ck->basePath = "/zymurgy/ckeditor/";
		$ck->editor($name, $value, $config);
?>
				</td>
			</tr>
<?
		$flavours = Zymurgy::GetAllFlavours();
		foreach($flavours as $flavour)
		{
			if (!$flavour['providescontent']) continue;
?>
			<tr>
				<td><?= $flavour['label'] ?>:</td>
				<td>
<?
		$config = array();
		$config["width"] = $ep[1];
		$config["height"] = $ep[2];
		$config["contentsCss"] = array_key_exists("fckeditorcss", $this->extra)
			? $this->extra["fckeditorcss"]
			: Zymurgy::$config["sitecss"];

		$ck = new CKEditor();
		$ck->basePath = "/zymurgy/ckeditor/";
		$ck->editor(
			$name."_".$flavour['code'],
			$this->GetFlavouredValue($value, $flavour['code']),
			$config);
?>
				</td>
			</tr>
<?
		}
?>
		</table>
<?
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Color(inputspecName) {\n";
		$output .= " if(inputspecName == 'color') {\n";
		$output .= "  var specifier = new InputSpecifier;\n";
		$output .= "  specifier.description = \"Color\";\n";
		$output .= "  specifier.type = inputspecName;\n";

		$output .= "  return specifier;\n";
		$output .= " }\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(6)";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_ZIW_Theme(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Color Theme\";\n";
		$output .= " specifier.type = inputspecName;\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	function GetDatabaseType($inputspecName, $parameters)
	{
		return "VARCHAR(60)";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
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
				return "passThroughFormSubmit = false;\n".
					"ok = false;\n";
					"function Asirra_CheckIfHuman(isHuman) {\n".
					" if (passThroughFormSubmit) {\n".
  					"  return true;\n".
					" }\n".
					" if (isHuman) {\n".
					"  passThroughFormSubmit = true;\n".
					"  me.submit();\n".
					" } else {\n".
					"  alert('Please correctly identify the cats.');\n".
					" }\n".
					"}\n;";
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

	function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetSpecifier_".get_class($this)."(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Human Interactive Proof (Asirra)\";\n";
		$output .= " specifier.type = \"hip.asirra\";\n";

		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Position (top, bottom, left or right)\", 10, 10, \"bottom\"));\n";
		$output .= " specifier.inputparameters.push(".
			"DefineTextParameter(\"Width (in cats)\", 3, 5, 6));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}
}

/**
 * @package Zymurgy
 */
class DataGridLookup
{
	public $values;
	public $keys = array(); //In the correct display order

	function DataGridLookup($table,$idcolumn,$valcolumn,$ordercolumn = '')
	{
		$sql = "SELECT `$idcolumn`, `$valcolumn` FROM `$table`";

		if ($ordercolumn != '')
			$sql .= " ORDER BY `$ordercolumn`";
		$ri = Zymurgy::$db->query($sql);

		if (!$ri)
		{
			echo "Error loading lookup: ".Zymurgy::$db->error()." [$sql]";
			exit;
		}

		while (($row = Zymurgy::$db->fetch_array($ri)) !== false)
		{
			$this->values[$row[$idcolumn]] = $row[$valcolumn];
			$this->keys[] = $row[$idcolumn];
		}

		Zymurgy::$db->free_result($ri);
	}

	function RenderDropList(
		$name,
		$selected,
		$allowNulls = false)
	{
		$r = array();
		$r[] = "<select id='$name' name='$name'>";

		if($allowNulls)
		{
			$r[] = "<option value=\"\"".
				($selected == "" ? " selected=\"selected\"" : "").
				">&nbsp;</option>";
		}

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

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_GMap extends ZIW_Base
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
			"$name\" rows=\"4\" cols=\"60\">$value</textarea>";
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
		$w = array_key_exists(1,$ep) ? intval($ep[1]) : 0;
		$h = array_key_exists(2,$ep) ? intval($ep[2]) : 0;
		$z = array_key_exists(3,$ep) ? intval($ep[3]) : 0;
		if ($w == 0) $w = 425;
		if ($h == 0) $h = 350;
		if ($z == 0) $z = 14;
		$q = urlencode(str_replace(array("\r","\n"),'',$display));
		$url = "http://maps.google.ca/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=$q&amp;ie=UTF8&amp;z=$z&amp;output=embed";
		return "<iframe width=\"$w\" height=\"$h\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\"
			src=\"$url\"></iframe><br /><small><a href=\"$url\" target=\"_blank\" style=\"color:#0000FF;text-align:left\">View Larger Map</a></small>";
	}
}

/**
 * @package Zymurgy
 * @subpackage inputwidgets
 */
class ZIW_Page extends ZIW_Base
{
	public function GetInputSpecifier()
	{
		$output = "";

		$output .= "function GetListItems_".get_class($this)."() {\n";
		$output .= " var items = new Array();\n";
		$output .= " items[0] = \"id\";\n";
		$output .= " items[1] = \"path\";\n";
		$output .= " return items;\n";
		$output .= "}\n";

		$output .= "function GetSpecifier_".get_class($this)."(inputspecName) {\n";
		$output .= " var specifier = new InputSpecifier;\n";
		$output .= " specifier.description = \"Page Reference\";\n";
		$output .= " specifier.type = \"page\";\n";

		$output .= " specifier.inputparameters.push(".
			"DefineSelectParameter(\"Reference Type:\", 30, 200, \"GetListItems_".get_class($this)."();\", \"\", \"\"));\n";

		$output .= " return specifier;\n";
		$output .= "}\n";

		return $output;
	}

	public function Render($ep,$name,$value)
	{
		$sql = "SELECT `id`, `linktext`, `linkurl`, `parent` FROM `zcm_sitepage` ORDER BY `parent`, `disporder`";
		$ri = Zymurgy::$db->query($sql)
			or die("Could not retrieve page structure: ".Zymurgy::$db->error().", $sql");

		$pages = array();

		while(($row = Zymurgy::$db->fetch_array($ri)) !== FALSE)
		{
			$page = array(
				"id" => $row["id"],
				"path" => "/".$row["linkurl"],
				"caption" => $row["linktext"],
				"parent" => $row["parent"]);

			$parent = $page["parent"];

			if($parent > 0)
			{
				$page["path"] = $pages[$parent]["path"].
					$page["path"];
				$page["caption"] = $pages[$parent]["caption"].
					" &raquo; ".
					$page["caption"];
			}

			$pages[$row["id"]] = $page;
		}

		Zymurgy::$db->free_result($ri);

		uasort($pages, "ZIW_Page::ComparePage");

		$output = "";
		$output .= "<select name=\"$name\" id=\"$name\">\n";

		foreach($pages as $page)
		{
			$pageValue = $ep[1] == "id" ? $page["id"] : "/pages".$page["path"];

			$output .= "<option value=\"".
				$pageValue.
				"\"".
				($value == $pageValue ? " SELECTED" : "").
				">".
				$page["caption"].
				"</option>\n";
		}

		$output .= "</select>\n";

		echo $output;
	}

	public static function ComparePage($page1, $page2)
	{
		return strcmp(
			strtoupper($page1["caption"]),
			strtoupper($page2["caption"]));
	}

	public function Display($ep,$display,$shell)
	{
		if($ep[1] == "id")
		{
			$sql = "SELECT `linktext` FROM `zcm_sitepage` WHERE `id` = '".
				Zymurgy::$db->escape_string($display).
				"'";
			$linktext = Zymurgy::$db->get($sql);

			return "<a href=\"/zymurgy/template.php?id=$display\">$linktext</a>";
		}
		else
		{
			return $display;
		}
	}
}


/**
 * Input widget handler
 *
 * @package Zymurgy
 */
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
	function &Get($type)
	{
		if (!array_key_exists($type, InputWidget::$widgets))
			$type = 'default';
		return InputWidget::$widgets[$type];
	}

	/**
	 * Find the Input Widget object for this input spec and return it
	 *
	 * @param string $inputspec
	 * @return ZIW_Base
	 */
	function GetFromInputSpec($inputspec)
	{
		$ep = explode('.',$inputspec);
		$widget = InputWidget::Get($ep[0]);
		return $widget;
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
			$value = htmlspecialchars($value);
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
		$widget = &InputWidget::Get($ep[0]);
		$widget->extra['dialogName'] = $dialogName;
		$widget->extra['tabsetName'] = $tabsetName;
		$widget->extra['tabName'] = $tabName;
		$this->SetExtras($widget);
		if ($widget->xlatehtmlentities)
			$value = htmlspecialchars($value);
		$widget->Render($ep,$name,$value);
	}

	function IsValid($type, $value)
	{
		$ep = explode('.',$type);
		$widget = InputWidget::Get($ep[0]);

		return $widget->IsValid($value);
	}

	function inputspec2sqltype($inputspec)
	{
		list($type,$params) = explode('.',$inputspec,2);
		$pp = explode('.',$params);

		if(
			array_key_exists($type,InputWidget::$widgets)
			&& method_exists(InputWidget::$widgets[$type], "GetDatabaseType"))
		{
			$dbType = call_user_func(array(InputWidget::$widgets[$type], "GetDatabaseType"), $type, $pp);
			// die($dbType);
			return $dbType;
		}
		else
		{
			return "text";
		}
	}
}

InputWidget::Register('input',new ZIW_Input());
InputWidget::Register("inputf", new ZIW_InputFlavoured());
InputWidget::Register('lookup',new ZIW_Lookup());
InputWidget::Register('textarea',new ZIW_TextArea());
InputWidget::Register("textareaf", new ZIW_TextAreaFlavoured());
InputWidget::Register('unixdatetime',new ZIW_UnixDateTime());
InputWidget::Register('autocomplete',new ZIW_AutoComplete());
InputWidget::Register('drop',new ZIW_Drop());
InputWidget::Register('time',new ZIW_Time());
// InputWidget::Register('unixdate',new ZIW_UnixDate());
InputWidget::Register('unixdate',new ZIW_YUIUnixDate());
InputWidget::Register('date',new ZIW_Date());
InputWidget::Register('datetime',new ZIW_DateTime());
InputWidget::Register('theme',new ZIW_Theme());
InputWidget::Register('inputspec',new ZIW_InputSpec());
InputWidget::Register('image',new ZIW_Image());
InputWidget::Register('yuihtml',new ZIW_YUIHtml());
InputWidget::Register('fckhtml',new ZIW_Html());
InputWidget::Register('ckhtml',new ZIW_CKHtml());
InputWidget::Register('html',InputWidget::Get(
	array_key_exists('richtexteditor',Zymurgy::$config) ? Zymurgy::$config['richtexteditor'] : 'ckhtml'));

InputWidget::Register("fckhtmlf", new ZIW_FlavouredHtml());
InputWidget::Register("ckhtmlf", new ZIW_FlavouredCKHtml());
InputWidget::Register("htmlf", InputWidget::Get(
	array_key_exists('richtexteditor',Zymurgy::$config) ? Zymurgy::$config['richtexteditor']."f" : 'fckhtmlf'));

InputWidget::Register('attachment',new ZIW_Attachment());
InputWidget::Register('plugin',new ZIW_Plugin()); //Ugly, needs tweaking
InputWidget::Register('remote',new ZIW_RemoteLookup());
InputWidget::Register('hip',new ZIW_HIP());
InputWidget::Register('default',new ZIW_Base());
InputWidget::Register('password',new ZIW_Password());
InputWidget::Register('numeric',new ZIW_Input());
InputWidget::Register('float',new ZIW_Input());
InputWidget::Register('checkbox',new ZIW_CheckBox());
InputWidget::Register('money',new ZIW_Money()); //Rounding problem (3.14 -> 3.00!)
InputWidget::Register('radio',new ZIW_Radio());
InputWidget::Register('color',new ZIW_Color());
InputWidget::Register('colour',new ZIW_Color());
InputWidget::Register('hidden',new ZIW_Hidden());
InputWidget::Register('gmap',new ZIW_GMap());
InputWidget::Register("page", new ZIW_Page());
InputWidget::Register("databasetable", new ZIW_DatabaseTable());

include_once(Zymurgy::$root."/zymurgy/PluginBase.php");
include_once(Zymurgy::$root."/zymurgy/plugins/TagCloud.php");

if(class_exists("PIW_CloudTagInput"))
{
	InputWidget::Register("taglist", new PIW_CloudTagInput());
	InputWidget::Register("tagcloud", new PIW_CloudTagCloud());
}

//InputWidget::Register('',new ZIW_);

if (file_exists(Zymurgy::$root.'/zymurgy/custom/CustomWidgets.php'))
	require_once(Zymurgy::$root.'/zymurgy/custom/CustomWidgets.php');
?>
