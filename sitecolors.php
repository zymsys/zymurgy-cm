<?php
	ini_set("display_errors", 1);
	
	$breadcrumbTrail = "Site Colors";	

	include('header.php');
	include('datagrid.php');
	require_once('InputWidget.php');
	
	echo InputWidget::GetPretext("color.");
	
	$sampleHTML = @file_get_contents(Zymurgy::$root."/zymurgy/config/colorsample.html");
	
	if($sampleHTML !== "")
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
		
		z = newColor();
		y = newColor();
		yx = newColor();
		p = newColor();
		pr = newColor();
		
		p.saturation = y.saturation = primaryColor.saturation;
		p.hue = y.hue = primaryColor.hue;
		
		y.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
		p.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		
		p = GetRGBFromHueAndSaturation(p);
		updateColor("color1", p);
		
		y = GetRGBFromHueAndSaturation(y);
		updateColor("color2", y);
		
		if(primaryColor.hue < 30)
		{
			pr.hue = yx.hue = y.hue = primaryColor.hue + 20;
			pr.saturation = yx.saturation = y.saturation = primaryColor.saturation;
			y.luminosity = primaryColor.luminosity;
			
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
			pr.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		}
		else if(primaryColor.hue < 60)
		{
			pr.hue = yx.hue = y.hue = primaryColor.hue + 150;
			y.saturation = rc(primaryColor.saturation - 30, 100);
			y.luminosity = rc(primaryColor.luminosity - 20, 100);
			pr.saturation = yx.saturation = rc(primaryColor.saturation - 70, 100);
			yx.luminosity = rc(primaryColor.luminosity + 20, 100);
			pr.luminosity = primaryColor.luminosity;			
		}
		else if(primaryColor.hue < 180)
		{
			pr.hue = yx.hue = y.hue = primaryColor.hue - 40;
			pr.saturation = yx.saturation = y.saturation = primaryColor.saturation;
			y.luminosity = primaryColor.luminosity;
			
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
			pr.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		}
		else if(primaryColor.hue < 220)
		{
			pr.hue = yx.hue = primaryColor.hue - 170;
			y.hue = primaryColor.hue - 160;
			pr.saturation = yx.saturation = y.saturation =  primaryColor.saturation;
			y.luminosity = primaryColor.luminosity;
			
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
			pr.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		}
		else if (primaryColor < 300)
		{
			pr.hue = yx.hue = y.hue = primaryColor.hue;
			pr.saturation = yx.saturation = y.saturation = rc(primaryColor.saturation - 60, 100);
			y.luminosity = primaryColor.luminosity;
			
			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
			pr.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		}
		else
		{
			pr.hue = yx.hue = y.hue = (primaryColor.hue + 20) % 360;
			
			if(primaryColor.saturation > 50)
			{
				pr.saturation = yx.saturation = y.saturation = primaryColor.saturation - 40;
			}
			else
			{
				pr.saturation = yx.saturation = y.saturation = primaryColor.saturation + 40;
			}
			
			y.luminosity = primaryColor.luminosity;

			yx.luminosity = AdjustLuminosity(primaryColor.luminosity, -30, 70);
			pr.luminosity = AdjustLuminosity(primaryColor.luminosity, -15, 70);
		}
		
		z = GetRGBFromHueAndSaturation(y);
		updateColor("color3", z);
		
		z = GetRGBFromHueAndSaturation(yx);
		updateColor("color5", z);
		
		y.hue = 0;
		y.saturation = 0;
		y.luminosity = 100 - primaryColor.luminosity;
		
		z = GetRGBFromHueAndSaturation(y);
		updateColor("color6", z);
		
		y.luminosity = primaryColor.luminosity;
		
		z = GetRGBFromHueAndSaturation(y);
		updateColor("color7", z);
		
		z = GetRGBFromHueAndSaturation(pr);
		updateColor("color4", z);
		
		if(primaryColor.luminosity >= 50)
		{
			y.luminosity = 0;
		}
		else
		{
			y.luminosity = 100;
		}
		
		z = GetRGBFromHueAndSaturation(y);
		updateColor("color8", z);
		
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
		hexValue = GetHexValueForRGB(clr);		
		
		updateSwatch("swatch" + colorName, hexValue);
		document.getElementById(colorName).value = hexValue;
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
			
			for(cntr = 1; cntr <= 8; cntr++)
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
	
	echo "<form method=\"post\" action=\"{$_SERVER['REQUEST_URI']}\" enctype=\"multipart/form-data\"><table>\r\n";
	
	echo "<tr><td valign=\"top\"><table>";
	
	echo "<tr><td align=\"right\">Primary Color:</td><td>";
	$iw = new InputWidget();
	$iw->Render("colormatchprimary","color0","FFFFFF");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Primary Header Background:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color1","D9D9D9");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Primary Header/Link Foreground:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color2","B3B3B3");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Secondary Header Background:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color3","FFFFFF");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Visited Link Foreground:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color4","D9D9D9");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Secondary Header Foreground:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color5","B3B3B3");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">(unused):</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color6","000000");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Page Background:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color7","FFFFFF");
	echo "</td></tr>\r\n";
	
	echo "<tr><td align=\"right\">Text Color:</td><td>";
	$iw = new InputWidget();
	$iw->Render("color","color8","000000");
	echo "</td></tr>\r\n";
	
	echo "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Save\"></td></tr>\r\n";
	
	echo "</table></td><td valign=\"top\">";
	
	echo "<iframe id=\"preview\" height=\"400\" width=\"400\"/>\r\n";
	
	echo "</td></tr></table></form>\n\n";	
?>
