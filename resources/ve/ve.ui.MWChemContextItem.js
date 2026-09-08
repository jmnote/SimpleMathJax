ve.ui.MWChemContextItem = function VeUiMWChemContextItem() {
	ve.ui.MWChemContextItem.super.apply( this, arguments );
};

OO.inheritClass( ve.ui.MWChemContextItem, ve.ui.MWFormulaContextItem );

ve.ui.MWChemContextItem.static.name = 'smjChem';

ve.ui.MWChemContextItem.static.icon = 'labFlask';

ve.ui.MWChemContextItem.static.label =
	OO.ui.deferMsg( 'simplemathjax-visualeditor-mwchemdialog-title' );

ve.ui.MWChemContextItem.static.modelClasses = [ ve.dm.MWChemNode ];

ve.ui.MWChemContextItem.static.commandName = 'smjChemDialog';

ve.ui.contextItemFactory.register( ve.ui.MWChemContextItem );
