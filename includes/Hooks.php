<?php

namespace MediaWiki\Extension\SimpleMathJax;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use Parser;
use PPFrame;

class Hooks {
	private static array $allowedAttributes = [];
	private static bool $extraDelimitersEnabled = false;
	private static array $extraDelimitersInlineMath = [];
	private static array $extraDelimitersDisplayMath = [];
	private static string $ignoreHtmlClass = '';

	public static function onParserFirstCallInit( Parser $parser ) {
		global $wgOut, $wgSmjCdnEnabled, $wgSmjCdnVersion, $wgSmjEnableMenu,
		$wgSmjDelimitersEnabled, $wgSmjDelimitersInlineMath, $wgSmjDelimitersDisplayMath,
		$wgSmjIgnoreHtmlClass, $wgSmjScale,
		$wgSmjAllowedAttributes, $wgSmjRevisionOverrides;

		$config = [
			"wgSmjCdnEnabled"                  => $wgSmjCdnEnabled,
			"wgSmjCdnVersion"                  => $wgSmjCdnVersion,
			"wgSmjDelimitersEnabled"      => $wgSmjDelimitersEnabled,
			"wgSmjDelimitersInlineMath"   => $wgSmjDelimitersInlineMath,
			"wgSmjDelimitersDisplayMath"  => $wgSmjDelimitersDisplayMath,
			"wgSmjIgnoreHtmlClass"             => $wgSmjIgnoreHtmlClass,
			"wgSmjScale"                       => $wgSmjScale,
			"wgSmjEnableMenu"                  => $wgSmjEnableMenu,
			"wgSmjAllowedAttributes"           => $wgSmjAllowedAttributes,
		];

		$articlerev = (int)$wgOut->getRevisionId();
		$config = self::applyRevisionOverrides( $config, $wgSmjRevisionOverrides, $articlerev );

		$clientConfigVars = [ "wgSmjCdnEnabled", "wgSmjCdnVersion",
			"wgSmjDelimitersEnabled", "wgSmjDelimitersInlineMath", "wgSmjDelimitersDisplayMath",
			"wgSmjIgnoreHtmlClass", "wgSmjScale", "wgSmjEnableMenu" ];
		foreach ( $clientConfigVars as $varname ) {
			$wgOut->addJsConfigVars( $varname, $config[$varname] );
		}

		self::$allowedAttributes =
			is_array( $config["wgSmjAllowedAttributes"] ) ? $config["wgSmjAllowedAttributes"] : [];
		self::$extraDelimitersEnabled = (bool)$config["wgSmjDelimitersEnabled"];
		self::$extraDelimitersInlineMath =
			is_array( $config["wgSmjDelimitersInlineMath"] ) ? $config["wgSmjDelimitersInlineMath"] : [];
		self::$extraDelimitersDisplayMath =
			is_array( $config["wgSmjDelimitersDisplayMath"] ) ? $config["wgSmjDelimitersDisplayMath"] : [];
		self::$ignoreHtmlClass =
			is_string( $config["wgSmjIgnoreHtmlClass"] ) ? $config["wgSmjIgnoreHtmlClass"] : '';

		if ( self::$extraDelimitersEnabled ) {
			$wgOut->addModules( [ 'ext.SimpleMathJax' ] );
		}

		$parser->setHook( 'math', __CLASS__ . '::renderMath' );
		$parser->setHook( 'chem', __CLASS__ . '::renderChem' );
	}

	// $pattern is an admin-supplied regex fragment with no delimiter of its
	// own, so avoid one that could occur inside it. The pattern is anchored
	// to whole space-delimited class tokens, mirroring the
	// "(?:^| )(?:pattern)(?: |$)" boundary MathJax itself applies to its
	// own ignoreHtmlClass option (see resources/MathJax/core.js), so e.g.
	// "comment" matches class="comment" but not class="commentary".
	// Public (like applyRevisionOverrides below) so it's unit-testable
	// without a MediaWiki bootstrap.
	public static function matchesIgnoreHtmlClass( string $pattern, string $class ): bool {
		$delimiter = strpos( $pattern, '~' ) === false ? '~' : "\x01";
		$anchored = '(?:^| )(?:' . $pattern . ')(?: |$)';
		$result = preg_match( $delimiter . $anchored . $delimiter, $class );
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
				if ( array_key_exists( $key, $config ) ) {
					$config[$key] = $value;
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
			// Strip only the placeholder opacity this method defaults to
			// while MathJax hasn't run yet; an editor-supplied "style" (via
			// $wgSmjAllowedAttributes) already replaced that default above
			// and must be kept, since an ignored element is never typeset
			// and so never gets its opacity reset to 1 either.
			if ( !isset( $validatedAttribs["style"] ) ) {
				unset( $attributes["style"] );
			}
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
		if ( !self::$extraDelimitersEnabled ) {
			return;
		}

		$text = Quotes::protectQuotesInMath(
			$text,
			static function ( $run ) use ( $parser ) {
				return $parser->insertStripItem( $run );
			},
			self::$extraDelimitersInlineMath,
			self::$extraDelimitersDisplayMath,
			true,
			true
		);
	}
}
