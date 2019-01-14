<?php
class SimpleMathJax {
	private static $moduleLoaded = false;
	
	public static function onRegistration() {
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::setup' );
	}

	public static function setup( Parser $parser ) {
		global $wgOut, $wgSmjUseCDN, $wgSmjSize, $wgSmjUseChem, $wgSmjInlineMath;

		$smjModule = $wgSmjUseCDN ? 'ext.SmjCDN' : 'ext.SmjLocal'; 
		$wgOut->addModules( $smjModule );
		// MobileFrontend requires explicit cloned modules targeting mobile
		$wgOut->addModules( $smjModule . ".mobile" );

		$wgOut->addJsConfigVars( 'wgSmjSize', $wgSmjSize );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgSmjInlineMath[] = ["[math]","[/math]"];
		$wgOut->addJsConfigVars( 'wgSmjInlineMath', $wgSmjInlineMath );
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
		$tex = '\ce{'.$tex.'}';
		return self::renderTex($tex, $parser);
	}

	private static function renderTex($tex, $parser) {
		return ["<span style='opacity:0.5'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
}

