The SimpleMathJax extension enables MathJax, a Javascript library, for typesetting TeX formula in MediaWiki inside math environments.

https://www.mediawiki.org/wiki/Extension:SimpleMathJax


| Setting name             | Default value                                       | Description                             |
| ------------------------ | --------------------------------------------------- | --------------------------------------- |
| `$wgSimpleMathJaxSize`   | 100                                                 | The default font size for SimpleMathJax |
| `$wgSimpleMathJaxChem`   | false                                               | enable `<chem>` tag                     |
| `$wgSimpleMathJaxJs`     | "//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS-MML_HTMLorMML" | URL path of Math.js        |
| `$wgSimpleMathJaxChemJs` | "//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/extensions/TeX/mhchem.js"                | URL path of mhchem.js      |

```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
```

To change font size, set `$wgSimpleMathJaxSize`.
```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
$wgSimpleMathJaxSize = 110;
```

To use chem tag, set `$wgSimpleMathJaxChem`.
```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
$wgSimpleMathJaxSize = 120;
$wgSimpleMathJaxChem = true;
```

To use local scripts, git submodule update and set variables.
```Bash
$ cd /path/to/extensions/SimpleMathJax
$ git submodule init
$ git submodule update
```
```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
$wgSimpleMathJaxSize = 115;
$wgSimpleMathJaxChem = true;
$wgSimpleMathJaxJs = "{$wgScriptPath}/extensions/SimpleMathJax/MathJax/MathJax.js?config=TeX-AMS-MML_HTMLorMML";
$wgSimpleMathJaxChemJs = "{$wgScriptPath}/extensions/SimpleMathJax/MathJax-mhchem/mhchem.js";
```
