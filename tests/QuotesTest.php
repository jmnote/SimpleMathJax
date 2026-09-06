<?php
use MediaWiki\Extension\SimpleMathJax\Quotes;

require __DIR__ . '/../includes/Quotes.php';

$failures = 0;

function protect_marker( $run ) {
	return '\x1' . $run . '\x2';
}

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

function run( $text, $inline = [], $display = [], $processEscapes = true, $protectEnvironments = true ) {
	return Quotes::protectQuotesInMath(
		$text, 'protect_marker', $inline, $display, $processEscapes, $protectEnvironments
	);
}

assert_same(
	'double prime inline',
	'a $y\x1\'\'\x2=1$ b',
	run( 'a $y\'\'=1$ b', [ [ '$', '$' ] ] )
);
assert_same(
	'triple prime inline',
	'a $f\x1\'\'\'\x2(x)$ b',
	run( "a \$f'''(x)\$ b", [ [ '$', '$' ] ] )
);

assert_same(
	'double prime display',
	'a $$y\x1\'\'\x2-xy=0$$ b',
	run( 'a $$y\'\'-xy=0$$ b', [], [ [ '$$', '$$' ] ] )
);

assert_same(
	'single prime untouched',
	'a $y\'=1$ b',
	run( 'a $y\'=1$ b', [ [ '$', '$' ] ] )
);

assert_same(
	'prose italics untouched',
	"a ''italic'' and '''bold''' b",
	run( "a ''italic'' and '''bold''' b", [ [ '$', '$' ] ] )
);

assert_same(
	'escaped dollar is not a delimiter',
	'a \\$y\'\'\\$ b',
	run( 'a \\$y\'\'\\$ b', [ [ '$', '$' ] ], [], true )
);

assert_same(
	'braced group hides inner $ from closing',
	'$a{$}b\x1\'\'\x2$',
	run( "\$a{\$}b''\$", [ [ '$', '$' ] ] )
);

assert_same(
	'environment protected',
	'\\begin{matrix}1&2\x1\'\'\x2\\end{matrix}',
	run( "\\begin{matrix}1&2''\\end{matrix}" )
);

assert_same(
	'nested same-name environment matches outer end',
	'\\begin{matrix}\\begin{matrix}1&2\x1\'\'\x2\\end{matrix}\\end{matrix} after\'\'',
	run( "\\begin{matrix}\\begin{matrix}1&2''\\end{matrix}\\end{matrix} after''" )
);

assert_same(
	'unbalanced dollar left untouched',
	"a \$y'' b",
	run( "a \$y'' b", [ [ '$', '$' ] ] )
);

assert_same(
	'multiple spans protected independently',
	'$a\x1\'\'\x2$ text $b\x1\'\'\'\x2$',
	run( "\$a''\$ text \$b'''\$", [ [ '$', '$' ] ] )
);

$start = microtime( true );
$result = run( "some ''text'' here", [ [ '', '' ] ] );
assert_same( 'empty delimiter pair does not hang', true, ( microtime( true ) - $start ) < 1.0 );
assert_same( 'empty delimiter pair is ignored, not protected', "some ''text'' here", $result );

assert_same(
	'malformed delimiter entries are skipped without fatal',
	'a $y\x1\'\'\x2$ b',
	run( 'a $y\'\'$ b', [ 'not-a-pair', [ '$' ], [ '$', '$' ] ] )
);

assert_same(
	'non-sequential-key delimiter pair is still read correctly (no warning)',
	'a $y\x1\'\'\x2$ b',
	run( 'a $y\'\'$ b', [ [ 1 => '$', 2 => '$' ] ] )
);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) FAILED.\n";
exit( $failures === 0 ? 0 : 1 );
