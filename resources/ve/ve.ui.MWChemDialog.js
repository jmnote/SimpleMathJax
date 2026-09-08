ve.ui.MWChemDialog = function VeUiMWChemDialog(config) {
	ve.ui.MWChemDialog.super.call(this, config);
};

OO.inheritClass(ve.ui.MWChemDialog, ve.ui.MWFormulaDialog);

ve.ui.MWChemDialog.static.name = 'smjChemDialog';
ve.ui.MWChemDialog.static.title = OO.ui.deferMsg('simplemathjax-visualeditor-mwchemdialog-title');
ve.ui.MWChemDialog.static.modelClasses = [ve.dm.MWChemNode];
ve.ui.windowFactory.register(ve.ui.MWChemDialog);
