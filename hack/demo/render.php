<?php
// Renders one field of one demo out of docs/demos.yaml for demo.sh:
//   render.php <path> <demo> settings   prints `wfLoadExtension(
//                                        'SimpleMathJax' );` followed by the
//                                        demo's `settings:` literal block
//                                        (its $wgSmj* overrides only — every
//                                        demo needs the wfLoadExtension line,
//                                        so it isn't repeated in demos.yaml)
//                                        — raw PHP appended into
//                                        LocalSettings.php (see up()) and
//                                        shown on the demo page in a
//                                        <syntaxhighlight lang="php"> block.
//   render.php <path> <demo> examples   prints the demo's `examples:` list
//                                        as wikitext, each example as a
//                                        syntaxhighlight block next to its
//                                        live render, laid out in a
//                                        responsive flex row.
//   render.php <path> <demo> addrightclickshot prints "true" if the demo has
//                                        `addRightClickShot: true`.
//   render.php <path> <demo> adddiffshot prints "true" if the demo has
//                                        `addDiffShot: true`.
//
// Only this narrow shape is supported, not general YAML: a top-level
// sequence of demo items (`- name: <demo>`), each an optional `settings:`
// literal block scalar (`|`), an `examples:` block sequence whose items are
// either quoted scalars or their own `- |` literal block scalar, and an
// optional `addRightClickShot: true` / `addDiffShot: true` scalar.
// YAML's C-style escaping for \\, \" and \n inside a double-quoted scalar
// is a strict subset of JSON's, so each quoted example is unescaped by
// wrapping it in JSON quotes and handing it to json_decode — this project
// already requires php (see demo.sh's json_field()), so reusing it here
// avoids a YAML library dependency just for this.

[ , $path, $demo, $mode ] = $argv;

// Slice out $demo's sub-document: lines after its "- name: <demo>" item
// header up to the next column-0 "-" (the following item) or EOF, then
// dedent them by their common indent so the rest of this script can treat
// them exactly like a standalone file.
$lines = [];
$capturing = false;
foreach ( file( $path ) as $line ) {
	if ( !$capturing ) {
		if ( preg_match( '/^-\s*name:\s*' . preg_quote( $demo, '/' ) . '\s*$/', $line ) ) {
			$capturing = true;
		}
		continue;
	}
	if ( $line !== '' && $line[0] === '-' ) {
		break;
	}
	$lines[] = $line;
}
if ( !$capturing ) {
	fwrite( STDERR, "render.php: no demo named '$demo' found in $path\n" );
	exit( 1 );
}
$indent = null;
foreach ( $lines as $line ) {
	if ( trim( $line ) === '' ) {
		continue;
	}
	$lineIndent = strlen( $line ) - strlen( ltrim( $line ) );
	$indent = $indent === null ? $lineIndent : min( $indent, $lineIndent );
}
if ( $indent !== null ) {
	foreach ( $lines as &$line ) {
		if ( trim( $line ) !== '' ) {
			$line = substr( $line, $indent );
		}
	}
	unset( $line );
}

if ( $mode === 'settings' ) {
	$out = [];
	$inBlock = false;
	$indent = null;
	foreach ( $lines as $line ) {
		if ( !$inBlock ) {
			if ( preg_match( '/^settings:\s*\|\s*$/', $line ) ) {
				$inBlock = true;
			}
			continue;
		}
		if ( trim( $line ) === '' ) {
			$out[] = "\n";
			continue;
		}
		$lineIndent = strlen( $line ) - strlen( ltrim( $line ) );
		$indent ??= $lineIndent;
		if ( $lineIndent < $indent ) {
			break;
		}
		$out[] = substr( $line, $indent );
	}
	echo "wfLoadExtension( 'SimpleMathJax' );\n";
	if ( $out !== [] ) {
		echo rtrim( implode( '', $out ) ) . "\n";
	}
	exit;
}

if ( $mode === 'examples' ) {
	$examples = [];
	$count = count( $lines );
	for ( $i = 0; $i < $count; $i++ ) {
		$line = $lines[$i];
		if ( preg_match( '/^\s*-\s*"(.*)"\s*$/', $line, $m ) ) {
			$examples[] = json_decode( "\"{$m[1]}\"" );
			continue;
		}
		if ( preg_match( "/^\s*-\s*'(.*)'\s*$/", $line, $m ) ) {
			// YAML single-quoted scalars escape a literal apostrophe as ''.
			$examples[] = str_replace( "''", "'", $m[1] );
			continue;
		}
		// A `- |` literal block scalar: every following line indented more
		// than the "-" is taken verbatim (no quote-escaping) until indentation
		// drops back to the item's own level or lower, then dedented by its
		// own common indent and trailing blank lines clipped — long examples
		// (e.g. a multi-line continued fraction) read better this way than
		// escaped into one quoted line.
		if ( preg_match( '/^(\s*)-\s*\|\s*$/', $line, $m ) ) {
			$itemIndent = strlen( $m[1] );
			$blockLines = [];
			$blockIndent = null;
			for ( $i++; $i < $count; $i++ ) {
				$next = $lines[$i];
				if ( trim( $next ) === '' ) {
					// Each non-blank line below still carries its own
					// trailing "\n" from file(), so joining with '' (not a
					// "\n" glue) reproduces the source exactly — matching
					// how the settings-mode block above is joined.
					$blockLines[] = "\n";
					continue;
				}
				$nextIndent = strlen( $next ) - strlen( ltrim( $next ) );
				if ( $nextIndent <= $itemIndent ) {
					break;
				}
				$blockIndent ??= $nextIndent;
				$blockLines[] = substr( $next, $blockIndent );
			}
			$i--; // the for loop's own $i++ will land back on the line that broke us out
			$examples[] = rtrim( implode( '', $blockLines ) );
		}
	}
	// Column fragmentation ("column-count" below) makes the container its
	// own block formatting context, so the first item's own top margin (a
	// browser default on <pre>, which <syntaxhighlight> renders as) doesn't
	// collapse into the page above it the way it normally would — visible
	// as a gap above column 1 only, since a later column's break point
	// isn't a "start" and so never re-applies that margin. Pull the whole
	// block up by that amount to cancel it out.
	echo '<div style="column-count: 2; column-rule: 1px solid #ccc">';
	foreach ( $examples as $example ) {
		echo "<syntaxhighlight lang=\"wikitext\">$example</syntaxhighlight> $example\n";
	}
	echo '</div>';
	exit;
}

if ( $mode === 'addrightclickshot' ) {
	foreach ( $lines as $line ) {
		if ( preg_match( '/^addRightClickShot:\s*true\s*$/', $line ) ) {
			echo "true\n";
			break;
		}
	}
	exit;
}

if ( $mode === 'adddiffshot' ) {
	foreach ( $lines as $line ) {
		if ( preg_match( '/^addDiffShot:\s*true\s*$/', $line ) ) {
			echo "true\n";
			break;
		}
	}
	exit;
}

fwrite( STDERR, "render.php: unknown mode '$mode' (want 'settings', 'examples', 'addrightclickshot' or 'adddiffshot')\n" );
exit( 1 );
