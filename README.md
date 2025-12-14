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

* LocalSettings.php
```PHP
wfLoadExtension( 'SimpleMathJax' );
```

# Optional Settings
| Setting name             | Description                      | default value             | custom value example        |
| ------------------------ | -------------------------------- | ------------------------- | --------------------------- |
| `$wgSmjUseCdn`           | use CDN or local scripts         | true                      | false                       |
| `$wgSmjUseChem`          | enable chem tag                  | true                      | false                       |
| `$wgSmjEnableMenu`       | MathJax.options.enableMenu       | true                      | false                       |
| `$wgSmjDisplayMath`      | MathJax.tex.displayMath          | []                        | [['$$','$$'],['\\[','\\]']] |
| `$wgSmjExtraInlineMath`  | MathJax.tex.inlineMath           | []                        | [['\\(', '\\)']]            |
| `$wgSmjIgnoreHtmlClass`  | MathJax.options.ignoreHtmlClass  | "mathjax_ignore\|comm..." | "mathjax_ignore"            |
| `$wgSmjScale`            | MathJax.chtml.scale              | 1                         | 1.5                         |
| `$wgSmjDisplayAlign`     | MathJax.chtml.displayAlign       | center                    | left                        |
| `$wgSmjWrapDisplaystyle` | wrap with displaystyle           | true                      | false                       |
| `$wgEnableHtmlAttributes` | process attributes of math tag  | false                     | true                        |

If you want to change font size, set `$wgSmjScale`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjScale = 1.5;
```

If you want to use local module, set `$wgSmjUseCdn`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjUseCdn = false;
```

If you want to enable some extra inlineMath symbol pairs, set `$wgSmjExtraInlineMath`. Pairs of `[math][/math]` are always in-line math delimiters. (And independently of this setting, you can use `$ ... $` to switch to math mode within chemical formulas in the mhchem extension.)
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjExtraInlineMath = [["$","$"],["\\(","\\)"]];
```

Since version 0.8.7, inlineMath and blockMath and environments are ignored in edit summaries and diffs. To restore the previous behavior (especially if you are using maths in edit summaries), set `$wgSmjIgnoreHtmlClass`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjIgnoreHtmlClass = "mathjax_ignore";
```

If you want to disable MathJax context menu, set `$wgSmjEnableMenu`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjEnableMenu = false;
```

Since version 0.8.8, by enabling `$wgEnableHtmlAttributes`, the `display` attribute of the `<math>` tag will work, and the `class` and `id` attributes of the `<math>` tag will be carried over to the `<span>` tag.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgEnableHtmlAttributes = true;
```

# Hooks
The hook `SimpleMathJaxAttributes` is available to add attributes to the span around the math. (Note that this process is performed only for `<math>` elements, and other delimiters are handled directly by MathJax.) This hook provides you with the opportunity to ensure that your own code does not interfere with MathJax's rendering of math.

For instance, if Lingo's JS functions are called before MathJax is invoked, then it is possible that Lingo will change the text so that MathJax could no longer render the math.

Lingo understands that [it should not touch anything inside an element with the class `noglossary`](https://www.mediawiki.org/wiki/Extension:Lingo#Excluding_text_from_markup) so the following code can be used to keep Lingo from ruining math:
```PHP
$wgHooks['SimpleMathJaxAttributes'][]
	= function ( array &$attributes, string $tex ) {
		$attributes['class'] .= ' noglossary';
	};
```

## Contributors

<!-- readme: collaborators,contributors -start -->
<table>
	<tbody>
		<tr>
            <td align="center">
                <a href="https://github.com/jmnote">
                    <img src="https://avatars.githubusercontent.com/u/2242405?v=4" width="48;" alt="jmnote"/>
                    <br />
                    <sub><b>jmnote</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/jamesmontalvo3">
                    <img src="https://avatars.githubusercontent.com/u/716482?v=4" width="48;" alt="jamesmontalvo3"/>
                    <br />
                    <sub><b>James Montalvo</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/hexmode">
                    <img src="https://avatars.githubusercontent.com/u/43581?v=4" width="48;" alt="hexmode"/>
                    <br />
                    <sub><b>Mark A. Hershberger</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/dummy-index">
                    <img src="https://avatars.githubusercontent.com/u/3407906?v=4" width="48;" alt="dummy-index"/>
                    <br />
                    <sub><b>dummy-index</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/lakejason0">
                    <img src="https://avatars.githubusercontent.com/u/36039861?v=4" width="48;" alt="lakejason0"/>
                    <br />
                    <sub><b>lakejason0</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/badshah400">
                    <img src="https://avatars.githubusercontent.com/u/3532467?v=4" width="48;" alt="badshah400"/>
                    <br />
                    <sub><b>Atri Bhattacharya</b></sub>
                </a>
            </td>
		</tr>
		<tr>
            <td align="center">
                <a href="https://github.com/Nikerabbit">
                    <img src="https://avatars.githubusercontent.com/u/1109395?v=4" width="48;" alt="Nikerabbit"/>
                    <br />
                    <sub><b>Niklas Laxström</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/cubercsl">
                    <img src="https://avatars.githubusercontent.com/u/22931465?v=4" width="48;" alt="cubercsl"/>
                    <br />
                    <sub><b>cubercsl</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/liberaldev">
                    <img src="https://avatars.githubusercontent.com/u/56965274?v=4" width="48;" alt="liberaldev"/>
                    <br />
                    <sub><b>Liberal Dev</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/Adnn">
                    <img src="https://avatars.githubusercontent.com/u/3911163?v=4" width="48;" alt="Adnn"/>
                    <br />
                    <sub><b>Adnn</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/dexgs">
                    <img src="https://avatars.githubusercontent.com/u/93449583?v=4" width="48;" alt="dexgs"/>
                    <br />
                    <sub><b>Dexter Gaon-Shatford</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/pastakhov">
                    <img src="https://avatars.githubusercontent.com/u/1772774?v=4" width="48;" alt="pastakhov"/>
                    <br />
                    <sub><b>Pavel Astakhov</b></sub>
                </a>
            </td>
		</tr>
		<tr>
            <td align="center">
                <a href="https://github.com/v-gar">
                    <img src="https://avatars.githubusercontent.com/u/11472697?v=4" width="48;" alt="v-gar"/>
                    <br />
                    <sub><b>Viktor Garske</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/rickselby">
                    <img src="https://avatars.githubusercontent.com/u/1564517?v=4" width="48;" alt="rickselby"/>
                    <br />
                    <sub><b>Rick Selby</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/guyru">
                    <img src="https://avatars.githubusercontent.com/u/1255135?v=4" width="48;" alt="guyru"/>
                    <br />
                    <sub><b>guyru</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/poiega">
                    <img src="https://avatars.githubusercontent.com/u/110189813?v=4" width="48;" alt="poiega"/>
                    <br />
                    <sub><b>poiega</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/vedmaka">
                    <img src="https://avatars.githubusercontent.com/u/592009?v=4" width="48;" alt="vedmaka"/>
                    <br />
                    <sub><b>Vedmaka</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/yardenac">
                    <img src="https://avatars.githubusercontent.com/u/687943?v=4" width="48;" alt="yardenac"/>
                    <br />
                    <sub><b>Yardena Cohen</b></sub>
                </a>
            </td>
		</tr>
	<tbody>
</table>
<!-- readme: collaborators,contributors -end -->
