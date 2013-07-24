<?php
require'../loader.php';

$api=new ApiCLI();

$tests = $api->add('Model_AgileTest');

$result='OK';

foreach($tests as $row){
    foreach($row as $key=>$val){
        if($key=='name')$val=str_pad($val,10);
        echo "$key: $val\t";
    }
    if($row['result']!='OK')$result='FAIL';
    echo "\n";
}

if($result!='OK')exit(1);
