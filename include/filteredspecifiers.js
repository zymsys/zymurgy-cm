function GetTextSpecifiers()
{
	var list = new Array();

    specifier = GetSpecifier_ZIW_TextArea("textarea");
    if(specifier) list.push(specifier);
    specifier = GetSpecifier_ZIW_YUIHtml("yuihtml");
    if(specifier) list.push(specifier);

	return list;
}