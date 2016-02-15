<?php
class Test_Views1 extends AbstractController {

    function prepare(){
        return array($this->app->add('MyView'));
    }
    function test_empty($t){
        return $t->x;
    }
    function test_getHTML($t){
        return $t->getHTML();
    }
    function test_template1($t){
        $t = $this->app->add('MyView2');
        $t->template->loadTemplateFromString('bleh');
        return $t->getHTML();
    }
    function test_template2($t){
        $t = $this->app->add('MyView2');
        $t->template->loadTemplateFromString('hello {$tag} world');
        return $t->getHTML();
    }
    function test_template3($t){
        $t = $this->app->add('MyView2');
        $t->template->loadTemplateFromString('hello {$tag} world');
        $t->add('MyView', null, 'tag');
        return $t->getHTML();
    }
    function test_template4($t){
        $t = $this->app->add('MyView2');
        $t->template->loadTemplateFromString('hello {nested}one {$tag} three{/}');
        $t->add('MyView2', null, 'nested', 'nested')->template->set('tag', 'two');
        return $t->getHTML();
    }
}

class MyView extends AbstractView {
    public $x=1;
    function render(){
        $this->output('Foo');
    }
}
class MyView2 extends AbstractView {
    public $x=1;
}
