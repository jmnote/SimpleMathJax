# AGENTS.md

Run `make checks` before committing. `test` runs plain PHP scripts
(`php tests/*.php`), not PHPUnit. `phpcs` needs `composer install`, which
`make` does automatically.

`hack/demo/demo.sh` / `make screenshots [demo]` render a demo through a
local Docker MediaWiki and regenerate `docs/screenshots-$MW_VERSION/*.png`
(`MW_VERSION` defaults to `1.43`, e.g. `MW_VERSION=1.45 make screenshots` to
test against a different MediaWiki version). Only run this when asked to,
or when a change affects rendering (`resources/`, `hack/demo/demos.yaml`)
— not for routine changes.
