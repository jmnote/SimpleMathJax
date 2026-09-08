.PHONY: checks test phpcs local-mathjax screenshots demo down animations

LOCAL_MATHJAX_VERSION ?= 4.1.3

checks: test phpcs ## Everything CI runs before merging (needs `composer install`, PHP >= 8.2)

test: ## Run the pure-PHP test suites
	php tests/QuotesTest.php
	php tests/RevisionOverridesTest.php
	php tests/IgnoreHtmlClassTest.php

phpcs: vendor/autoload.php ## parallel-lint + minus-x + phpcs against the MediaWiki coding standard
	composer test

local-mathjax: ## Pin the bundled local MathJax submodule, e.g. `make local-mathjax LOCAL_MATHJAX_VERSION=4.1.3`
	hack/local-mathjax.sh $(LOCAL_MATHJAX_VERSION)

vendor/autoload.php: composer.json
	composer install --no-progress

demo: ## Start (or reuse) a local demo wiki at http://localhost:8080 (Admin / demo12345678), e.g. `make demo 1.45` to target that MediaWiki version instead of the default 1.43
	MW_VERSION=$(or $(filter-out $@,$(MAKECMDGOALS)),1.43) hack/demo/demo.sh up

down: ## Stop the local demo wiki and wipe its data (next `make demo` reinstalls fresh)
	hack/demo/demo.sh down

screenshots: ## Screenshot a demo page, e.g. `make screenshots custom01` (no demo = every demo in hack/demo/demo-screenshots.yaml); `MW_VERSION=1.45 make screenshots` targets a different mediawiki Docker image tag (default 1.43)
	hack/demo/demo.sh screenshot $(filter-out $@,$(MAKECMDGOALS))

animations: ## Record docs/animations/$MW_VERSION/animation-<name>.gif per doc in hack/demo/demo-animations.yaml: inserting its formulas via VisualEditor's Insert menu, then saving; e.g. `make animations 1.45` for just that MediaWiki version, or no version for every one listed under demo-animations.yaml's own `mediawiki: versions:`
	hack/demo/demo.sh animations $(filter-out $@,$(MAKECMDGOALS))

# Swallows the extra word in `make screenshots custom01` / `make demo 1.45`
# so make doesn't treat "custom01"/"1.45" as a target of its own and fail
# with "No rule to make target". Scoped to only fire when screenshots,
# demo, or animations is actually one of the invoked goals, so an
# unrelated typo like `make cheks` still fails loudly instead of silently
# no-op'ing.
ifneq ($(filter screenshots demo animations,$(MAKECMDGOALS)),)
%:
	@:
endif
