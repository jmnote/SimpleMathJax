ve.ce.MWMathNode = function VeCeMWMathNode() {
	ve.ce.MWMathNode.super.apply(this, arguments);
};

OO.inheritClass(ve.ce.MWMathNode, ve.ce.MWFormulaNode);

ve.ce.MWMathNode.static.name = 'smjMath';
ve.ce.MWMathNode.static.primaryCommandName = 'smjMathDialog';
ve.ce.MWMathNode.static.iconWhenInvisible = 'mathematics';
ve.ce.nodeFactory.register(ve.ce.MWMathNode);
