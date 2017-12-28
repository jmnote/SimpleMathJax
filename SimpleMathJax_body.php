<?php
class SimpleMathJax {
	public static function onRegistration() {
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::setup' );
	}

	public static function setup( Parser $parser ) {
		global $wgOut, $wgSmjSize, $wgSmjUseChem, $wgSmjInlineMath;
		$wgSmjInlineMath[] = ["[math]","[/math]"];
		$wgOut->addJsConfigVars( 'wgSmjSize', $wgSmjSize );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
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
		global $wgSmjUseCDN;
		$parser->getOutput()->addModules( $wgSmjUseCDN ? 'ext.SmjCDN' : 'ext.SmjLocal' );
		return ["<span style='display:none'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
}

