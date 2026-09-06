.PHONY: checks test phpcs mathjax

MATHJAX_VERSION_LOCAL ?= 4.1.3
MATHJAX_VERSION_CDN ?= 4

checks: test phpcs ## Everything CI runs before merging (needs `composer install`, PHP >= 8.2)

test: ## Run the pure-PHP test suites
	php tests/QuotesTest.php
	php tests/RevisionOverridesTest.php

phpcs: vendor/autoload.php ## parallel-lint + minus-x + phpcs against the MediaWiki coding standard
	composer test

vendor/autoload.php: composer.json
	composer install --no-progress

mathjax: ## Pin the local MathJax submodule + CDN major version, e.g. `make mathjax MATHJAX_VERSION_LOCAL=4.1.3 MATHJAX_VERSION_CDN=4`
	hack/mathjax.sh $(MATHJAX_VERSION_LOCAL) $(MATHJAX_VERSION_CDN)
