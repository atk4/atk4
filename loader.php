<?php
// This sets initial path for the include.
// Further includes will be initialized through PathFinder.
$places = array(
    dirname($_SERVER['PHP_SELF']),
    dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR . 'lib',
    __DIR__  . DIRECTORY_SEPARATOR . 'lib',
);
set_include_path(join(PATH_SEPARATOR, $places));

// check PHP version requirements
if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    echo 'PHP 5.3.0 is required';
    exit;
}

// include some static functions
require'static.php';
