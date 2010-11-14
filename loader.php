<?php
$p=@ini_get('include_path');if(!$p)$p='.';
ini_set('include_path',$p.PATH_SEPARATOR.'.'.DIRECTORY_SEPARATOR.'lib'.PATH_SEPARATOR.dirname(__FILE__).'/lib');
include 'static.php';
?>
