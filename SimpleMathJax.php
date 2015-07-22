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
        'version'     => '0.3',
        'author'      => 'Jmkim dot com',
        'description' => 'render TeX between <nowiki><math></nowiki> and <nowiki></math></nowiki>',
        'url'         => '//www.mediawiki.org/wiki/Extension:SimpleMathJax'
);

// Register class
$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['SimpleMathJax'] = $dir.'SimpleMathJax.class.php';

$wgHooks['BeforePageDisplay'][] = 'SimpleMathJax::loadJS';
$wgExtensionFunctions[] = 'SimpleMathJax::init';
