<?php
// This sets initial path for the include. Further includes will be initialized through PathFinder
set_include_path('.'.PATH_SEPARATOR.'.'.DIRECTORY_SEPARATOR.'lib'.PATH_SEPARATOR.dirname(__FILE__).'/lib');
include 'static.php';
//include dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'static.php';
?>
