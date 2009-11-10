function ZymurgyTagCloud(elTarget, tagsUrl, cloudID) {
	elTarget = YAHOO.util.Dom.get(elTarget);
	var elHd;
	var elBd;
	var elSelected;
	var tags = new Array(); //Tags in the cloud
	var selected = new Array(); //Tags on the selected list
	var maxmag = 5;
	var DataTableRefresh; //Function to refresh the table view

	this.buildUI = function() {
		elHd = document.createElement("div");
		elHd.setAttribute("class","ZymurgyTagCloudHd");
		elHd.appendChild(document.createTextNode("Selected: "));
		elSelected = document.createElement("span");
		elSelected.innerHTML = "<i>None</i>";
		elHd.appendChild(elSelected);
		elTarget.appendChild(elHd);
		elBd = document.createElement("div");
		elBd.setAttribute("class","ZymurgyTagCloudBd");
		elTarget.appendChild(elBd);
	};

	this.createTags = function (tagData) {
		for (var i in tagData.results) {
			var tag = tagData.results[i];
			tags.push(tag);
		}
		var count = tags.length;
		tags.sort(function (a,b) {
			return (b.hits - a.hits);
		});
		var ms = 1/maxmag; //Size of a magnitude step
		var cs = 1/count; //Size of a tag interation step
		var l = 0; //Current level
		var mt = ms; //Magnitude threshold before next step up
		var m = 1; //Current magnitude (maxmag is smallest, 1 is biggest - think header tags)
		for (var i in tags) {
			tags[i].magnitude = m;
			l += cs;
			if (l > mt) {
				m++;
				mt += ms;
			}
		}
		//Finally randomize the sort order
		tags.sort(function () {
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
		var oTag = selected[elTag.zymurgy_idx];
		selected.splice(elTag.zymurgy_idx,1);
		//Re-do zymurgy_idx's in selected el's after splice
		for (var i in selected) {
			selected[i].el.zymurgy_idx = i;
		}
		elSelected.removeChild(elTag);

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
		for (var i in selected) {
			query.push('s'+i+'='+escape(selected[i].name));
		}
		return query.join('&');
	}

	this.onTagClick = function (e, oTagCloud) {
		var elTag = e.currentTarget;
		var oTag = tags[elTag.zymurgy_idx];
		tags.splice(elTag.zymurgy_idx,1); //Remove tag from cloud list
		elBd.removeChild(elTag); //Remove element from cloud body
		if (selected.length == 0) {
			elSelected.innerHTML = ''; //Clear "none" placeholder
		}
		elTag = oTagCloud.createTagElement(oTag);
		elTag.setAttribute("class","ZymurgyTagCloudSelectedTag");
		var elClose = document.createElement("span");
		elClose.setAttribute("class","ZymurgyTagCloudSelectedCloseTag");
		elClose.appendChild(document.createTextNode('[x]'));
		elTag.appendChild(elClose);
		elSelected.appendChild(elTag);
		oTag.el = elTag;
		elTag.zymurgy_idx = selected.push(oTag)-1;
		YAHOO.util.Event.addListener(elClose,'click', oTagCloud.onCloseSelectedClick, oTagCloud);

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

	this.renderTags = function () {
		for (var i in tags) {
			var oTag = tags[i];
			var elTag = this.createTagElement(oTag);
			var ndWhiteSpace = document.createTextNode(" ");
			elTag.zymurgy_idx = i;
			elTag.setAttribute("class","ZymurgyTagCloudTag ZymurgyTagCloudTag"+oTag.magnitude);
			elBd.appendChild(elTag);
			elBd.appendChild(ndWhiteSpace);
			YAHOO.util.Event.addListener(elTag,'click', this.onTagClick, this);
			tags[i].el = elTag;
		}
	};

	this.loadTags = function() {
		for (var i in tags) {
			elBd.removeChild(tags[i].el);
		}
		tags = new Array();
		var qs = this.getQueryString();
		dsTags.sendRequest(qs+'&what=tags',{
			scope: this,
			success: function (oResponse, oParsedResponse) {
				this.createTags(oParsedResponse);
				this.renderTags();
			},
			failure: function () {
				alert('Failed to load tag cloud data.');
			}

		});
		if (DataTableRefresh) {
			DataTableRefresh(qs);
		}
	};

	var CloudDataSource = tagsUrl + "&"; // + "?";
	var dsTags = new YAHOO.util.XHRDataSource(CloudDataSource);
	dsTags.responseType = YAHOO.util.XHRDataSource.TYPE_XML;
	dsTags.responseSchema = {
		resultNode: "tag",
		fields: [ {key: "name"}, {key: "hits", parser: "number"} ]
	};
	this.buildUI();
	this.loadTags();
    var dt = function() {
        var myColumnDefs = [ {key:"name", label:"Name"} ];
        var dsTable = new YAHOO.util.XHRDataSource(CloudDataSource);
        dsTable.responseType = YAHOO.util.XHRDataSource.TYPE_XML;
        dsTable.connXhrMode = "queueRequests";
        dsTable.responseSchema = { resultNode: "hit", fields: [ {key: "name"}, {key: "tag"} ] };

        var myDataTable = new YAHOO.widget.DataTable(elTarget.id+"Table", myColumnDefs, dsTable,
        	{initialRequest:"what=results"});

        var mySuccessHandler = function() {
            this.set("sortedBy", null);
            this.onDataReturnAppendRows.apply(this,arguments);
        };
        var myFailureHandler = function() {
            this.showTableMessage(YAHOO.widget.DataTable.MSG_ERROR, YAHOO.widget.DataTable.CLASS_ERROR);
            this.onDataReturnAppendRows.apply(this,arguments);
        };
        var callbackObj = {
            success : mySuccessHandler,
            failure : myFailureHandler,
            scope : myDataTable
        };

        DataTableRefresh = function(queryString) {
			myDataTable.deleteRows(myDataTable.getRecordSet().getLength() - 1, -1 * myDataTable.getRecordSet().getLength());
			myDataTable.initializeTable();
			myDataTable.render();
        	dsTable.sendRequest(queryString+'&what=results', callbackObj);
        }
    }();
}
