<?php
require'../loader.php';
/*
require'../lib/AbstractObject.php';
require'../lib/AbstractView.php';
require'../lib/AbstractModel.php';
require'../lib/AbstractController.php';
require'../lib/Model.php';
require'../lib/Controller/Data.php';
require'../lib/Controller/Data/Array.php';
require'../lib/Dummy.php';
require'../lib/BaseException.php';
require'../lib/Exception/Logic.php';
require'../lib/Exception/InitError.php';
require'../lib/Exception/Hook.php';
require'../lib/Exception/ForUser.php';
require'../lib/PathFinder.php';
require'../lib/ApiCLI.php';
require'../lib/Page.php';
require'../lib/Page/Tester.php';
 */

$api=new ApiCLI('sample_project');

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
