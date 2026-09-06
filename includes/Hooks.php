<?php

namespace MediaWiki\Extension\SimpleMathJax;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use Parser;
use PPFrame;

class Hooks {
	private const DIRECT_MATH_DEFAULTS = [
		'enabled' => false,
		'inlineMath' => [],
		'displayMath' => [],
	];

	private static bool $displaystyle            = false;
	private static bool $enableRenderAttributes  = true;
	private static array $allowedAttributes      = [];
	private static array $directMath             = self::DIRECT_MATH_DEFAULTS;

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjUseCdn, $wgSmjEnableMenu,
		$wgSmjDirectMath, $wgSmjIgnoreHtmlClass,
		$wgSmjScale, $wgSmjDisplayAlign, $wgSmjDisplaystyle,
		$wgSmjEnableRenderAttributes, $wgSmjAllowedAttributes, $wgSmjRevisionOverrides;

		$config = [
			"wgSmjUseCdn"                  => $wgSmjUseCdn,
			"wgSmjDirectMath"              => self::mergeDirectMath( $wgSmjDirectMath ),
			"wgSmjIgnoreHtmlClass"         => $wgSmjIgnoreHtmlClass,
			"wgSmjScale"                   => $wgSmjScale,
			"wgSmjEnableMenu"              => $wgSmjEnableMenu,
			"wgSmjDisplayAlign"            => $wgSmjDisplayAlign,
			"wgSmjDisplaystyle"            => $wgSmjDisplaystyle,
			"wgSmjEnableRenderAttributes"  => $wgSmjEnableRenderAttributes,
			"wgSmjAllowedAttributes"       => $wgSmjAllowedAttributes,
		];

		$articlerev = (int)$wgOut->getRevisionId();
		$config = self::applyRevisionOverrides( $config, $wgSmjRevisionOverrides, $articlerev );

		$clientConfigVars = [ "wgSmjUseCdn", "wgSmjDirectMath",
			"wgSmjIgnoreHtmlClass", "wgSmjScale", "wgSmjEnableMenu", "wgSmjDisplayAlign" ];
		foreach ( $clientConfigVars as $varname ) {
			$wgOut->addJsConfigVars( $varname, $config[$varname] );
		}

		self::$displaystyle           = $config["wgSmjDisplaystyle"];
		self::$enableRenderAttributes = $config["wgSmjEnableRenderAttributes"];
		self::$allowedAttributes      =
			is_array( $config["wgSmjAllowedAttributes"] ) ? $config["wgSmjAllowedAttributes"] : [];
		self::$directMath             = $config["wgSmjDirectMath"];

		if ( self::$directMath['enabled'] ) {
			$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		}

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		$parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}

	public static function mergeDirectMath( $value ): array {
		return array_merge( self::DIRECT_MATH_DEFAULTS, is_array( $value ) ? $value : [] );
	}

	/**
	 * Apply $wgSmjRevisionOverrides on top of $config for the given revision id.
	 * Pulled out of onParserFirstCallInit() as a pure function so it can be
	 * unit-tested without a MediaWiki bootstrap (see tests/RevisionOverridesTest.php).
	 */
	public static function applyRevisionOverrides( array $config, array $overrides, int $articlerev ): array {
		foreach ( $overrides as $confset ) {
			if ( $articlerev == 0 ) {
				break;
			}

			if ( !isset( $confset["min"] ) && !isset( $confset["max"] ) ) {
				continue;
			}

			if ( isset( $confset["max"] ) && $confset["max"] < $articlerev ) {
				continue;
			}

			if ( isset( $confset["min"] ) && $confset["min"] > $articlerev ) {
				continue;
			}

			foreach ( $confset as $key => $value ) {
				if ( strpos( $key, '.' ) !== false ) {
					[ $varname, $subkey ] = explode( '.', $key, 2 );
					if ( array_key_exists( $varname, $config ) && is_array( $config[$varname] ) ) {
						$config[$varname][$subkey] = $value;
					}
					continue;
				}

				if ( array_key_exists( $key, $config ) ) {
					$config[$key] = $key === "wgSmjDirectMath" ? self::mergeDirectMath( $value ) : $value;
				}
			}
		}
		return $config;
	}

	public static function renderMath( ?string $tex, array $args, Parser $parser, PPFrame $frame ) {
		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.SimpleMathJax' ] );

		// The chem attribute just preloads mhchem (a JS optimization, not a
		// rendering-behavior switch) and <chem>...</chem> always does the same
		// thing regardless of $wgSmjEnableRenderAttributes (see renderChem()
		// below), so <math chem> shouldn't be gated differently.
		if ( isset( $args["chem"] ) ) {
			$parserOutput->setJsConfigVar( "smjPreloadChem", true );
		}

		if ( !self::$enableRenderAttributes ) {
			// display= and inline-block are SimpleMathJax's own render-control
			// attributes; when this setting is off they're ignored entirely, same as
			// if they'd never been written on the <math> tag.
			$args = array_diff_key( $args, array_flip( [ "display", "inline-block" ] ) );
		}
		if ( isset( $args["inline-block"] ) ) {
			if ( isset( $args["display"] ) ) {
				return self::renderError( 'SimpleMathJax: inline-block and display cannot be used together.' );
			}
			$tex = "\\displaystyle{ $tex }";
		} elseif ( !isset( $args["display"] ) ) {
			if ( self::$displaystyle ) {
				$tex = "\\displaystyle{ $tex }";
			}

		} else {
			switch ( $args["display"] ) {
				case "":
					break;
				case "inline":
					$tex = "\\textstyle{ $tex }";
					break;
				case "block":
					break;
				case "linebreak":
					// Same block layout as "block", but also turns on MathJax's
					// automatic line-breaking for this page (chtml.displayOverflow,
					// see ext.SimpleMathJax.js — a MathJax 4+ feature) for equations
					// too wide for their container, matching Math extension's
					// display="linebreak".
					$parserOutput->setJsConfigVar( "smjLinebreak", true );
					break;
				default:
					return self::renderError( 'SimpleMathJax: invalid display="' . $args["display"] . '"' );
			}
		}

		return self::renderTex( $tex, $parser, $args );
	}

	public static function renderChem( ?string $tex, array $args, Parser $parser, PPFrame $frame ) {
		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.SimpleMathJax' ] );
		$parserOutput->setJsConfigVar( "smjPreloadChem", true );

		return self::renderTex( "\\ce{ $tex }", $parser, $args );
	}

	private static function renderTex( ?string $tex, Parser $parser, array $args ) {
		$hookContainer    = MediaWikiServices::getInstance()->getHookContainer();
		$attributes       = [ "style" => "opacity:.5", "class" => "" ];
		$allTags          = [ "class", "id", "title", "lang", "dir" ];
		$inherit_tags     = array_intersect( $allTags, self::$allowedAttributes );
		$validatedAttribs = Sanitizer::validateAttributes( $args, array_fill_keys( $inherit_tags, true ) );
		$attributes       = array_merge( $attributes, $validatedAttribs );

		$hookContainer->run( "SimpleMathJaxAttributes", [ &$attributes, $tex, $args ] );
		if ( !isset( $attributes["smj-debug"] ) && !isset( $args["smj-debug"] ) ) {
			$attributes["class"] .= " smj-container";
		}

		if ( isset( $args["display"] ) && in_array( $args["display"], [ "block", "linebreak" ], true ) ) {
			$element = Html::Element( "span", $attributes, "\\begin{displaymjx}{$tex}\\end{displaymjx}" );
		} else {
			$element = Html::Element( "span", $attributes, "[math]{$tex}[/math]" );
		}
		return [ $element, 'markerType' => 'nowiki' ];
	}

	private static function renderError( string $str ) {
		$attributes = [ "class" => "error texerror" ];
		$element    = Html::Element( "strong", $attributes, $str );
		return [ $element, 'markerType' => 'nowiki' ];
	}

	public static function onInternalParseBeforeLinks( $parser, &$text, $stripState ) {
		if ( !self::$directMath['enabled'] ) {
			return;
		}

		$text = Quotes::protectQuotesInMath(
			$text,
			static function ( $run ) use ( $parser ) {
				return $parser->insertStripItem( $run );
			},
			self::$directMath['inlineMath'],
			self::$directMath['displayMath'],
			true,
			true
		);
	}
}
