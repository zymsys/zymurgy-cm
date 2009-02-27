<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
	<HEAD>
		<TITLE>Edit Color Theme</TITLE>
<?php
	ini_set("display_errors", 1);
	
	$breadcrumbTrail = "Site Colors";	

	// require_once('header.php');
	require_once("cmo.php");
	require_once('datagrid.php');
	require_once('InputWidget.php');
	
	echo InputWidget::GetPretext("color.");
	
	$sampleHTML = @file_get_contents(Zymurgy::$root."/zymurgy/config/colorsample.html");
	
	if($sampleHTML == "")
	{
		$sampleHTML = @file_get_contents(Zymurgy::$root."/zymurgy/include/colorsample.html");
	}
	
	$sampleHTML = str_replace("\r\n", "", $sampleHTML);
	$sampleHTML = str_replace("\n", "", $sampleHTML);
	$sampleHTML = str_replace("\t", "", $sampleHTML);
	
	$iw = new InputWidget();
?>

<script language="javascript" type="text/javascript">
	var primaryColor = newColor();
	var sampleHTML = '<?= $sampleHTML ?>';
		
	function newColor()
	{
		return { 
			red: 255,
			green: 255,
			blue: 255,
			hue: 255,
			saturation: 255,
			luminosity: 255	};
	}
		
	function GetHueAndSaturationFromRGB(clr)
	{
		m = GetMFromRGB(clr);
		v = GetVFromRGB(clr);
		value = 100 * v / 255;
		delta = v - m;

		// alert("m: " + m + "\nv: " + v + "\nvalue: " + value + "\ndelta: " + delta);

		if(v == 0.0)
		{
			clr.saturation = 0;
		}
		else
		{
			clr.saturation = 100 * delta / v;
		}
		
		if(clr.saturation == 0)
		{
			clr.hue = 0;
		}
		else
		{
			if(clr.red == v)
			{
				clr.hue = 60.0 * (clr.green - clr.blue) / delta;
			}
			else if(clr.green == v)
			{
				clr.hue = 120.0 + 60.0 * (clr.blue - clr.red) / delta;
			}
			else if(clr.blue == v)
			{
				clr.hue = 240.0 + 60.0 * (clr.red - clr.green) / delta;
			}
			
			if(clr.hue < 0.0)
			{
				clr.hue = clr.hue + 360.0;
			}
		}
		
		clr.luminosity = Math.round(value);
		clr.hue = Math.round(clr.hue);
		clr.saturation = Math.round(clr.saturation);
		
		return clr;
	}
	
	function GetRGBFromHueAndSaturation(clr)
	{		
		if(clr.saturation == 0)
		{
			clr.red = clr.green = clr.blue = Math.round(clr.luminosity * 2.55);
		}
		else
		{
			saturation = clr.saturation / 100;
			luminosity = clr.luminosity / 100;
			hue = clr.hue / 60;
			
			i = Math.floor(hue);
			f = hue - i;
			
			p = luminosity * (1 - saturation);
			q = luminosity * (1 - saturation * f);
			t = luminosity * (1 - saturation * (1 - f));
			
			switch(i)
			{
				case 0:
					clr.red = Math.round(luminosity * 255);
					clr.green = Math.round(t * 255);
					clr.blue = Math.round(p * 255);
					break;
					
				case 1:
					clr.red = Math.round(q * 255);
					clr.green = Math.round(luminosity * 255);
					clr.blue = Math.round(p * 255);
					break;
					
				case 2:
					clr.red = Math.round(p * 255);
					clr.green = Math.round(luminosity * 255);
					clr.blue = Math.round(t * 255);
					break;
					
				case 3:
					clr.red = Math.round(p * 255);
					clr.green = Math.round(q * 255);
					clr.blue = Math.round(luminosity * 255);
					break;
					
				case 4:
					clr.red = Math.round(t * 255);
					clr.green = Math.round(p * 255);
					clr.blue = Math.round(luminosity * 255);
					break;
					
				default:
					clr.red = Math.round(luminosity * 255);
					clr.green = Math.round(p * 255);
					clr.blue = Math.round(q * 255);
					break;
			}
		}
		
		return clr;
	}
	
	function GetMFromRGB(clr)
	{
		m = clr.red;
		
		if(clr.green < m)
		{
			m = clr.green;
		}
		
		if(clr.blue < m)
		{
			m = clr.blue;
		}

		return m;
	}
	
	function GetVFromRGB(clr)
	{
		v = clr.red;
		
		if(clr.green > v)
		{
			v = clr.green;
		}
		
		if(clr.blue > v)
		{
			v = clr.blue;
		}
		
		return v;
	}

	function matchColors(newPrimary)
	{
		// hex2num is included by InputWidget as part of the preText for 
		// the YUI color picker inputspec
		rgb = hex2num(newPrimary);
		
		primaryColor.red = rgb[0];
		primaryColor.green = rgb[1];
		primaryColor.blue = rgb[2];
		
		primaryColor = GetHueAndSaturationFromRGB(primaryColor);
		
		updateColor("color1", primaryColor);
		
		y = newColor();
		yx = newColor();
		p = newColor();
		pr = newColor();
		
		p.saturation = y.saturation = primaryColor.saturation;
		p.hue = y.hue = primaryColor.hue;
		
		y.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		p.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		
		p = GetRGBFromHueAndSaturation(p);
		updateColor("color3", p);
		
		y = GetRGBFromHueAndSaturation(y);
		updateColor("color2", y);
		
		if(primaryColor.hue < 30)
		{
			yx.hue =primaryColor.hue + 20;
			yx.saturation = primaryColor.saturation;			
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		}
		else if(primaryColor.hue < 60)
		{
			yx.hue = primaryColor.hue + 150;
			yx.saturation = rc(primaryColor.saturation - 70, 100);
			yx.luminosity = rc(primaryColor.luminosity + 20, 100);
		}
		else if(primaryColor.hue < 180)
		{
			yx.hue = primaryColor.hue - 40;
			yx.saturation = primaryColor.saturation;
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		}
		else if(primaryColor.hue < 220)
		{
			yx.hue = primaryColor.hue - 170;
			yx.saturation = primaryColor.saturation;
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		}
		else if (primaryColor < 300)
		{
			yx.hue = primaryColor.hue;
			yx.saturation = rc(primaryColor.saturation - 60, 100);
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		}
		else
		{
			yx.hue = (primaryColor.hue + 20) % 360;
			yx.saturation = AdjustLuminosity(primaryColor.saturation, -40, 50);
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		}
		
		yx = GetRGBFromHueAndSaturation(yx);
		updateColor("color6", yx);
		
		y.hue = 0;
		y.saturation = 0;		
		y.luminosity = primaryColor.luminosity;
		
		y = GetRGBFromHueAndSaturation(y);
		updateColor("color4", y);
		
		if(primaryColor.luminosity >= 50)
		{
			y.luminosity = 0;
		}
		else
		{
			y.luminosity = 100;
		}
		
		y = GetRGBFromHueAndSaturation(y);
		updateColor("color5", y);
		
		UpdatePreview();
	}
	
	function AdjustLuminosity(luminosity, adjustment, pivotPoint)
	{
		return luminosity + ((luminosity > pivotPoint) ? adjustment : -adjustment);
	}

	function rc(x,m)
	{
		if(x>m)
		{
			return m
		}
		if(x<0)
		{
			return 0
		}
		else
		{
			return x
		}
	}

	function updateColor(colorName, clr)
	{
		if(!(document.getElementById(colorName + "locked"))
			|| !(document.getElementById(colorName + "locked").checked))
		{
			hexValue = GetHexValueForRGB(clr);		
			
			updateSwatch("swatch" + colorName, hexValue);
			document.getElementById(colorName).value = hexValue;			
		}
	}

	function GetHexValueForRGB(clr)
	{
		return ConvertDecimalToHex(clr.red) + 
			ConvertDecimalToHex(clr.green) + 
			ConvertDecimalToHex(clr.blue);
	}
	
	function ConvertDecimalToHex(d)
	{
		hch="0123456789ABCDEF";
		a=d%16;
		q=(d-a)/16;
		return hch.charAt(q)+hch.charAt(a);
	}
	
	function UpdatePreview()
	{
		preview = document.getElementById("preview").contentDocument;
		
		if(preview == undefined || preview == null)
		{
			preview = document.getElementById("preview").contentWindow.document;
		}
		
		if(!(preview == undefined || preview == null))
		{
			newContent = sampleHTML;
			
			for(cntr = 0; cntr <= 6; cntr++)
			{
				while(newContent.indexOf("color" + cntr + "_background") >= 0
					|| newContent.indexOf("color" + cntr + "_foreground") >= 0)
				{
					newContent = newContent.replace(
						"color" + cntr + "_background", 
						"background-color: #" + document.getElementById("color" + cntr).value + "; ");
					newContent = newContent.replace(
						"color" + cntr + "_foreground", 
						"color: #" + document.getElementById("color" + cntr).value + "; ");
				}
			}			
				
			preview.open();
			preview.write(newContent);
			preview.close();
		}
	}
	
	var primaryColorPicker;
	
	function createPrimaryColorPicker() {
		primaryColorPicker = new YAHOO.widget.ColorPicker(
			"primaryColorContainer",
			{
				showhsvcontrols: false,
				showhexcontrols: true,
				showwebsafe: false,					
				images: 
				{
					PICKER_THUMB: "<?= Zymurgy::YUIBaseURL() ?>colorpicker/assets/picker_thumb.png",
					HUE_THUMB: "<?= Zymurgy::YUIBaseURL() ?>colorpicker/assets/hue_thumb.png"
				}
			}
		);
		
		primaryColorPicker.setValue(
			hex2num(document.getElementById("color0").value),
			true);
					
		primaryColorPicker.on("rgbChange", function(o) {	
			document.getElementById("color0").value = this.get("hex");
			matchColors(this.get("hex"));			
			UpdatePreview();
		});
	}
		
	function SaveTheme()
	{
		var newTheme = "";
		
		for(cntr = 0; cntr <= 6; cntr++)
		{
			colorLabel = "color" + cntr;
			
			colorControlLabel = "swatch" + 
				document.getElementById("themeControl").value + 
				cntr;	
				
			colorControl = window.opener.document.getElementById(colorControlLabel);

			if(document.getElementById(colorLabel + "locked")
				&& document.getElementById(colorLabel + "locked").checked)
			{
				newTheme = newTheme + ",L";	
				
				if(colorControl)
				{
					colorControl.style.border = "1px inset black";
				}
			}
			else
			{
				newTheme = newTheme + ",#";
				
				if(colorControl)
				{
					colorControl.style.border = "1px solid #" + 
						document.getElementById(colorLabel).value;
				}
			}

			newTheme = newTheme + document.getElementById(colorLabel).value;	
			
			if(colorControl)
			{
				colorControl.style.backgroundColor = "#" + 
					document.getElementById(colorLabel).value;				
			}
		}
		
		themeControl = window.opener.document.getElementById(
			document.getElementById("themeControl").value);
		
		themeControl.value = newTheme.substring(1);
			
		window.close();
	}
	
	function LoadTheme()
	{
		var theme = window.opener.document.getElementById(
			document.getElementById("themeControl").value).value;
			
		if(theme !== '')
		{
			var clrs = theme.split(",");
			
			// alert(clrs[1]);
			
			for(cntr = 0; cntr <= 6; cntr++)
			{
				colorLabel = "color" + cntr;
				// alert(colorLabel);
				
				var locked = clrs[cntr].substring(0, 1);
				var hex = clrs[cntr].substring(1);
				
				// alert("Locked:" + locked + "\nHex: " + hex);
							
				if(locked == "L")
				{
					document.getElementById(colorLabel + "locked").checked = true;
				}
				
				document.getElementById(colorLabel).value = hex;			
				updateSwatch('swatch' + colorLabel, hex);
				
				// alert("whee");
			}	
		}
		
		createPrimaryColorPicker();
		
		UpdatePreview();
	}
	
	function LoadTheme_Timer()
	{
		setTimeout("LoadTheme();", 250);
	}
	
	YAHOO.util.Event.onDOMReady(LoadTheme_Timer);	
</script>
	</HEAD>
	<BODY>
		<form method="post" action="<?= $_SERVER['REQUEST_URI'] ?>" enctype="multipart/form-data">
			<input type="hidden" name="themeControl" id="themeControl" value="<?= $_GET['themeControl'] ?>">
			<input type="hidden" name="color0" id="color0" value="B5B5FF">
			<table>
				<tr>
					<td valign="top">
						<table>
							<tr>
								<td colspan="2">
									
									<div id="primaryColorContainer" style="position: relative; width: 350px; height:200px;"></div>
								</td>
							</tr>
							<tr>
								<td align="right">Header Background:</td>
								<td>
									<? 
										$iw->Render("color","color1","B5B5FF");
										echo(" ");
										$iw->Render("checkbox.", "color1locked", ""); ?><label for=\"color1locked\">Locked</label>
								</td>								
							</tr>
							<tr>
								<td align="right">Menu Background:</td>
								<td>
									<? 
										$iw->Render("color","color2","7F7FB3");
										echo(" ");
										$iw->Render("checkbox.", "color2locked", ""); ?><label for=\"color2locked\">Locked</label>
								</td>								
							</tr>
							<tr>
								<td align="right">Menu Highlight:</td>
								<td>
									<? 
										$iw->Render("color","color3","9A9AD9");
										echo(" ");
										$iw->Render("checkbox.", "color3locked", ""); ?><label for=\"color3locked\">Locked</label>
								</td>								
							</tr>
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
							<tr>
								<td align="right">Page Background:</td>
								<td>
									<? 
										$iw->Render("color","color4","FFFFFF");
										echo(" ");
										$iw->Render("checkbox.", "color4locked", ""); ?><label for=\"color4locked\">Locked</label>
								</td>								
							</tr>
							<tr>
								<td align="right">Text Color:</td>
								<td>
									<? 
										$iw->Render("color","color5","000000");
										echo(" ");
										$iw->Render("checkbox.", "color5locked", ""); ?><label for=\"color5locked\">Locked</label>
								</td>								
							</tr>
							<tr>
								<td align="right">Link Color:</td>
								<td>
									<? 
										$iw->Render("color","color6","6037B3");
										echo(" ");
										$iw->Render("checkbox.", "color6locked", ""); ?><label for=\"color6locked\">Locked</label>
								</td>								
							</tr>
						</table>
					</td>
					<td valign="top">
						<iframe id="preview" height="380" width="440">
						</iframe>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="button" value="Save" onClick="SaveTheme();">
						<input type="button" value="Cancel" onClick="window.close();">
					</td>
				</tr>
			</table>
		</form>
	</BODY>
</HTML>
