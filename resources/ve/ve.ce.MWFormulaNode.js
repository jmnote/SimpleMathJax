ve.ce.MWFormulaNode = function VeCeMWFormulaNode() {
	ve.ce.MWFormulaNode.super.apply( this, arguments );
};

OO.inheritClass( ve.ce.MWFormulaNode, ve.ce.MWInlineExtensionNode );

ve.ce.MWFormulaNode.prototype.onSetup = function () {
	ve.ce.MWFormulaNode.super.prototype.onSetup.call( this );

	this.$element.addClass( 've-ce-smjFormulaNode' );
};

ve.ce.MWFormulaNode.prototype.validateGeneratedContents = function ( $element ) {
	return !( $element.find( '.error' ).addBack( '.error' ).length );
};

ve.ce.MWFormulaNode.prototype.afterRender = function () {
	var node = this;
	var $elements = this.$element;

	function done() {
		ve.ce.MWFormulaNode.super.prototype.afterRender.call( node );
	}

	mw.libs.smj.typeset( $elements.toArray() ).then( () => {
		var $containers = $elements.filter( '.smj-container' ).add( $elements.find( '.smj-container' ) );
		$containers.children( '.MathJax' ).parent().css( 'opacity', 1 );
		done();
	}, () => {
		done();
	} );
};
