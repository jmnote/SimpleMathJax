.PHONY: checks test phpcs mathjax screenshots

MATHJAX_VERSION_LOCAL ?= 4.1.3
MATHJAX_VERSION_CDN ?= 4

checks: test phpcs ## Everything CI runs before merging (needs `composer install`, PHP >= 8.2)

test: ## Run the pure-PHP test suites
	php tests/QuotesTest.php
	php tests/RevisionOverridesTest.php

phpcs: vendor/autoload.php ## parallel-lint + minus-x + phpcs against the MediaWiki coding standard
	composer test

mathjax: ## Pin local/CDN MathJax versions, e.g. `make mathjax MATHJAX_VERSION_LOCAL=4.1.3 MATHJAX_VERSION_CDN=4`
	hack/mathjax.sh $(MATHJAX_VERSION_LOCAL) $(MATHJAX_VERSION_CDN)

vendor/autoload.php: composer.json
	composer install --no-progress

screenshots: ## Screenshot a demo page, e.g. `make screenshots custom01` (no demo = every demo in hack/demo/demos.yaml)
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
