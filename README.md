The SimpleMathJax extension enables MathJax, a Javascript library, for typesetting TeX formula in MediaWiki inside math environments.

https://www.mediawiki.org/wiki/Extension:SimpleMathJax


| Setting name                    | Default value                                       | Description                             |
| ------------------------------- | --------------------------------------------------- | --------------------------------------- |
| `$wgSimpleMathJaxSize`          | 100                                                 | The default font size for SimpleMathJax |
| `$wgSimpleMathJaxChem`          | false                                               | enable Chem tag                         |
| `$wgSimpleMathJaxMathJsUrlPath` | "//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1"                 | URL path of Math.js        |
| `$wgSimpleMathJaxChemJsUrlPath` | "//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/extensions/TeX"  | URL path of mhchem.js      |


```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
```

```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
$wgSimpleMathJaxSize = 110;
```

```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
$wgSimpleMathJaxSize = 120;
$wgSimpleMathJaxChem = true;
```

```PHP
require "$IP/extensions/SimpleMathJax/SimpleMathJax.php";
$wgSimpleMathJaxSize = 115;
$wgSimpleMathJaxMathJsUrlPath = "{$wgScriptPath}/extensions/SimpleMathJax/MathJax";
$wgSimpleMathJaxChemJsUrlPath = "{$wgScriptPath}/extensions/SimpleMathJax/MathJax-mhchem";
```
