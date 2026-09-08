ve.ce.MWChemNode = function VeCeMWChemNode() {
	ve.ce.MWChemNode.super.apply(this, arguments);
};

OO.inheritClass(ve.ce.MWChemNode, ve.ce.MWFormulaNode);

ve.ce.MWChemNode.static.name = 'smjChem';
ve.ce.MWChemNode.static.primaryCommandName = 'smjChemDialog';
ve.ce.MWChemNode.static.iconWhenInvisible = 'labFlask';
ve.ce.nodeFactory.register(ve.ce.MWChemNode);
