<?php
// This sets initial path for the include. Further includes will be initialized through PathFinder
set_include_path('.'.PATH_SEPARATOR.'.'.DIRECTORY_SEPARATOR.'lib'.PATH_SEPARATOR.dirname(__FILE__).'/lib');
if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    echo 'PHP 5.3.0 is required';
    exit;
}
require'static.php';
