function ZymurgyTagCloudWidget(widgetId, elTarget, tagsUrl, startValue) {
	elTarget = YAHOO.util.Dom.get(elTarget);
	var elInput;
	var elAdd;
	var elSelected;
	var selected = startValue.split(","); // new Array();

	this.buildUI = function() {
		elTarget.setAttribute("class","yui-skin-sam");
		//Header for selected tags
		var elHd = document.createElement("div");
		elHd.appendChild(document.createTextNode("Selected: "));
		elTarget.appendChild(elHd);
		//Selected tags span
		elSelected = document.createElement("span");

		if(selected.length <= 0)
		{
			elSelected.innerHTML = "<i>None</i>";
		}
		else
		{
			for(cntr = 0; cntr < selected.length; cntr++)
			{
				this.renderTag(selected[cntr]);
			}
		}

		elHd.appendChild(elSelected);
		//Auto-complete input
		elInput = document.createElement("input");
		elInput.setAttribute("type","text");
		elInput.setAttribute("id","ZymurgyTCWI_"+widgetId);
		YAHOO.util.Dom.setStyle(elInput, 'width', '25em');
		elTarget.appendChild(elInput);
		//Add button
		elAdd = document.createElement("input");
		elAdd.setAttribute("type","button");
		elAdd.setAttribute("value","Add");
		elAdd.setAttribute("position","absolute");
		elAdd.setAttribute("disabled",true);
		elAdd.setAttribute("id","ZymurgyTCWB_"+widgetId);
		elTarget.appendChild(elAdd);
		//Auto-complete drop down box
		var elAC = document.createElement("div");
		elAC.setAttribute("id","ZymurgyTCWACC_"+widgetId);
		YAHOO.util.Dom.setStyle(elAC, 'width', '25em');
		elTarget.appendChild(elAC);
	};

	this.removeTag = function(e) {
		var elClose = e.currentTarget;
		var elTxtName = elClose.previousElementSibling;
		var tag = elTxtName.innerHTML;
//		alert(tag);
		elClose.parentNode.parentNode.removeChild(elClose.parentNode);

		newSelected = new Array();

		for(var cntr = 0; cntr < selected.length; cntr++)
		{
			if(selected[cntr] !== tag)
			{
				newSelected.push(selected[cntr]);
			}
		}

		selected = newSelected;

		if(selected.length <= 0)
		{
			elSelected.innerHTML = "<i>None</i>";
		}

		this.updateHiddenField();
	};

	this.appendTag = function(tag) {
		if (selected.length <= 0) {
			elSelected.innerHTML = ''; //Clear "none" placeholder
		}

		this.renderTag(tag);

		selected.push(tag);
		this.updateHiddenField();
	};

	this.renderTag = function(tag)
	{
		var elTag = document.createElement("span");

		elTag.style.paddingRight = "5px";

		var elTxtName = document.createElement("span");
		var ndTxt = document.createTextNode(tag);
		elTxtName.appendChild(ndTxt);
		elTag.appendChild(elTxtName);
		var elClose = document.createElement("span");
		elClose.appendChild(document.createTextNode('[x]'));
		YAHOO.util.Dom.setStyle(elClose,'cursor','pointer');
		YAHOO.util.Event.addListener(elClose, "click", this.removeTag, null, this);
		elTag.appendChild(elClose);
		elSelected.appendChild(elTag);
	}

	this.updateHiddenField = function()
	{
		document.getElementById(widgetId).value = selected.join(",");
	}

	this.tweakUI = function() {
		var xpos = YAHOO.util.Dom.getX(elInput);
		var wtf = xpos+elInput.offsetWidth;
		YAHOO.util.Dom.setX(elAdd, xpos+elInput.offsetWidth);
	};

	var dsTags = new YAHOO.util.XHRDataSource(tagsUrl);
	dsTags.responseType = YAHOO.util.XHRDataSource.TYPE_XML;
	dsTags.responseSchema = {
		resultNode: "tag",
		fields: [ {key: "name"}, {key: "id", parser: "number"} ]
	};
	this.buildUI();
	var oAC = new YAHOO.widget.AutoComplete("ZymurgyTCWI_"+widgetId, "ZymurgyTCWACC_"+widgetId, dsTags);
	this.tweakUI();
	oAC.generateRequest = function(sQuery) {
    	return "?what=tags&q=" + sQuery;
	};
	YAHOO.util.Event.addListener(elInput, "keyup", function () {
		var disabled = (elInput.value == '');
		elAdd.disabled = disabled;
	}, null, this);
	YAHOO.util.Event.addListener(elAdd, "click", function() {
		var dsAddTag = new YAHOO.util.XHRDataSource(tagsUrl+'?what=add&tag='+elInput.value);
		dsAddTag.sendRequest(); //Make a success handler to consume the ID returned by data source.
		this.appendTag(elInput.value);
		elInput.value = "";
	}, null, this);
}