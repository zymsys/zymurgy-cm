<?php
	ini_set("display_errors", 1);
	
	$breadcrumbTrail = "Site Colors";	

	require_once('header.php');
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
	
	YAHOO.util.Event.on(window, "load", UpdatePreview);
</script>

<?php
	$iw = new InputWidget();
	
	echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\" enctype=\"multipart/form-data\"><table>\r\n";
	
	echo "<tr><td valign=\"top\"><table>";
	 
	echo "<tr><td colspan=\"2\">";
	echo "<input type=\"hidden\" name=\"color0\" onChange=\"if(typeof UpdatePreview == 'function') { UpdatePreview(); }; matchColors(this.value);\" value=\"B5B5FF\">";
	echo "<div id=\"primaryColorContainer\" style=\"position: relative; width: 350px; height:200px;\"></div>";
	echo "<script type=\"text/javascript\">";
?>
	(function() {
		var primaryColorPicker;
		
		function createPrimaryColorPicker() {
			primaryColorPicker = new YAHOO.widget.ColorPicker(
				"primaryColorContainer",
				{
					showhsvcontrols: true,
					showhexcontrols: false,
					showwebsafe: false,					
					images: 
					{
						PICKER_THUMB: "<?= Zymurgy::YUIBaseURL() ?>colorpicker/assets/picker_thumb.png",
						HUE_THUMB: "<?= Zymurgy::YUIBaseURL() ?>colorpicker/assets/hue_thumb.png"
					}
				}
			);
			
			primaryColorPicker.setValue(hex2num("B5B5FF"));
			
			primaryColorPicker.on("rgbChange", function(o) {				
				// alert(colourPickerDlg.cpEditor.name);
							
				if(typeof matchColors == "function")
				{						
					// alert(this.get("hex"));
					matchColors(this.get("hex"));
					UpdatePreview();
				}
			});
		}
		
		YAHOO.util.Event.onDOMReady(createPrimaryColorPicker);		
	})();
<?php 
	echo "</script>";
	echo "</td></tr>\r\n";

	
	// echo "<tr><td align=\"right\">Primary Color:</td><td>";
	// $iw->Render("colormatchprimary","color0","B5B5FF");
	// echo "</td></tr>\r\n";
	
	// echo("<tr><td>&nbsp;</td></tr>");
	
	echo "<tr><td align=\"right\">Header Background:</td><td>";
	$iw->Render("color","color1","B5B5FF");
	echo(" ");
	$iw->Render("checkbox.", "color1locked", "");
	echo "<label for=\"color1locked\">Locked</label></td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Menu Background:</td><td>";
	$iw->Render("color","color2","7F7FB3");
	echo(" ");
	$iw->Render("checkbox.", "color2locked", "");
	echo "<label for=\"color2locked\">Locked</label></td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Menu Highlight:</td><td>";
	$iw->Render("color","color3","9A9AD9");
	echo(" ");
	$iw->Render("checkbox.", "color3locked", "");
	echo "<label for=\"color3locked\">Locked</label></td></tr>\r\n";
	
	echo("<tr><td>&nbsp;</td></tr>");
	
	echo "<tr><td align=\"right\">Page Background:</td><td>";
	$iw->Render("color","color4","FFFFFF");
	echo(" ");
	$iw->Render("checkbox.", "color4locked", "");
	echo "<label for=\"color4locked\">Locked</label></td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Text Color:</td><td>";
	$iw->Render("color","color5","000000");
	echo(" ");
	$iw->Render("checkbox.", "color5locked", "");
	echo "<label for=\"color5locked\">Locked</label></td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Link Color:</td><td>";
	$iw->Render("color","color6","6037B3");
	echo(" ");
	$iw->Render("checkbox.", "color6locked", "");
	echo "<label for=\"color6locked\">Locked</label></td></tr>\r\n";
	
	echo("<tr><td>&nbsp;</td></tr>");	
	
	echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\"></td></tr>\r\n";
	
	echo "</table></td><td valign=\"top\">";
	
	echo "<iframe id=\"preview\" height=\"380\" width=\"440\"/>\r\n";
	
	echo "</td></tr></table></form>\n\n";	
?>
