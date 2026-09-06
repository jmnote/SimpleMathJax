.PHONY: checks test phpcs local-mathjax screenshots

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

screenshots: ## Screenshot a demo page, e.g. `make screenshots custom01` (no demo = every demo in hack/demo/demos.yaml); `MW_VERSION=1.45 make screenshots` targets a different mediawiki Docker image tag (default 1.43)
	hack/demo/demo.sh screenshot $(filter-out $@,$(MAKECMDGOALS))

# Swallows the extra word in `make screenshots custom01` so make doesn't
# treat "custom01" as a target of its own and fail with "No rule to make
# target". Scoped to only fire when `screenshots` is actually one of the
# invoked goals, so an unrelated typo like `make cheks` still fails loudly
# instead of silently no-op'ing.
ifneq ($(filter screenshots,$(MAKECMDGOALS)),)
%:
	@:
endif
