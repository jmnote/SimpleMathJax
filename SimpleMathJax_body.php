<?php
class SimpleMathJax {

	public static function onParserFirstCallInit( &$parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjUseChem, $wgSmjEnableMenu, $wgSmjDisplayMath, $wgSmjExtraInlineMath, $wgSmjScale;

		$wgOut->addJsConfigVars( 'wgSmjUseCdn', $wgSmjUseCdn );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgOut->addJsConfigVars( 'wgSmjEnableMenu', $wgSmjEnableMenu );
		$wgOut->addJsConfigVars( 'wgSmjDisplayMath', $wgSmjDisplayMath );
		$wgOut->addJsConfigVars( 'wgSmjExtraInlineMath', $wgSmjExtraInlineMath );
		$wgOut->addJsConfigVars( 'wgSmjScale', $wgSmjScale );
		
		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( $wgSmjUseChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}
	
	public static function renderMath($tex, array $args, Parser $parser, PPFrame $frame ) {
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		return self::renderTex($tex, $parser);
	}

	public static function renderChem($tex, array $args, Parser $parser, PPFrame $frame ) {
		return self::renderTex('\ce{'.$tex.'}', $parser);
	}

	private static function renderTex($tex, $parser) {
		$parser->getOutput()->addModules( 'ext.SimpleMathJax' ); 
		$parser->getOutput()->addModules( 'ext.SimpleMathJax.mobile' ); // For MobileFrontend
		return ["<span style='opacity:.5'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
}

