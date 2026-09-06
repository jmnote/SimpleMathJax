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

// mergeDirectMath(): fills in defaults for whichever keys are missing.
assert_same(
	'mergeDirectMath fills defaults when nothing is set',
	[ 'enabled' => false, 'inlineMath' => [], 'displayMath' => [] ],
	Hooks::mergeDirectMath( null )
);
assert_same(
	'mergeDirectMath keeps unrelated defaults when only enabled is set',
	[ 'enabled' => true, 'inlineMath' => [], 'displayMath' => [] ],
	Hooks::mergeDirectMath( [ 'enabled' => true ] )
);
assert_same(
	'mergeDirectMath keeps all three when fully specified',
	[ 'enabled' => true, 'inlineMath' => [ [ '$', '$' ] ], 'displayMath' => [ [ '$$', '$$' ] ] ],
	Hooks::mergeDirectMath( [
		'enabled' => true,
		'inlineMath' => [ [ '$', '$' ] ],
		'displayMath' => [ [ '$$', '$$' ] ],
	] )
);

// applyRevisionOverrides(): no overrides, or revision 0 (e.g. Preview/History/SpecialPages), is a no-op.
$baseConfig = [ 'wgSmjScale' => 1, 'wgSmjDirectMath' => Hooks::mergeDirectMath( null ) ];
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

// A plain (non-dotted) key replaces the whole value for a matching revision range.
assert_same(
	'applyRevisionOverrides replaces a top-level key inside its range',
	[ 'wgSmjScale' => 2, 'wgSmjDirectMath' => Hooks::mergeDirectMath( null ) ],
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

// A dot-path key ('wgSmjDirectMath.enabled') reaches into an array-shaped
// setting and overrides only that one field, leaving its siblings alone.
assert_same(
	'applyRevisionOverrides supports a dot path into wgSmjDirectMath',
	[ 'wgSmjScale' => 1, 'wgSmjDirectMath' => [ 'enabled' => true, 'inlineMath' => [], 'displayMath' => [] ] ],
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'min' => 1, 'max' => 50000, 'wgSmjDirectMath.enabled' => true ] ],
		25000
	)
);

// An entry with neither 'min' nor 'max' has no way to match a revision, so
// it's ignored rather than applying unconditionally.
assert_same(
	'applyRevisionOverrides ignores an entry with no min or max',
	$baseConfig,
	Hooks::applyRevisionOverrides(
		$baseConfig,
		[ [ 'wgSmjScale' => 2 ] ],
		25000
	)
);

// A reversed range ('min' greater than 'max') describes an empty window —
// every revision is either above 'max' or below 'min', so it never matches.
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
