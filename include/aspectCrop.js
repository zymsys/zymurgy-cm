function cropImage(
		imgId,
		imgX,
		imgY) {
	img = document.getElementById(imgId);	
	img.style.left = -imgX + "px";
	img.style.top  = -imgY + "px";
}

function setDebug(txt) {
	var dd = document.getElementById('debug');
	dd.innerHTML = txt;
}

function setConstraints(
		ddObject,
		handleObject,
		panelDiv,
		imgBackground,
		initOffsetX,
		initOffsetY,	
		panelOffsetX,
		panelOffsetY,	
		borderThickness) {
	var handleXConstraint = document.getElementById(imgBackground).width;
	handleXConstraint -= panelOffsetX;
	handleXConstraint -= borderThickness * 2;
	
	var handleYConstraint = document.getElementById(imgBackground).height;
	handleYConstraint -= panelOffsetY;
	handleYConstraint -= borderThickness * 2;
    		
    handleObject.setXConstraint(
    	panelOffsetX,
    	handleXConstraint,
    	granularity);
    handleObject.setYConstraint(
    	panelOffsetX,
    	handleYConstraint,
    	granularity);
    	
	var panelXConstraint = document.getElementById(imgBackground).width;
	panelXConstraint -= initOffsetX;
	panelXConstraint -= document.getElementById(panelDiv).clientWidth; 	
	//panelXConstraint -= borderThickness * 2;
	if (panelXConstraint < 0) panelXConstraint = 0;
	
	var panelYConstraint = document.getElementById(imgBackground).height;
	panelYConstraint -= initOffsetY;
	panelYConstraint -= document.getElementById(panelDiv).clientHeight; 	
	//panelYConstraint -= borderThickness * 2;
	if (panelYConstraint < 0) panelYConstraint = 0;
	
//setDebug('oy: '+offsetY+' pyc: '+panelYConstraint+' g:'+granularity);
    ddObject.setXConstraint(
    		(panelXConstraint == 0) ? 0 : initX,
    		panelXConstraint,
    		granularity);
    ddObject.setYConstraint(
    		(panelYConstraint == 0) ? 0 : initY,
    		panelYConstraint,
    		granularity);
}

function submitForm() {
	document.frmCrop.cropX.value = 
		document.getElementById("panelDiv").offsetLeft - initX + borderWidth;
	document.frmCrop.cropY.value =
		document.getElementById("panelDiv").offsetTop - initY + borderWidth;
	/*alert('offsettop: '+document.getElementById("panelDiv").offsetTop+'\ninitY: '+initY+'\nborderWidth: '+borderWidth+'\ncropY: '+document.frmCrop.cropY.value);
	return;*/
		
	document.frmCrop.cropWidth.value = 
		document.getElementById("panelDiv").clientWidth;
		
	document.frmCrop.cropHeight.value = 
		document.getElementById("panelDiv").clientHeight;
	
	document.frmCrop.submit();
}

function clearImage() {
	if (confirm("Are you sure you want to clear this image from the server?")==true) {
		document.frmCrop.action.value = 'clear';
		document.frmCrop.submit();
	}
}