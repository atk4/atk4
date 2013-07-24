#!/bin/bash

cd `pwd`
php run_tests.php && echo "Tests passed OK" || echo "Some tests failed"
