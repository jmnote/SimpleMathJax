# Configuration

Full reference for every `$wgSmj*` setting, plus how to migrate from
before 1.0.0 (settings were redesigned then with no backward
compatibility — see ["Migrating from pre-1.0.0"](#migrating-from-pre-100)
below for what changed and why). The quick-reference table in the
[README](../README.md#optional-settings) links here for details.

## Settings reference

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

### `$wgSmjScale`

If you want to change font size, set `$wgSmjScale`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjScale = 1.5;
```

### `$wgSmjUseCdn`

If you want to use local module, set `$wgSmjUseCdn`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjUseCdn = false;
```

### `$wgSmjDirectMath`

If you want to enable some extra inline-math delimiter pairs, set `$wgSmjDirectMath['inlineMath']` (this also requires `$wgSmjDirectMath['enabled']` to be `true` — see below). Pairs of `[math][/math]` are always in-line math delimiters. (And independently of this setting, you can use `$ ... $` to switch to math mode within text (`\text{}` etc.) or chemical formulas (`\ce{}`).)
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDirectMath = [
	'enabled' => true,
	'inlineMath' => [["$","$"],["\\(","\\)"]],
];
```

By default, `$wgSmjDirectMath['enabled']` is `false`, so only TeX wrapped in `<math>` or `<chem>` is recognized — bare `$...$`/`$$...$$` delimiters are left as plain text (and `$wgSmjDirectMath['inlineMath']`/`['displayMath']`, and `$wgSmjIgnoreHtmlClass`, are meaningless in this mode). Set `$wgSmjDirectMath['enabled']` to `true` if you also want those bare delimiters recognized directly.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDirectMath = [ 'enabled' => true ];
```

### `$wgSmjIgnoreHtmlClass`

Since version 0.8.7, inlineMath and blockMath and environments are ignored in edit summaries and diffs. To restore the previous behavior (especially if you are using maths in edit summaries), set `$wgSmjIgnoreHtmlClass`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjIgnoreHtmlClass = "mathjax_ignore";
```

### `$wgSmjEnableMenu`

If you want to disable MathJax context menu, set `$wgSmjEnableMenu`.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjEnableMenu = false;
```

### `$wgSmjEnableRenderAttributes` / `$wgSmjAllowedAttributes`

`$wgSmjEnableRenderAttributes` (on by default) makes the `display` and `inline-block` attributes of the `<math>` tag work — these are SimpleMathJax's own attributes that control how the tag renders. Disable it if you'd rather those attributes were ignored. (The `chem` attribute is unrelated and always works, the same as `<chem>...</chem>` itself — see below.)

`display` accepts `inline` (force normal text size) or `block` (its own line, display-style size). `display="linebreak"` is the same as `block`, but also turns on MathJax's automatic line-breaking for the page, for equations too wide for their container — matching [Math extension's `display="linebreak"`](https://www.mediawiki.org/wiki/Extension:Math/Syntax#Top-level_syntax).
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjEnableRenderAttributes = false;
```

`$wgSmjAllowedAttributes` lists which generic HTML attributes of the `<math>`/`<chem>` tag are carried over to the output `<span>` tag — a subset of `class`, `id`, `title`, `lang`, `dir`. None are allowed by default; list the ones you want. A common case is wanting a CSS styling hook plus a hover tooltip, without carrying over `id` (which must be unique on the page, so an editor-supplied one can collide with an anchor link (`#section`) or another element/JS gadget):
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjAllowedAttributes = [ 'class', 'title' ];
```

### `$wgSmjDisplaystyle`

By default, inline `<math>` without an explicit `display=` attribute renders at normal text size. Set `$wgSmjDisplaystyle` to `true` if you'd rather it default to the larger "displaystyle" look (bigger fractions, bigger subscripts) — the same look `display="block"` always gets:
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDisplaystyle = true;
```

### `$wgSmjRevisionOverrides`

By using `$wgSmjRevisionOverrides`, you can apply different settings to an article's revisions up to a certain point and to revisions after that. This allows past revisions to be displayed with the settings that were in place at the time. Each entry needs a `min` and/or a `max` (the revision id bounds it applies to, either end open if omitted); the rest of its keys are the overrides to apply. Only the keys written inside the [] are overwritten; to reach into an array-shaped setting like `$wgSmjDirectMath`, use a dot path (`'wgSmjDirectMath.enabled'`). Preview, History and SpecialPages are treated as base cases that do not apply this setting.
```PHP
wfLoadExtension( 'SimpleMathJax' );
$wgSmjDirectMath = [ 'enabled' => false ];    #To match the preview with the actual rendering, write the latest settings in the base case
$wgSmjRevisionOverrides = [
	["max"=>50000, "wgSmjDirectMath.enabled"=>true],
	["min"=>50001, "max"=>60000, "wgSmjDirectMath.enabled"=>false]
];
```

## Migrating from pre-1.0.0

1.0.0 redesigns the `$wgSmj*` settings with no backward compatibility, to
fix names and defaults that no longer matched what they actually did (see
the per-setting notes below for specifics). This section covers what
admins with an existing `LocalSettings.php` need to change. The settings
stay one flat `$wgSmj*` global per option, just like
before — there's no new nested structure to learn, other than
`$wgSmjDirectMath` which already existed as an array-ish concept split
across three old settings.

Before upgrading, find every `$wgSmj*` line in your `LocalSettings.php` and
follow the table below. **Anything left unchanged is silently ignored** —
the old names are no longer read in 1.0.0.

> ⚠️ **This is not a pure rename.** Two defaults actually flip:
> - Bare `$...$`/`$$...$$` math scanning now defaults to *off* (see
>   `$wgSmjDirectMath` above). If you never explicitly set
>   `$wgSmjDirectMathJax`/`$wgSmjExtraInlineMath`/`$wgSmjDisplayMath` and
>   relied on their old default (`'full'`), those formulas **stop
>   rendering** unless you add `$wgSmjDirectMath = [ 'enabled' => true ]`.
> - Inline `<math>` without a `display=` attribute now renders at normal
>   text size by default instead of the larger "displaystyle" look (see
>   `$wgSmjDisplaystyle` above). If you never touched
>   `$wgSmjWrapDisplaystyle`, your inline formulas **get smaller** unless
>   you add `$wgSmjDisplaystyle = true`.

### Renamed only

| Before 1.0.0 | 1.0.0 |
| --- | --- |
| `$wgSmjConfigByRevision` | `$wgSmjRevisionOverrides` |
| `$wgSmjWrapDisplaystyle` | `$wgSmjDisplaystyle` (name only — **default also changes**, see above) |

The overall structure is unchanged, but each entry's own `upto`/`since`
bounds are renamed to `max`/`min`:

```diff
- $wgSmjConfigByRevision = [ [ 'upto' => 1048576, 'wgSmjDisplayAlign' => 'left' ] ];
+ $wgSmjRevisionOverrides = [ [ 'max' => 1048576, 'wgSmjDisplayAlign' => 'left' ] ];
```

If `$wgSmjRevisionOverrides` overrides fields inside `$wgSmjDirectMath`,
also check the "dot paths" section below.

### `$wgSmjUseChem` removed — `<chem>` is always registered now

There was never a real reason to turn `<chem>` off: it's the same tag name
MediaWiki's own [Math extension](https://www.mediawiki.org/wiki/Extension:Math/Syntax)
uses for chemical formulas, so a wiki that wants chemistry markup at all
wants this specific tag, and a wiki running both Math and SimpleMathJax
was never workable anyway — they'd already collide on `<math>` itself,
which has no such toggle. If you had `$wgSmjUseChem = false;`, just remove
the line; `<chem>` now behaves like `<math>` and is not configurable.

### Unchanged

These are unchanged in name, type, and meaning — nothing to do:

`$wgSmjUseCdn`, `$wgSmjEnableMenu`, `$wgSmjIgnoreHtmlClass`, `$wgSmjScale`,
`$wgSmjDisplayAlign`

### `$wgSmjEnableHtmlAttributes` split into two, and one becomes a list

Before 1.0.0, this single boolean controlled **all** attribute processing
on `<math>`/`<chem>` tags — not just generic HTML attributes like
`id`/`class`/`title`/`lang`/`dir`, but also SimpleMathJax's own
rendering-control attributes like `display="block"`, `inline-block`, and
`chem`. (`style` was never one of the passthrough attributes — see
[includes/Hooks.php:140](../includes/Hooks.php#L140).)

As of 1.0.0 these are split, and the HTML-attribute side becomes an
allow-list instead of an on/off switch (see the reference section above
for both).

**If this affects you**: if your site set `$wgSmjEnableHtmlAttributes =
false;` before 1.0.0, that also silently disabled `<math display="block">`
and friends. In 1.0.0, `$wgSmjEnableRenderAttributes` defaults to `true`, so
those attributes **start working again** — fine if that's what you
actually want, but if you meant to keep them off too, add it explicitly:

```diff
- $wgSmjEnableHtmlAttributes = false;
+ $wgSmjAllowedAttributes = [];          // no HTML attribute passthrough: unchanged (this is the default, so it can be omitted)
+ $wgSmjEnableRenderAttributes = false;  // add this to also keep display=/inline-block disabled (chem is unaffected — see above)
```

If your site set `$wgSmjEnableHtmlAttributes = true;`, the equivalent is
listing all five names explicitly:

```diff
- $wgSmjEnableHtmlAttributes = true;
+ $wgSmjAllowedAttributes = [ 'class', 'id', 'title', 'lang', 'dir' ];
```

While you're here, consider narrowing the list instead of listing all
five — e.g. `[ 'class', 'title' ]` covers the common case of a CSS styling
hook plus a hover tooltip, without carrying over `id` (which can collide
with other elements on the page).

### `$wgSmjDirectMathJax` / `$wgSmjDisplayMath` / `$wgSmjExtraInlineMath` → `$wgSmjDirectMath`

These three settings are merged into one array, and the `"full"`/`"env"`/
`"none"` three-way mode collapses into a single boolean (`enabled`).
**The `"env"` mode is gone** — it used to let you turn `\ref`/escape
handling on or off separately, but turning escapes off only made stray
`\$`/`\\` more likely to be misread as delimiters, and turning `\ref` off
did nothing for sites that didn't use it — so there was never a real
reason to pick it over `"full"`. It's folded into `enabled`.

**The default also flips, from on to off.** `$wgSmjDirectMathJax` used to
default to `'full'` (bare `$...$` math worked out of the box).
`$wgSmjDirectMath`'s default is now `['enabled' => false, 'inlineMath' =>
[], 'displayMath' => []]` — only `<math>`/`<chem>` tags work out of the box,
matching this extension's original contract ("TeX between `<math>` and
`</math>`"). This is a real behavior change, not just a rename: **if you
never touched these settings, you are affected.**

```diff
- // (nothing set — relied on the old default, 'full')
+ $wgSmjDirectMath = [ 'enabled' => true ];   // keep bare $...$/$$...$$ working after the upgrade
```

```diff
- $wgSmjDirectMathJax = 'full';
+ $wgSmjDirectMath = [ 'enabled' => true ];
```

```diff
- $wgSmjExtraInlineMath = [ [ '$', '$' ] ];
- $wgSmjDisplayMath = [ [ '$$', '$$' ] ];
+ $wgSmjDirectMath = [
+     'enabled' => true,
+     'inlineMath' => [ [ '$', '$' ] ],
+     'displayMath' => [ [ '$$', '$$' ] ],
+ ];
```

```diff
- $wgSmjDirectMathJax = 'none';
+ // (nothing needed — the new default already behaves like 'none')
```

```diff
- $wgSmjDirectMathJax = 'env';
+ $wgSmjDirectMath = [ 'enabled' => true ];   // \ref and escapes now come along too — fine for most sites
```

### If `$wgSmjRevisionOverrides` overrode an array-shaped setting

To override a field inside `$wgSmjDirectMath` per revision range, use a dot
path:

```diff
  $wgSmjRevisionOverrides = [
-     [ 'upto' => 2000000, 'wgSmjDirectMathJax' => 'none' ],
+     [ 'max' => 2000000, 'wgSmjDirectMath.enabled' => false ],
  ];
```

### Checklist

1. Rename `$wgSmjConfigByRevision` → `$wgSmjRevisionOverrides`, and rename
   each entry's `upto`/`since` bounds to `max`/`min`. `$wgSmjUseCdn` is
   unchanged, nothing to do there. Remove `$wgSmjUseChem` if you had it —
   `<chem>` is always registered now.
2. If you had `$wgSmjEnableHtmlAttributes` set to anything, split it into
   `$wgSmjEnableRenderAttributes` (bool) and `$wgSmjAllowedAttributes`
   (list) — `[]` for the old `false`, or list all five names
   (`['class', 'id', 'title', 'lang', 'dir']`) for the old `true`.
3. **If you want bare `$...$`/`$$...$$` math to keep working**, add
   `$wgSmjDirectMath = [ 'enabled' => true ]` explicitly — this is now
   required even if you never set `$wgSmjDirectMathJax` before, since the
   default flipped from on to off. If you used
   `$wgSmjDisplayMath`/`$wgSmjExtraInlineMath`, fold their values into the
   same array (`displayMath`/`inlineMath` keys). If you used `'env'`, be
   aware it now behaves like `'full'`.
4. **If you want inline `<math>` to keep rendering at the old, larger
   size**, add `$wgSmjDisplaystyle = true` explicitly — the default
   flipped from on to off, same caveat as step 3.
5. If `$wgSmjConfigByRevision` overrode `$wgSmjDirectMathJax` or another
   value that's now inside `$wgSmjDirectMath`, switch to a dot path.
