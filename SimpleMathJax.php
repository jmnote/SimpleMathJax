<?php
/**
 * MediaWiki extension to render TeX
 * Installation instructions can be found on
 * http://www.mediawiki.org/wiki/Extension:SimpleMathJax
 *
 * @ingroup Extensions
 * @author Jmkim dot com
 * @license GNU Public License
 */
 
// Exit if called outside of MediaWiki
if( !defined( 'MEDIAWIKI' ) ) exit;

// Global Settings
$wgSimpleMathJaxSize = 100;
$wgExtensionCredits['parserhook'][] = array(
        'path'        => __FILE__,
        'name'        => 'SimpleMathJax',
        'version'     => '0.1.0',
        'author'      => 'Jmkim dot com',
        'description' => 'render TeX between <nowiki><math></nowiki> and <nowiki></math></nowiki>',
        'url'         => 'http://www.mediawiki.org/wiki/Extension:SimpleMathJax'
);

$wgHooks['BeforePageDisplay'][] = 'SimpleMathJax::loadJS';
$wgExtensionFunctions[] = 'SimpleMathJax::init';

class SimpleMathJax {
	static function init() {
	    global $wgParser;
		$wgParser->setHook( 'math', 'SimpleMathJax::render' );
	}

	static function render($tex) {
		return array("<span class='mathjax-wrapper'>[math]${tex}[/math]</span>", 'markerType'=>'nowiki');
	}
	
	static function loadJS(&$out, &$skin ) {
		global $wgSimpleMathJaxSize;
		$out->addScript( "<style>.MathJax_Display{display:inline !important;}
.mathjax-wrapper{font-size:${wgSimpleMathJaxSize}%;}</style>
<script type='text/x-mathjax-config'>MathJax.Hub.Config({displayAlign:'left',tex2jax:{displayMath:[['[math]','[/math]']]}})</script>
<script src='http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML'></script>" );
		return true;
	}
}
