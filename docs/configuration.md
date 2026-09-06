# Configuration

Full reference for every `$wgSmj*` setting, plus the `SimpleMathJaxAttributes`
hook for extending it in code. The quick-reference table in the
[README](../README.md#optional-settings) links here for full explanations
and examples. Upgrading from before 1.0.0? See the
[migration guide](mig-1.0.md) instead.

## Settings reference

| Setting name             | Default value | Description |
| ------------------------ | ------------- | ----------- |
| `$wgSmjCdnEnabled`       | `true` | Whether to load MathJax from a CDN instead of the bundled local copy |
| `$wgSmjCdnVersion`       | `'4'` | MathJax version to load from the CDN |
| `$wgSmjScale`            | `1` | `MathJax.chtml.scale` |
| `$wgSmjEnableMenu`       | `true` | `MathJax.options.enableMenu` |
| `$wgSmjDelimitersEnabled` | `false` | Whether to also scan for bare delimiters (e.g. `$...$`) outside `<math>`/`<chem>` |
| `$wgSmjDelimitersInlineMath` | `[]` | Inline math delimiter pairs, e.g. `[['$','$']]` |
| `$wgSmjDelimitersDisplayMath` | `[]` | Display math delimiter pairs, e.g. `[['$$','$$']]` |
| `$wgSmjAllowedAttributes` | `[]` | List of generic HTML attributes to carry over to the output `<span>` |
| `$wgSmjIgnoreHtmlClass`  | `'mathjax_ignore\|comment\|`<br>`diff-(context\|`<br>`addedline\|deletedline)'` | `MathJax.options.ignoreHtmlClass` |
| `$wgSmjRevisionOverrides` | `[]` | Switch the configuration according to the article's revision |

### `$wgSmjCdnEnabled`

The default follows the latest 4.x release. To pin the CDN to an exact
version, set it explicitly:

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjCdnVersion = '4.1.3';
```

To use the local MathJax module, disable the CDN.

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjCdnEnabled = false;
```

When working from the Git repository, initialize or update the bundled
MathJax submodule with `make local-mathjax` — see
[Updating MathJax](development.md#updating-mathjax) for version
selection. Normal extension packages already include the bundled MathJax
resources and do not require Git commands.

### `$wgSmjScale`

`$wgSmjScale` is passed straight through as MathJax's own `chtml.scale`
option — a multiplier on the default font size, where `1` is 100% (the
default) and, say, `1.5` is 150%. Any positive number works; there's no
built-in minimum or maximum.

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjScale = 1.5;
```

### `$wgSmjEnableMenu`

If you want to disable MathJax context menu, set `$wgSmjEnableMenu`.

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjEnableMenu = false;
```

### `$wgSmjDelimitersEnabled`

By default, `$wgSmjDelimitersEnabled` is `false`, so only TeX wrapped in
`<math>` or `<chem>` is recognized — bare `$...$`/`$$...$$` delimiters are
left as plain text, and `$wgSmjDelimitersInlineMath`/`DisplayMath` go
unused (as does [`$wgSmjIgnoreHtmlClass`](#wgsmjignorehtmlclass)'s
diff/comment protection, since there's nothing for it to protect against).
Set `Enabled` to `true` and list the delimiter pairs yourself to also
recognize bare delimiters:

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDelimitersEnabled = true;
$wgSmjDelimitersInlineMath = [ [ '$', '$' ] ];
$wgSmjDelimitersDisplayMath = [ [ '$$', '$$' ] ];
```

### `$wgSmjAllowedAttributes`

`$wgSmjAllowedAttributes` is the administrator-defined allow-list of generic
HTML attributes to copy from `<math>`/`<chem>` to the output `<span>`. No
attributes are copied by default; add the attribute names you want. Values
are still passed through MediaWiki's attribute sanitizer. A common case is
wanting a CSS styling hook plus a hover tooltip, without carrying over `id`
(which must be unique on the page, so an editor-supplied one can collide with
an anchor link (`#section`) or another element/JS gadget):

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjAllowedAttributes = [ 'class', 'title' ];
```

`display` and `chem` are unrelated to this list and always work regardless —
see [Display styles](displaystyle.md).

### `$wgSmjIgnoreHtmlClass`

You probably don't need to change this — the default already covers what it
needs to:

```
mathjax_ignore|comment|diff-(context|addedline|deletedline)
```

`mathjax_ignore` lets an editor skip one `<math>`/`<chem>` by adding that
class, if `class` is allowed via
[`$wgSmjAllowedAttributes`](#wgsmjallowedattributes) (empty by default).
`comment` and `diff-(context|addedline|deletedline)` match what MediaWiki
puts on edit-summary and diff-table elements, protecting them — though only
if [`$wgSmjDelimitersEnabled`](#wgsmjextradelimitersenabled) is on (see
[Rendering internals](development.md#rendering-internals) for why).

**Don't** replace the whole pattern with just your own class when extra
delimiters are on — the default's diff/comment protection goes with it:

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDelimitersEnabled = true;
// Don't: replaces the whole pattern, so it loses diff/comment protection.
$wgSmjIgnoreHtmlClass = 'my_custom_class';
```

**Do**, only if you really need your own class, extend the default with `|`
instead — and add `class` to `$wgSmjAllowedAttributes` so it survives on
individual `<math class="my_custom_class">` elements:

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDelimitersEnabled = true;
// Do, if you really need it: extends the default pattern instead of replacing it.
$wgSmjIgnoreHtmlClass = 'mathjax_ignore|comment|diff-(context|addedline|deletedline)|my_custom_class';
$wgSmjAllowedAttributes = [ 'class' ];
```

### `$wgSmjRevisionOverrides`

`$wgSmjRevisionOverrides` applies different settings to different ranges of
an article's revisions. This doesn't happen on its own — a past revision only
keeps rendering with the settings that were in effect when it was current if
you map that revision range to those settings yourself, as below. Each entry
is an array whose other keys are the overrides to apply, plus:

- A `min` and/or a `max` (the revision id bounds it applies to; either end
  is open if omitted). Both bounds are inclusive. Revision ids increase
  across the whole wiki, not per page — check the page's history for the
  actual numbers rather than guessing from its edit count.
- Only the keys written inside `[]` are overwritten — the rest of your
  `$wgSmj*` settings stay as they are.
- When more than one entry matches the same revision, later entries in the
  list win — each one overwrites whatever the ones before it set for the
  same key.
- Preview, History and Special pages are treated as revision `0`, a base
  case where no entry ever applies, so they always render with the plain
  `$wgSmj*` settings instead.

```php
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDelimitersEnabled = false;    // To match the preview with the actual rendering, write the latest settings in the base case
$wgSmjRevisionOverrides = [
	[ 'max' => 50000, 'wgSmjDelimitersEnabled' => true ],
	[ 'min' => 50001, 'max' => 60000, 'wgSmjDelimitersEnabled' => false ],
];
```

## Hooks

The hook `SimpleMathJaxAttributes` is available to add attributes to the span around the math. (Note that this process is performed only for `<math>` and `<chem>` elements, and other delimiters are handled directly by MathJax.) This hook provides you with the opportunity to ensure that your own code does not interfere with MathJax's rendering of math.

For instance, if Lingo's JS functions are called before MathJax is invoked, then it is possible that Lingo will change the text so that MathJax could no longer render the math.

Lingo understands that [it should not touch anything inside an element with the class `noglossary`](https://www.mediawiki.org/wiki/Extension:Lingo#Excluding_text_from_markup) so the following code can be used to keep Lingo from ruining math:

```php
$wgHooks['SimpleMathJaxAttributes'][]
	= function ( array &$attributes, string $tex, array $args = [] ) {
		$attributes['class'] .= ' noglossary';
	};
```
