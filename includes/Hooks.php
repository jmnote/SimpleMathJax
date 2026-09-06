<?php

namespace MediaWiki\Extension\SimpleMathJax;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use Parser;
use PPFrame;

class Hooks {
	private const EXTRA_DELIMITERS_DEFAULTS = [
		'enabled' => false,
		'inlineMath' => [],
		'displayMath' => [],
	];

	private static array $allowedAttributes = [];
	private static array $extraDelimiters   = self::EXTRA_DELIMITERS_DEFAULTS;
	private static string $ignoreHtmlClass  = '';

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjCdn, $wgSmjEnableMenu,
		$wgSmjExtraDelimiters, $wgSmjIgnoreHtmlClass,
		$wgSmjScale,
		$wgSmjAllowedAttributes, $wgSmjRevisionOverrides;

		$config = [
			"wgSmjCdn"               => $wgSmjCdn,
			"wgSmjExtraDelimiters"   => self::mergeExtraDelimiters( $wgSmjExtraDelimiters ),
			"wgSmjIgnoreHtmlClass"   => $wgSmjIgnoreHtmlClass,
			"wgSmjScale"             => $wgSmjScale,
			"wgSmjEnableMenu"        => $wgSmjEnableMenu,
			"wgSmjAllowedAttributes" => $wgSmjAllowedAttributes,
		];

		$articlerev = (int)$wgOut->getRevisionId();
		$config = self::applyRevisionOverrides( $config, $wgSmjRevisionOverrides, $articlerev );

		$clientConfigVars = [ "wgSmjCdn", "wgSmjExtraDelimiters",
			"wgSmjIgnoreHtmlClass", "wgSmjScale", "wgSmjEnableMenu" ];
		foreach ( $clientConfigVars as $varname ) {
			$wgOut->addJsConfigVars( $varname, $config[$varname] );
		}

		self::$allowedAttributes =
			is_array( $config["wgSmjAllowedAttributes"] ) ? $config["wgSmjAllowedAttributes"] : [];
		self::$extraDelimiters   = $config["wgSmjExtraDelimiters"];
		self::$ignoreHtmlClass   =
			is_string( $config["wgSmjIgnoreHtmlClass"] ) ? $config["wgSmjIgnoreHtmlClass"] : '';

		if ( self::$extraDelimiters['enabled'] ) {
			$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		}

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		$parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}

	public static function mergeExtraDelimiters( $value ): array {
		return array_merge( self::EXTRA_DELIMITERS_DEFAULTS, is_array( $value ) ? $value : [] );
	}

	// $pattern is an admin-supplied regex fragment with no delimiter of its
	// own, so avoid one that could occur inside it.
	private static function matchesIgnoreHtmlClass( string $pattern, string $class ): bool {
		$delimiter = strpos( $pattern, '~' ) === false ? '~' : "\x01";
		$result = preg_match( $delimiter . $pattern . $delimiter, $class );
		return $result === 1;
	}

	// Apply $wgSmjRevisionOverrides on top of $config for the given revision id.
	// A free function so it's unit-testable without a MediaWiki bootstrap.
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
					$config[$key] = $key === "wgSmjExtraDelimiters" ? self::mergeExtraDelimiters( $value ) : $value;
				}
			}
		}
		return $config;
	}

	public static function renderMath( ?string $tex, array $args, Parser $parser, PPFrame $frame ) {
		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.SimpleMathJax' ] );

		// Unconditional: this only preloads the mhchem JS package, unrelated
		// to display handling.
		if ( isset( $args["chem"] ) ) {
			$parserOutput->setJsConfigVar( "smjPreloadChem", true );
		}

		if ( isset( $args["display"] ) && !in_array( $args["display"], [ "", "inline", "block" ], true ) ) {
			return self::renderError( 'SimpleMathJax: invalid display="' . $args["display"] . '"' );
		}

		// renderTex() applies \displaystyle{}/\textstyle{} itself, since it's
		// only a default guess, not part of what the editor wrote.
		return self::renderTex( $tex, $parser, $args, true );
	}

	public static function renderChem( ?string $tex, array $args, Parser $parser, PPFrame $frame ) {
		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.SimpleMathJax' ] );
		$parserOutput->setJsConfigVar( "smjPreloadChem", true );

		// Wrapping happens inside renderTex(), not here, so an ignored
		// element (see below) shows the editor's original TeX rather than
		// the \ce{} wrapper meant for MathJax.
		return self::renderTex( $tex, $parser, $args, false, true );
	}

	private static function renderTex(
		?string $tex, Parser $parser, array $args, bool $mathTag, bool $wrapChem = false
	) {
		$hookContainer    = MediaWikiServices::getInstance()->getHookContainer();
		$attributes       = [ "style" => "opacity:.5", "class" => "" ];
		$allowedAttributes = array_filter( self::$allowedAttributes, 'is_string' );
		$validatedAttribs  = Sanitizer::validateAttributes(
			$args,
			array_fill_keys( $allowedAttributes, true )
		);
		$attributes       = array_merge( $attributes, $validatedAttribs );

		$hookContainer->run( "SimpleMathJaxAttributes", [ &$attributes, $tex, $args ] );
		// An ignored element is never typeset, so it skips smj-container and
		// the delimiter wrapping instead of showing them as literal text.
		$isIgnored = self::$ignoreHtmlClass !== ''
			&& self::matchesIgnoreHtmlClass( self::$ignoreHtmlClass, $attributes["class"] );
		if ( $isIgnored ) {
			unset( $attributes["style"] );
			$element = Html::Element( "span", $attributes, $tex );
		} else {
			if ( !isset( $attributes["smj-debug"] ) && !isset( $args["smj-debug"] ) ) {
				$attributes["class"] .= " smj-container";
			}
			if ( $wrapChem ) {
				$tex = "\\ce{ $tex }";
			}
			if ( $mathTag ) {
				if ( !isset( $args["display"] ) ) {
					$tex = "\\displaystyle{ $tex }";
				} elseif ( $args["display"] === "inline" ) {
					$tex = "\\textstyle{ $tex }";
				}
			}
			$element = isset( $args["display"] ) && $args["display"] === "block"
				? Html::Element( "span", $attributes, "\\begin{displaymjx}{$tex}\\end{displaymjx}" )
				: Html::Element( "span", $attributes, "[math]{$tex}[/math]" );
		}
		return [ $element, 'markerType' => 'nowiki' ];
	}

	private static function renderError( string $str ) {
		$attributes = [ "class" => "error texerror" ];
		$element    = Html::Element( "strong", $attributes, $str );
		return [ $element, 'markerType' => 'nowiki' ];
	}

	public static function onInternalParseBeforeLinks( $parser, &$text, $stripState ) {
		if ( !self::$extraDelimiters['enabled'] ) {
			return;
		}

		$text = Quotes::protectQuotesInMath(
			$text,
			static function ( $run ) use ( $parser ) {
				return $parser->insertStripItem( $run );
			},
			self::$extraDelimiters['inlineMath'],
			self::$extraDelimiters['displayMath'],
			true,
			true
		);
	}
}
