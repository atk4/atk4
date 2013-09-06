<?php

class ApiCLI_configtest extends ApiCLI {
    public $config_location='test-configs';
}

class page_readconfig extends Page_Tester {
    function prepare(){
        return array(new ApiCLI_configtest('test'));
    }
    function test_empty($api){
        return 'OK';
    }
    function test_getconfig($api){
        return json_encode($api->getConfig('val'));
    }
    function test_getconfig2($api){
        $api->readConfig('config1');
        return json_encode($api->getConfig('val'));
    }
    function test_getconfig3($api){
        $api->readConfig('config1');
        return json_encode($api->getConfig('arr'));
    }
    function test_setconfig($api){
        $api->readConfig('config1');
        $api->setConfig('val','myval');
        return json_encode($api->getConfig('val'));
    }
}

