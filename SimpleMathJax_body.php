<?php
class SimpleMathJax {

	public static function onRegistration() {
		global $wgSimpleMathJaxUseCDN, $wgExtensionFunctions;
		if( $wgSimpleMathJaxUseCDN ) $wgExtensionFunctions[] = __CLASS__ . '::setupCDN';
		else $wgExtensionFunctions[] = __CLASS__ . '::setupLocal';
		Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::onParserFirstCallInit' );
	}

	public static function setupCDN() {
		global $wgOut, $wgSimpleMathJaxSize;
		$wgOut->addScript( <<<HEREDOC
<script type="text/x-mathjax-config">
MathJax.Hub.Config({"messageStyle":"none","HTML-CSS":{scale:${wgSimpleMathJaxSize}},"tex2jax":{"preview":"none","inlineMath":[["[math]","[/math]"]]}});
MathJax.Hub.Queue(function(){\$(".MathJax").parent().show();});</script>
<script src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/extensions/TeX/mhchem.js"></script>
HEREDOC
);
	}

	public static function setupLocal() {
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
