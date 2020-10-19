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
| Setting name            | Description                      | default value | example value               |
| ----------------------- | -------------------------------- | ------------- | --------------------------- |
| `$wgSmjUseCdn`          | use CDN or local scripts         | true          | false                       |
| `$wgSmjUseChem`         | enable chem tag                  | true          | false                       |
| `$wgSmjEnableMenu`      | MathJax.options.enableMenu       | true          | false                       |
| `$wgSmjDisplayMath`     | MathJax.tex.displayMath          | []            | [['$$','$$'],['\\[','\\]']] |
| `$wgSmjExtraInlineMath` | MathJax.tex.inlineMath           | []            | [['\\(', '\\)']]            |
| `$wgSmjScale`           | MathJax.chtml.scale              | 1             | 1.5                         |
| `$wgSmjDisplayAlign`    | MathJax.chtml.displayAlign       | center        | left                        |

If you want to change font size, set `$wgSmjSize`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjScale = 1.5;
```

If you want to use local module, set `$wgSmjUseCdn`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjUseCdn = false;
```

If you want to enable some extra inlineMath symbol pairs, set `$wgSmjExtraInlineMath`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjExtraInlineMath = [["$","$"],["\\(","\\)"]];
```

If you want to disable MathJax context menu, set `$wgSmjShowMathMenu`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjEnableMenu = false;
```
