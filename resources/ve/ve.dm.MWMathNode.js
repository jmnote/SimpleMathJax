ve.dm.MWMathNode = function VeDmMWMathNode() {
	ve.dm.MWMathNode.super.apply(this, arguments);
};

OO.inheritClass(ve.dm.MWMathNode, ve.dm.MWInlineExtensionNode);

ve.dm.MWMathNode.static.name = 'smjMath';
ve.dm.MWMathNode.static.extensionName = 'math';
ve.dm.modelRegistry.register(ve.dm.MWMathNode);
