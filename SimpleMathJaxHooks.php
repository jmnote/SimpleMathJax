<?php
use MediaWiki\Html\Html;
class SimpleMathJaxHooks {

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjUseChem, $wgSmjEnableMenu,
			$wgSmjDisplayMath, $wgSmjExtraInlineMath, $wgSmjIgnoreHtmlClass,
			$wgSmjScale, $wgSmjDisplayAlign;

		$wgOut->addJsConfigVars( 'wgSmjUseCdn', $wgSmjUseCdn );
		$wgOut->addJsConfigVars( 'wgSmjUseChem', $wgSmjUseChem );
		$wgOut->addJsConfigVars( 'wgSmjDisplayMath', $wgSmjDisplayMath );
		$wgOut->addJsConfigVars( 'wgSmjExtraInlineMath', $wgSmjExtraInlineMath );
		$wgOut->addJsConfigVars( 'wgSmjIgnoreHtmlClass', $wgSmjIgnoreHtmlClass );
		$wgOut->addJsConfigVars( 'wgSmjScale', $wgSmjScale );
		$wgOut->addJsConfigVars( 'wgSmjEnableMenu', $wgSmjEnableMenu );
		$wgOut->addJsConfigVars( 'wgSmjDisplayAlign', $wgSmjDisplayAlign );
		$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		$wgOut->addModules( [ 'ext.SimpleMathJax.mobile' ] ); // For MobileFrontend

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( $wgSmjUseChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );	}

	public static function renderMath($tex, array $args, Parser $parser, PPFrame $frame ) {
		global $wgSmjWrapDisplaystyle, $wgSmjEnableHtmlAttributes;

		$tex = str_replace('\>', '\;', $tex);
		$tex = str_replace('<', '\lt ', $tex);
		$tex = str_replace('>', '\gt ', $tex);
		if( !$wgSmjEnableHtmlAttributes ) $args = [];
		if( !isset($args["display"]) ) {
			if( $wgSmjWrapDisplaystyle ) $tex = "\\displaystyle{ $tex }";
		} else switch ($args["display"]) {
			case "":
				break;
			case "inline":
				$tex = "\\textstyle{ $tex }";
				break;
			case "block":
				$tex = "\\displaystyle{ $tex }";
				break;
			default:
				;
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
		$hookContainer->run( "SimpleMathJaxAttributes", [ &$attributes, $tex ] );
		if( !isset($args["debug"]) && $wgSmjEnableHtmlAttributes ) {
			$attributes["class"] .= " smj-container";
		}
		$inherit_tags = [ "id", "title", "lang", "dir" ];
		foreach( $inherit_tags as $tag ) {
			if( isset($args[$tag]) ) $attributes[$tag] = $args[$tag];
		}

		if( isset($args["display"]) && $args["display"] == "block" ) {
			$element = Html::Element( "span", $attributes, "\\begin{displaymjx}{$tex}\\end{displaymjx}" );
		} else {
			$element = Html::Element( "span", $attributes, "[math]{$tex}[/math]" );
		}
		return [$element, 'markerType'=>'nowiki'];
	}
}
