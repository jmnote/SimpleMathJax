 <?php
class SimpleMathJaxHooks {

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjUseChem, $wgSmjEnableMenu,
			$wgSmjDisplayMath, $wgSmjExtraInlineMath,
			$wgSmjScale, $wgSmjDisplayAlign;

		$wgOut->addJsConfigVars( 'wgSmjUseCdn', $wgSmjUseCdn );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgOut->addJsConfigVars( 'wgSmjDisplayMath', $wgSmjDisplayMath );
		$wgOut->addJsConfigVars( 'wgSmjExtraInlineMath', $wgSmjExtraInlineMath );
		$wgOut->addJsConfigVars( 'wgSmjScale', $wgSmjScale );
		$wgOut->addJsConfigVars( 'wgSmjEnableMenu', $wgSmjEnableMenu );
		$wgOut->addJsConfigVars( 'wgSmjDisplayAlign', $wgSmjDisplayAlign );
		$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		$wgOut->addModules( [ 'ext.SimpleMathJax.mobile' ] ); // For MobileFrontend

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( $wgSmjUseChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );	}

	public static function renderMath($tex, array $args, Parser $parser, PPFrame $frame ) {
		global $wgSmjWrapDisplaystyle;
		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		if( $wgSmjWrapDisplaystyle ) $tex = "\displaystyle{ $tex }";
		return self::renderTex($tex, $parser);
	}

	public static function renderChem($tex, array $args, Parser $parser, PPFrame $frame ) {
		return self::renderTex("\ce{ $tex }", $parser);
	}

	private static function renderTex($tex, $parser) {
		$attributes = [ "style" => "opacity:.5" ];
		Hooks::run( "SimpleMathJaxAttributes", [ &$attributes, $tex ] );
		$element = Html::Element( "span", $attributes, "[math]{$tex}[/math]" );
		return [$element, 'markerType'=>'nowiki'];
	}
}
