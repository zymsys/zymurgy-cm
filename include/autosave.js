var autosaveform; //Global...  What form are we auto-saving values from?
var lastdraft = new LastDraft(); //Global...  Used to skip writing drafts when nothing has changed.
var fckeditorcount; //Global... How many fckeditors are we expecting to render?
var fckeditorsrendered = 0; //Global...  How many fckeditors have already rendered?

var loader = new YAHOO.util.YUILoader({
    require: ["json","connection"],
    loadOptional: true,
    onSuccess: function() {
		//Get available drafts
    	RefreshDraftList('');
    }
});
loader.insert();

function Form2JSON(form) {
	this.form = document.getElementById(form);
	this.elvalues = new Array();
	this.names = new Array();
	this.keys = new Array();
	var walk = function(parent) {
		var el = parent.firstChild;
		while (el !== null) {
			//Ignore OPTION elements.  Keep SELECT elements instead.
			if (el.tagName!='OPTION') {
				//Check what to do with el - add it to values? Recurse to children?
				if (el.firstChild !== null) this.walk(el);
				if (el.value)
					this.elvalues[this.elvalues.length] = el;
			}
			el = el.nextSibling;
		}
	}
	this.getfckvalues = function() {
		//The walker doesn't grock fckeditor, so we handle it seperately.
		//It sucks to use the internal __Instances property, but I don't know a public way to enumerate the danged things.
		if (window.FCKeditorAPI) {
			for(var editorName in FCKeditorAPI.__Instances) {
				var oEditor = FCKeditorAPI.__Instances[editorName];
				var html = oEditor.GetHTML();
				this.names[editorName] = html;
				this.keys.push(editorName);
			}
		}
		if (window.CKEDITOR) {
			for (var editorName in CKEDITOR.instances)
			{
				var oEditor = CKEDITOR.instances[editorName];
				var html = oEditor.getData();
				this.names[editorName] = html;
				this.keys.push(editorName);
			}
		}
	}
	this.makeNameValuePairs = function() {
		var l = this.elvalues.length;
		for (var n = 0; n < l; n++) {
			var el = this.elvalues[n];
			switch (el.type) {
				case "checkbox":
				case "radio":
					if (el.checked) {
						this.names[el.name] = el.value;
					}
					break;
				default:
					this.names[el.name] = el.value;
			}
			this.keys.push(el.name);
		}
	}
	this.getJSON = function() {
		this.keys.sort( function (a, b){return (a > b) - (a < b);} );
		var newnames = new Object();
		for (var i = 0; i < this.keys.length; i++) {
			var keyname = this.keys[i];
			if (keyname != '') {
				newnames[keyname] = this.names[keyname];
			}
		}
		var json = YAHOO.lang.JSON.stringify(newnames);
		return json;
	}
	walk(this.form);
	this.makeNameValuePairs();
	this.getfckvalues();
	return this;
}

function JSON2Form(form,json) {
	this.form = document.getElementById(form);
	this.names = YAHOO.lang.JSON.parse(json);
	this.walk = function(parent) {
		var el = parent.firstChild;
		while (el !== null) {
			//Ignore OPTION elements.  Set SELECT elements instead.
			if (el.tagName!='OPTION') {
				//Check what to do with el - set a value? Recurse to children?
				if (el.firstChild !== null) this.walk(el);
				if (this.names[el.name])
					el.value = this.names[el.name];
			}
			el = el.nextSibling;
		}
	}
	this.setfckvalues = function() {
		//The walker doesn't grock fckeditor, so we handle it seperately.
		//It sucks to use the internal __Instances property, but I don't know a public way to enumerate the danged things.
		if (FCKeditorAPI) {
			for(var editorName in FCKeditorAPI.__Instances) {
				if (this.names[editorName]) {
					var oEditor = FCKeditorAPI.__Instances[editorName];
					oEditor.SetHTML(this.names[editorName]);
				}
			}
		}
	}
	this.walk(this.form);
	this.setfckvalues();
	lastdraft.setLastDraft(json);
}

function LastDraft() {
	this.lastdraft = '';
	this.setLastDraft = function(json) {
		this.lastdraft = json;
	};
	this.isLastDraft = function(json) {
		return (this.lastdraft == json);
	};
}

function SetSavedDraftHtml(html) {
	var ui = document.getElementById('DraftTool');
	ui.innerHTML = html;
}

function SaveDraft(formname,onSuccess) {
	var json = Form2JSON(formname);
	var jsonstr = json.getJSON();
	if (lastdraft.isLastDraft(jsonstr)) {
		//Do nothing because nothing has changed in the draft data.
		//If onSuccess is defined, consider this success.
		if (onSuccess!=null) {
			onSuccess();
		}
		return;
	}
	lastdraft.setLastDraft(jsonstr);
	var d = new Date();
	var h = d.getHours();
	var m = d.getMinutes();
	var time = (h > 12 ? h - 12 : h)+':'+(m < 10 ? "0" : "")+m+" "+(h > 12 ? 'pm' : 'am');
	YAHOO.util.Connect.asyncRequest('POST', 'http://'+window.location.hostname+'/zymurgy/include/autosave.php', 
		{
			success: function(o) {
				if (onSuccess==null) {
					RefreshDraftList('Last draft auto-save at '+time);
				} else {
					onSuccess();
				}
			},
			failure: function(o) {
				RefreshDraftList('Unable to save draft at '+time+'.  Will try again in one minute.');
			}
		},
		'form='+json.form.id+'&json='+escape(jsonstr));
}

function LoadDraft() {
	var ddl = document.getElementById('DraftToolList');
	var draft = ddl.value;
	if (ddl.value == 0) return; //Ignore "Select to load" option
	SaveDraft(autosaveform,function () {
		SetSavedDraftHtml('Loading draft...');
		YAHOO.util.Connect.asyncRequest('GET', 'http://'+window.location.hostname+'/zymurgy/include/autosave.php?fetchdraft='+draft,
		{
			success: function(o) {
				lastdraft.setLastDraft(o.responseText); //Don't save extra copies if we're jumping around between drafts.
				JSON2Form(autosaveform,o.responseText);
				RefreshDraftList('Draft loaded.');
			},
			failure: function(o) {
				RefreshDraftList('Unable to load selected draft.');
			}
		});
	});
}

function RefreshDraftList(msg) {
	YAHOO.util.Connect.asyncRequest('GET', 'http://'+window.location.hostname+'/zymurgy/include/autosave.php?listdrafts='+autosaveform,
	{
		success: function(o) {
			var drafts = YAHOO.lang.JSON.parse(o.responseText);
			var ddl = '<select id="DraftToolList" onChange="LoadDraft()">';
			ddl += '<option value="0">Select to load</option>';
			var foundone = false;
			for (var draft in drafts) {
				ddl += '<option value="'+draft+'">'+drafts[draft]+'</option>';
				foundone = true;
			}
			if (foundone) {
				ddl += '</select>';
			} else {
				ddl = "No saved drafts available.";
			}
			SetSavedDraftHtml('Available Drafts: '+ddl+' '+msg);
		},
		failure: function(o) {
			SetSavedDraftHtml('Unable to fetch list of available drafts.  '+msg);
		}
	});
}

function InitializeAutoSave(formid,fckcount) {
	autosaveform = formid;
	fckeditorcount = fckcount;
	document.write('<div id="DraftTool">Looking for available previous drafts...</div>');
	setInterval ("SaveDraft('"+formid+"')",60000); //Auto save every minute.
}

function FCKeditor_OnComplete( editorInstance )
{
    fckeditorsrendered++;
    if (fckeditorsrendered == fckeditorcount) {
    	//Load initial json info to lastdraft
		var json = Form2JSON(autosaveform);
		lastdraft.setLastDraft(json.getJSON());
    }
}
