<?php
require'../loader.php';
$api=new App_CLI('sample_project');

$tests = $api->add('Model_AgileTest');

$result='OK';

foreach($tests as $row){
    foreach($row as $key=>$val){
        if($key=='name')$val=str_pad($val,10);
        if($key=='failures')continue;
        echo "$key: $val\t";
    }
    if($row['fail'])$result='FAIL';
    echo "\n";
}

if($result!='OK')exit(1);
