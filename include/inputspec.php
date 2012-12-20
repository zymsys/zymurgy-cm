<?php
	/**
	 * Inputspec dialog box.
	 *
	 * @package Zymurgy
	 * @subpackage backend-modules
	 */

	echo Zymurgy::YUI("yahoo-dom-event/yahoo-dom-event.js");
	echo Zymurgy::RequireOnce(Zymurgy::getUrlPath("~include/inputspec.js"));

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

	// Special inputspec used in the form plugin
	if(window.location.href.indexOf("pluginsuperadmin") > 0)
	{
		list.push(GetVerbiageSpecifier());
	}

	return list;
}
<?php
	echo(implode("\n\n", $pushedSpecs));

	echo("</script>\n");
?>