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
	//Get elements and their position info
	var elbg = document.getElementById(imgBackground);
	var elpn = document.getElementById(panelDiv);
	var rgbg = YAHOO.util.Dom.getRegion(elbg);
	var rgpn = YAHOO.util.Dom.getRegion(elpn);
	
	//Determine handle constraints
	var handleXConstraint = rgbg.width - panelOffsetX;
	var handleYConstraint = rgbg.height - panelOffsetY;
	//Apply handle constraints
	handleObject.setXConstraint(panelOffsetX,handleXConstraint);
	handleObject.setYConstraint(panelOffsetY,handleYConstraint);
	
	//Determine panel constraings
	var panelXConstraint = parseInt(rgbg.width) - initX - parseInt(rgpn.width);
	var panelYConstraint = parseInt(rgbg.height) - initY - parseInt(rgpn.height);
	//Apply panel constraints
	ddObject.setXConstraint(initOffsetX, panelXConstraint);
	ddObject.setYConstraint(initOffsetY, panelYConstraint);
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
	
	document.frmCrop.cropX.value = pdol - bgol;
	document.frmCrop.cropY.value = pdot - bgot;
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