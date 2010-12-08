function ZymurgyTagCloud(elTarget, tagsUrl, cloudID) {
	this.elTarget = YAHOO.util.Dom.get(elTarget);
	this.elHd = undefined;
	this.elBd = undefined;
	this.elSelected = undefined;
	this.elNotice = undefined;
	this.tags = new Array(); //Tags in the cloud
	this.selected = new Array(); //Tags on the selected list
	this.maxmag = 5;
	this.DataTableRefresh = undefined; //Function to refresh the table view
	this.selectTagsCallback = undefined; //Function to select tags after a load
	this.CloudDataSource = tagsUrl + "&"; // + "?";
	this.dsTags = new YAHOO.util.XHRDataSource(this.CloudDataSource);
	this.dsTags.responseType = YAHOO.util.XHRDataSource.TYPE_XML;
	this.dsTags.responseSchema = {
		resultNode: "tag",
		fields: [ {key: "name"}, {key: "hits", parser: "number"} ]
	};

	this.buildUI = function() {
		this.elHd = document.createElement("div");
		this.elHd.setAttribute("class","ZymurgyTagCloudHd");
		this.elHd.appendChild(document.createTextNode("Selected: "));
		this.elSelected = document.createElement("span");
		this.elSelected.innerHTML = "<i>None</i>";
		this.elHd.appendChild(this.elSelected);
		this.elTarget.appendChild(this.elHd);
		this.elBd = document.createElement("div");
		this.elBd.setAttribute("class","ZymurgyTagCloudBd");
		this.elTarget.appendChild(this.elBd);
	};

	this.createTags = function (tagData) {
		for (var i in tagData.results) {
			var tag = tagData.results[i];
			this.tags.push(tag);
		}
		var count = this.tags.length;
		this.tags.sort(function (a,b) {
			return (b.hits - a.hits);
		});
		var ms = 1/this.maxmag; //Size of a magnitude step
		var cs = 1/count; //Size of a tag interation step
		var l = 0; //Current level
		var mt = ms; //Magnitude threshold before next step up
		var m = 1; //Current magnitude (maxmag is smallest, 1 is biggest - think header tags)
		for (var i in this.tags) {
			this.tags[i].magnitude = m;
			l += cs;
			if (l > mt) {
				m++;
				mt += ms;
			}
		}
		//Finally randomize the sort order
		this.tags.sort(function () {
			return Math.random() - 0.5;
		});
	};

	this.createTagElement = function (oTag) {
//		var elTag = document.createElement("div");
		var elTxtSpan = document.createElement("span");
		var ndTxt = document.createTextNode(oTag.name);
		elTxtSpan.setAttribute("class","ZymurgyTagCloudTagText");
		elTxtSpan.appendChild(ndTxt);
//		elTag.appendChild(elTxtSpan);
		return elTxtSpan; // elTag;
	};

	this.onCloseSelectedClick = function (e, oTagCloud) {
		var elTag = e.currentTarget.parentNode;
		var oTag = oTagCloud.selected[elTag.zymurgy_idx];
		oTagCloud.selected.splice(elTag.zymurgy_idx,1);
		//Re-do zymurgy_idx's in selected el's after splice
		for (var i in oTagCloud.selected) {
			oTagCloud.selected[i].el.zymurgy_idx = i;
		}
		oTagCloud.elSelected.removeChild(elTag);

		if(document.getElementById("tcr" + cloudID))
		{
			// For some reason I can't call getQueryString() from here,
			// so I'll just copy that code block instead.
			var query = new Array();
			for (var i in selected) {
				query.push('s'+i+'='+escape(selected[i].name));
			}
			// return query.join('&');

			eval("datafor" +
				cloudID +
				".startRequest(\"" +
				query.join("&") +
				"\");");
		}

		oTagCloud.loadTags();
	};

	this.getQueryString = function () {
		var query = new Array();
		for (var i in this.selected) {
			query.push('s'+i+'='+escape(this.selected[i].name));
		}
		return query.join('&');
	}
	
	this.selectTag = function (elTag, oTagCloud) {
		var oTag = this.tags[elTag.zymurgy_idx];
		this.tags.splice(elTag.zymurgy_idx,1); //Remove tag from cloud list
		this.elBd.removeChild(elTag); //Remove element from cloud body
		if (this.selected.length == 0) {
			this.elSelected.innerHTML = ''; //Clear "none" placeholder
		}
		elTag = oTagCloud.createTagElement(oTag);
		elTag.setAttribute("class","ZymurgyTagCloudSelectedTag");
		var elClose = document.createElement("span");
		elClose.setAttribute("class","ZymurgyTagCloudSelectedCloseTag");
		elClose.appendChild(document.createTextNode('[x]'));
		elTag.appendChild(elClose);
		this.elSelected.appendChild(elTag);
		oTag.el = elTag;
		elTag.zymurgy_idx = this.selected.push(oTag)-1;
		YAHOO.util.Event.addListener(elClose,'click', oTagCloud.onCloseSelectedClick, oTagCloud);

		if(document.getElementById("tcr" + cloudID))
		{
			// For some reason I can't call getQueryString() from here,
			// so I'll just copy that code block instead.
			var query = new Array();
			for (var i in this.selected) {
				query.push('s'+i+'='+escape(this.selected[i].name));
			}
			// return query.join('&');

			eval("datafor" +
				cloudID +
				".startRequest(\"" +
				query.join("&") +
				"\");");
		}

		oTagCloud.loadTags();
	};

	this.onTagClick = function (e, oTagCloud) {
		var elTag = e.currentTarget;
		oTagCloud.selectTag(elTag,oTagCloud);
	};

	this.renderTags = function () {
		if(this.tags.length <= 0)
		{
//			alert("No related tags");
			this.elNotice = document.createTextNode("No related tags");
			this.elBd.appendChild(this.elNotice);
		}
		else
		{
			for (var i in this.tags) {
				var oTag = this.tags[i];
				var elTag = this.createTagElement(oTag);
				var ndWhiteSpace = document.createTextNode(" ");
				elTag.zymurgy_idx = i;
				elTag.setAttribute("id", cloudID + "_" + oTag.name);
				elTag.setAttribute("class","ZymurgyTagCloudTag ZymurgyTagCloudTag"+oTag.magnitude);
				this.elBd.appendChild(elTag);
				this.elBd.appendChild(ndWhiteSpace);
				YAHOO.util.Event.addListener(elTag,'click', this.onTagClick, this);
				this.tags[i].el = elTag;
			}
		}
	};

	this.loadTags = function() {
		if(this.elNotice)
		{
			this.elBd.removeChild(this.elNotice);
			this.elNotice = undefined;
		}

		for (var i in this.tags) {
			this.elBd.removeChild(this.tags[i].el);
		}
		this.tags = new Array();
		var qs = this.getQueryString();
		var urlp = location.href.split('/');
		var flavour = (urlp.length >3) ? urlp[3] : 'pages'; //Default flavour if none in URL
		this.dsTags.sendRequest(qs+'&what=tags&flavour='+flavour,{
			scope: this,
			success: function (oResponse, oParsedResponse) {
				this.createTags(oParsedResponse);
				this.renderTags();
				if (this.selectTagsCallback) {
					this.selectTagsCallback(this);
				}
			},
			failure: function () {
				alert('Failed to load tag cloud data.');
			}

		});
		if (this.DataTableRefresh) {
			this.DataTableRefresh(qs);
		}
	};

	this.buildUI();
	this.loadTags();

    return this;
}
