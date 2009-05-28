<?
/*
This could be a hell of a lot better if I actually designed a framework for input widgets, but
all I did was move the widget code out of the datagrid and into this so that I could reuse
it elsewhere.  This is more of a library than a proper OOP thing.  Maybe one day PHP's
OOP support and my code can meet in the middle.

Now I require PHP5 and the oop is good, but I still need to come back to this to "fix" it.
*/

require_once("include/colorpicker.php");

class InputWidget
{
	var $fck = array();
	var $mypath;
	var $fckeditorpath;
	var $UsePennies = true;
	var $lookups = array();
	var $editkey = 0;
	var $datacolumn = 'sitetext.body';
	var $OnBeforeAutoInsert;
	var $OnAutoInsert;

	function InputWidget()
	{
		$this->fckeditorpath = '/zymurgy/fckeditor/';
		if (array_key_exists('editkey',$_GET))
			$this->editkey = $_GET['editkey'];
	}

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

	function PostValue($type,$postname)
	{
		$postname = str_replace(' ','_',$postname);
		$ep = explode('.',$type);
		switch ($ep[0])
		{
			case 'image':
			case 'attachment':
				$file = $_FILES[$postname];
				if ($file['type']!='')
					return $file['type'];
				break;
			case "money":
				$m = $_POST[$postname];
				$m = str_replace(array("$",","),'',$m);
				if ($this->UsePennies)
					$m = $m * 100;
				return $m;
				break;
			case "autocomplete":
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
					if (isset($this->OnBeforeAutoInsert))
						$newrecord = call_user_func($this->OnBeforeAutoInsert, $table, $newrecord);
					$sql = "insert into $table (".
						implode(',',array_keys($newrecord)).") values ('".
						implode("','",$newrecord)."')";
					Zymurgy::$db->run($sql);
					$value = $newrecord[$idcolumn] = Zymurgy::$db->insert_id();
					if (isset($this->OnAutoInsert))
						call_user_func($this->OnAutoInsert,$table, $newrecord);
				}
				return $value;
				break;
			case "unixdate":
				$dp = explode("-",$_POST[$postname]);
				return mktime(0,0,0,$dp[1],$dp[2],$dp[0]);
				break;
			case "unixdatetime":
			case "datetime":
				//'%Y-%m-%d [%I:%M %p]'
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
				$tm = mktime($tp[0],$tp[1],0,$dp[1],$dp[2],$dp[0]);
				if ($ep[0]=='unixdatetime')
					return $tm;
				else
					return strftime('%Y-%m-%d %H:%M:%S',$tm);
				break;
			case "time":
				$t = strtotime($_POST[$postname]);
				return date('H:i:s',$t);
				break;
			case "date": //%Y-%m-%d, untouched.
			default:
				if (array_key_exists($postname,$_POST))
				{
					return $_POST[$postname];
				}
				else
					return '';
		}
	}

	function Display($type,$template,$value,$masterkey='')
	{
		if ($template == '') return '';
		$display = str_replace(array("{0}","{ID}"),array($value,$masterkey),$template);
		$ep = explode('.',$type);
		switch ($ep[0])
		{
			case "unixdate":
				$display = date("Y-m-d",$display);
				break;
			case "unixdatetime":
				$display = date("Y-m-d [g:i A]",$display);
				break;
			case "datetime":
				$display = date("Y-m-d [g:i A]",strtotime($display));
				break;
			case "time": //Convert 24hr clock to hh:mm am/pm
				$display = date("g:i a",strtotime($display));
				break;
			case "money":
				$display = '$'.number_format($this->UsePennies ? ($display / 100) : $display,2,'.',',');
				break;
			case "lookup":
				$display = $this->lookups[$ep[1]]->values[$display];
				break;
			case "autocomplete":
				$table = $ep[1];
				$idcolumn = $ep[2];
				$column = $ep[3];
				$autocreate = array_key_exists(4,$ep) ? ($ep[4] == 'true') : false;
				$sql = "select `$column` from `$table` where `$idcolumn`='".
					Zymurgy::$db->escape_string($display)."'";
				$display = Zymurgy::$db->get($sql);
				break;
			case "image":
				if (isset($this->targetsize))
					$targetsize = $this->targetsize;
				else
					$targetsize = "{$ep[1]}x{$ep[2]}";
				require_once(Zymurgy::$root.'/zymurgy/include/Thumb.php');
				$ext = Thumb::mime2ext($display);
				list($dataset,$datacolumn) = explode('.',$this->datacolumn);
				$imgsrc = "/zymurgy/file.php?mime=$display&dataset=$dataset&datacolumn=$datacolumn&id={$this->editkey}&w={$ep[1]}&h={$ep[2]}";
				//$imgsrc = "/UserFiles/DataGrid/{$this->datacolumn}/{$this->editkey}thumb$targetsize.$ext?".rand(0,99999);
				$display = "<img id=\"sitetext.body.$targetsize\" src=\"$imgsrc\" /></a>";
				break;
			case "drop":
			case "radio":
				$ep = explode('.',$type,2); //Otherwise arrays which contain a . will cause it to barf.
				$ritems = $this->HackedUnserialize($ep[1]);
				$display = $ritems[$display];
				break;
			case "plugin":
				$ep = explode('&',$display);
				$display = urldecode($ep[0]).' ('.urldecode($ep[1]).')';
				break;
			case "password":
				$display = "*******";
				break;
		}
		return $display;
	}

	static function GetPretext($type)
	{
		$output = "";

		$tp = explode('.',$type,2);
		switch($tp[0])
		{
			case "colour":
			case "color":
				$output .= Zymurgy::YUI("fonts/fonts-min.css");
				$output .= Zymurgy::YUI("container/assets/skins/sam/container.css");
				$output .= Zymurgy::YUI("colorpicker/assets/skins/sam/colorpicker.css");
				$output .= Zymurgy::YUI("utilities/utilities.js");
				$output .= Zymurgy::YUI("container/container-min.js");
				$output .= Zymurgy::YUI("slider/slider-min.js");
				$output .= Zymurgy::YUI("colorpicker/colorpicker-min.js");
				$output .= ColorPicker_JavaScript();
				$output .= ColorPicker_DialogHTML();

				break;

			case "theme":
				$output .= Zymurgy::YUI("container/assets/container.css");
				$output .= Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
				$output .= Zymurgy::YUI("animation/animation-min.js");
				$output .= Zymurgy::YUI("container/container-min.js");
				$output .= Theme_JavaScript();

				break;

			case "yuihtml":
				$output .= Zymurgy::YUI("assets/skins/sam/skin.css");
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

				break;
		}

		return $output;
	}

	function JSRender($type,$name,$value)
	{
		$ep = explode('.',$type);
		if ($ep[0]!='html')
			$value = htmlentities($value);
		switch($ep[0])
		{
			case "hip":
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
				break;
		}
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
		if (($ep[0]!='html') && ($ep[0]!='plugin'))
			$value = htmlentities($value);
		switch($ep[0])
		{
			case "input":
			case "numeric":
			case "float":
				echo "<input type=\"text\" size=\"{$ep[1]}\" maxlength=\"{$ep[2]}\" id=\"$name\" name=\"".
					"$name\" value=\"$value\" />";
				break;

			case "hidden":
				echo "<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$value\" />";
				break;

			case "password":
				echo "<input type=\"password\" size=\"{$ep[1]}\" maxlength=\"{$ep[2]}\" id=\"$name\" name=\"".
					"$name\" value=\"$value\" />";
				break;

			case "checkbox":
				echo "<input type=\"checkbox\" id=\"$name\" name=\"$name\"";
				if (isset($ep[1]) && ($ep[1]=='checked') || ($value!='')) echo " checked=\"checked\"";
				echo " />";
				break;

			case "textarea":
				echo "<textarea id=\"$name\" name=\"".
					"$name\" rows=\"{$ep[2]}\" cols=\"{$ep[1]}\">$value</textarea>";
				break;

			case "money":
				$m = $value;
				if ($this->UsePennies)
					$m = $m / 100;
				$m = '$'.number_format($m,2,'.',',');
				echo "<input type=\"text\" id=\"$name\" name=\"$name\" value=\"$m\" />";
				break;

			case "inputspec":
				require_once(Zymurgy::$root."/zymurgy/include/inputspec.php");
				echo "<script>makeInputSpecifier('".
					str_replace("'","\'",$name)."','".
					str_replace("'","\'",$value)."');</script>";
				break;

			case "autocomplete":
			case "plugin":
				global $ZymurgyAutocompleteZIndex;
				$isplugin = ($ep[0] == "plugin");
				$acwidth = 200;
				if (!isset($ZymurgyAutocompleteZIndex)) $ZymurgyAutocompleteZIndex = 9000;
				$jsname = str_replace('.','_',$name);
				if ($isplugin)
				{ //Show drop down list of available plugins
					$ep = explode('&',$value);
					$pluginvalue = urldecode($ep[0]);
					$textvalue = urldecode($ep[1]);
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
				else 
				{
					$table = $ep[1];
					$idcolumn = $ep[2];
					$column = $ep[3];
					$textvalue = $this->Display($type,'{0}',$value);
				}
				echo "<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"".
					addslashes($textvalue)."\"/>";
				if ($isplugin)
				{
					echo "<div style=\"float:left; margin-left:6px\">";
				}
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
				echo "<div id=\"{$name}-autocomplete\" style=\"width: {$acwidth}px\"><input id=\"{$name}-input\" type=\"text\" ";
				if ($isplugin)
				{
					echo "title=\"Choose a Plugin First\" ";
				}
				echo "value=\"".htmlentities($textvalue)."\" onchange=\"{$jsname}_update\" />";
				echo "<div id=\"{$name}-container\" style=\"z-index:$ZymurgyAutocompleteZIndex\"></div></div>";
				echo "<div style=\"float:left;z-index:$ZymurgyAutocompleteZIndex;";
				echo " margin-left:".($acwidth+5)."px";
				echo "\"><input type=\"button\" value=\"&raquo;\" onclick=\"{$jsname}_autocomp.toggleContainer(); Zymurgy.toggleText(this,'&raquo;','&laquo;');\" /></div>";
				echo "</div>";
				echo '<script type="text/javascript">
					'.$jsname.'_text = document.getElementById("'.$name.'-input");
					'.$jsname.'_plugin = document.getElementById("'.$name.'-plugin");
					'.$jsname.'_hidden = document.getElementById("'.$name.'");
					';
				if ($isplugin)
				{
					echo "Zymurgy.enableHint({$jsname}_text);\r\n";
				}
				if ($isplugin)
				{
					if (empty($textvalue))
						echo "{$jsname}_text.disabled = true;\r\n";
					echo 'function '.$jsname.'_update() {
							if ('.$jsname.'_text.disabled || '.$jsname.'_plugin.value == 0)
								'.$jsname.'_hidden.value = "&";
							else
								'.$jsname.'_hidden.value = escape('.$jsname.'_plugin.options['.$jsname.'_plugin.selectedIndex].text) + "&" + escape('.$jsname.'_text.value);
						}
						YAHOO.util.Event.addListener("'.$name.'-plugin", "change", function () {
						if (this.value == "")
						{
							'.$jsname.'_text.disabled = true;
							YAHOO.util.Dom.setAttribute('.$jsname.'_text,"title","Choose a Plugin First");
						}
						else
						{
							'.$jsname.'_text.disabled = false;
							YAHOO.util.Dom.setAttribute('.$jsname.'_text,"title","Select one, or name a new one");
						}
						Zymurgy.refreshHint('.$jsname.'_text);
						'.$jsname.'_update();
					});
					var '.$jsname.'_datasource = new YAHOO.util.XHRDataSource("/zymurgy/include/plugin.php?pg='.$_GET['d'].'&");
						'.$jsname.'_datasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSARRAY;
						'.$jsname.'_datasource.responseSchema = {fields : ["plugin"]};
						var '.$jsname.'_autocomp = new YAHOO.widget.AutoComplete("'.$name.'-input","'.$name.'-container", '.$jsname.'_datasource);
						'.$jsname.'_autocomp.textboxChangeEvent.subscribe('.$jsname.'_update);
						'.$jsname.'_autocomp.generateRequest = function(sQuery) {
							var elSel = document.getElementById("'.$name.'-plugin");
							return "/zymurgy/include/plugin.php?pg='.$_GET['d'].'&pi=" + elSel.value + "&q=" + sQuery;
						};
						
						';
				}
				else 
				{ //Autocomplete
					echo 'var '.$jsname.'_datasource = new YAHOO.util.XHRDataSource("/zymurgy/include/autocomplete.php");
						'.$jsname.'_datasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
						'.$jsname.'_datasource.responseSchema = {
							resultsList : "results",
							fields : ["value"]};
						var '.$jsname.'_autocomp = new YAHOO.widget.AutoComplete("'.$name.'-input","'.$name.'-container", '.$jsname.'_datasource);
						'.$jsname.'_autocomp.generateRequest = function(sQuery) {
							return "/zymurgy/include/autocomplete.php?t='.urlencode($table).'&c='.
								urlencode($column).'&i='.urlencode($idcolumn).'&q=" + sQuery;
						};
						function '.$jsname.'_update() {
							'.$jsname.'_hidden.value = escape('.$jsname.'_text.value);
						}
						'.$jsname.'_autocomp.textboxChangeEvent.subscribe('.$jsname.'_update);
						';
				}
				echo "</script>\r\n";
				$ZymurgyAutocompleteZIndex++;
				break;

			case "radio":
				$rp = $ep;
				array_shift($rp);
				$radioarray = $this->HackedUnserialize(implode('.',$rp));
				foreach($radioarray as $rkey=>$rcaption)
				{
					echo "<label><input type=\"radio\" id=\"$name-$rkey\" name=\"$name\" value=\"$rkey\"";
					if ($value == $rkey) echo " checked=\"checked\"";
					echo " />$rcaption</label><br />\r\n";
				}
				break;

			case "drop":
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
				break;

			case "time":
				$value = date("g:i a",strtotime($value));
				echo "<input type=\"text\" size=\"8\" maxlength=\"8\" id=\"$name\" name=\"".
					"$name\" value=\"$value\" /> <i>hh:mm am/pm</i>";
				break;

			case "unixdate":
			case "date":
				require_once(Zymurgy::$root."/zymurgy/jscalendar/calendar.php");
				if ($ep[0] == "unixdate")
				{
					$date = $value;
				}
				else
				{
					$date = strtotime($value);
				}
				if ($date == 0) $date=time();
				$cal = new DHTML_Calendar(
					"/zymurgy/jscalendar/",
					'en',
					'calendar-win2k-2',
					false);

				$cal->SetFieldPrefix($name);
				$cal->SetIncludeID(false);

				$cal->load_files();
				$cal->make_input_field(
		           array('firstDay'       => 0, // show Monday first
		                 'showOthers'     => true,
		                 'ifFormat'       => '%Y-%m-%d',
		                 'timeFormat'     => '12'),
		           // field attributes go here
		           array('style'       => 'width: 15em; color: #840; background-color: #ff8; border: 1px solid #000; text-align: center',
		                 'name'        => $name,
		                 'value'       => strftime('%Y-%m-%d', $date)));
				break;

			case "unixdatetime":
			case "datetime":
				require_once(Zymurgy::$root."/zymurgy/jscalendar/calendar.php");
				if ($ep[0]=='unixdatetime')
					$date = $value;
				else
					$date = strtotime($value);
				if ($date == 0) $date=time();

				$cal = new DHTML_Calendar(
					"/zymurgy/jscalendar/",
					'en',
					'calendar-win2k-2',
					false);

				$cal->SetFieldPrefix($name);
				$cal->SetIncludeID(false);

				$cal->load_files();
				$cal->make_input_field(
		           array('firstDay'       => 0, // show Monday first
		                 'showOthers'     => true,
		                 'ifFormat'       => '%Y-%m-%d [%I:%M %p]',
		                 'timeFormat'     => '12',
		                 'showsTime'	  => 1),
		           // field attributes go here
		           array('style'       => 'width: 15em; color: #840; background-color: #ff8; border: 1px solid #000; text-align: center',
		                 'name'        => $name,
		                 'value'       => ($date == 0) ? '' : strftime('%Y-%m-%d [%I:%M %p]', $date)));
				break;

			case "lookup":
				echo $this->lookups[$ep[1]]->RenderDropList($name,$value);
				break;

			case "image":
				array_shift($ep); //Remove type
				$ep = explode(',',implode('.',$ep)); //Re-explode on ,
				$thumbs = array();
				if (($this->editkey==0) && array_key_exists('editkey',$_GET))
					$this->editkey = 0 + $_GET['editkey'];
				foreach($ep as $targetsize)
				{
					$targetsize = str_replace('.','x',$targetsize);
					if ($this->editkey > 0)
					{
						require_once(Zymurgy::$root."/zymurgy/include/Thumb.php");
						$ext = Thumb::mime2ext($value);
						$imgsrc = "/UserFiles/DataGrid/{$this->datacolumn}/{$this->editkey}thumb$targetsize.$ext?".rand(0,99999);
						$thumbs[] = "<a onclick=\"aspectcrop_popup('{$this->datacolumn}','$targetsize','{$this->editkey}','{$this->datacolumn}',true)\">".
							"<img id=\"{$this->datacolumn}.$targetsize\" src=\"$imgsrc\" style=\"cursor: pointer\" /></a> ";
					}
				}
				echo "<table><tr><td valign=\"center\"><input type=\"file\" id=\"$name\" name=\"$name\" /></td><td>".implode($thumbs,"</td><td>")."</td></tr></table>";
				break;

			case "attachment":
				echo "<input type=\"file\" id=\"$name\" name=\"$name\" />";
				break;

			case "yuihtml":
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

				break;

			case "html":
				require_once(Zymurgy::$root."/zymurgy/fckeditor/fckeditor.php");
				$fck = new FCKeditor($name);
				$fck->BasePath = "/zymurgy/fckeditor/";
				$fck->ToolbarSet = 'Zymurgy';
				$fck->Width = $ep[1];
				$fck->Height = $ep[2];
				$fck->Value = $value;
				$fck->Config['EditorAreaCSS'] = $this->fckeditorcss;
				$fck->Create();
				break;

			case "colour":
			case "color":
				$matchJS = "";

				echo "#<input type=\"text\" name=\"$name\" id=\"$name\" value=\"$value\" maxlength=\"6\" size=\"6\" onChange=\"updateSwatch('swatch$name', this.value); if(typeof UpdatePreview == 'function') { UpdatePreview(); }; if(document.getElementById('{$name}locked')) {document.getElementById('{$name}locked').checked = true;}; $matchJS\">&nbsp;";
				echo "<span id=\"swatch$name\" onclick=\"showColourPicker('$name','swatch$name')\" style=\"width:15px; height:15px; background-color:#$value; border: #000000 solid 1px; cursor:pointer;\">&nbsp;&nbsp;&nbsp;</span>";
				break;

			case "theme":
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

				break;

			case "hip":
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
				break;
		}
	}
}

class DataGridLookup
{
	var $values;
	var $keys; //In the correct display order

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
?>
