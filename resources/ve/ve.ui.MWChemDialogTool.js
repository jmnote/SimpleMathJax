ve.ui.MWChemDialogTool = function VeUiMWChemDialogTool(toolGroup, config) {
	ve.ui.MWChemDialogTool.super.call(this, toolGroup, config);
};

OO.inheritClass(ve.ui.MWChemDialogTool, ve.ui.FragmentWindowTool);

ve.ui.MWChemDialogTool.static.name = 'smjChem';
ve.ui.MWChemDialogTool.static.group = 'object';
ve.ui.MWChemDialogTool.static.icon = 'labFlask';
ve.ui.MWChemDialogTool.static.title =
	OO.ui.deferMsg('simplemathjax-visualeditor-mwchemdialog-title');

ve.ui.MWChemDialogTool.static.modelClasses = [ve.dm.MWChemNode];
ve.ui.MWChemDialogTool.static.commandName = 'smjChemDialog';
ve.ui.toolFactory.register(ve.ui.MWChemDialogTool);

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'smjChemDialog', 'window', 'open',
		{ args: ['smjChemDialog'], supportedSelections: ['linear'] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence('smjWikitextChem', 'smjChemDialog', '<chem', 5)
);

ve.ui.commandHelpRegistry.register('insert', 'smjChemDialog', {
	sequences: ['smjWikitextChem'],
	label: OO.ui.deferMsg('simplemathjax-visualeditor-mwchemdialog-title')
});
