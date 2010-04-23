function GetTextSpecifiers()
{
	var list = new Array();

    specifier = GetSpecifier_ZIW_TextArea("textarea");
    if(specifier) list.push(specifier);
    specifier = GetSpecifier_ZIW_YUIHtml("yuihtml");
    if(specifier) list.push(specifier);

	return list;
}

function GetVarcharSpecifiers()
{
	var list = new Array();

    specifier = GetSpecifier_ZIW_Input("input");
    if(specifier) list.push(specifier);
    specifier = GetSpecifier_ZIW_Attachment("attachment");
    if(specifier) list.push(specifier);
    specifier = GetSpecifier_ZIW_Image("image");
    if(specifier) list.push(specifier);
	
	return list;
}