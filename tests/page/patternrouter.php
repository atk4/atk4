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

    // Below is the collection of test for PatternRouter. Please review 
    // and add more if you can think of them!
    //
    // Tests execute $this->r to simulate requests and third argument is 
    // parameters

    // no routing, should reconnect string as-is
    function test_passthrough1($r){
        return $this->r($r);
    }
    function test_passthrough2($r){
        return $this->r($r,'hello',array('id'=>123));
    }
    function test_passthrough3($r){
        return $this->r($r

            ->link('hello/:id')

            ,'hello',array('id'=>123));
    }
    function test_basic1($r){
        return $this->r($r

            ->link('hello/:id')

            ,'hello/123');
    }
    function test_basic2($r){
        return $this->r($r

            ->link('hello/:action/:id')

            ,'hello/share/123');
    }
    function test_basic3($r){
        return $this->r($r

            ->link('hello/:action/:id')

            ,'hello/share');
    }
    function test_basic4($r){
        return $this->r($r

            ->link('hello/:id/edit')

            ,'hello/123/edit');
    }
    function test_basic5($r){
        return $this->r($r

            ->link('hello/:id/edit')

            ,'hello/123');
    }
    function test_output($r){
        return $this->r($r

            ->link('hello/:foo/:bar','hello/:bar/:foo')

            ,'hello/xx/yy');
    }
    function test_rule_selection1($r){
        return $this->r($r

            ->link(':id/edit','first')
            ->link(':id/view','second')

            ,'123/edit');
    }
    function test_rule_selection2($r){
        return $this->r($r

            ->link(':id/edit','first')
            ->link(':id/view','second')

            ,'123/view');
    }
}
