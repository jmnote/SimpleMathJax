# AGENTS.md

Run `make checks` before committing. `test` runs plain PHP scripts
(`php tests/*.php`), not PHPUnit. `phpcs` needs `composer install`, which
`make` does automatically.

`hack/demo/demo.sh` / `make screenshots [demo]` render a demo through a
local Docker MediaWiki and regenerate `docs/screenshots/$MW_VERSION/*.png`
(`MW_VERSION` defaults to `1.43`, e.g. `MW_VERSION=1.45 make screenshots` to
test against a different MediaWiki version). Only run this when asked to,
or when a change affects rendering (`resources/`, `hack/demo/demo-screenshots.yaml`)
— not for routine changes.

`make demo [version]` / `make down` start and stop that same local wiki
directly (e.g. `make demo 1.45`), for manual poking around — VisualEditor
is installed there too (bundled with the image), so `?veaction=edit` works.
`make animations [version]` drives it through VisualEditor's Insert menu —
inserting, per doc listed in `hack/demo/demo-animations.yaml`, each of that
doc's formulas, then saving — and records
`docs/animations/$MW_VERSION/animation-<name>.gif` per doc. `make
animations 1.45` targets just that version; with none given, it loops over
every version in demo-animations.yaml's own `mediawiki: versions:` list
instead of defaulting to one. Only run this when asked to, or when a
change affects `resources/ve/*.js` — not for routine changes.
