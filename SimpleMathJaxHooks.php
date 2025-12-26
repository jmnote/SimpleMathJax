<?php
use MediaWiki\Html\Html;
class SimpleMathJaxHooks {

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjUseChem, $wgSmjDirectMathJax, $wgSmjEnableMenu,
			$wgSmjDisplayMath, $wgSmjExtraInlineMath, $wgSmjIgnoreHtmlClass,
			$wgSmjScale, $wgSmjDisplayAlign, $wgSmjEnableHtmlAttributes;

		$wgOut->addJsConfigVars( 'wgSmjUseCdn', $wgSmjUseCdn );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgOut->addJsConfigVars( 'wgSmjDirectMathJax', $wgSmjDirectMathJax );
		$wgOut->addJsConfigVars( 'wgSmjDisplayMath', $wgSmjDisplayMath );
		$wgOut->addJsConfigVars( 'wgSmjExtraInlineMath', $wgSmjExtraInlineMath );
		$wgOut->addJsConfigVars( 'wgSmjIgnoreHtmlClass', $wgSmjIgnoreHtmlClass );
		$wgOut->addJsConfigVars( 'wgSmjScale', $wgSmjScale );
		$wgOut->addJsConfigVars( 'wgSmjEnableMenu', $wgSmjEnableMenu );
		$wgOut->addJsConfigVars( 'wgSmjDisplayAlign', $wgSmjDisplayAlign );
		$wgOut->addJsConfigVars( 'wgSmjEnableHtmlAttributes', $wgSmjEnableHtmlAttributes );
		$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		$wgOut->addModules( [ 'ext.SimpleMathJax.mobile' ] ); // For MobileFrontend

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( $wgSmjUseChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );	}

	public static function renderMath($tex, array $args, Parser $parser, PPFrame $frame ) {
		global $wgSmjWrapDisplaystyle, $wgSmjEnableHtmlAttributes;

		if( !$wgSmjEnableHtmlAttributes ) $args = [];
		if( !isset($args["chem"]) ) {
			$tex = str_replace('\>', '\;', $tex);
			$tex = str_replace('<', '\lt ', $tex);
			$tex = str_replace('>', '\gt ', $tex);
		}
		if( isset($args["inline-block"]) ) {
			if( isset($args["display"]) ) {
				return self::renderError('SimpleMathJax: Do not use the inline-block attribute and the display attribute together on the same element.');
			}
			$tex = "\\displaystyle{ $tex }";
		} else if( !isset($args["display"]) ) {
			if( $wgSmjWrapDisplaystyle ) $tex = "\\displaystyle{ $tex }";
		} else switch ($args["display"]) {
			case "":
				break;
			case "inline":
				$tex = "\\textstyle{ $tex }";
				break;
			case "block":
				break;
			default:
				return self::renderError('SimpleMathJax: Invalid attribute value: display="' . $args["display"] . '"');
		}
		return self::renderTex($tex, $parser, $args);
	}

	public static function renderChem($tex, array $args, Parser $parser, PPFrame $frame ) {
		global $wgSmjEnableHtmlAttributes;

		if( !$wgSmjEnableHtmlAttributes ) $args = [];
		return self::renderTex("\\ce{ $tex }", $parser, $args);
	}

	private static function renderTex($tex, $parser, $args) {
		global $wgSmjEnableHtmlAttributes;

		$hookContainer = MediaWiki\MediaWikiServices::getInstance()->getHookContainer();
		$attributes = [ "style" => "opacity:.5" ];
		$attributes["class"] = ($args["class"] ?? '');
		if( !$wgSmjEnableHtmlAttributes ) {
			$attributes["class"] .= " smj-container";
		}
		$inherit_tags = [ "id", "title", "lang", "dir" ];
		foreach( $inherit_tags as $tag ) {
			if( isset($args[$tag]) ) $attributes[$tag] = $args[$tag];
		}
		$hookContainer->run( "SimpleMathJaxAttributes", [ &$attributes, $tex ] );
		if( $wgSmjEnableHtmlAttributes && !isset($args["smj-debug"]) ) {
			$attributes["class"] .= " smj-container";
		}

		if( isset($args["display"]) && $args["display"] == "block" ) {
			$element = Html::Element( "span", $attributes, "\\begin{displaymjx}{$tex}\\end{displaymjx}" );
		} else {
			$element = Html::Element( "span", $attributes, "[math]{$tex}[/math]" );
		}
		return [$element, 'markerType'=>'nowiki'];
	}

	private static function renderError($str) {
		$attributes = [ "class" => "error texerror" ];
		$element = Html::Element( "strong", $attributes, $str );
		return [$element, 'markerType'=>'nowiki'];
	}
}
