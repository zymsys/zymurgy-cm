<?php
	echo Zymurgy::YUI("yahoo/yahoo-min.js")."\n".
		Zymurgy::YUI("dom/dom-min.js")."\n".
		Zymurgy::YUI("event/event-min.js")."\n".
		"<script src=\"/zymurgy/include/inputspec.js\"></script>\r\n";

	echo("<script type=\"text/javascript\">\n");
?>
function GetSupportedSpecifiers()
{
	var list = new Array();

<?php
	$pushedSpecs = array();

	foreach(InputWidget::$widgets as $widgetName => $widget)
	{
		if(method_exists($widget, "GetInputSpecifier"))
		{
			$specifier = call_user_func(array($widget, "GetInputSpecifier"));
			if(strlen($specifier) > 0)
			{
				$pushedSpecs[get_class($widget)] = $specifier;
				echo("    specifier = GetSpecifier_".get_class($widget)."(\"$widgetName\");\n");
				echo("    if(specifier) list.push(specifier);\n");
			}
		}
	}
?>

	list.push(GetThemeSpecifier());
	list.push(GetVerbiageSpecifier());
	list.push(GetHipAsirraSpecifier());

	return list;
}
<?php
	echo(implode("\n\n", $pushedSpecs));

	echo("</script>\n");
?>