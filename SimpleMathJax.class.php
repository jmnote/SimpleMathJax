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
		$tex = str_replace('<', '\lt', $tex);
		$tex = str_replace('>', '\gt', $tex);
		return array("<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki');
	}
	
	static function renderChem($tex) {
		$tex = '\ce{'.$tex.'}';
		return array("<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki');
	}

	static function loadJS(&$out, &$skin ) {
		global $wgSimpleMathJaxSize, $wgSimpleMathJaxChem;

		$config = array(
			'displayAlign' => 'left',
			'tex2jax' => array(
				'displayMath' => array(
					array(
						'[math]','[/math]'
					)
				)
			)
		);

		if( $wgSimpleMathJaxChem ) {
			$config['extensions'] = array(
				'[Contrib]/mhchem/mhchem.js'
			);
		}

		$configJs = json_encode($config, JSON_UNESCAPED_SLASHES);

		$out->addScript( "<style>.MathJax_Display{display:inline !important;}
.mathjax-wrapper{font-size:${wgSimpleMathJaxSize}%;}</style>
<script type='text/x-mathjax-config'>MathJax.Hub.Config(${configJs})</script>
<script src='//cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML'></script>" );
		return true;
	}
}
