<?php
class Test_Model1 extends AbstractController {

    function prepare(){
        return array($this->app->add('MyModel'));
    }
    function test_get($t){
        return $t['surname'];
    }
    function test_set($t){
        return $t->set('name', 'Bob')->get('name');
    }
    function test_save($t){
        return $t->save();
    }
}

class MyModel extends Model {
    function init(){
        parent::init();
        $this->addField('name');
        $this->addField('surname')->defaultValue('Smith');
    }
}
