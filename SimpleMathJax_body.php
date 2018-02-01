<?php
class SimpleMathJax {
	private static $moduleLoaded = false;
	
	public static function onRegistration() {
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::setup' );
	}

	public static function setup( Parser $parser ) {
		global $wgOut, $wgSmjSize, $wgSmjUseChem, $wgSmjInlineMath;
		if( count($wgSmjInlineMath)>0 ) self::loadModule();
		$wgSmjInlineMath[] = ["[math]","[/math]"];
		$wgOut->addJsConfigVars( 'wgSmjSize', $wgSmjSize );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgOut->addJsConfigVars( 'wgSmjInlineMath', $wgSmjInlineMath );
		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( $wgSmjUseChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}
	
	private static function loadModule( ) {
		if( self::$moduleLoaded ) return;
		self::$moduleLoaded = true;
		global $wgSmjUseCDN, $wgOut;
		$wgOut->addModules( $wgSmjUseCDN ? 'ext.SmjCDN' : 'ext.SmjLocal' );
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
		self::loadModule();
		return ["<span style='display:none'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
}

