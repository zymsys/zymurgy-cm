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
	foreach(InputWidget::$widgets as $widgetName => $widget)
	{
		if(method_exists($widget, "GetInputSpecifier"))
		{
			echo "    list.push(GetSpecifier_".get_class($widget)."(\"$widgetName\"));\n";
		}
	}
?>

	list.push(GetTextAreaSpecifier());
	list.push(GetHtmlSpecifier());
	list.push(GetCheckboxSpecifier());
	list.push(GetRadioSpecifier());
	list.push(GetDropSpecifier());
	list.push(GetAttachmentSpecifier());
	list.push(GetImageSpecifier());
	list.push(GetMoneySpecifier());
	list.push(GetUnixDateSpecifier());
	list.push(GetLookupSpecifier());
	list.push(GetColourSpecifier());
	list.push(GetThemeSpecifier());
	list.push(GetVerbiageSpecifier());
	list.push(GetHipAsirraSpecifier());

	return list;
}
<?php
	$pushedSpecs = array();

	foreach(InputWidget::$widgets as $widget)
	{
		if(method_exists($widget, "GetInputSpecifier") && !in_array(get_class($widget), $pushedSpecs))
		{
			echo call_user_func(array($widget, "GetInputSpecifier"));
			$pushedSpecs[] = get_class($widget);
		}
	}

	echo("</script>\n");
?>