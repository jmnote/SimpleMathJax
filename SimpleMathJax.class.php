<?php
class SimpleMathJax {
	static function init() {
	    global $wgParser;
		$wgParser->setHook( 'math', 'SimpleMathJax::render' );
	}

	static function render($tex) {
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt', $tex);
		$tex = str_replace('>', '\gt', $tex);
		return array("<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki');
	}
	
	static function loadJS(&$out, &$skin ) {
		global $wgSimpleMathJaxSize;
		$out->addScript( "<style>.MathJax_Display{display:inline !important;}
.mathjax-wrapper{font-size:${wgSimpleMathJaxSize}%;}</style>
<script type='text/x-mathjax-config'>MathJax.Hub.Config({displayAlign:'left',tex2jax:{displayMath:[['[math]','[/math]']]}})</script>
<script src='//cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML'></script>" );
		return true;
	}
}
