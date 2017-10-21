<?php
class SimpleMathJax {

	public static function onRegistration() {
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::setup' );
	}

	public static function setup( Parser $parser ) {
		global $wgOut, $wgSmjDisableChem, $wgSmjInlineMath, $wgSmjScripts, $wgSmjSize;
		if( $wgSmjDisableChem != true ) $wgSmjDisableChem = false;

		$wgSmjInlineMath[] = ["[math]","[/math]"];
		if( !$wgSmjScripts ) {
			$wgSmjScripts = ['//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=TeX-AMS-MML_HTMLorMML'];
			if( !$wgSmjDisableChem ) $wgSmjScripts[] = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/extensions/TeX/mhchem.js';
		}
		if( !$wgSmjSize ) $wgSmjSize = 110;
		$wgOut->addJsConfigVars( 'wgSmjInlineMath', $wgSmjInlineMath );
		$wgOut->addJsConfigVars( 'wgSmjScripts', $wgSmjScripts );
		$wgOut->addJsConfigVars( 'wgSmjSize', $wgSmjSize );
		
		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( !$wgSmjDisableChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );
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
		$parser->getOutput()->addModules( 'ext.SimpleMathJax' );
		return ["<span style='display:none'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
}
