<?php

class page_model3 extends Page_Tester {
    public $proper_responses=array(
        "Test_create"=>'[]',
        "Test_source"=>'{"controller_data_array":[]}',
        "Test_field"=>'{"controller_data_array":[]}',
        "Test_save"=>'{"controller_data_array":{"1":{"name":"John","id":1}}}',
        "Test_save2"=>'{"controller_data_array":{"1":{"name":"John","id":1},"2":{"name":"Susan","id":2}}}',
        "Test_savelater"=>'{"controller_data_array":{"1":{"name":"John","id":1}}}',
        "Test_savelater2"=>'{"controller_data_array":{"1":{"name":"John","id":1},"2":{"name":"Susan","id":2}}}',
        "Test_save3"=>'{"name":"Susan","id":2}',
        "Test_load"=>'{"name":"John","id":1}',
        "Test_loadby"=>'{"name":"John","id":1}'
    );
    function prepare(){
        return array();
    }
    function test_create(){
        $m=$this->add('Model_Simple');
        return json_encode($m->_table);
    }
    function test_source(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        return json_encode($m->_table);
    }
    function test_field(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        return json_encode($m->_table);
    }
    function test_save(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();

        return json_encode($m->_table);
    }
    function test_save2(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();
        $m->unload();

        $m->set('name','Susan');
        $m->save();

        return json_encode($m->_table);
    }
    function test_savelater(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();
        $m->unload();

        $m->set('name','Susan');
        $m->saveLater();

        return json_encode($m->_table);
    }
    function test_savelater2(){
        $m=$this->add('Model_Simple');

        // TODO: test array connectivity

        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();
        $m->unload();

        $m->set('name','Susan');
        $m->saveLater();
        $m->unload();

        return json_encode($m->_table);
    }
    function test_save3(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();
        $m->unload();

        $m->set('name','Susan');
        $m->save();

        return json_encode($m->get());
    }
    function test_load(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();
        $m->unload();

        $m->set('name','Susan');
        $m->save();

        $m->load(1);

        return json_encode($m->get());
    }
    function test_loadby(){
        $m=$this->add('Model_Simple');
        $m->setSource('Array',array());
        $m->addField('name');
        $m->set('name','John');
        $m->save();
        $m->unload();

        $m->set('name','Susan');
        $m->save();

        $m->loadBy('name','John');

        return json_encode($m->get());
    }
}

class Model_Simple extends Model {
}
