function ZymurgyCreateDGCM(triggers,mytablename) {
	var oContextMenu = new YAHOO.widget.ContextMenu('contextmenu', {
		trigger: triggers,
		lazyload: true,
		itemdata: [
			{ text: 'Cut', onclick: { fn: ZymurgyCreateDGCMAction, obj: "cut" } },
			{ text: 'Copy', onclick: { fn: ZymurgyCreateDGCMAction, obj: "copy" } },
			{ text: 'Paste', onclick: { fn: ZymurgyCreateDGCMAction, obj: "paste" } }
		]
	});
	oContextMenu.tablename = mytablename;
	oContextMenu.render();
	return oContextMenu;
}

function ZymurgyCreateDGCMAction(p_sType, p_aArgs, p_sAction) {
	var elid = this.parent.contextEventTarget.id;
	var dbid = elid.substr(6);
	var table = this.parent.tablename;
	switch(p_sAction) {
		case 'cut':
			break;
		case 'copy':
			break;
		case 'paste':
			break;
	}
}