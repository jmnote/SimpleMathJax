<?php
class SimpleMathJax {

	public static $mathjaxScriptLoaded = false;

	static function onParserFirstCallInit ( Parser &$parser ) {
		global $wgSimpleMathJaxChem;
		$parser->setHook( 'math', 'SimpleMathJax::renderMath' );
		if( $wgSimpleMathJaxChem ) {
			$parser->setHook('chem', 'SimpleMathJax::renderChem');
		}
	}

	static function renderMath($tex) {
		if ( ! self::$mathjaxScriptLoaded ) {
			self::$mathjaxScriptLoaded = true;
			self::loadJS();
		}
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		return ["<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}

	static function renderChem($tex) {
		if ( ! self::$mathjaxScriptLoaded ) {
			self::$mathjaxScriptLoaded = true;
			self::loadJS();
		}
		$tex = '\ce{'.$tex.'}';
		return ["<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki'];
	}

	static function loadJS() {
		global $wgOut, $wgSimpleMathJaxSize, $wgSimpleMathJaxChem;
		$config = [
			'messageStyle' => 'none',
			'tex2jax' => [
				'preview' => 'none',
				'displayMath' => [ ['[math]','[/math]'] ],
			]
		];
		$configJs = json_encode($config, JSON_UNESCAPED_SLASHES);

		// create path to extension script files
		global $wgServer, $wgExtensionAssetsPath;
		$scriptBase = "$wgServer/$wgExtensionAssetsPath/SimpleMathJax/MathJax";

		$mathjaxURL = "$scriptBase/MathJax.js?config=TeX-AMS-MML_HTMLorMML";

		$script = <<<HEREDOC
<style>.MathJax_Display{display:inline !important;}
.mathjax-wrapper{display:none;font-size:${wgSimpleMathJaxSize}%;}</style>
<script type='text/x-mathjax-config'>MathJax.Hub.Config(${configJs});MathJax.Hub.Queue(function(){\$('.mathjax-wrapper').show();});</script>
<script src='$mathjaxURL'></script>
HEREDOC;
		}
		if( $wgSimpleMathJaxChem ) {
			$chemURL = "$scriptBase/extensions/TeX/mhchem.js";
			$script .= "<script src='$chemURL'></script>";
		}
		$wgOut->addScript( $script );
		return true;
	}
}
