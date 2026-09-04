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

		$config = [
			"wgSmjUseCdn" => $wgSmjUseCdn,
			"wgSmjUseChem" => $wgSmjUseChem,
			"wgSmjDirectMathJax" => $wgSmjDirectMathJax,
			"wgSmjDisplayMath" => $wgSmjDisplayMath,
			"wgSmjExtraInlineMath" => $wgSmjExtraInlineMath,
			"wgSmjIgnoreHtmlClass" => $wgSmjIgnoreHtmlClass,
			"wgSmjScale" => $wgSmjScale,
			"wgSmjEnableMenu" => $wgSmjEnableMenu,
			"wgSmjDisplayAlign" => $wgSmjDisplayAlign,
			"wgSmjWrapDisplaystyle" => $wgSmjWrapDisplaystyle,
			"wgSmjEnableHtmlAttributes" => $wgSmjEnableHtmlAttributes,
		];

		$articlerev = (int)$wgOut->getRevisionId();
		foreach ($wgSmjConfigByRevision as $confset) {
			if ($articlerev == 0) break;
			if (!isset($confset["upto"]) && !isset($confset["since"])) continue;
			if (isset($confset["upto"]) && $confset["upto"] < $articlerev) continue;
			if (isset($confset["since"]) && $confset["since"] > $articlerev) continue;
			foreach( array_keys( $config ) as $varname ) {
				if( array_key_exists($varname, $confset) ) $config[$varname] = $confset[$varname];
			}
		}

		$clientConfigVars = [ "wgSmjUseCdn", "wgSmjDirectMathJax",
				"wgSmjDisplayMath", "wgSmjExtraInlineMath", "wgSmjIgnoreHtmlClass",
				"wgSmjScale", "wgSmjEnableMenu", "wgSmjDisplayAlign" ];
		foreach( $clientConfigVars as $varname ) {
			$wgOut->addJsConfigVars( $varname, $config[$varname] );
		}

		self::$useChem = $config["wgSmjUseChem"];
		self::$wrapDisplaystyle = $config["wgSmjWrapDisplaystyle"];
		self::$enableHtmlAttributes = $config["wgSmjEnableHtmlAttributes"];

		if ( $config["wgSmjDirectMathJax"] !== 'none' ) {
			$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		}

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		if( self::$useChem ) $parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}

	public static function renderMath($tex, array $args, Parser $parser, PPFrame $frame ) {
		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.SimpleMathJax' ] );
		if( !self::$enableHtmlAttributes ) $args = [];
		if( isset($args["chem"]) ) {
			$parserOutput->setJsConfigVar( "wgSmjPreloadChem", true );
		}
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
		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.SimpleMathJax' ] );
		$parserOutput->setJsConfigVar( "wgSmjPreloadChem", true );
		if( !self::$enableHtmlAttributes ) $args = [];
		return self::renderTex("\\ce{ $tex }", $parser, $args);
	}

	private static function renderTex($tex, $parser, $args) {

		$hookContainer = MediaWiki\MediaWikiServices::getInstance()->getHookContainer();
		$attributes = [ "style" => "opacity:.5", "class" => "" ];
		$inherit_tags = [ "class", "id", "title", "lang", "dir" ];
		$validatedAttribs = Sanitizer::validateAttributes( $args, array_fill_keys( $inherit_tags, true ) );
		$attributes = array_merge( $attributes, $validatedAttribs );

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

	/**
	 * Protect '' / ''' runs inside MathJax-delimited math from MediaWiki's
	 * wikitext emphasis parsing (InternalParseBeforeLinks runs after
	 * nowiki/tag stripping but before handleAllQuotes, so code blocks are
	 * already markers and prose italics is still to come). In TeX, '' and
	 * ''' are primes (y'', f'''(x)); without this guard the parser inserts
	 * <i>/<b> inside the delimited text, splitting it so MathJax cannot find
	 * the closing delimiter (dangling $ then swallows prose as math).
	 *
	 * Runs only when direct $…$/$$…$$ parsing is enabled (mode 'full'/'env');
	 * in 'none' mode MathJax handles only <math>/<chem> tags, whose content
	 * is already protected from wikitext parsing.
	 */
	public static function onInternalParseBeforeLinks( $parser, &$text, $stripState ) {
		global $wgSmjDirectMathJax, $wgSmjDisplayMath, $wgSmjExtraInlineMath;

		if ( $wgSmjDirectMathJax === 'none' ) {
			return;
		}

		$text = SimpleMathJaxQuotes::protectQuotesInMath(
			$text,
			static function ( $run ) use ( $parser ) {
				return $parser->insertStripItem( $run );
			},
			$wgSmjExtraInlineMath,
			$wgSmjDisplayMath,
			true, // processEscapes — $wgSmjDirectMathJax 'full'
			true  // \begin…\end environments
		);
	}
}
