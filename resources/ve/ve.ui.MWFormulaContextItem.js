ve.ui.MWFormulaContextItem = function VeUiMWFormulaContextItem() {
	ve.ui.MWFormulaContextItem.super.apply( this, arguments );

	this.$element.addClass( 've-ui-mwFormulaContextItem' );
};

OO.inheritClass( ve.ui.MWFormulaContextItem, ve.ui.LinearContextItem );

ve.ui.MWFormulaContextItem.static.embeddable = false;

ve.ui.MWFormulaContextItem.prototype.getDescription = function () {
	return ve.ce.nodeFactory.getDescription( this.model );
};
