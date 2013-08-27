<?php

class page_pagemanager extends Page_Tester {
        public $proper_responses=array(
        "Test_cli"=>'http://yahoo.com/admin/?page=foobar',
        "Test_prefix"=>'http://yahoo.com/admin/foobar',
        "Test_postfix"=>'http://yahoo.com/admin/foobar.yey',
        "Test_index"=>'http://yahoo.com/admin/',
        "Test_remote"=>'http://google.com/xx'
    );
    function prepare(){
        return null;
    }
    function test_cli(){
        $api = new ApiCLI('test');
        $api->add('Controller_PageManager')
            ->setURL('http://yahoo.com/admin/');
        return $api->url('foobar')->absolute();
    }
    function test_prefix(){
        $api = new ApiCLI('test');
        $api->setConfig('url_prefix','');
        $api->add('Controller_PageManager')
            ->setURL('http://yahoo.com/admin/');
        return $api->url('foobar')->absolute();
    }
    function test_postfix(){
        $api = new ApiCLI('test');
        $api->setConfig('url_prefix','');
        $api->setConfig('url_postfix','.yey');
        $api->add('Controller_PageManager')
            ->setURL('http://yahoo.com/admin/');
        return $api->url('foobar')->absolute();
    }
    function test_index(){
        $api = new ApiCLI('test');
        $api->setConfig('url_prefix','');
        $api->setConfig('url_postfix','.yey');
        $api->add('Controller_PageManager')
            ->setURL('http://yahoo.com/admin/');
        return $api->url('/')->absolute();
    }
    function test_remote(){
        $api = new ApiCLI('test');
        $api->setConfig('url_prefix','');
        $api->setConfig('url_postfix','.yey');
        $api->add('Controller_PageManager')
            ->setURL('http://yahoo.com/admin/');
        return $api->url('http://google.com/xx')->absolute();
    }
}

