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

demo: ## Start (or reuse) a local demo wiki at http://localhost:8080 (Admin / demo12345678), e.g. `make demo 1.45`
	MW_VERSION=$(or $(filter-out $@,$(MAKECMDGOALS)),1.43) hack/demo/demo.sh up

down: ## Stop the local demo wiki and wipe its data
	hack/demo/demo.sh down

screenshots: ## Screenshot a demo page, e.g. `make screenshots custom01` (no demo = every demo)
	hack/demo/demo.sh screenshot $(filter-out $@,$(MAKECMDGOALS))

animations: ## Record a VisualEditor insert animation per doc in hack/demo/demo-animations.yaml, e.g. `make animations 1.45`
	hack/demo/demo.sh animations $(filter-out $@,$(MAKECMDGOALS))

# Swallows the extra word in `make screenshots custom01` / `make demo 1.45`
# so make doesn't treat it as a target of its own and fail.
ifneq ($(filter screenshots demo animations,$(MAKECMDGOALS)),)
%:
	@:
endif
