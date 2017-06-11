<?php
class SimpleMathJax {

	public static function onRegistration() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = __CLASS__ . '::loadModule';
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::onParserFirstCallInit' );
	}

	public static function loadModule() {
		global $wgOut, $wgSimpleMathJaxSize;
		$wgOut->addJsConfigVars( 'wgSimpleMathJaxSize', $wgSimpleMathJaxSize );
		$wgOut->addModules( 'ext.SimpleMathJax' );
	}

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		$parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}

	public static function renderMath($tex) {
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		return self::renderTex($tex);
	}
	
	public static function renderChem($tex) {
		$tex = '\ce{'.$tex.'}';
		return self::renderTex($tex);
	}

	public static function renderTex($tex) {
		return ["<span style='display:none'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}

}
