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
					Zymurgy::YUI("colorpicker/colorpicker-beta-min.js").'
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
				width : "500px",
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
					YAHOO.log("Instantiating the color picker", "info", "example");
					this.picker = new YAHOO.widget.ColorPicker("yui-picker", {
						container: this.dialog,
						showhexcontrols: true,
						images: {
							PICKER_THUMB: "http://yui.yahooapis.com/2.4.0/build/colorpicker/assets/picker_thumb.png",
							HUE_THUMB: "http://yui.yahooapis.com/2.4.0/build/colorpicker/assets/hue_thumb.png"
						}
					});
					this.picker.on("rgbChange", function(o) {
						YAHOO.log(lang.dump(o), "info", "example");
					});
					colourPicker = this.picker;
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
			this.submit();
		},
		handleCancel: function() {
			this.cancel();
		},
		handleSuccess: function(o) {
		},
		handleFailure: function(o) {
		}
	}
}();

YAHOO.util.Event.onDOMReady(YAHOO.zymurgy.colorpicker.inDialog.init, YAHOO.zymurgy.colorpicker.inDialog, true);

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
	
	function Render($type,$name,$value)
	{
		global $ZymurgyRoot;
		
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
				require_once("$ZymurgyRoot/zymurgy/include/inputspec.php");
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
					if ($value == $rkey) echo " checked";
					echo " />$rcaption</label><br>\r\n";
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
			case "unixdate":
				require_once("$ZymurgyRoot/zymurgy/jscalendar/calendar.php");
				$date = $value;
				if ($date == 0) $date=time();
				$cal = new DHTML_Calendar($this->mypath.'jscalendar/','en','calendar-win2k-2', false);
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
				require_once("$ZymurgyRoot/zymurgy/jscalendar/calendar.php");
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
				foreach($ep as $targetsize)
				{
					$targetsize = str_replace('.','x',$targetsize);
					$imgsrc = "/UserFiles/DataGrid/sitetext.body/{$_GET['editkey']}thumb$targetsize.jpg?".rand(0,99999);
					$thumbs[] = "<a onclick=\"aspectcrop_popup('sitetext.body','$targetsize','{$_GET['editkey']}','sitetext.body')\">".
						"<img id=\"sitetext.body.$targetsize\" src=\"$imgsrc\" /></a> ";
				}
				echo "<input type=\"file\" id=\"$name\" name=\"$name\" /> ".implode($thumbs);
				break;
			case "attachment":
				echo "<input type=\"file\" id=\"$name\" name=\"$name\" />";
				break;
			case "yuihtml":
				Zymurgy::YUI("assets/skins/sam/skin.css");
				Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
				Zymurgy::YUI("element/element-beta-min.js");
				Zymurgy::YUI("container/container_core-min.js");
				Zymurgy::YUI("menu/menu-min.js");
				Zymurgy::YUI("button/button-beta-min.js");
				Zymurgy::YUI("editor/editor-beta-min.js");
				Zymurgy::YUI("connection/connection-min.js");
				
				break;
			case "html":
				require_once("$ZymurgyRoot/zymurgy/fckeditor/fckeditor.php");
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
				echo "#<input type=\"text\" name=\"$name\" id=\"$name\" value=\"$value\" maxlength=\"6\" size=\"6\">&nbsp;";
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
