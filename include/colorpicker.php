<?php
	/**
	 * Color Picker dialog. Used by the color and theme input widgets.
	 *
	 * @package Zymurgy
	 * @subpackage backend-modules
	 * @todo Turn this into a class, and make helper methods private
	 */

	/**
	 * Returns the Javascript required to render the Color Picker dialog.
	 *
	 * @return string
	 */
	function ColorPicker_JavaScript()
	{
		return '
<script type="text/javascript">
	var colourPicker;
	var colourPickerDlg;

' . ColorPicker_JavaScript_ShowColorPicker() . '

	YAHOO.namespace("zymurgy.colorpicker")
	YAHOO.zymurgy.colorpicker.inDialog = function() {
		var Event=YAHOO.util.Event,
			Dom=YAHOO.util.Dom,
			lang=YAHOO.lang;

		return {
	        init: function() {
' . ColorPicker_JavaScript_DefineDialog() . '
' . ColorPicker_JavaScript_RenderEvent() . '

	            this.dialog.validate = function() {
					return true;
	            };

	            this.dialog.callback = { success: this.handleSuccess, thisfailure: this.handleFailure };
	            this.dialog.render();

	            colourPickerDlg = this.dialog;
			},
' . ColorPicker_JavaScript_HandleSubmit() . '
			handleCancel: function() {
				this.cpEditor.value = rgbToHex(
					YAHOO.util.Dom.getStyle([this.cpSwatch], "backgroundColor"));

				if(typeof UpdatePreview == "function")
				{
					UpdatePreview();
				}

				if(document.getElementById("primaryColorContainer"))
				{
					document.getElementById("primaryColorContainer").style.visibility = "visible";
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
	    if(s && s.length==3)
	    {
	        d="";

	        for(i in s)
	        {
	            e=parseInt(s[i],10).toString(16);
	            if(e.length == 1)
	            {
	            	e == "0" ? d+="00" : d+= ("0" + e);
	            }
	            else
	            {
	            	d+=e;
	            }
	        }

	        return d;
	    }
	    else
	    {
	      return rgbval;
	    }
	  }
	  else
	  {
	    return rgbval;
	  }
	}
</script>
		';
	}

	/**
	 * Returns the javascript required to instantiate the YUI dialog.
	 *
	 * @return string
	 */
	function ColorPicker_JavaScript_DefineDialog()
	{
		return '
            this.dialog = new YAHOO.widget.Dialog("yui-picker-panel", {
				width : "400px",
				close: true,
				fixedcenter : false,
				visible : false,
				constraintoviewport : true,
				postmethod: "manual",
				buttons : [ { text:"Submit", handler:this.handleSubmit, isDefault:true },
							{ text:"Cancel", handler:this.handleCancel } ]
             });
		';
	}

	/**
	 * Returns the Javascript required to handle the submit button on the
	 * color picker dialog.
	 *
	 * @return string
	 */
	function ColorPicker_JavaScript_HandleSubmit()
	{
		return '
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

				if(document.getElementById("primaryColorContainer"))
				{
					document.getElementById("primaryColorContainer").style.visibility = "visible";
				}
			},
		';
	}

	/**
	 * Returns the javascript required to handle the Render Event in the
	 * Color Picker dialog.
	 *
	 * @return string
	 */
	function ColorPicker_JavaScript_RenderEvent()
	{
		return '
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
		';
	}

	/**
	 * Returns the javascript required to display the color picker dialog.
	 *
	 * @return string
	 */
	function ColorPicker_JavaScript_ShowColorPicker()
	{
		return '
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

				if(document.getElementById("primaryColorContainer"))
				{
					document.getElementById("primaryColorContainer").style.visibility = "hidden";
				}
			}
		';
	}

	/**
	 * Returns the HTML block required to support the color picker dialog.
	 *
	 * @return string
	 */
	function ColorPicker_DialogHTML()
	{
		return '
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
	}

	/**
	 * Returns the Javascript block required to render the Theme dialog.
	 *
	 * @return unknown
	 */
	function Theme_JavaScript()
	{
		return '
			<script type="text/javascript">
				function OpenThemeWindow(control) {
					themeWindow = window.open(
						"/zymurgy/sitecolors.php?themeControl=" + control,
						"themeWindow",
						"status=0,toolbar=0,width=840,height=450");
				}
			</script>
		';
	}
?>