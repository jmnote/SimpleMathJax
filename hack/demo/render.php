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
//   render.php <path> <demo> addmenushot prints "true" if the demo has
//                                        `addMenuShot: true`, else nothing —
//                                        see screenshot.mjs's RIGHT_CLICK.
//
// Only this narrow shape is supported, not general YAML: a top-level
// sequence of demo items (`- name: <demo>`), each an optional `settings:`
// literal block scalar (`|`), an `examples:` block sequence of
// double-quoted scalars, and an optional `addMenuShot: true` plain scalar.
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
	foreach ( $lines as $line ) {
		if ( preg_match( '/^\s*-\s*"(.*)"\s*$/', $line, $m ) ) {
			$examples[] = json_decode( "\"{$m[1]}\"" );
		}
	}
	echo "<div style=\"display:flex;flex-wrap:wrap;gap:1em;\">\n\n";
	foreach ( $examples as $example ) {
		echo "<div class=\"mw-message-box\" style=\"flex:1 1 auto;min-width:240px;overflow-x:auto;\">\n";
		echo "<syntaxhighlight lang=\"tex\">\n$example\n</syntaxhighlight>\n\n";
		echo "$example\n</div>\n\n";
	}
	echo "</div>\n";
	exit;
}

if ( $mode === 'addmenushot' ) {
	foreach ( $lines as $line ) {
		if ( preg_match( '/^addMenuShot:\s*true\s*$/', $line ) ) {
			echo "true\n";
			break;
		}
	}
	exit;
}

fwrite( STDERR, "render.php: unknown mode '$mode' (want 'settings', 'examples' or 'addmenushot')\n" );
exit( 1 );
