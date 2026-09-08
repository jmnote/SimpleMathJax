<?php
// Renders one field of one demo out of docs/demo-screenshots.yaml for demo.sh:
//   render.php <path> <demo> settings           the demo's settings: block as
//                                                PHP appended into LocalSettings.php
//   render.php <path> <demo> examples            the demo's examples: list as wikitext
//   render.php <path> <demo> addrightclickshot   "true" if addRightClickShot: true
//   render.php <path> <demo> adddiffshot         "true" if addDiffShot: true
//
// Only this narrow YAML shape is supported: a top-level sequence of demo
// items (`- name: <demo>`), each an optional `settings:` literal block
// scalar (`|`), an `examples:` block sequence of quoted or `- |` block
// scalars, and optional `addRightClickShot:`/`addDiffShot:` booleans.

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
		// A `- |` literal block scalar, for long examples (e.g. a
		// multi-line continued fraction) that read better unescaped than
		// squeezed into one quoted line.
		if ( preg_match( '/^(\s*)-\s*\|\s*$/', $line, $m ) ) {
			$itemIndent = strlen( $m[1] );
			$blockLines = [];
			$blockIndent = null;
			for ( $i++; $i < $count; $i++ ) {
				$next = $lines[$i];
				if ( trim( $next ) === '' ) {
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
	// column-count fragmentation would otherwise collapse the first
	// example's top margin into the page above it; this cancels that out.
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
