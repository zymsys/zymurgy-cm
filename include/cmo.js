/*
Javascript helper functions for Zymurgy:CM (www.zymurgycm.com).
*/
function ZymurgyFactory() {
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
		req.open('get',url,true);
		req.onreadystatechange = function() {
			if (onComplete)
				onComplete(req);
		};
		req.send(null);
	};
	
	this.getparam = function (name)
	{
		name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		var regexS = "[\\?&]"+name+"=([^&#]*)";
		var regex = new RegExp( regexS );
		var results = regex.exec( window.location.href );
		if( results == null )
			return false;
		else
			return results[1];
	}
	
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

var Zymurgy = ZymurgyFactory();