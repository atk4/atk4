<?php
class page_jquerychain extends Page_Tester {
        public $proper_responses=array(
        "Test_hello"=>'$(\'#sample_project\').hi()',
        "Test_custselector"=>'$(".button").hi()',
        "Test_document"=>'$(document).hi()',
        "Test_window"=>'$(\'#sample_project\')._selectorWindow.hi()',
        "Test_region"=>'$(\'#sample_project\')._selectorRegion.hi()',
        "Test_jsselector"=>'$($(\'#sample_project\').find(\'button\')).hi()',
        "Test_custom"=>'window.player.hi()',
        "Test_enclose"=>'function(ev,ui){$(".button").hi()}',
        "Test_enclose2"=>'function(ev,ui){$(\'#sample_project\').hi()}',
        "Test_enclose3"=>'function(ev,ui){ev.preventDefault();ev.stopPropagation(); $(\'#sample_project\').hi()}',
        "Test_argument"=>'$(window).append($(\'#sample_project\').find(\'button\'))',
        "Test_lib"=>'window.player["sample_project_page_jquerychain"].pause()' // this is OK to fail
    );
    function prepare(){
        $this->api->add('jQuery');
        return array($this->api->js());
    }

    function test_hello($c){
        return $c->hi();
    }
    function test_custselector($c){
        return $c->_selector('.button')->hi();
    }
    function test_document($c){
        return $c->_selectorDocument()->hi();
    }
    function test_window($c){
        return $c->_selectorWindow->hi();
    }
    function test_region($c){
        return $c->_selectorRegion->hi();
    }
    function test_jsselector($c){
        $c->find('button');
        return $this->api->js()->_selector($c)->hi();
    }
    function test_custom($c){
        return $c->_library('window.player')->hi();
    }
    function test_enclose($c){
        return $c->_enclose()->_selector('.button')->hi();
    }
    function test_enclose2($c){
        return $c->_enclose()->hi();
    }
    function test_enclose3($c){
        // prevent default also
        return $c->_enclose(true,true)->hi();
    }
    function test_argument($c){
        // prevent default also
        $c->find('button');
        return $this->api->js()->_selectorWindow()->append($c);
    }
    function test_lib($c){
        return $c->_library('window.player['.json_encode($this->getJSID()).']')->pause();
    }
}
