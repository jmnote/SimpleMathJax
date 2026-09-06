.PHONY: checks test phpcs

checks: test phpcs ## Everything CI runs before merging (needs `composer install`, PHP >= 8.2)

test: ## Run the pure-PHP test suites
	php tests/QuotesTest.php

phpcs: ## parallel-lint + minus-x + phpcs against the MediaWiki coding standard
	composer test
