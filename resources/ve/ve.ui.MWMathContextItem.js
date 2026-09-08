ve.ui.MWMathContextItem = function VeUiMWMathContextItem() {
	ve.ui.MWMathContextItem.super.apply(this, arguments);
};

OO.inheritClass(ve.ui.MWMathContextItem, ve.ui.MWFormulaContextItem);

ve.ui.MWMathContextItem.static.name = 'smjMath';
ve.ui.MWMathContextItem.static.icon = 'mathematics';
ve.ui.MWMathContextItem.static.label =
	OO.ui.deferMsg('simplemathjax-visualeditor-mwmathdialog-title');

ve.ui.MWMathContextItem.static.modelClasses = [ve.dm.MWMathNode];
ve.ui.MWMathContextItem.static.commandName = 'smjMathDialog';
ve.ui.contextItemFactory.register(ve.ui.MWMathContextItem);
