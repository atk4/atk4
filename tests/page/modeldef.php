<?php

class page_modeldef extends Page_Tester {
    public $db;
    public $proper_responses=array(
        "Test_addfield"=>'OK',
        "Test_settergetter"=>'int',
        "Test_person1"=>'John',
        "Test_person2"=>''
    );
    function init(){
        $this->db=$this->add('DB');
        parent::init();
    }
    function prepare(){
        return array($this->add('Model'));
    }
    function test_addfield($m){
        $m->addField('hello');
        return 'OK';
    }
    function test_settergetter($m){
        $m->addField('name')->type('string');
        $m->addField('age')->type('int')->calculated(true);

        return $m->getElement('age')->type();
    }

    function makePerson($m){

        $m->addField('name')->type('string');
        $m->addField('age')->type('int')->calculated(true);
        $m->addMethod('calculate_age',function(){
            return "test123";
        });
    }


    function test_person1($m){
        $this->makePerson($m);

        $m->set('name','John');
        return $m->get('name');
    }
    function test_person2($m){
        $this->makePerson($m);

        try{
            $m->set('nosuchfield','John');
        }catch(Exception_Logic $e){
            return 'No such field: GOOD!';
        }
        return $m->get('name');
    }
}

