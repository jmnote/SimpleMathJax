# Demo tooling

`hack/demo/demo.sh` (driven by the `Makefile` targets below) runs a local
Docker `mediawiki` image + SQLite, with this repo bind-mounted in as the
extension — no MediaWiki core checkout needed. VisualEditor is bundled with
the image, so `?veaction=edit` works too.

- `make demo [version]` / `make down` — start/stop the wiki manually at
  `http://localhost:8080` (`Admin` / `demo12345678`), e.g. `make demo 1.45`
  to target that MediaWiki version instead of the default `1.43`.

- `make screenshots [demo]` — regenerate `docs/screenshots/$MW_VERSION/*.png`
  from `hack/demo/demo-screenshots.yaml` (no `demo` = every demo in that
  file). `MW_VERSION=1.45 make screenshots` targets a different MediaWiki
  version. Only run this when asked to, or when a change affects rendering
  (`resources/`, `demo-screenshots.yaml`) — not for routine changes.

- `make animations [version]` — drives the wiki through VisualEditor's
  Insert menu, per doc listed in `hack/demo/demo-animations.yaml`,
  inserting each of that doc's formulas then saving, and records
  `docs/animations/$MW_VERSION/animation-<name>.gif` per doc.
  `make animations 1.45` targets just that version; with none given it
  loops over every version in `demo-animations.yaml`'s own
  `mediawiki: versions:` list instead of defaulting to one. Only run this
  when asked to, or when a change affects `resources/ve/*.js` — not for
  routine changes.
