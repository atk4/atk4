<?php
define('AMODULES3_DIR',dirname(__FILE__));
define('AMODULES3_LIB',AMODULES3_DIR.'/lib');

$p=@ini_get('include_path');if(!$p)$p='.';
ini_set('include_path',$p.PATH_SEPARATOR."lib".PATH_SEPARATOR.AMODULES3_LIB);

include 'static.php';
?>
