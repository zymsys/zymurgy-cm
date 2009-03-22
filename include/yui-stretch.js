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
		            this.getInputEl().focus(); // Needed to keep widget active
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
} 

ZymurgyStretchesYUI();
