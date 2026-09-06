# Display styles

SimpleMathJax follows MediaWiki Math's display conventions for `<math>`.

| Syntax | Layout | TeX style |
| --- | --- | --- |
| `<math>...</math>` | inline | `\displaystyle` |
| `<math display="inline">...</math>` | inline | `\textstyle` |
| `<math display="block">...</math>` | block, centered | `\displaystyle` |

The `display` attribute is always processed.

Examples:

```wikitext
Inline display-style: <math>\frac{1}{2}</math>

Inline text-style: <math display="inline">\frac{1}{2}</math>

Block display-style:
<math display="block">a^2 + b^2 = c^2</math>

```

The default `<math>` behavior intentionally matches MediaWiki Math: the
formula remains in the text flow, while fractions, sums, and integrals use
display-style sizing.

Block formulas are centered, as in MediaWiki Math. Inline formulas use the
surrounding text flow.

## `\displaystyle` and `\textstyle`

These are TeX math styles used by MathJax. They control the size and layout
of parts of a formula; they do not control whether the surrounding HTML is
inline or block.

`\displaystyle` is the larger, equation-oriented style. Fractions are
larger, and sum and integral operators have room for larger limits.
It is the default style for `<math>` and `display="block"`.

`\textstyle` is the more compact, prose-oriented style. It is useful when
the formula should fit comfortably inside a sentence, especially when it
contains fractions or large operators. It is used by `display="inline"`.

For example, these two formulas have the same inline placement but different
math styles:

```wikitext
<math>\sum_{i=0}^\infty \frac{1}{2^i}</math>
<math display="inline">\sum_{i=0}^\infty \frac{1}{2^i}</math>
```
