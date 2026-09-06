The SimpleMathJax extension enables MathJax, a Javascript library, for typesetting TeX formula in MediaWiki inside math environments. It requires MediaWiki 1.43 or later.

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
| Setting name             | Description                      | Default value             | Custom value example        |
| ------------------------ | -------------------------------- | ------------------------- | --------------------------- |
| `$wgSmjUseCdn`           | use CDN or local scripts         | true                      | false                       |
| `$wgSmjEnableRenderAttributes` | process SimpleMathJax's own render-control attributes on <math> (`display=`, `inline-block`) | true | false |
| `$wgSmjAllowedAttributes` | list of generic HTML attributes to carry over to the output `<span>` | `[]` | `['class', 'title']` |
| `$wgSmjDirectMath`       | direct (bare `$...$`) math scanning — `enabled`, `inlineMath`, `displayMath` | `['enabled'=>false,`<br>`'inlineMath'=>[],`<br>`'displayMath'=>[]]` | `['enabled'=>true,`<br>`'inlineMath'=>[['$','$']],`<br>`'displayMath'=>[['$$','$$']]]` |
| `$wgSmjEnableMenu`       | MathJax.options.enableMenu       | true                      | false                       |
| `$wgSmjIgnoreHtmlClass`  | MathJax.options.ignoreHtmlClass  | "mathjax_ignore\|comment\|<br>diff-(context\|<br>addedline\|deletedline)" | "mathjax_ignore" |
| `$wgSmjScale`            | MathJax.chtml.scale              | 1                         | 1.5                         |
| `$wgSmjDisplayAlign`     | MathJax.chtml.displayAlign       | "left"                    | "center"                    |
| `$wgSmjDisplaystyle`     | render bare `<math>` (no `display=`) at display-style size | false | true |
| `$wgSmjRevisionOverrides` | switch the configuration according to the article's revision  | [] | [['max'=>1048576,<br>'wgSmjDisplayAlign'<br>=>'left']] |

See [docs/config.md](docs/config.md) for a detailed walkthrough of each
setting, with usage examples. Upgrading from before 1.0.0? Settings were
redesigned with no backward compatibility — the same document's
"Migrating from pre-1.0.0" section covers what to change in your
`LocalSettings.php`.

# Hooks
The hook `SimpleMathJaxAttributes` is available to add attributes to the span around the math. (Note that this process is performed only for `<math>` and `<chem>` elements, and other delimiters are handled directly by MathJax.) This hook provides you with the opportunity to ensure that your own code does not interfere with MathJax's rendering of math.

For instance, if Lingo's JS functions are called before MathJax is invoked, then it is possible that Lingo will change the text so that MathJax could no longer render the math.

Lingo understands that [it should not touch anything inside an element with the class `noglossary`](https://www.mediawiki.org/wiki/Extension:Lingo#Excluding_text_from_markup) so the following code can be used to keep Lingo from ruining math:
```PHP
$wgHooks['SimpleMathJaxAttributes'][]
	= function ( array &$attributes, string $tex, array $args = [] ) {
		$attributes['class'] .= ' noglossary';
	};
```
