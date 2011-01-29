<?php
$safe_extensions=array(
		'gif','jpg','css','js','png'
		);
@list($fn,$ext,$junk)=explode('.',$_GET['file']);
header("Content-type: ".str_replace(',','/',$_GET['ct']));
if(isset($junk))exit;
if(!in_array($ext,$safe_extensions))exit;
$fn=ereg_replace('^amodules3',$amodules3_path,$fn);
readfile($fn.'.'.$ext);
