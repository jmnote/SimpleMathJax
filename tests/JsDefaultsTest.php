<?php
// The client module falls back to its own defaults when the page HTML does not
// carry a setting (e.g. HTML cached before an upgrade). Keep those defaults in
// sync with extension.json.

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

$extension = json_decode( file_get_contents( __DIR__ . '/../extension.json' ), true );
$js = file_get_contents( __DIR__ . '/../resources/ext.SimpleMathJax.js' );

if ( !preg_match( '/var defaults = (\{.*?\});/s', $js, $m ) ) {
	echo "FAIL defaults object not found in ext.SimpleMathJax.js\n";
	exit( 1 );
}
// The object literal uses unquoted keys and single-quoted strings.
$json = preg_replace( '/^(\s*)(\w+):/m', '$1"$2":', $m[1] );
$json = preg_replace_callback( "/'((?:[^'\\\\]|\\\\.)*)'/", static function ( $s ) {
	return json_encode( stripcslashes( $s[1] ) );
}, $json );
$defaults = json_decode( $json, true );
assert_same( 'defaults object parses', true, is_array( $defaults ) );

// Every setting the module reads must have a default.
preg_match_all( "/config\\('(wgSmj\\w+)'\\)/", $js, $reads );
foreach ( array_unique( $reads[1] ) as $name ) {
	assert_same( "$name has a client default", true, array_key_exists( $name, $defaults ?? [] ) );
}

foreach ( $defaults ?? [] as $name => $value ) {
	$key = substr( $name, 2 );
	$expected = $extension['config'][$key]['value'] ?? null;
	assert_same( "$name default matches extension.json", $expected, $value );
}

// Legacy names are only for settings the migration guide lists as renamed.
if ( preg_match( '/var legacyNames = (\{.*?\});/s', $js, $m ) ) {
	preg_match_all( "/(\w+): '(\w+)'/", $m[1], $pairs, PREG_SET_ORDER );
	$guide = file_get_contents( __DIR__ . '/../docs/mig-1.0.md' );
	foreach ( $pairs as [ , $name, $legacy ] ) {
		assert_same( "$name has a client default", true, array_key_exists( $name, $defaults ?? [] ) );
		assert_same(
			"$legacy -> $name is a rename in docs/mig-1.0.md",
			1,
			preg_match( '/\| `\$' . $legacy . '` \| `\$' . $name . '` \|/', $guide )
		);
	}
}

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) FAILED.\n";
exit( $failures === 0 ? 0 : 1 );
