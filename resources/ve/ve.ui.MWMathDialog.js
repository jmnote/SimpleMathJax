ve.ui.MWMathDialog = function VeUiMWMathDialog(config) {
	ve.ui.MWMathDialog.super.call(this, config);
};

OO.inheritClass(ve.ui.MWMathDialog, ve.ui.MWFormulaDialog);

ve.ui.MWMathDialog.static.name = 'smjMathDialog';
ve.ui.MWMathDialog.static.title = OO.ui.deferMsg('simplemathjax-visualeditor-mwmathdialog-title');
ve.ui.MWMathDialog.static.modelClasses = [ve.dm.MWMathNode];
ve.ui.windowFactory.register(ve.ui.MWMathDialog);
