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

# Optional Settings
| Setting name                 | Default value           | Description                                   |
| ---------------------------- | ----------------------- | --------------------------------------------- |
| `$wgSimpleMathJaxSize`       | 125                     | font size                                     |
| `$wgSimpleMathJaxUseCDN`     | true                    | use CDN or local files                        |
| `$wgSimpleMathJaxInlineMath` | []                      | add some additional inlineMath symbols pairs  |

If you want to change font size, set `$wgSimpleMathJaxSize`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSimpleMathJaxSize = 150;
```

If you want to use local module, set `$wgSimpleMathJaxUseCDN`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSimpleMathJaxUseCDN = false;
```

If you want to enable some additional inlineMath symbol pairs, set `$wgSimpleMathJaxInlineMath`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSimpleMathJaxInlineMath = [["$","$"],["\\(","\\)"]];
```
