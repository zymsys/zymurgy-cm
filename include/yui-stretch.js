/* Extend YUI to do even more. */
function ZymurgyStretchesYUI() {
	if ((YAHOO) &&
		(YAHOO.widget) &&
		(YAHOO.widget.AutoComplete))
	{
		//Stretch the build in YUI AutoComplete object:
		
		//Add toggleContainer, which expands the container if it is closed, or closes it if it is open.  Good for combo-box style functionality.
		if (!YAHOO.widget.AutoComplete.toggleContainer) {
			YAHOO.widget.AutoComplete.prototype.toggleContainer = function()
			{
		        // Is open
		        if (this.isContainerOpen()) {
		            this.collapseContainer();
		        }
		        else // Is closed
		        {
		        	var elInp = this.getInputEl();
		            elInp.focus(); // Needed to keep widget active
		            var me = this;
		            setTimeout(function() { // sendQuery expands the container
		            	var oldql = me.minQueryLength;
		            	me.minQueryLength = 0; //Only expands if query length is long enough; default is 1.  Needs to be zero for our use here.
		                me.sendQuery("");
		                me.minQueryLength = oldql; //Restore original value of minQueryLength
		            },0);
		        }
			};
		}
	}
	if ((YAHOO) &&
		(YAHOO.widget) &&
		(YAHOO.widget.Editor))
	{
		//Stretch the built in editor to support Z:CM media galleries
		if (!YAHOO.widget.Editor.handleZCMImageClick) {
			YAHOO.widget.Editor.prototype.STR_ZCMIMG_PROP_TITLE = 'Zymurgy:CM Image Options';
			YAHOO.widget.Editor.prototype.STR_ZCMIMG_URL_PREFIX = '/zymurgy/media.php?action=stream_media_file&media_file_id=';
			YAHOO.widget.Editor.prototype.addZCMImageButton = function() {
				this.on("toolbarLoaded", function()	{
					var editor = this;
					var mediaFileImageConfig = {
						type: "push",
						label: "Insert Image from Zymurgy:CM",
						value: "zcmimage"
					};
	
					this.toolbar.addButtonToGroup(
						mediaFileImageConfig,
						"insertitem");
					
					//Create window contents for inserting from Z:CM media gallery	
	                var body = document.createElement('div');
	                body.innerHTML = 'Loading Media Gallery...';
	                editor._windows.zcmimage = {};
	                editor._windows.zcmimage.body = body;
	                
					YAHOO.util.Connect.asyncRequest(
						"GET",
						"/zymurgy/media.php?action=insert_image_into_yuihtml" +
							"&editor_id=" + this.get('id') + "Editor",
						{
							success: function(o) {
								body.innerHTML = o.responseText;
							},
							failure: function(o) {
								body.innerHTML = o.status + ": " + o.responseText;
							}
						},
						null);

					this.toolbar.on("zcmimageClick", function () {
		                var win = new YAHOO.widget.EditorWindow('zcmimage', {
		                    width: '400px'
		                });
		                
		                editor.execCommand('insertimage',''); //Create blank, or load selected image into currentElement[0]
		                
		                var el = editor.currentElement[0];
		                if (el) {
		                	//Load defaults from selected element
		                	YAHOO.util.Event.onAvailable('mediaFileWidth',function () {
		                		var url = el.getAttribute('src');
		                		if (url.substring(0,editor.STR_ZCMIMG_URL_PREFIX.length)==editor.STR_ZCMIMG_URL_PREFIX) {
			                		var mfid = url.substring(editor.STR_ZCMIMG_URL_PREFIX.length).split('&',2).shift();
			                		var mfalt = el.getAttribute('alt');
			                		var mfw = el.getAttribute('width');
			                		var mfh = el.getAttribute('height');
			                		var bogoimg = document.createElement('img');
			                		bogoimg.setAttribute('src','media.php?action=stream_media_file&media_file_id=' +
			                			mfid + '&suffix=thumb50x50');
			                		SelectFile(bogoimg,mfid,mfalt,mfw,mfh);
		                		}
		                	});
		                }
		                win.setHeader(editor.STR_ZCMIMG_PROP_TITLE);
		                editor.openWindow(win);
					});
					this.on('windowzcmimageOpen', function () {
					}, this, true);
					this.on('windowzcmimageClose', function () {
						var el = editor.currentElement[0];
						var mfid = document.getElementById('mediaFileID').value;
						var mfw = document.getElementById('mediaFileWidth').value;
						var mfh = document.getElementById('mediaFileHeight').value;
						var mfalt = document.getElementById('mediaFileAlt').value;
						var cachebuster = new Date();
						if (mfid === '') {
							//No image selected - remove empty stub
							el.parentNode.removeChild(el);
						} else {
							var url = editor.STR_ZCMIMG_URL_PREFIX +
								mfid + '&suffix=thumb' + mfw + 'x' + mfh +
								'&cachebuster=' + cachebuster.getTime();
							el.setAttribute('src', url);
							el.setAttribute('width', mfw);
							el.setAttribute('height', mfh);
							el.setAttribute('src', url);
			                el.setAttribute('title', mfalt);
			                el.setAttribute('alt', mfalt);
			                editor.currentElement = [];
							editor.nodeChange();
						}
                		SelectFile(null,0,'','','');
					}, this, true);
					this.on('afterNodeChange', function () {
						var el = this._getSelectedElement();
						if (el.tagName == 'IMG')
						{
							var img_button = this.toolbar.getButtonByValue('insertimage');
							var zcmimg_button = this.toolbar.getButtonByValue('zcmimage');
							//Is this a ZCM image?
							var url = el.getAttribute('src');
							if (url.substring(0,editor.STR_ZCMIMG_URL_PREFIX.length)==editor.STR_ZCMIMG_URL_PREFIX)
							{
								//Yes, it is a ZCM image
								editor.toolbar.selectButton(zcmimg_button);
								editor.toolbar.enableButton(zcmimg_button);
								editor.toolbar.deselectButton(img_button);
								editor.toolbar.disableButton(img_button);
							} else {
								//Not a ZCM image
								editor.toolbar.selectButton(img_button);
								editor.toolbar.enableButton(img_button);
								editor.toolbar.deselectButton(zcmimg_button);
								editor.toolbar.disableButton(zcmimg_button);
							}
						}
					}, this, true);
				});
				/*YAHOO.widget.Editor.prototype.handleZCMImageClick = function()
				{
		            this.on('afterExecCommand', function() {
				};*/
			};
		}
	}
}

ZymurgyStretchesYUI();
