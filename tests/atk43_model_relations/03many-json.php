<?php
class loan_tester extends AbstractController {
    function prepare(){
        return [$this->add('Model')];
    }
    function test_create($m)
    {
        $m->containsOne(['test','json'=>true], 'MyModel');
        return $m->getElement('test')->short_name;
    }

    function test_ref($m)
    {
        $m->containsOne(['test','json'=>true], 'MyModel');
        return $m->ref('test')->get('name');
    }

    function test_refsave($m)
    {
        $m->containsOne(['test','json'=>true], 'MyModel');
        $m->ref('test')->set('name','boss')->save();

        $this->tmp1 = $m->data;
        return $m['test'];
    }

    function test_refload($m)
    {
        $m->data = array ( 'test' => '{"name":"boss"}', );
        $m->containsOne(['test','json'=>true], 'MyModel');

        return json_encode($m->ref('test')->get('name'));
    }

    function test_set($m)
    {
        $m->containsOne(['test','json'=>true], 'MyModel');
        $m->set('test', '{"name":"ok"}');

        return json_encode($m->ref('test')->get('name'));
    }


        public $proper_responses=array(
        "Test_create"=>'test',
        "Test_ref"=>'smith',
        "Test_refsave"=>'{"name":"boss"}',
        "Test_refload"=>'"boss"',
        "Test_set"=>'"ok"',
    );

}

class Model_MyModel extends Model {
    function init() {
        parent::init();

        $this->addField('name')->defaultValue('smith');
        $this->addField('surname');
    }
}