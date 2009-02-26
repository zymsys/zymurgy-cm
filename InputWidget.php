<?
/*
This could be a hell of a lot better if I actually designed a framework for input widgets, but
all I did was move the widget code out of the datagrid and into this so that I could reuse
it elsewhere.  This is more of a library than a proper OOP thing.  Maybe one day PHP's
OOP support and my code can meet in the middle.

Now I require PHP5 and the oop is good, but I still need to come back to this to "fix" it.
*/
class InputWidget
{
	var $fck = array();
	var $mypath;
	var $fckeditorpath;
	var $UsePennies = true;
	var $lookups = array();
	var $editkey = 0;
	var $datacolumn = 'sitetext.body';
	
	function InputWidget()
	{
		$this->fckeditorpath = '/zymurgy/fckeditor/';
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
			case "image":
				if (isset($this->targetsize))
					$targetsize = $this->targetsize;
				else
					$targetsize = "{$ep[1]}x{$ep[2]}";
				$imgsrc = "/UserFiles/DataGrid/sitetext.body/{$_GET['editkey']}thumb$targetsize.jpg?".rand(0,99999);
				$display = "<img id=\"sitetext.body.$targetsize\" src=\"$imgsrc\" /></a>";
				break;
			case "drop":
			case "radio":
				$ep = explode('.',$type,2); //Otherwise arrays which contain a . will cause it to barf.
				$ritems = $this->HackedUnserialize($ep[1]);
				$display = $ritems[$display];
				break;
			case "password":
				$display = "*******";
				break;
		}
		return $display;
	}
	
	static function GetPretext($type)
	{
		$tp = explode('.',$type,2);
		switch($tp[0])
		{
			case "colour":
			case "color":
				return Zymurgy::YUI("fonts/fonts-min.css").
					Zymurgy::YUI("container/assets/skins/sam/container.css").
					Zymurgy::YUI("colorpicker/assets/skins/sam/colorpicker.css").
					Zymurgy::YUI("utilities/utilities.js").
					Zymurgy::YUI("container/container-min.js").
					Zymurgy::YUI("slider/slider-min.js").
					Zymurgy::YUI("colorpicker/colorpicker-min.js").'
<script type="text/javascript">
var colourPicker;
var colourPickerDlg;

function showColourPicker(editorId,swatchId) {
	var editor = document.getElementById(editorId);
	var colour = editor.value;
	var r = parseInt(colour.substr(0,2),16);
	var g = parseInt(colour.substr(2,2),16);
	var b = parseInt(colour.substr(4,2),16);
	colourPicker.setValue(new Array(r,g,b),true);
	colourPickerDlg.cpEditor = editor;
	colourPickerDlg.cpSwatch = swatchId;
	colourPickerDlg.show();
}

YAHOO.namespace("zymurgy.colorpicker")
YAHOO.zymurgy.colorpicker.inDialog = function() {
	var Event=YAHOO.util.Event,
		Dom=YAHOO.util.Dom,
		lang=YAHOO.lang;
	
	return {
        init: function() {
            this.dialog = new YAHOO.widget.Dialog("yui-picker-panel", { 
				width : "370px",
				close: true,
				fixedcenter : true,
				visible : false, 
				constraintoviewport : true,
				postmethod: "manual",
				buttons : [ { text:"Submit", handler:this.handleSubmit, isDefault:true },
							{ text:"Cancel", handler:this.handleCancel } ]
             });
            this.dialog.renderEvent.subscribe(function() {
				if (!this.picker) {
					var pickerOptions = {
						container: this.dialog,
						showhexcontrols: true,
						images: {
							PICKER_THUMB: "'.Zymurgy::YUIBaseURL().'colorpicker/assets/picker_thumb.png",
							HUE_THUMB: "'.Zymurgy::YUIBaseURL().'colorpicker/assets/hue_thumb.png"
						}
					};
					var el = document.getElementById("yui-picker");
					this.picker = new YAHOO.widget.ColorPicker(el,pickerOptions);
					colourPicker = this.picker;
					this.picker.on("rgbChange", function(o) {				
						// alert(colourPickerDlg.cpEditor.name);
						colourPickerDlg.cpEditor.value = this.get("hex");
									
						if(typeof matchColors == "function" && colourPickerDlg.cpEditor.name == "color0")
						{						
							// alert(this.get("hex"));
							matchColors(this.get("hex"));
						}
						
						if(typeof UpdatePreview == "function")
						{
							UpdatePreview();
						}
					});
				}
			});	
            this.dialog.validate = function() {
				return true;
            };
            this.dialog.callback = { success: this.handleSuccess, thisfailure: this.handleFailure };
            this.dialog.render();
            colourPickerDlg = this.dialog;
		},
		handleSubmit: function() {
			var hex = document.getElementById(this.picker.ID.HEX);
			this.cpEditor.value = hex.value;
			YAHOO.util.Dom.setStyle([this.cpSwatch],"backgroundColor","#"+hex.value);
			
			if(typeof matchColors == "function" && colourPickerDlg.cpEditor.name == "color0")
			{						
				matchColors(hex.value);
			}
			
			this.submit();
			
			if(typeof UpdatePreview == "function")
			{
				UpdatePreview();
			}
			
			// alert(this.cpEditor.name);
			
			if(document.getElementById(this.cpEditor.name + "locked"))
			{
				document.getElementById(this.cpEditor.name + "locked").checked = true;
			}
		},
		handleCancel: function() {
			this.cpEditor.value = rgbToHex(
				YAHOO.util.Dom.getStyle([this.cpSwatch], "backgroundColor"));
			
			if(typeof UpdatePreview == "function")
			{
				UpdatePreview();
			}
			
			this.cancel();
		},
		handleSuccess: function(o) {
		},
		handleFailure: function(o) {
		}
	}
}();

YAHOO.util.Event.onDOMReady(YAHOO.zymurgy.colorpicker.inDialog.init, YAHOO.zymurgy.colorpicker.inDialog, true);

function updateSwatch(swatchName, newValue)
{
	YAHOO.util.Dom.setStyle(swatchName, "backgroundColor", "#"+newValue);
}
//Convert a hex value to its decimal value - the inputted hex must be in the
//	format of a hex triplet - the kind we use for HTML colours. The function
//	will return an array with three values.

function hex2num(hex) {
	if(hex.charAt(0) == "#") hex = hex.slice(1); //Remove the # char - if there is one.
	hex = hex.toUpperCase();
	var hex_alphabets = "0123456789ABCDEF";
	var value = new Array(3);
	var k = 0;
	var int1,int2;
	for(var i=0;i<6;i+=2) {
		int1 = hex_alphabets.indexOf(hex.charAt(i));
		int2 = hex_alphabets.indexOf(hex.charAt(i+1)); 
		value[k] = (int1 * 16) + int2;
		k++;
	}
	return(value);
}
function rgbToHex(rgbval){
  var s = rgbval.toString().match(/rgb\s*\x28((?:25[0-5])|(?:2[0-4]\d)|(?:[01]?\d?\d))\s*,\s*((?:25[0-5])|(?:2[0-4]\d)|(?:[01]?\d?\d))\s*,\s*((?:25[0-5])|(?:2[0-4]\d)|(?:[01]?\d?\d))\s*\x29/);
  if(s){
    s=s.splice(1);
    if(s && s.length==3){
        d="";

        for(i in s){
            e=parseInt(s[i],10).toString(16);
            if(e.length == 1){
              e == "0" ? d+="00" : d+= ("0" + e);
            }else{
              d+=e;

            }
        } return d;
    }else{
      return rgbval;
    }
  }else{

    return rgbval;
  }
}
</script>
<div id="yui-picker-panel" class="yui-picker-panel">
	<div class="hd">Please choose a color:</div>
	<div class="bd">
		<form name="yui-picker-form" id="yui-picker-form">
		<div class="yui-picker" id="yui-picker"></div>
		</form>
	</div>
	<div class="ft"></div>
</div>
';
				break;
		}
		return '';
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
		if ($ep[0]!='html')
			$value = htmlentities($value);
		switch($ep[0])
		{
			case "input":
			case "numeric":
			case "float":
				echo "<input type=\"text\" size=\"{$ep[1]}\" maxlength=\"{$ep[2]}\" id=\"$name\" name=\"".
					"$name\" value=\"$value\" />";
				break;
			case "password":
				echo "<input type=\"password\" size=\"{$ep[1]}\" maxlength=\"{$ep[2]}\" id=\"$name\" name=\"".
					"$name\" value=\"$value\" />";
				break;
			/*case "colour":
			case "color":
				echo "<input type=\"text\" size=\"7\" maxlength=\"7\" name=\"".
					"$name\" value=\"$value\" />";
				break;*/
			case "checkbox":
				echo "<input type=\"checkbox\" id=\"$name\" name=\"$name\"";
				if (($ep[1]=='checked') || ($value!='')) echo " checked=\"checked\"";
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
				$cal = new DHTML_Calendar($this->mypath.'jscalendar/','en','calendar-win2k-2', false);
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
		                 'value'       => strftime('%Y-%m-%d [%I:%M %p]', $date)));
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
						$imgsrc = "/UserFiles/DataGrid/{$this->datacolumn}/{$this->editkey}thumb$targetsize.jpg?".rand(0,99999);
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
				echo Zymurgy::YUI("assets/skins/sam/skin.css");
				echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
				echo Zymurgy::YUI("element/element-beta-min.js");
				echo Zymurgy::YUI("container/container_core-min.js");
				echo Zymurgy::YUI("editor/editor-min.js");
				
				if($dialogName !== "")
				{
					echo("<div id=\"{$name}_div\"></div>");
				}
				
				echo("<textarea id=\"$name\" name=\"$name\" cols=\"60\" rows=\"10\">$value</textarea>\r\n");
					
				?>					
					<script type="text/javascript">
						var Display<?= $name ?>Exists = true;
						var <?= $name ?>Editor;
					
						function Display<?= $name ?>() {
							var myConfig = {
								height: '<?= $ep[2] ?>px',
								width: '<?= $ep[1] ?>px',
								dompath: true,
								focusAtStart: false
							};
							
							<?= $name ?>Editor = new YAHOO.widget.Editor(
								'<?= $name ?>',
								myConfig);
							
							<? if($dialogName !== "") { ?>
							<?= $name ?>Editor.on('windowRender', function() {
								document.getElementById('<?= $name ?>_div').appendChild(this.get('panel').element);
							});
							
							if(typeof <?= $dialogName ?> == "Dialog")
							{
								Link<?= $name ?>ToDialog();
							}
							<? } ?>
								
							<?= $name ?>Editor.render();							
						}
						
						function Link<?= $name ?>ToDialog()
						{
							<?= $dialogName ?>.showEvent.subscribe(
								<?= $name ?>Editor.show,
								<?= $name ?>Editor,
								true);
							<?= $dialogName ?>.hideEvent.subscribe(
								<?= $name ?>Editor.hide,
								<?= $name ?>Editor,
								true);
						}
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
			case "colormatchprimary":
				$matchJS = "";
				
				if($ep[0] == "colormatchprimary")
				{
					$matchJS = "matchColors(this.value);";
				}
				
				echo "#<input type=\"text\" name=\"$name\" id=\"$name\" value=\"$value\" maxlength=\"6\" size=\"6\" onChange=\"updateSwatch('swatch$name', this.value); if(typeof UpdatePreview == 'function') { UpdatePreview(); }; if(document.getElementById('{$name}locked')) {document.getElementById('{$name}locked').checked = true;}; $matchJS\">&nbsp;";
				echo "<span id=\"swatch$name\" onclick=\"showColourPicker('$name','swatch$name')\" style=\"width:15px; height:15px; background-color:#$value; border: #000000 solid 1px; cursor:pointer;\">&nbsp;&nbsp;&nbsp;</span>";
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
		$r[] = "<select name='$name'>";
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
