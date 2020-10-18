The SimpleMathJax extension enables MathJax, a Javascript library, for typesetting TeX formula in MediaWiki inside math environments.

https://www.mediawiki.org/wiki/Extension:SimpleMathJax


# Installation
* git clone in extensions directory
* Using CDN is recommended. Because it's much faster than using local resources in most cases. ("the benefits of using a CDN")
```Bash
$ git clone https://github.com/jmnote/SimpleMathJax.git
```

* (Optional) If you want to use not CDN but local mathjax scripts, you can use git clone recursive.
```Bash
$ git clone --recursive https://github.com/jmnote/SimpleMathJax.git
```

* LocalSetting.php
```PHP
wfLoadExtension( 'SimpleMathJax' );
```

# Optional Settings
| Setting name            | Default value | Description                      |
| ----------------------- | ------------- | -------------------------------- |
| `$wgSmjUseCDN`          | true          | use CDN or local scripts         |
| `$wgSmjUseChem`         | true          | enable chem tag                  |
| `$wgSmjEnableMenu`      | true          | MathJax.options.enableMenu       |
| `$wgSmjDisplayMath`     | []            | MathJax.tex.displayMath          |
| `$wgSmjExtraInlineMath` | []            | MathJax.tex.inlineMath           |
| `$wgSmjScale`           | 1             | MathJax.chtml.scale              |

If you want to change font size, set `$wgSmjSize`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjSize = 150;
```

If you want to use local module, set `$wgSmjUseCDN`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjUseCDN = false;
```

If you want to enable some additional inlineMath symbol pairs, set `$wgSmjInlineMath`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjInlineMath = [["$","$"],["\\(","\\)"]];
```

If you want to enable MathJax context menu, set `$wgSmjShowMathMenu`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjShowMathMenu = true;
```
