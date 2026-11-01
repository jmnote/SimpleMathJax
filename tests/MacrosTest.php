<?php
// Ensure ext.SimpleMathJax.js macros are syntactically valid and balanced.

$failures = 0;

function assert_same( $name, $expected, $actual ) {
	global $failures;
	if ( $expected === $actual ) {
		echo "ok   $name\n";
		return;
	}
	$failures++;
	echo "FAIL $name\n";
	echo '  expected: ' . var_export( $expected, true ) . "\n";
	echo '  actual:   ' . var_export( $actual, true ) . "\n";
}

$js = file_get_contents( __DIR__ . '/../resources/ext.SimpleMathJax.js' );

if ( !preg_match( '/macros:\s*(\{.*?\n\s{6}\})/s', $js, $m ) ) {
	echo "FAIL macros object not found in ext.SimpleMathJax.js\n";
	exit( 1 );
}

$lines = explode( "\n", $m[1] );
$macros = [];
foreach ( $lines as $line ) {
	if ( preg_match( '/^\s*([A-Za-z0-9_]+):\s*(.+?),?\s*$/', $line, $lm ) ) {
		$k = $lm[1];
		$v = trim( $lm[2] );
		$macros[$k] = $v;
	}
}

assert_same( 'macros parsed', true, count( $macros ) > 0 );

// Verify PR #87 fixes
assert_same( 'Bbb macro does not wrap in outer group', '"\\\\mathbb"', $macros['Bbb'] ?? null );
assert_same( 'bold macro does not wrap in outer group', '"\\\\mathbf"', $macros['bold'] ?? null );
assert_same( 'vline macro has valid smash lvert syntax', '"{\\\\smash{\\\\large\\\\lvert}}"', $macros['vline'] ?? null );

// All macros have balanced braces
$unbalanced = [];
foreach ( $macros as $name => $def ) {
	if ( substr_count( $def, '{' ) !== substr_count( $def, '}' ) ) {
		$unbalanced[] = "$name: $def";
	}
}
assert_same( 'all macros have balanced braces', [], $unbalanced );

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) FAILED.\n";
exit( $failures === 0 ? 0 : 1 );
