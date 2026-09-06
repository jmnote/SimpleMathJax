# Migrating from pre-1.0.0

1.0.0 redesigns the `$wgSmj*` settings with no backward compatibility, to
fix names and defaults that no longer matched what they actually did (see
the per-setting notes below for specifics). This section covers what
admins with an existing `LocalSettings.php` need to change. The settings
stay one flat `$wgSmj*` global per option, just like
before — there's no new nested structure to learn, other than
`$wgSmjExtraDelimiters` which already existed as an array-ish concept split
across three old settings.

Before upgrading, find every `$wgSmj*` line in your `LocalSettings.php` and
follow the table below. **Anything left unchanged is silently ignored** —
the old names are no longer read in 1.0.0.

> ⚠️ **This is not a pure rename.** Two defaults actually flip:
> - Bare `$...$`/`$$...$$` math scanning now defaults to *off* (see
>   `$wgSmjExtraDelimiters` above). If you never explicitly set
>   `$wgSmjDirectMathJax`/`$wgSmjExtraInlineMath`/`$wgSmjDisplayMath` and
>   relied on their old default (`'full'`), those formulas **stop
>   rendering** unless you add `$wgSmjExtraDelimiters = [ 'enabled' => true ]`.

### Renamed only

| Before 1.0.0 | 1.0.0 |
| --- | --- |
| `$wgSmjUseCdn` | `$wgSmjCdn` |
| `$wgSmjConfigByRevision` | `$wgSmjRevisionOverrides` |

The overall structure is unchanged, but each entry's own `upto`/`since`
bounds are renamed to `max`/`min`:

```diff
- $wgSmjConfigByRevision = [ [ 'upto' => 1048576, 'wgSmjScale' => 1 ] ];
+ $wgSmjRevisionOverrides = [ [ 'max' => 1048576, 'wgSmjScale' => 1 ] ];
```

If `$wgSmjRevisionOverrides` overrides fields inside `$wgSmjExtraDelimiters`,
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

`$wgSmjIgnoreHtmlClass`, `$wgSmjScale`, `$wgSmjEnableMenu`

### `$wgSmjEnableHtmlAttributes` becomes an allow-list

Before 1.0.0, this single boolean controlled **all** attribute processing
on `<math>`/`<chem>` tags — not just generic HTML attributes like
`id`/`class`/`title`/`lang`/`dir`, but also SimpleMathJax's own
rendering-control attributes like `display="block"` and
`chem`. (`style` was never one of the passthrough attributes — see
[includes/Hooks.php:140](../includes/Hooks.php#L140).)

As of 1.0.0, generic HTML attributes are controlled by an allow-list
instead of an on/off switch. Rendering attributes such as `display` are
always supported.

### `inline-block` removed

The former SimpleMathJax-only attribute:

```diff
- <math inline-block>...</math>
+ <math>...</math>
```

The default `<math>` rendering is now inline with `\displaystyle`, so the
attribute is no longer needed.

**If this affects you**: if your site set `$wgSmjEnableHtmlAttributes =
false;` before 1.0.0, that also silently disabled `<math display="block">`
and friends. In 1.0.0, rendering attributes are always supported.

```diff
- $wgSmjEnableHtmlAttributes = false;
+ $wgSmjAllowedAttributes = [];          // no HTML attribute passthrough: unchanged (this is the default, so it can be omitted)
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

### `$wgSmjDirectMathJax` / `$wgSmjDisplayMath` / `$wgSmjExtraInlineMath` → `$wgSmjExtraDelimiters`

These three settings are merged into one array, and the `"full"`/`"env"`/
`"none"` three-way mode collapses into a single boolean (`enabled`).
**The `"env"` mode is gone** — it used to let you turn `\ref`/escape
handling on or off separately, but turning escapes off only made stray
`\$`/`\\` more likely to be misread as delimiters, and turning `\ref` off
did nothing for sites that didn't use it — so there was never a real
reason to pick it over `"full"`. It's folded into `enabled`.

**The default also flips, from on to off.** `$wgSmjDirectMathJax` used to
default to `'full'` (bare `$...$` math worked out of the box).
`$wgSmjExtraDelimiters`'s default is now `['enabled' => false, 'inlineMath' =>
[], 'displayMath' => []]` — only `<math>`/`<chem>` tags work out of the box,
matching this extension's original contract ("TeX between `<math>` and
`</math>`"). This is a real behavior change, not just a rename: **if you
never touched these settings, you are affected.**

```diff
- // (nothing set — relied on the old default, 'full')
+ $wgSmjExtraDelimiters = [ 'enabled' => true ];   // keep bare $...$/$$...$$ working after the upgrade
```

```diff
- $wgSmjDirectMathJax = 'full';
+ $wgSmjExtraDelimiters = [ 'enabled' => true ];
```

```diff
- $wgSmjExtraInlineMath = [ [ '$', '$' ] ];
- $wgSmjDisplayMath = [ [ '$$', '$$' ] ];
+ $wgSmjExtraDelimiters = [
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
+ $wgSmjExtraDelimiters = [ 'enabled' => true ];   // \ref and escapes now come along too — fine for most sites
```

### If `$wgSmjRevisionOverrides` overrode an array-shaped setting

To override a field inside `$wgSmjExtraDelimiters` per revision range, use a dot
path:

```diff
  $wgSmjRevisionOverrides = [
-     [ 'upto' => 2000000, 'wgSmjDirectMathJax' => 'none' ],
+     [ 'max' => 2000000, 'wgSmjExtraDelimiters.enabled' => false ],
  ];
```

### Checklist

1. Rename `$wgSmjConfigByRevision` → `$wgSmjRevisionOverrides`, and rename
   each entry's `upto`/`since` bounds to `max`/`min`. Rename `$wgSmjUseCdn`
   → `$wgSmjCdn`, and wrap its old boolean value in the new array shape —
   `$wgSmjCdn = [ 'enabled' => $oldValue ]`. Remove `$wgSmjUseChem` if you
   had it — `<chem>` is always registered now.
2. If you had `$wgSmjEnableHtmlAttributes` set to anything, replace it with
   `$wgSmjAllowedAttributes`
   (list) — `[]` for the old `false`, or list the attributes you want to
   preserve for the old `true` behavior.
3. **If you want bare `$...$`/`$$...$$` math to keep working**, add
   `$wgSmjExtraDelimiters = [ 'enabled' => true ]` explicitly — this is now
   required even if you never set `$wgSmjDirectMathJax` before, since the
   default flipped from on to off. If you used
   `$wgSmjDisplayMath`/`$wgSmjExtraInlineMath`, fold their values into the
   same array (`displayMath`/`inlineMath` keys). If you used `'env'`, be
   aware it now behaves like `'full'`.
4. If `$wgSmjConfigByRevision` overrode `$wgSmjDirectMathJax` or another
   value that's now inside `$wgSmjExtraDelimiters`, switch to a dot path.
