<?php

namespace MediaWiki\Extension\SimpleMathJax;

class Quotes {
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

		$starts = [];
		foreach ( array_merge( $displayDelims, $inlineDelims ) as $pair ) {
			if ( !is_array( $pair ) || count( $pair ) < 2 ) {
				continue;
			}
			// array_values() re-indexes from 0: a pair with non-sequential or
			// associative keys (e.g. [1 => '$', 2 => '$']) would otherwise
			// leave $open undefined (list-assignment reads keys 0/1 by
			// position) and emit a warning before being filtered below.
			[ $open, $close ] = array_values( $pair );
			if ( !is_string( $open ) || !is_string( $close ) || $open === '' || $close === '' ) {
				continue;
			}
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

			if ( $processEscapes && $ch === '\\' && $i + 1 < $len
				&& ( $text[$i + 1] === '\\' || $text[$i + 1] === '$' )
			) {
				$out .= $ch . $text[$i + 1];
				$i += 2;
				continue;
			}

			if ( $protectEnvironments && $ch === '\\' && substr( $text, $i, 7 ) === '\begin{' ) {
				$envEnd = strpos( $text, '}', $i + 7 );
				if ( $envEnd !== false ) {
					$env = substr( $text, $i + 7, $envEnd - $i - 7 );
					$open = '\\begin{' . $env . '}';
					$close = '\\end{' . $env . '}';
					$closed = self::closeSpan(
						$text, substr( $text, $i, $envEnd + 1 - $i ), $envEnd + 1, $close, $protect, $open );
					if ( $closed !== null ) {
						$out .= $closed[0];
						$i = $closed[1];
						continue;
					}
				}
			}

			$matched = false;
			foreach ( $starts as $open => $close ) {
				$oL = $openLengths[$open];
				if ( substr( $text, $i, $oL ) === $open ) {
					$closed = self::closeSpan( $text, $open, $i + $oL, $close, $protect );
					if ( $closed !== null ) {
						$out .= $closed[0];
						$i = $closed[1];
						$matched = true;
						break;
					}
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

	private static function closeSpan(
		string $text, string $prefix, int $contentStart, string $close, callable $protect, ?string $nestOpen = null
	): ?array {
		$end = self::findClose( $text, $contentStart, $close, $nestOpen );
		if ( $end === -1 ) {
			return null;
		}
		$contentLen = $end - $contentStart - strlen( $close );
		$replacement = $prefix
			. self::protectSpan( substr( $text, $contentStart, $contentLen ), $protect )
			. $close;
		return [ $replacement, $end ];
	}

	private static function findClose( string $text, int $from, string $close, ?string $nestOpen = null ): int {
		$len = strlen( $text );
		$cL = strlen( $close );
		$oL = $nestOpen !== null ? strlen( $nestOpen ) : 0;
		$braces = 0;
		$depth = 0;
		$i = $from;
		while ( $i + $cL <= $len ) {
			if ( substr( $text, $i, $cL ) === $close ) {
				if ( $braces === 0 ) {
					if ( $depth === 0 ) {
						return $i + $cL;
					}
					$depth--;
					$i += $cL;
					continue;
				}
				$i += $cL;
				continue;
			}
			if ( $braces === 0 && $nestOpen !== null && substr( $text, $i, $oL ) === $nestOpen ) {
				$depth++;
				$i += $oL;
				continue;
			}
			$ch = $text[$i];
			if ( $ch === '\\' ) {
				$i += 2;
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
