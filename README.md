The SimpleMathJax extension enables MathJax, a Javascript library, for typesetting TeX formula in MediaWiki inside math environments. It requires MediaWiki 1.43 or later.

https://www.mediawiki.org/wiki/Extension:SimpleMathJax


# Installation
* git clone in extensions directory
* Using CDN is recommended. Because it's much faster than using local resources in most cases. ("the benefits of using a CDN")
```bash
$ git clone https://github.com/jmnote/SimpleMathJax.git
```

* (Optional) If you want to use not CDN but local mathjax scripts, you can use git clone recursive, then set `$wgSmjCdn = [ 'enabled' => false ]` (see [`$wgSmjCdn`](docs/configuration.md#wgsmjcdn)).
```bash
$ git clone --recursive https://github.com/jmnote/SimpleMathJax.git
```

* LocalSettings.php
```php
wfLoadExtension( 'SimpleMathJax' );
```

# Optional Settings
| Setting name             | Default value              | Description                      | Custom value example        |
| ------------------------ | --------------------------- | -------------------------------- | --------------------------- |
| `$wgSmjCdn`              | `['enabled'=>true, 'version'=>'4']` | MathJax CDN settings             | `['enabled'=>false]` |
| `$wgSmjScale`            | `1`                         | `MathJax.chtml.scale`              | `1.5`                         |
| `$wgSmjEnableMenu`       | `true`                      | `MathJax.options.enableMenu`       | `false`                       |
| `$wgSmjExtraDelimiters`       | `['enabled'=>false,`<br>`'inlineMath'=>[],`<br>`'displayMath'=>[]]` | Extra delimiter scanning outside `<math>`/`<chem>` (e.g. bare `$...$`) — `enabled`, `inlineMath`, `displayMath` | `['enabled'=>true,`<br>`'inlineMath'=>[['$','$']],`<br>`'displayMath'=>[['$$','$$']]]` |
| `$wgSmjAllowedAttributes` | `[]` | List of generic HTML attributes to carry over to the output `<span>` | `['class', 'title']` |
| `$wgSmjIgnoreHtmlClass`  | `'mathjax_ignore\|comment\|`<br>`diff-(context\|`<br>`addedline\|deletedline)'` | `MathJax.options.ignoreHtmlClass`  | `'mathjax_ignore\|comment\|`<br>`diff-(context\|`<br>`addedline\|deletedline)\|my_custom_class'` |
| `$wgSmjRevisionOverrides` | `[]` | Switch the configuration according to the article's revision  | `[['max'=>1048576,`<br>`'wgSmjScale'=>1.5]]` |

See [docs/configuration.md](docs/configuration.md) for a detailed walkthrough of each
setting, with usage examples. Upgrading from before 1.0.0? See the
[migration guide](docs/mig-1.0.md) for what to change in your
`LocalSettings.php`.

See [docs/displaystyle.md](docs/displaystyle.md) for the `<math display>`
rendering modes and examples.

Need to keep another extension (e.g. Lingo) from interfering with MathJax's
rendering? See [Hooks](docs/configuration.md#hooks) in the configuration
guide for the `SimpleMathJaxAttributes` hook.
