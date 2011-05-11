<?php
// This sets initial path for the include. Further includes will be initialized through PathFinder
ini_set('include_path','.'.PATH_SEPARATOR.'.'.DIRECTORY_SEPARATOR.'lib'.PATH_SEPARATOR.dirname(__FILE__).'/lib');
include dirname(__FILE__).'lib/static.php';
?>
