#!/usr/bin/env sh

set -e

vendor/bin/phpstan analyze
vendor/bin/phpunit -vvv test/
# vendor/bin/behat --suite use_cases
vendor/bin/behat --suite system
