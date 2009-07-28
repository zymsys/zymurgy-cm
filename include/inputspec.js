function InputParameter()
{
	this.description = "";

	this.type = "text";
	this.size = "5";
	this.maxlength = "50";

	this.options = new Array();
	this.optionLoader = "";

	this.value = "";

	this.onchange = "";

	this.renderedit = function(index)
	{
		var output;

		if(this.type == "select")
		{
			if(this.optionLoader != "")
			{
				this.options = eval(this.optionLoader);
			}

			output = "<select id=\"param_" + index + "\"";

			if(this.onchange != "")
			{
				output = output + " onchange=\"" + this.onchange + "\"";
			}

			output = output + ">";

			for(var cntr = 0; cntr < this.options.length; cntr++)
			{
				var option = this.options[cntr];

				output = output + "<option value=\"" + option + "\"";

				if(option == this.value)
				{
					output = output + " selected";
				}

				output = output + ">" + option + "</option>";
			}

			output = output + "</select>";

			output = output + "<input type=\"hidden\" id=\"param_" + index + "_default\"";
			output = output + " value=\"" + this.value + "\">";
		}
		else
		{
			output = "<input";

			output = output + " id=\"param_" + index + "\"";

			output = output + " type=\"" + this.type + "\"";

			if(this.type == "text")
			{
				output = output + " size=\"" + this.size + "\"";
				output = output + " maxlength=\"" + this.maxlength + "\"";
				output = output + " value=\"" + this.value + "\"";
			}

			if(this.type == "checkbox")
			{
				if(this.value == "checked")
				{
						output = output + " checked";
				}
			}

			if(this.onchange != "")
			{
				output = output + " onchange=\"" + this.onchange + "\"";
			}

			output = output + ">";
		}

		if(this.onchange != "")
		{
			setTimeout(this.onchange, 100);
		}

		return output;
	}

	this.Clone = function()
	{
		var newParameter = new InputParameter;

		newParameter.description = this.description;
		newParameter.type = this.type;
		newParameter.size = this.size;
		newParameter.maxlength = this.maxlength;
		newParameter.value = this.value;
		newParameter.options = this.options;
		newParameter.optionLoader = this.optionLoader;
		newParameter.onchange = this.onchange;

		return newParameter;
	}
}

function InputSpecifier()
{
	this.description = "";
	this.type = "";

	this.inputparameters = new Array();

	this.tableoutput = function()
	{
		var output = this.type;

		for(var cntr = 0; cntr < this.inputparameters.length; cntr++)
		{
			var param = this.inputparameters[cntr];

			output = output + "." + param.value;
		}

		return output;
	}

	this.renderedit = function(specifierElement, saveCall, cancelCall)
	{
		var output = "<table>";

		output = output + "<tr>";
		output = output + "<td>Parameter Type</td>";

		output = output + "<td><select id=\"specifierType\" onchange=\"changeSpecifier('" + specifierElement + "')\">";
		var specifiers = GetSupportedSpecifiers();
		for(var cntr = 0; cntr < specifiers.length; cntr++)
		{
			output = output + "<option value=\"" + specifiers[cntr].type +
				"\"" + (specifiers[cntr].type == this.type ? " selected" : "") +
				">" + specifiers[cntr].description + "</option>";
		}
		output = output + "</select></td>";

		output = output + "</tr>";

		output = output + "<tr>";
		output = output + "<td colspan=\"2\"><hr></td>";
		output = output + "</tr>";

		for(var cntr = 0; cntr < this.inputparameters.length; cntr++)
		{
			if (this.inputparameters[cntr].type=='hidden') {
				this.inputparameters[cntr].renderedit(cntr);
			}
			else {
				output = output + "<tr>";
				output = output + "<td>" + this.inputparameters[cntr].description + "</td>";
				output = output + "<td>" + this.inputparameters[cntr].renderedit(cntr) + "</td>";
				output = output + "</tr>";
			}
		}

		output = output + "<tr>";
		output = output + "<td colspan=\"2\">";

		output = output + "<input type=\"button\" id=\"btnSaveSpecifier\" onclick=\"" + saveCall + "\" value=\"Ok\"> ";
		output = output + "<input type=\"button\" id=\"btnCancelSpecifier\" onclick=\"" + cancelCall + "\" value=\"Cancel\">";

		output = output + "</td>";
		output = output + "</tr>";
		output = output + "</table>";

		return output;
	}

	this.getvaluesfromedit = function()
	{
		for(var cntr = 0; cntr < this.inputparameters.length; cntr++)
		{
			var control = document.getElementById("param_" + cntr);
			var parameter = this.inputparameters[cntr];

			// alert("Parsing parameter " + cntr + " as " + parameter);

			if(parameter.type == "text")
			{
				parameter.value = control.value;
			}

			if(parameter.type == "checkbox")
			{
				// alert(control.checked);
				parameter.value = control.checked ? "checked" : "";
			}

			if(parameter.type == "select")
			{
				parameter.value = control.options[control.selectedIndex].value;
			}
		}
	}

	this.Clone = function()
	{
		var newSpecifier = new InputSpecifier;

		newSpecifier.description = this.description;
		newSpecifier.type = this.type;

		for(var cntr = 0; cntr < this.inputparameters.length; cntr++)
		{
			var newParameter = this.inputparameters[cntr].Clone();

			newSpecifier.inputparameters.push(newParameter);
		}

		return newSpecifier;
	}
}

function GetVerbiageSpecifier()
{
	var specifier = new InputSpecifier;
	specifier.description = "Verbiage";
	specifier.type = "verbiage";

	var valueParameter = new InputParameter;
	valueParameter.description = "Text to display";
	valueParameter.type = "text";
	valueParameter.size = "30";
	valueParameter.maxlength = "200";
	valueParameter.value = "";
	specifier.inputparameters.push(valueParameter);

	return specifier;
}

function GetSpecifierFromText(specifierText)
{
	specifierText = specifierText + ".";

	var specifierID = specifierText.substr(
		0,
		specifierText.indexOf('.'));
	specifierText = specifierText.substr(
		specifierText.indexOf('.') + 1,
		specifierText.length);

	var specifiers = GetSupportedSpecifiers();
	var specifier = specifiers[0].Clone();

	// alert(specifierText);

	for(var i = 0; i < specifiers.length; i++)
	{
		if(specifierID == specifiers[i].type)
		{
			specifier = specifiers[i].Clone();

			break;
		}
	}

	for(var i = 0; i < specifier.inputparameters.length; i++)
	{
		specifier.inputparameters[i].value = specifierText.substr(
			0,
			specifierText.indexOf('.'));

		specifierText = specifierText.substr(
			specifierText.indexOf('.') + 1,
			specifierText.length);
	}

	// alert(specifier.tableoutput());

	return specifier;
}

function initSpecifierDiv()
{
	//Create the specifiers div
	var specs = document.createElement("div");
	specs.id = 'specifiers';
	document.body.appendChild(specs);

	//Set CSS for specifiers div
	YAHOO.util.Dom.setStyle(['specifiers'],'border-width',1);
	YAHOO.util.Dom.setStyle(['specifiers'],'border-style','solid');
	YAHOO.util.Dom.setStyle(['specifiers'],'border-color','#0066FF');
	YAHOO.util.Dom.setStyle(['specifiers'],'background-color','#FFFFCC');
	YAHOO.util.Dom.setStyle(['specifiers'],'position','absolute');
	YAHOO.util.Dom.setStyle(['specifiers'],'display','none');
	YAHOO.util.Dom.setStyle(['specifiers'],'visibility','hidden');

	//Initialize the different specifiers
	var output = "";
	var specifiers = GetSupportedSpecifiers();
	for(var i = 0; i < specifiers.length; i++)
	{
		// alert(i + ": " + specifiers[i].tableoutput());

		output = output + specifiers[i].tableoutput() + "<br>";
	}

	document.getElementById("specifiers").innerHTML = output;
}
YAHOO.util.Event.onDOMReady(initSpecifierDiv);

var m_specifier;

function editSpecifier(specifierElement)
{
	var specifierText = document.getElementById(specifierElement).value + ".";

	m_specifier = GetSpecifierFromText(
		document.getElementById(specifierElement).value);

	document.getElementById("specifiers").innerHTML = m_specifier.renderedit(
		specifierElement,
		"saveSpecifier('" + specifierElement + "');",
		"cancelEditSpecifier();");

	document.getElementById("specifiers").style.visibility = "visible";
	document.getElementById("specifiers").style.display = "block";

	var textPos = YAHOO.util.Dom.getXY(specifierElement);
	textPos[0] += 150;
	textPos[1] += 24;
	YAHOO.util.Dom.setXY('specifiers', textPos);

	// If the specifierText is empty, then force the dialog
	// to use the "input" specifier.
	if(specifierText == ".")
	{
		changeSpecifierByText(specifierElement, "input");
	}
}

function saveSpecifier(specifierElement)
{
	m_specifier.getvaluesfromedit();

	document.getElementById(specifierElement).value =
		m_specifier.tableoutput();

	document.getElementById("specifiers").innerHTML = "";

	document.getElementById("specifiers").style.visibility = "hidden";
	document.getElementById("specifiers").style.display = "none";
}

function cancelEditSpecifier()
{
	document.getElementById("specifiers").innerHTML = "";

	document.getElementById("specifiers").style.visibility = "hidden";
	document.getElementById("specifiers").style.display = "none";
}

function changeSpecifier(specifierElement)
{
	var specifierIndex = document.getElementById("specifierType").selectedIndex;
	var specifierText = document.getElementById("specifierType")[specifierIndex].value;
	// alert(specifierText);

	changeSpecifierByText(specifierElement, specifierText);
}

function changeSpecifierByText(specifierElement, specifierText)
{
	var specifiers = GetSupportedSpecifiers();

	for(var i = 0; i < specifiers.length; i++)
	{
		if(specifiers[i].type == specifierText)
		{
			m_specifier = specifiers[i].Clone();

			document.getElementById("specifiers").innerHTML = m_specifier.renderedit(
				specifierElement,
				"saveSpecifier('" + specifierElement + "');",
				"cancelEditSpecifier();");

			break;
		}
	}
}

function makeInputSpecifier(name, value) {
	document.write('<input type="text" id="'+name+'" name="'+name+'" value="'+value+'">'+
		'<input type="button" id="'+name+'_edit" onClick="editSpecifier('+"'"+name+"'"+')" value="&raquo;">');
}

function DefineCheckboxParameter(label, value)
{
	var parameter = new InputParameter;

	parameter.description = label;
	parameter.type = "checkbox";
	parameter.value = value;

	return parameter;
}

function DefineHiddenParameter(label, value)
{
	var parameter = new InputParameter;

	parameter.description = label;
	parameter.type = "hidden";
	parameter.value = value;

	return parameter;
}

function DefineTextParameter(label, size, maxlength, value)
{
	var parameter = new InputParameter;

	parameter.description = label;
	parameter.type = "text";
	parameter.size = size;
	parameter.maxlength = maxlength;
	parameter.value = value;

	return parameter;
}

function DefineSelectParameter(label, size, maxlength, optionLoader, optionChange, value)
{
	var parameter = new InputParameter;

	parameter.description = label;
	parameter.type = "select";
	parameter.size = size;
	parameter.maxlength = maxlength;
	parameter.optionLoader = optionLoader;
	parameter.onchange = optionChange;
	parameter.value = "";

	return parameter;
}
