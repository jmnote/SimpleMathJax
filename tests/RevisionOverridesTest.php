<?php
use MediaWiki\Extension\SimpleMathJax\Hooks;

require __DIR__ . '/../includes/Hooks.php';

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

$baseConfig = [ 'wgSmjScale' => 1, 'wgSmjCdnEnabled' => true, 'wgSmjExtraDelimitersEnabled' => false ];
assert_same(
	'applyRevisionOverrides is a no-op with no overrides configured',
	$baseConfig,
	Hooks::applyRevisionOverrides( $baseConfig, [], 12345 )
);
assert_same(
	'applyRevisionOverrides is a no-op for revision 0 (base case)',
	$baseConfig,
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'max' => 50000, 'wgSmjScale' => 2 ] ],
		0
	)
);

assert_same(
	'applyRevisionOverrides replaces a top-level key inside its range',
	[ 'wgSmjScale' => 2, 'wgSmjCdnEnabled' => true, 'wgSmjExtraDelimitersEnabled' => false ],
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'max' => 50000, 'wgSmjScale' => 2 ] ],
		40000
	)
);
assert_same(
	'applyRevisionOverrides skips an override outside its range',
	$baseConfig,
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'max' => 50000, 'wgSmjScale' => 2 ] ],
		60000
	)
);

assert_same(
	'applyRevisionOverrides sets an unrelated flat key without touching the rest',
	[ 'wgSmjScale' => 1, 'wgSmjCdnEnabled' => true, 'wgSmjExtraDelimitersEnabled' => true ],
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'min' => 1, 'max' => 50000, 'wgSmjExtraDelimitersEnabled' => true ] ],
		25000
	)
);

assert_same(
	'applyRevisionOverrides ignores an entry with no min or max',
	$baseConfig,
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'wgSmjScale' => 2 ] ],
		25000
	)
);

assert_same(
	'applyRevisionOverrides never matches a reversed min/max range',
	$baseConfig,
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'min' => 100, 'max' => 50, 'wgSmjScale' => 2 ] ],
		75
	)
);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) FAILED.\n";
exit( $failures === 0 ? 0 : 1 );
