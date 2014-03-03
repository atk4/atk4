<?php

class page_normalize extends Page_Tester {
    public $proper_responses=array(
        "Test_empty"=>'',
        "Test_normal"=>'Lazy_dog_over_the_fox',
        "Test_before_after"=>'Hey_there',
        "Test_padder1"=>'Hey-there-Bring-me-my-sandwich',
        "Test_padder2"=>'Hey.there.Bring.me.my.sandwich',
        "Test_padder3"=>'Hey\\there\\Bring\\me\\my\\sandwich',
        "Test_padder4"=>'Hey^there^Bring^me^my^sandwich',
        "Test_unicode"=>'aurslie_u_dzelzce',
        "Test_c_basic"=>'Model_Hi',
        "Test_c_basic2"=>'Model_Hi',
        "Test_c_basic3"=>'Model_Data_Eater',
        "Test_c_prefix"=>'hellokitty\\Model_Hi',
        "Test_c_prefix2"=>'hellokitty\\Model_Hi',
        "Test_c_prefix3"=>'hellokitty\\Model_Hi',
        "Test_c_insidematch"=>'Model_View_Model_Test'
    );
    function prepare(){
        return null;
    }
    function test_empty(){
        return $this->api->normalizeName('');
    }
    function test_normal(){
        return $this->api->normalizeName('Lazy dog )(*&)(& over the fox');
    }
    function test_before_after(){
        return $this->api->normalizeName('>> Hey there!');
    }
    function test_padder1(){
        return $this->api->normalizeName('>> Hey there! Bring me my sandwich!','-');
    }
    function test_padder2(){
        return $this->api->normalizeName('>> Hey there! Bring me my sandwich!','.');
    }
    function test_padder3(){
        return $this->api->normalizeName('>> Hey there! Bring me my sandwich!','\\');
    }
    function test_padder4(){
        return $this->api->normalizeName('>> Hey there! Bring me my sandwich!','^_^');
    }
    function test_unicode(){
        return $this->api->normalizeName('Šaursliežu dzelzceļš');
    }
    function test_c_basic(){
        return $this->api->normalizeClassName('Hi','Model');
    }
    function test_c_basic2(){
        return $this->api->normalizeClassName('Model_Hi','Model');
    }
    function test_c_basic3(){
        return $this->api->normalizeClassName('Model_Data_Eater','Model_Data');
    }
    function test_c_prefix(){
        return $this->api->normalizeClassName('hellokitty/Model_Hi','Model');
    }
    function test_c_prefix2(){
        return $this->api->normalizeClassName('hellokitty/Hi','Model');
    }
    function test_c_prefix3(){
        return $this->api->normalizeClassName('hellokitty\\Hi','Model');
    }
    function test_c_insidematch(){
        return $this->api->normalizeClassName('View_Model_Test','Model');
    }
}

