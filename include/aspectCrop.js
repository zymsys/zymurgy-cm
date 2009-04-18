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
	var elpd = document.getElementById("panelDiv");
	var elbg = document.getElementById("imgBackground");
	
	var bgol = elbg.offsetLeft;
	var bgot = elbg.offsetTop;
	var pdol = elpd.offsetLeft;
	var pdot = elpd.offsetTop;
	var pdcw = elpd.clientWidth;
	var pdch = elpd.clientHeight;
	
	document.frmCrop.cropX.value = pdol - bgol + borderWidth;
	document.frmCrop.cropY.value = pdot - bgot + borderWidth;
	document.frmCrop.cropWidth.value = pdcw;
	document.frmCrop.cropHeight.value = pdch;
	
	document.frmCrop.submit();
}

function clearImage() {
	if (confirm("Are you sure you want to clear this image from the server?")==true) {
		document.frmCrop.action.value = 'clear';
		document.frmCrop.submit();
	}
}