#!/bin/bash

cd `dirname "$0"`
php run_tests.php && echo "Tests passed OK" || echo "Some tests failed"
