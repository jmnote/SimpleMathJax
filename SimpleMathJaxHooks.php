<?php
use MediaWiki\Html\Html;
use MediaWiki\Parser\Sanitizer;
class SimpleMathJaxHooks {
	private static $useChem;
	private static $wrapDisplaystyle;
	private static $enableHtmlAttributes;

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjUseChem, $wgSmjDirectMathJax, $wgSmjEnableMenu,
			$wgSmjDisplayMath, $wgSmjExtraInlineMath, $wgSmjIgnoreHtmlClass,
			$wgSmjScale, $wgSmjDisplayAlign, $wgSmjWrapDisplaystyle,
			$wgSmjEnableHtmlAttributes, $wgSmjConfigByRevision;

		$globalvars = [ "wgSmjUseCdn", "wgSmjUseChem", "wgSmjDirectMathJax",
				"wgSmjDisplayMath", "wgSmjExtraInlineMath", "wgSmjIgnoreHtmlClass",
				"wgSmjScale", "wgSmjEnableMenu", "wgSmjDisplayAlign" ];
		foreach( $globalvars as $varname ) {
			$wgOut->addJsConfigVars( $varname, $$varname );
		}
		self::$wrapDisplaystyle = $wgSmjWrapDisplaystyle;
		self::$enableHtmlAttributes = $wgSmjEnableHtmlAttributes;

		$articlerev = (int)$wgOut->getRevisionId();
		foreach ($wgSmjConfigByRevision as $confset) {
			if ($articlerev == 0) break;
			if (!isset($confset["upto"]) && !isset($confset["since"])) continue;
			if (isset($confset["upto"]) && $confset["upto"] < $articlerev) continue;
			if (isset($confset["since"]) && $confset["since"] > $articlerev) continue;
			foreach( $globalvars as $varname ) {
				if( isset($confset[$varname]) ) $wgOut->addJsConfigVars( $varname, $confset[$varname] );
			}
			if (isset($confset["wgSmjWrapDisplaystyle"]) ) self::$wrapDisplaystyle = $confset["wgSmjWrapDisplaystyle"];
			if (isset($confset["wgSmjEnableHtmlAttributes"]) ) self::$enableHtmlAttributes = $confset["wgSmjEnableHtmlAttributes"];
		}
		self::$useChem = $wgOut->getJsConfigVars()["wgSmjUseChem"];

		$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		$wgOut->addModules( [ 'ext.SimpleMathJax.mobile' ] ); // For MobileFrontend

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( self::$useChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}

	public static function renderMath($tex, array $args, Parser $parser, PPFrame $frame ) {
		if( !self::$enableHtmlAttributes ) $args = [];
		if( isset($args["inline-block"]) ) {
			if( isset($args["display"]) ) {
				return self::renderError('SimpleMathJax: Do not use the inline-block attribute and the display attribute together on the same element.');
			}
			$tex = "\\displaystyle{ $tex }";
		} else if( !isset($args["display"]) ) {
			if( self::$wrapDisplaystyle ) $tex = "\\displaystyle{ $tex }";
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
		if( !self::$enableHtmlAttributes ) $args = [];
		return self::renderTex("\\ce{ $tex }", $parser, $args);
	}

	private static function renderTex($tex, $parser, $args) {

		$hookContainer = MediaWiki\MediaWikiServices::getInstance()->getHookContainer();
		$attributes = [ "style" => "opacity:.5", "class" => "" ];
		$inherit_tags = [ "class", "id", "title", "lang", "dir" ];
		$attributes = array_merge( $attributes, Sanitizer::validateAttributes( $args , array_fill_keys( $inherit_tags, true ) ) );

		$hookContainer->run( "SimpleMathJaxAttributes", [ &$attributes, $tex, $args ] );
		if( !isset($attributes["smj-debug"]) && !isset($args["smj-debug"]) ) {
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
