<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'SimpleMathJax' );
	wfWarn(
		'Deprecated PHP entry point used for SimpleMathJax extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the SimpleMathJax extension requires MediaWiki 1.25+' );
}
