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

$pattern = 'mathjax_ignore|comment|diff-(context|addedline|deletedline)';

assert_same(
	'matchesIgnoreHtmlClass matches an exact single class',
	true,
	Hooks::matchesIgnoreHtmlClass( $pattern, 'comment' )
);
assert_same(
	'matchesIgnoreHtmlClass matches one token among several classes',
	true,
	Hooks::matchesIgnoreHtmlClass( $pattern, 'foo comment bar' )
);
assert_same(
	'matchesIgnoreHtmlClass does not match a class that merely contains the pattern as a substring',
	false,
	Hooks::matchesIgnoreHtmlClass( $pattern, 'commentary' )
);
assert_same(
	'matchesIgnoreHtmlClass does not match a class with the pattern as a prefix of a longer token',
	false,
	Hooks::matchesIgnoreHtmlClass( $pattern, 'diff-contextual' )
);
assert_same(
	'matchesIgnoreHtmlClass does not match a class with the pattern as a suffix of a longer token',
	false,
	Hooks::matchesIgnoreHtmlClass( $pattern, 'notmathjax_ignoreable' )
);
assert_same(
	'matchesIgnoreHtmlClass does not match an unrelated class list',
	false,
	Hooks::matchesIgnoreHtmlClass( $pattern, 'foo bar' )
);
assert_same(
	'matchesIgnoreHtmlClass does not match an empty class',
	false,
	Hooks::matchesIgnoreHtmlClass( $pattern, '' )
);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) FAILED.\n";
exit( $failures === 0 ? 0 : 1 );
