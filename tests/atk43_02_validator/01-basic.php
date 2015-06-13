<?php

class ATK_Test_Object extends AbstractController {

    function prepare(){
        return [$this->add('Controller_Validator')];
    }

    function test_init($v) {
        return get_class($v);
    }

    function test_basic1($v) {
        $v->is('name|len|>5?Name is too short');
        $v->setSource(['name'=>'John']);
        $v->now();
    }

    function test_basic2($v) {
        $v->is(['name|len|>5?Name is too short']);
        $v->setSource(['name'=>'John']);
        $v->now();
    }
    function test_basic3($v) {
        $v->is([['name','len','>5?Name is too short']]);
        $v->setSource(['name'=>'John']);
        $v->now();
    }

}
