#!/bin/bash

# Exit immediately for non-zero, fail on use of unset variables
set -eu

#
# Run unit tests using current version of PHPUnit
#

PHPUNIT=./phpunit-8.4.2.phar
TESTS=.
FAILED=false

for t in ${TESTS[@]}; do
  echo "$t"
  php "${PHPUNIT}" --colors=auto "${t}" || FAILED=true
done

if [ $FAILED = true ]; then
  echo
  echo FAIL: Some tests failed.
  echo
  exit 1
fi

echo
echo PASS: All tests passed!
echo
exit 0