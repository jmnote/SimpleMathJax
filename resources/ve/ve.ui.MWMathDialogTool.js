ve.ui.MWMathDialogTool = function VeUiMWMathDialogTool( toolGroup, config ) {
	ve.ui.MWMathDialogTool.super.call( this, toolGroup, config );
};

OO.inheritClass( ve.ui.MWMathDialogTool, ve.ui.FragmentWindowTool );

ve.ui.MWMathDialogTool.static.name = 'smjMath';

ve.ui.MWMathDialogTool.static.group = 'object';

ve.ui.MWMathDialogTool.static.icon = 'mathematics';

ve.ui.MWMathDialogTool.static.title =
	OO.ui.deferMsg( 'simplemathjax-visualeditor-mwmathdialog-title' );

ve.ui.MWMathDialogTool.static.modelClasses = [ ve.dm.MWMathNode ];

ve.ui.MWMathDialogTool.static.commandName = 'smjMathDialog';

ve.ui.toolFactory.register( ve.ui.MWMathDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'smjMathDialog', 'window', 'open',
		{ args: [ 'smjMathDialog' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'smjWikitextMath', 'smjMathDialog', '<math', 5 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'smjMathDialog', {
	sequences: [ 'smjWikitextMath' ],
	label: OO.ui.deferMsg( 'simplemathjax-visualeditor-mwmathdialog-title' )
} );
