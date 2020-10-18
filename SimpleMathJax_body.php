<?php
class SimpleMathJax {
	public static function onRegistration() {
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::setup' );
	}

	public static function setup( Parser $parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjScale, $wgSmjUseChem, $wgSmjShowMathMenu, $wgSmjExtraInlineMath, $wgSmjDisplayMath;
		#trace( $wgSmjUseCdn );
		#exit;
		$wgOut->addModules( 'ext.SimpleMathJax' ); 
		// MobileFrontend requires explicit cloned modules targeting mobile
		$wgOut->addModules( 'ext.SimpleMathJax.mobile' );

		$wgOut->addJsConfigVars( 'wgSmjUseCdn', $wgSmjUseCdn );
		$wgOut->addJsConfigVars( 'wgSmjScale', $wgSmjScale );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgOut->addJsConfigVars( 'wgSmjShowMathMenu', $wgSmjShowMathMenu );
		$wgOut->addJsConfigVars( 'wgSmjExtraInlineMath', $wgSmjExtraInlineMath );
		$wgOut->addJsConfigVars( 'wgSmjDisplayMath', $wgSmjDisplayMath );

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
		return ["<span style='opacity:.5'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
}

