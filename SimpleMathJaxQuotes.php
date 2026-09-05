<?php

/**
 * Protects apostrophe runs inside TeX math from MediaWiki's wikitext
 * emphasis parsing.
 *
 * MediaWiki turns '' and ''' in wikitext into italics/bold markup. Inside
 * MathJax-delimited math, however, '' and ''' are TeX primes (y'', f'''(x))
 * — the parser inserted <i>/<b> tags split the delimited text, so MathJax
 * can no longer find the closing delimiter and the math silently breaks
 * (dangling $ then swallows the surrounding prose as math).
 *
 * This scanner locates the SAME math spans MathJax will typeset (mirroring
 * MathJax v3's FindTeX: longest-first start delimiters, closing delimiter
 * search that skips braced groups and control sequences, and the
 * processEscapes \\ and \$ skip) and hands every run of 2+ apostrophes
 * inside them to a caller-supplied protector — in MediaWiki, a strip
 * marker via Parser::insertStripItem(), so the emphasis pass skips the
 * quotes and the literal '' is re-inserted into the final HTML for MathJax
 * to parse as primes.
 *
 * Pure PHP — no MediaWiki dependency; unit-testable standalone.
 *
 * @license GPL-2.0-or-later
 */
class SimpleMathJaxQuotes {

	/**
	 * Scan $text for math delimited by the given pairs and protect
	 * apostrophe runs inside each span.
	 *
	 * @param string $text wikitext/parser text to scan
	 * @param callable $protect callback(string $run): string — turns a run
	 *   of 2+ apostrophes into a place-holder that survives quote parsing
	 * @param array[] $inlineDelims list of [open, close] inline pairs
	 * @param array[] $displayDelims list of [open, close] display pairs
	 * @param bool $processEscapes honour \$ and \\ (MathJax processEscapes)
	 * @param bool $protectEnvironments also protect \begin{env}…\end{env}
	 * @return string text with protected spans replaced
	 */
	public static function protectQuotesInMath(
		string $text,
		callable $protect,
		array $inlineDelims,
		array $displayDelims,
		bool $processEscapes = true,
		bool $protectEnvironments = true
	): string {
		if ( $text === '' || ( $inlineDelims === [] && $displayDelims === [] && !$protectEnvironments ) ) {
			return $text;
		}

		// Longest start delimiters first ($$ before $, \[ before \() — the
		// MathJax sortLength ordering.
		$starts = [];
		foreach ( array_merge( $displayDelims, $inlineDelims ) as [ $open, $close ] ) {
			$starts[$open] = $close;
		}
		uksort( $starts, static function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		} );
		$openLengths = [];
		foreach ( array_keys( $starts ) as $open ) {
			$openLengths[$open] = strlen( $open );
		}

		$len = strlen( $text );
		$out = '';
		$i = 0;
		while ( $i < $len ) {
			$ch = $text[$i];

			// Escaped characters (processEscapes): \\ and \$ are consumed by
			// MathJax as literal text and never open or close math.
			if ( $processEscapes && $ch === '\\' && $i + 1 < $len
				&& ( $text[$i + 1] === '\\' || $text[$i + 1] === '$' )
			) {
				$out .= $ch . $text[$i + 1];
				$i += 2;
				continue;
			}

			// \begin{env}…\end{env} counts as math when processEnvironments
			// is on (MathJax typesets it without any $ delimiter).
			if ( $protectEnvironments && $ch === '\\' && substr( $text, $i, 7 ) === '\begin{' ) {
				$envEnd = strpos( $text, '}', $i + 7 );
				if ( $envEnd !== false ) {
					$env = substr( $text, $i + 7, $envEnd - $i - 7 );
					$close = '\\end{' . $env . '}';
					$end = self::findClose( $text, $envEnd + 1, $close );
					if ( $end !== -1 ) {
						// Content runs from after \begin{…} up to the start
						// of \end{…}; findClose returns just PAST the close.
						$contentLen = $end - $envEnd - 1 - strlen( $close );
						$out .= substr( $text, $i, $envEnd + 1 - $i );
						$out .= self::protectSpan(
							substr( $text, $envEnd + 1, $contentLen ), $protect );
						$out .= $close;
						$i = $end;
						continue;
					}
				}
			}

			// Try every configured start delimiter at this position.
			$matched = false;
			foreach ( $starts as $open => $close ) {
				$oL = $openLengths[$open];
				if ( substr( $text, $i, $oL ) === $open ) {
					$end = self::findClose( $text, $i + $oL, $close );
					if ( $end !== -1 ) {
						// Content runs from after the opener up to the start
						// of the closer; findClose returns just PAST it.
						$contentLen = $end - $i - $oL - strlen( $close );
						$out .= $open;
						$out .= self::protectSpan(
							substr( $text, $i + $oL, $contentLen ), $protect );
						$out .= $close;
						$i = $end;
						$matched = true;
						break;
					}
					// Unbalanced delimiter: MathJax ignores it; move past the
					// opener so we do not loop on it.
					$out .= substr( $text, $i, $oL );
					$i += $oL;
					$matched = true;
					break;
				}
			}
			if ( $matched ) {
				continue;
			}

			$out .= $ch;
			$i++;
		}

		return $out;
	}

	/**
	 * Find the closing delimiter starting at $from, skipping braced groups
	 * ({…}) and control sequences (backslash + one char) — the MathJax
	 * FindTeX::findEnd behaviour.
	 *
	 * @param string $text
	 * @param int $from
	 * @param string $close
	 * @return int index just past the closing delimiter, or -1
	 */
	private static function findClose( string $text, int $from, string $close ): int {
		$len = strlen( $text );
		$cL = strlen( $close );
		$braces = 0;
		$i = $from;
		while ( $i + $cL <= $len ) {
			if ( substr( $text, $i, $cL ) === $close ) {
				if ( $braces === 0 ) {
					return $i + $cL;
				}
				// Close delimiter inside a braced group is math content
				// (MathJax skips it and keeps scanning).
				$i += $cL;
				continue;
			}
			$ch = $text[$i];
			if ( $ch === '\\' ) {
				$i += 2; // control sequence or escape: backslash + one char
			} elseif ( $ch === '{' ) {
				$braces++;
				$i++;
			} elseif ( $ch === '}' ) {
				if ( $braces > 0 ) {
					$braces--;
				}
				$i++;
			} else {
				$i++;
			}
		}
		return -1;
	}

	/**
	 * Replace every run of 2+ apostrophes in $span via $protect. Single
	 * apostrophes are not wikitext emphasis and pass through untouched.
	 *
	 * @param string $span
	 * @param callable $protect
	 * @return string
	 */
	private static function protectSpan( string $span, callable $protect ): string {
		if ( $span === '' || strpos( $span, "''" ) === false ) {
			return $span;
		}
		return preg_replace_callback(
			"/'{2,}/",
			static function ( array $m ) use ( $protect ) {
				return $protect( $m[0] );
			},
			$span
		) ?? $span;
	}
}
