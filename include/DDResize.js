/* Copyright (c) 2006 Yahoo! Inc. All rights reserved. */

/**
 * @extends YAHOO.util.DragDrop
 * @constructor
 * @param {String} handle the id of the element that will cause the resize
 * @param {String} panel id of the element to resize
 * @param {String} sGroup the group of related DragDrop items
 */
YAHOO.example.DDResize = function(panelElId, handleElId, sGroup, config) {
    if (panelElId) {
        this.init(panelElId, sGroup, config);
        this.handleElId = handleElId;
        this.setHandleElId(handleElId);
        this.logger = this.logger || YAHOO;
    }
};

// YAHOO.example.DDResize.prototype = new YAHOO.util.DragDrop();
YAHOO.extend(YAHOO.example.DDResize, YAHOO.util.DragDrop);

YAHOO.example.DDResize.prototype.onMouseDown = function(e) {
    var panel = this.getEl();
    this.startWidth = panel.offsetWidth;
    this.startHeight = panel.offsetHeight;

    this.startPos = [YAHOO.util.Event.getPageX(e),
                     YAHOO.util.Event.getPageY(e)];
};

//Called when the bottom right resize grip is moved.
YAHOO.example.DDResize.prototype.onDrag = function(e) {
    var newPos = [YAHOO.util.Event.getPageX(e),
                  YAHOO.util.Event.getPageY(e)];

    var offsetX = newPos[0] - this.startPos[0];
    var offsetY = newPos[1] - this.startPos[1];

    var newWidth = Math.max(this.startWidth + offsetX, 10);
    var newHeight = Math.max(this.startHeight + offsetY, 10);
    
    // **********
    // ZK - Edits to support minimum size
    // **********

	if(newWidth < minWidth) {
		newWidth = minWidth;
	}
	
	if(newHeight < minHeight) {
		newHeight = minHeight;
	}
	    
    // **********
    // ZK - Edits to support bounding inside the background object
    // **********
    
    // make sure the right bound does not leave the background
    // image area 
    if(newWidth > this.rightConstraint) {
    	newWidth = this.rightConstraint;
    }
    
    // make sure the bottom bound does not leave the background
    // image area
    if(newHeight > this.bottomConstraint) {
    	newHeight = this.bottomConstraint;
    }
     
    // **********
    // ZK - Edits to support aspect ratio
    // **********
    if (aspectRatio>0) {
	    var currentAspect = parseFloat(newWidth) / parseFloat(newHeight);
	    
	    if(currentAspect > aspectRatio) {
	    	// the highlighted area is too wide
	    	// make it higher to compensate, if possible.
	    	
	    	// otherwise, make it as high as possible, and adjust the width
	    	// to the maximum possible for the given aspect ratio
	    	
	    	if(newWidth * (1 / aspectRatio) > this.bottomConstraint) {
	    		newHeight = this.bottomConstraint;
	    		newWidth = newHeight * aspectRatio;
	    		
	    		if(newWidth > this.rightConstraint) {
	    			newWidth = this.rightConstraint;
	    			newHeight = newWidth * (1 / aspectRatio);
	    		}
	    	} else {
	    		newHeight = newWidth * (1 / aspectRatio);
	    	}
	    } else if(currentAspect < aspectRatio) {
	    	// the highlighted area is too high
	    	// make it wider to compensate, if possible.
	    	
	    	// otherwise, make it as wide as possible, and adjust the height
	    	// to the maximum possible for the given aspect ratio
	    	
	    	if(newHeight * aspectRatio > this.rightConstraint) {
	    		newWidth = this.rightConstraint;
	    		newHeight = newWidth * (1 / aspectRatio);
	    		
	    		if(newHeight > this.bottomConstraint) {
	    			newHeight = this.bottomConstraint;
	    			newWidth = newHeight * aspectRatio;
	    		}
	    	} else {
		    	newWidth = newHeight * aspectRatio;
	    	}
	    }
    }
    // **********
    
    var panel = this.getEl();
    panel.style.width = newWidth + "px";
    panel.style.height = newHeight + "px";
};