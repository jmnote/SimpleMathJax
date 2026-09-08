ve.dm.MWChemNode = function VeDmMWChemNode() {
	ve.dm.MWChemNode.super.apply(this, arguments);
};

OO.inheritClass(ve.dm.MWChemNode, ve.dm.MWInlineExtensionNode);

ve.dm.MWChemNode.static.name = 'smjChem';
ve.dm.MWChemNode.static.extensionName = 'chem';
ve.dm.modelRegistry.register(ve.dm.MWChemNode);
