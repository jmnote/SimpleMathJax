# Development

## Updating MathJax

The `mathjax` make target updates both the bundled local MathJax submodule
and the CDN version in `extension.json`.

Run the `mathjax` Make target from the repository root:

```bash
make mathjax
```

The versions are configured in the `Makefile`: `MATHJAX_VERSION_LOCAL`
defaults to `4.1.3`, and `MATHJAX_VERSION_CDN` defaults to `4`.

Change these values in the `Makefile` when changing the project's default
versions, or override them on the command line for a one-off update.

## Rendering internals

A few things in `includes/Hooks.php`/`resources/ext.SimpleMathJax.js` aren't
obvious from the code alone:

**`\displaystyle{}`/`\textstyle{}` wrapping happens in `renderTex()`, not in
`renderMath()`.** It's only a rendering-mode default guess (no `display`
attribute means block-like `\displaystyle`, `display="inline"` means
`\textstyle`) — not part of what the editor wrote — so `renderTex()` applies
it itself, gated by the `$mathTag` flag (`<chem>` never gets it) and skipped
entirely when the element is ignored (see below), since an ignored element
is never typeset and would otherwise show that wrapping as literal,
meaningless text.

**An ignored element (`class` matching `$wgSmjIgnoreHtmlClass`) skips both
`smj-container` and the `[math]...[/math]`/`\begin{displaymjx}...` wrapping,
and drops the `opacity:.5` style.** `smj-container` is also listed in the JS
module's `processHtmlClass`, which makes MathJax typeset an element even if
it (or an ancestor) matches `ignoreHtmlClass` — needed so ordinary `<math>`
tags still render inside a diff/comment wrapper. That same override would
defeat an editor deliberately opting one `<math>` out via a class matching
`$wgSmjIgnoreHtmlClass` (e.g. `class="mathjax_ignore"`), so `renderTex()`
skips adding it in that case. And since an ignored element is never
typeset, `opacity:.5` and the delimiter wrapping — both only meaningful as a
placeholder MathJax is expected to replace — would otherwise permanently
show a half-transparent, undelimited blob of TeX source instead of the
plain text an editor expects.

**`renderMath()` sets `smjPreloadChem` unconditionally for `<math chem>`,
not gated by `display`.** The `chem` attribute only preloads the mhchem
package (a JS optimization); it's unrelated to display handling, and
`<chem>...</chem>` itself always behaves the same way regardless of
display (see `renderChem()`), so there's no reason to gate `<math chem>`
differently.

**`applyRevisionOverrides()` is a free function, not inlined into
`onParserFirstCallInit()`.** That's so it can be unit-tested as a pure
function without a MediaWiki bootstrap — see `tests/RevisionOverridesTest.php`.

**`ext.SimpleMathJax.js` always adds `['[math]','[/math]']` to MathJax's
`inlineMath` list, regardless of `$wgSmjExtraDelimiters*`.** That's
SimpleMathJax's own internal marker for the `<math>`/`<chem>` output
`renderTex()` wraps TeX in (see above), not wikitext syntax an editor would
type themselves — MathJax needs it in its delimiter list to typeset that
output at all, independently of whatever bare delimiters the admin
configured for extra-delimiter scanning.

**`$wgSmjIgnoreHtmlClass`'s diff/comment protection only matters with
`$wgSmjExtraDelimitersEnabled` on.** `$wgSmjExtraDelimitersEnabled` also
decides how much of the page MathJax scans: off (the default), the JS
module's `elements` option restricts it to its own `<span
class="smj-container">` output, so a diff or comment is never a target
regardless of `ignoreHtmlClass`; on, `elements` is `null` and MathJax scans
the whole page instead, including a diff table's literal text — that's what
the default `ignoreHtmlClass` pattern protects against. `<math>`/`<chem>`
tags are never at risk in a diff either way, since MediaWiki shows diffs as
raw, unparsed wikitext — only bare delimiters can leak through there.

**`matchesIgnoreHtmlClass()` picks its own `preg_match()` delimiter instead of
hardcoding `~`.** `$wgSmjIgnoreHtmlClass` is an admin-supplied regex fragment
with no delimiter of its own, so a hardcoded `~` would break for any pattern
that contains one; the function uses `~` unless the pattern contains it, in
which case it falls back to `\x01`, a control character unlikely to appear in
a class-matching regex. A failed/invalid pattern is treated as "does not
match" rather than suppressed with `@`, so a broken regex surfaces instead of
silently misbehaving.

## Quote protection

A few things in `includes/Quotes.php` aren't obvious from the code alone:

**`protectQuotesInMath()` re-indexes each delimiter pair with `array_values()`
before reading `$open`/`$close`.** List-assignment (`[ $open, $close ] = $pair`)
reads keys 0 and 1 by position, not by order of insertion, so a pair with
non-sequential or associative keys (e.g. `[ 1 => '$', 2 => '$' ]`) would
otherwise leave `$open` undefined and emit a warning before the pair is
filtered out below.
