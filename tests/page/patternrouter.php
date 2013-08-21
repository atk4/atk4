<?php
class page_patternrouter extends Page_Tester {

    function prepare(){
        return array($this->add('Controller_PatternRouter'));
    }
    function r($router, $url_in='index', $args_in=array()){

        throw $this->exception('Test is not ready yet','SkipTests');


        $args = $args_in;

        $url_out = $router->route($url_in, $args);
        $args_out = $args;

        // try reversing back
        $url = $router->url($url_out, $args);

        // Verify few things
        if($url != $url_in) return 'Page missmatch after rerouting ('.
            $url.' != '.$url_in.')';

        // Verify arguments
        if($args != $args_in) return 'Arguments missmatch after rerouting ('.
            json_encode($args).' != '.json_encode($args_in).')';

        // Seems to match

        return 'url='.$url_out.' args='.json_encode($args_out);
    }
    function test_passthrough($r){
        return $this->r($r,'hello');
    }
}
