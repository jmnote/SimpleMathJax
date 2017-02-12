<?php
class SimpleMathJax {

	static function init() {
		global $wgParser, $wgSimpleMathJaxChem;
		$wgParser->setHook( 'math', 'SimpleMathJax::renderMath' );
		if( $wgSimpleMathJaxChem ) {
			$wgParser->setHook('chem', 'SimpleMathJax::renderChem');
		}
	}

	static function renderMath($tex) {
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		return ["<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}
	
	static function renderChem($tex) {
		$tex = '\ce{'.$tex.'}';
		return ["<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}

	static function loadJS(&$out, &$skin ) {
		global $wgSimpleMathJaxSize, $wgSimpleMathJaxChem;

		$config = [
			'messageStyle' => 'none',
			'tex2jax' => [
				'preview' => 'none',
				'displayMath' => [ ['[math]','[/math]'] ],
			]
		];
		$configJs = json_encode($config, JSON_UNESCAPED_SLASHES);

		$script = <<<HEREDOC
<style>.MathJax_Display{display:inline !important;}
.mathjax-wrapper{display:none;font-size:${wgSimpleMathJaxSize}%;}</style>
<script type='text/x-mathjax-config'>MathJax.Hub.Config(${configJs});MathJax.Hub.Queue(function(){\$('.mathjax-wrapper').show();});</script>
<script src='//cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML'></script>
HEREDOC;
		if( $wgSimpleMathJaxChem ) {
			$script .= "<script src='//cdn.mathjax.org/mathjax/latest/extensions/TeX/mhchem.js'></script>";
		}
		$out->addScript( $script );
		return true;
	}
}
