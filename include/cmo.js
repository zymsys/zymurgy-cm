/*
Javascript helper functions for Zymurgy:CM (www.zymurgycm.com).
*/
function ZymurgyFactory() {
	/**
	 * AJAX URL and a callback function.
	 * If you set onComplete to false then the request will be syncronous and will return when it has the result.
	 */
	this.lightAJAX = function(url,onComplete) {
		var req;
		if (window.XMLHttpRequest) {
			req = new XMLHttpRequest();
		}
		else if (window.ActiveXObject) {
			req = new ActiveXObject("Microsoft.XMLHTTP");
		}
		else {
			//No AJAX available...
			return;
		}
		req.onreadystatechange = function() {
			if (onComplete)
				onComplete(req);
		};
		var async = (onComplete !== false);
		req.open('get',url,async);
		req.send(null);
		return req.responseText;
	};
	
	this.getparam = function (name) {
		name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		var regexS = "[\\?&]"+name+"=([^&#]*)";
		var regex = new RegExp( regexS );
		var results = regex.exec( window.location.href );
		if( results == null )
			return false;
		else
			return results[1];
	};
	
	this.getactiveflavourcode = function () {
		var pp = window.location.href.split('/');
		pp.shift(); //Throw away protocol
		pp.shift(); //Throw away blank
		pp.shift(); //Throw away host name
		return pp.shift();
	};
	
	this.track = function(userid) {
		var url = "/zymurgy/tracking.php?r="+escape(document.referrer)+"&u="+userid;
		var tag = this.getparam('zcmtag');
		if (tag)
		{
			url += "&t="+escape(tag);
		}
		if (document.cookie.indexOf("zcmtracking")==-1)
		{
			var expTicks = 1000 * // milliseconds
				60 * // seconds
				60 * // minutes
				24 * // hours
				365* // days
				10;  // years
			var exp = new Date();
			exp.setTime(exp.getTime() + expTicks);
			var setTrackingCookie = function(req) {
				var trckid = req.responseText;
				document.cookie = "zcmtracking="+trckid+"; expires="+exp.toGMTString() + "; path=/;";
			};
			this.lightAJAX(url,setTrackingCookie);
		} else {
			this.lightAJAX(url);
		}
	};
	
	this.sitetext = function(tag,defaulttext,inputspec) {
		if (typeof this.sitetextcache === 'undefined') {
			this.sitetextcache = [];
		}
		if (this.sitetextcache[tag]) return this.sitetextcache[tag];
		this.sitetextcache[tag] = defaulttext;
		var url = "/zymurgy/include/sitetext.php?t=" +
			escape(tag) + "&d=" +
			escape(defaulttext) + "&pt=" + Zymurgy.pagetype + "&pi=" + Zymurgy.pageid;
		if (inputspec) {
			url += "&i=" + escape(inputspec);
		}
		this.lightAJAX(url);
		return defaulttext;
	}
	
	this.toggleText = function(el, a, b) {
		if (el.value == a)
			el.value = b;
		else
			el.value = a;
	};
	
	this.enableHint = function(elTextbox) {
		elTextbox._oldTextBoxColor = YAHOO.util.Dom.getStyle(elTextbox,'color');
		elTextbox._showingHint = false;
		var ShowHint = function() {
			if (elTextbox.value == '')
			{
				var title = YAHOO.util.Dom.getAttribute(elTextbox,'title');
				if (title)
				{
					elTextbox._oldTextBoxColor = YAHOO.util.Dom.getStyle(elTextbox,'color');
					YAHOO.util.Dom.setStyle(elTextbox,'color','#aaaaaa');
					elTextbox.value = title;
					elTextbox._showingHint = true;
					return;
				}
				elTextbox._showingHint = false;
			}
			else
			{
				elTextbox._showingHint = false;
			}
		};
		YAHOO.util.Event.addListener(elTextbox, "focus", function () {
			if (elTextbox._showingHint)
			{
				YAHOO.util.Dom.setStyle(this,'color',elTextbox._oldTextBoxColor);
				elTextbox.value = '';
			}
		});
		YAHOO.util.Event.addListener(elTextbox, "blur", function () {
			ShowHint();
		});
		ShowHint();
	};
	
	this.refreshHint = function(elTextbox) {
		if (elTextbox._showingHint)
		{
			var title = YAHOO.util.Dom.getAttribute(elTextbox,'title');
			elTextbox.value = title;
		}
	}
	
	return this;
};

var Zymurgy = new ZymurgyFactory();