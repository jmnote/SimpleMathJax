<?php
class SimpleMathJax {
	
	static function init() {
		global $wgParser, $wgSimpleMathJaxChem;
		$wgParser->setHook( 'math', 'SimpleMathJax::renderMath' );
		if( $wgSimpleMathJaxChem )
			$wgParser->setHook( 'chem', 'SimpleMathJax::renderChem' );
	}
	
	static function renderMath($tex) {
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		return self::renderTex($tex);
	}
	
	static function renderChem($tex) {
		$tex = '\ce{'.$tex.'}';
		return self::renderTex($tex);
	}

	static function renderTex($tex) {
		return ["<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
	
	static function addScripts( $out ) {
		global $wgSimpleMathJaxSize, $wgSimpleMathJaxChem, $wgSimpleMathJaxJs, $wgSimpleMathJaxChemJs;
		
		if( !$wgSimpleMathJaxJs )
			$wgSimpleMathJaxJs = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS-MML_HTMLorMML';
		if( !$wgSimpleMathJaxChemJs )
			$wgSimpleMathJaxChemJs = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/extensions/TeX/mhchem.js';
		$out->addScript( <<<HEREDOC
<style>.mathjax-wrapper{display:none;font-size:${wgSimpleMathJaxSize}%;}.MathJax_Display{display:inline !important;}</style>
<script type="text/x-mathjax-config">
MathJax.Hub.Config({"messageStyle":"none","tex2jax":{"preview":"none","displayMath":[["[math]","[/math]"]]}});
MathJax.Hub.Queue(function(){\$(".mathjax-wrapper").show();});</script>
<script src="${wgSimpleMathJaxJs}"></script>
HEREDOC
);
		if( $wgSimpleMathJaxChem )
			$out->addScript( "<script src='${wgSimpleMathJaxChemJs}'></script>" );
		return true;
	}
}
