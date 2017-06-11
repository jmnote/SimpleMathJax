The SimpleMathJax extension enables MathJax, a Javascript library, for typesetting TeX formula in MediaWiki inside math environments.

https://www.mediawiki.org/wiki/Extension:SimpleMathJax


# Installation
* git clone recursive in extensions directory
```Bash
$ git clone --recursive https://github.com/jmnote/SimpleMathJax.git
```
* LocalSetting.php
```PHP
wfLoadExtension( 'SimpleMathJax' );
```

# Variables
| Setting name             | Default value                                       | Description                             |
| ------------------------ | --------------------------------------------------- | --------------------------------------- |
| `$wgSimpleMathJaxSize`   | 125                                                 | The default font size for SimpleMathJax |

If you want to change font size, set `$wgSimpleMathJaxSize`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSimpleMathJaxSize = 150;
```
